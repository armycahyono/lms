<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\QuestionItemHistory;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class QuestionAnalysisService
{
    public function isAnswerCorrect(QuizQuestion $question, mixed $studentAnswer): ?bool
    {
        if ($question->type === 'essay') {
            return null;
        }

        if ($question->type === 'multiple_choice') {
            if (! is_string($studentAnswer) || trim($studentAnswer) === '') {
                return false;
            }

            return strcasecmp(trim($studentAnswer), trim((string) $question->correct_answer)) === 0;
        }

        if ($question->type === 'short_answer') {
            if (! is_string($studentAnswer) || trim($studentAnswer) === '') {
                return false;
            }

            $given = mb_strtolower(trim(preg_replace('/\s+/', ' ', $studentAnswer) ?? $studentAnswer));
            $acceptedKeys = collect(explode('|', (string) $question->correct_answer))
                ->map(fn ($k) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $k) ?? $k)))
                ->filter()
                ->values();

            return $acceptedKeys->contains($given);
        }

        if ($question->type === 'multiple_choice_complex') {
            $given = collect(is_array($studentAnswer) ? $studentAnswer : explode(',', (string) $studentAnswer))
                ->map(fn ($v) => strtoupper(trim((string) $v)))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $expected = collect(explode(',', (string) $question->correct_answer))
                ->map(fn ($v) => strtoupper(trim($v)))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            return $expected !== [] && $given === $expected;
        }

        if ($question->type === 'matching') {
            $expectedPairs = json_decode((string) $question->correct_answer, true);
            if (! is_array($expectedPairs) || ! is_array($studentAnswer)) {
                return false;
            }

            foreach ($expectedPairs as $left => $right) {
                if (strcasecmp(trim((string) ($studentAnswer[$left] ?? '')), trim((string) $right)) !== 0) {
                    return false;
                }
            }

            return true;
        }

        return null;
    }

    /**
     * @param  array<int, int>  $questionBankIds
     */
    public function incrementUsageCount(array $questionBankIds): void
    {
        $ids = array_values(array_unique(array_filter($questionBankIds)));
        if ($ids === []) {
            return;
        }

        QuestionBank::whereIn('id', $ids)->update([
            'usage_count' => DB::raw('usage_count + 1'),
            'last_used_at' => now(),
        ]);
    }

    public function recordQuizAttemptStats(Quiz $quiz, QuizAttempt $attempt): void
    {
        $quiz->loadMissing(['questions.questionBank', 'course.academicYear']);
        $answers = $attempt->answers_json ?? [];
        $yearName = $quiz->course?->academicYear?->name;

        foreach ($quiz->questions as $question) {
            if (! $question->question_bank_id || ! $question->questionBank) {
                continue;
            }

            $given = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;
            $hasAnswer = is_array($given) ? $given !== [] : trim((string) $given) !== '';
            if (! $hasAnswer) {
                continue;
            }

            $isCorrect = $this->isAnswerCorrect($question, $given);
            $qb = $question->questionBank;

            $optStats = is_array($qb->option_stats_json) ? $qb->option_stats_json : [];
            if (in_array($question->type, ['multiple_choice', 'multiple_choice_complex'], true)) {
                $chosenKeys = is_array($given) ? $given : [$given];
                foreach ($chosenKeys as $key) {
                    $k = strtoupper(trim((string) $key));
                    if (in_array($k, ['A', 'B', 'C', 'D', 'E'], true)) {
                        $optStats[$k] = (int) ($optStats[$k] ?? 0) + 1;
                    }
                }
            }

            $qb->total_answered = (int) $qb->total_answered + 1;
            if ($isCorrect === true) {
                $qb->total_correct = (int) $qb->total_correct + 1;
            }
            $qb->option_stats_json = $optStats !== [] ? $optStats : $qb->option_stats_json;
            $qb->last_used_at = now();
            $qb->save();

            $history = QuestionItemHistory::firstOrNew([
                'question_bank_id' => $qb->id,
                'source_type' => 'lms_quiz',
                'source_id' => $quiz->id,
            ]);

            $histOpt = is_array($history->option_distribution_json) ? $history->option_distribution_json : [];
            if (in_array($question->type, ['multiple_choice', 'multiple_choice_complex'], true)) {
                $chosenKeys = is_array($given) ? $given : [$given];
                foreach ($chosenKeys as $key) {
                    $k = strtoupper(trim((string) $key));
                    if (in_array($k, ['A', 'B', 'C', 'D', 'E'], true)) {
                        $histOpt[$k] = (int) ($histOpt[$k] ?? 0) + 1;
                    }
                }
            }

            $history->source_title = $quiz->title;
            $history->academic_year_name = $yearName;
            $history->total_answered = (int) $history->total_answered + 1;
            if ($isCorrect === true) {
                $history->total_correct = (int) $history->total_correct + 1;
            }
            $history->difficulty_index = $history->total_answered > 0
                ? round(($history->total_correct / $history->total_answered) * 100, 2)
                : null;
            $history->option_distribution_json = $histOpt !== [] ? $histOpt : null;
            $history->recorded_at = now();
            $history->save();
        }
    }

    public function highlightKeywords(string $answerText, ?string $keywords): string
    {
        $escaped = e($answerText);
        if (trim((string) $keywords) === '') {
            return nl2br($escaped);
        }

        $terms = collect(preg_split('/[,|]+/', (string) $keywords) ?: [])
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique()
            ->sortByDesc(fn ($t) => mb_strlen($t))
            ->values();

        foreach ($terms as $term) {
            $pattern = '/('.preg_quote(e($term), '/').')/iu';
            $escaped = preg_replace($pattern, '<mark class="bg-warning-lt px-1 rounded fw-semibold">$1</mark>', $escaped) ?? $escaped;
        }

        return nl2br($escaped);
    }
}
