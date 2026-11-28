<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuestionBankExcelService;
use App\Services\QuestionBankWordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $subjectId = $request->query('subject_id');
        $gradeLevel = $request->query('grade_level');
        $department = trim((string) $request->query('department', ''));
        $category = trim((string) $request->query('category', ''));
        $difficulty = trim((string) $request->query('difficulty', ''));
        $visibility = trim((string) $request->query('visibility', ''));
        $activeType = trim((string) $request->query('type', 'all'));
        if ($activeType !== 'all' && ! array_key_exists($activeType, QuestionBank::TYPES)) {
            $activeType = 'all';
        }

        $scope = $this->resolveTeacherScope($user);
        $taughtSubjectIds = $scope['subject_ids'];
        $taughtGradeLevels = $scope['grade_levels'];

        $baseQuery = QuestionBank::query()
            ->when($user->isGuru(), function ($q) use ($user, $scope, $taughtSubjectIds, $taughtGradeLevels): void {
                $q->where(function ($sub) use ($user, $scope, $taughtSubjectIds, $taughtGradeLevels): void {
                    $sub->where(function ($own) use ($user, $scope, $taughtSubjectIds, $taughtGradeLevels): void {
                        $own->where('teacher_id', $user->id);
                        if ($scope['has_courses']) {
                            if ($taughtSubjectIds !== []) {
                                $own->where(fn ($s) => $s->whereIn('subject_id', $taughtSubjectIds)->orWhereNull('subject_id'));
                            }
                            if ($taughtGradeLevels !== []) {
                                $own->where(fn ($g) => $g->whereIn('grade_level', $taughtGradeLevels)->orWhereNull('grade_level'));
                            }
                        }
                    });
                    if ($taughtSubjectIds !== []) {
                        $sub->orWhere(function ($shared) use ($taughtSubjectIds, $taughtGradeLevels): void {
                            $shared->where('is_shared', true)
                                ->whereIn('subject_id', $taughtSubjectIds);
                            if ($taughtGradeLevels !== []) {
                                $shared->where(fn ($g) => $g->whereIn('grade_level', $taughtGradeLevels)->orWhereNull('grade_level'));
                            }
                        });
                    }
                });
            });

        $typeCounts = (clone $baseQuery)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $questions = (clone $baseQuery)
            ->when($activeType !== 'all', fn ($q) => $q->where('type', $activeType))
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($gradeLevel, fn ($q) => $q->where('grade_level', (int) $gradeLevel))
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->when($category !== '', fn ($q) => $q->where('category', 'like', "%{$category}%"))
            ->when($difficulty !== '', fn ($q) => $q->where('difficulty', $difficulty))
            ->when($visibility === 'private', fn ($q) => $q->where('is_shared', false))
            ->when($visibility === 'shared', fn ($q) => $q->where('is_shared', true))
            ->with(['subject', 'teacher'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $questionGradeGroups = $questions->getCollection()
            ->groupBy(fn (QuestionBank $q): string => (string) ($q->grade_level ?? 'other'))
            ->map(function ($byGrade): array {
                $first = $byGrade->first();
                $subjects = $byGrade->groupBy(fn (QuestionBank $q): string => (string) ($q->subject_id ?? 0))
                    ->map(function ($bySubject): array {
                        /** @var QuestionBank $sample */
                        $sample = $bySubject->first();

                        return [
                            'subject_id' => $sample->subject_id ?? 0,
                            'subject_name' => $sample->subject?->name ?? 'Umum / Tanpa Mapel',
                            'questions' => $bySubject->values(),
                        ];
                    })
                    ->sortBy('subject_name')
                    ->values();

                return [
                    'grade_level' => $first->grade_level,
                    'subjects' => $subjects,
                    'total_questions' => $byGrade->count(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        return view('question-bank.index', [
            'questions' => $questions,
            'questionGradeGroups' => $questionGradeGroups,
            'subjects' => $scope['subjects'],
            'gradeLevels' => $scope['grade_levels'],
            'departments' => $scope['departments'],
            'taughtClassrooms' => $scope['classrooms'],
            'typeCounts' => $typeCounts,
            'totalAllTypes' => $typeCounts->sum(),
            'activeType' => $activeType,
            'filters' => [
                'subject_id' => $subjectId,
                'grade_level' => $gradeLevel,
                'department' => $department,
                'category' => $category,
                'difficulty' => $difficulty,
                'visibility' => $visibility,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $this->validateQuestionRequest($request, $user);
        [$options, $correctAnswer] = $this->normalizeQuestionOptionsAndAnswer($validated);
        [$questionImage, $optionImages] = $this->storeQuestionMedia($request);

        QuestionBank::create([
            'teacher_id' => $user->id,
            'subject_id' => $validated['subject_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'department' => $validated['department'] ?? null,
            'category' => $validated['category'] ?? null,
            'kisi_indicator' => $validated['kisi_indicator'] ?? null,
            'difficulty' => $validated['difficulty'],
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'question_image' => $questionImage,
            'options_json' => $options,
            'option_images_json' => $optionImages !== [] ? $optionImages : null,
            'correct_answer' => $correctAnswer,
            'points' => $validated['points'],
            'is_shared' => $request->boolean('is_shared', false),
        ]);

        return back()->with('status', 'Soal berhasil ditambahkan ke Bank Soal.');
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $questionBank->teacher_id === $user->id, 403);

        $validated = $this->validateQuestionRequest($request, $user);
        [$options, $correctAnswer] = $this->normalizeQuestionOptionsAndAnswer($validated);
        [$questionImage, $optionImages] = $this->storeQuestionMedia($request, $questionBank);

        $questionBank->update([
            'subject_id' => $validated['subject_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'department' => $validated['department'] ?? null,
            'category' => $validated['category'] ?? null,
            'kisi_indicator' => $validated['kisi_indicator'] ?? null,
            'difficulty' => $validated['difficulty'],
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'question_image' => $questionImage,
            'options_json' => $options,
            'option_images_json' => $optionImages !== [] ? $optionImages : null,
            'correct_answer' => $correctAnswer,
            'points' => $validated['points'],
            'is_shared' => $request->boolean('is_shared', false),
        ]);

        return back()->with('status', 'Perubahan soal berhasil disimpan.');
    }

    public function media(string $path): BinaryFileResponse
    {
        $cleanPath = ltrim(urldecode($path), '/');
        abort_unless(
            $cleanPath !== ''
            && str_starts_with($cleanPath, 'question-media/')
            && ! str_contains($cleanPath, '..')
            && Storage::disk('local')->exists($cleanPath),
            404
        );

        return response()->file(Storage::disk('local')->path($cleanPath));
    }

    public function downloadTemplate(Request $request, QuestionBankExcelService $excel): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $spreadsheet = $excel->buildTemplateSpreadsheet();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-bank-soal-lms.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadWordTemplate(Request $request, QuestionBankWordService $word): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $binary = $word->buildWordTemplate();

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, 'template-bank-soal-lms.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function importPreview(Request $request, QuestionBankExcelService $excel, QuestionBankWordService $word): View
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $request->validate([
            'import_file' => ['nullable', 'file', 'mimes:xlsx,xls,docx', 'max:15360', 'required_without:excel_file'],
            'excel_file' => ['nullable', 'file', 'mimes:xlsx,xls,docx', 'max:15360', 'required_without:import_file'],
            'file_format' => ['nullable', 'in:excel,word'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'grade_level' => ['nullable', 'integer', 'in:10,11,12'],
            'department' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'in:multiple_choice,multiple_choice_complex,matching,short_answer,essay'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $scope = $this->resolveTeacherScope($user);
        $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        $gradeLevel = $request->filled('grade_level') ? (int) $request->input('grade_level') : null;

        if ($user->isGuru() && $scope['has_courses']) {
            if ($subjectId !== null) {
                abort_unless(in_array($subjectId, $scope['subject_ids'], true), 403, 'Mata pelajaran tidak sesuai dengan kelas yang Anda ampu.');
            } elseif (count($scope['subject_ids']) === 1) {
                $subjectId = $scope['subject_ids'][0];
            }

            if ($gradeLevel !== null && $scope['grade_levels'] !== []) {
                abort_unless(in_array($gradeLevel, $scope['grade_levels'], true), 403, 'Tingkat kelas tidak sesuai dengan kelas yang Anda ampu.');
            } elseif ($gradeLevel === null && count($scope['grade_levels']) === 1) {
                $gradeLevel = $scope['grade_levels'][0];
            }
        }

        $uploadedFile = $request->file('import_file') ?? $request->file('excel_file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $format = $request->input('file_format') ?: ($extension === 'docx' ? 'word' : 'excel');

        $context = [
            'subject_id' => $subjectId,
            'grade_level' => $gradeLevel,
            'department' => $request->filled('department') ? trim((string) $request->input('department')) : null,
            'type' => $request->filled('type') ? (string) $request->input('type') : null,
            'is_shared' => $request->has('is_shared') ? $request->boolean('is_shared') : null,
        ];

        $parsed = $format === 'word' || $extension === 'docx'
            ? $word->parseUploadedDocx($uploadedFile->getRealPath(), $context, $user->id)
            : $excel->parseUploadedFile($uploadedFile->getRealPath(), $user->id, $context);

        $request->session()->put("question_bank_import_{$user->id}", $parsed);

        return view('question-bank.import-preview', [
            'parsed' => $parsed,
            'importFormat' => $format,
        ]);
    }

    public function importConfirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $parsed = $request->session()->pull("question_bank_import_{$user->id}");
        if (! is_array($parsed) || ! empty($parsed['errors']) || empty($parsed['rows'])) {
            return redirect()->route('question-bank.index')
                ->withErrors(['excel_file' => 'Sesi pratinjau impor tidak valid atau tidak ada soal valid untuk disimpan.']);
        }

        DB::transaction(function () use ($parsed, $user): void {
            foreach ($parsed['rows'] as $row) {
                QuestionBank::create([
                    'teacher_id' => $user->id,
                    'subject_id' => $row['subject_id'],
                    'grade_level' => $row['grade_level'],
                    'department' => $row['department'] ?? null,
                    'category' => $row['category'],
                    'kisi_indicator' => $row['kisi_indicator'],
                    'difficulty' => $row['difficulty'],
                    'type' => $row['type'],
                    'question_text' => $row['question_text'],
                    'question_image' => $row['question_image'] ?? null,
                    'options_json' => $row['options_json'],
                    'option_images_json' => $row['option_images_json'] ?? null,
                    'correct_answer' => $row['correct_answer'],
                    'points' => $row['points'],
                    'is_shared' => $row['is_shared'],
                ]);
            }
        });

        $count = count($parsed['rows']);

        return redirect()->route('question-bank.index')
            ->with('status', "{$count} soal berhasil diimpor ke Bank Soal.");
    }

    public function toggleShare(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $questionBank->teacher_id === $user->id, 403);

        $questionBank->update(['is_shared' => ! $questionBank->is_shared]);

        return back()->with('status', $questionBank->is_shared
            ? 'Soal sekarang dibagikan ke rekan guru mapel ini.'
            : 'Soal dikembalikan ke status Privat.');
    }

    public function analysis(Request $request, QuestionBank $questionBank): View
    {
        $user = $request->user();
        abort_unless(
            $user->isAdmin()
            || $questionBank->teacher_id === $user->id
            || $questionBank->is_shared,
            403
        );

        $questionBank->load(['subject', 'teacher', 'itemHistories']);

        return view('question-bank.analysis', [
            'question' => $questionBank,
        ]);
    }

    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $questionBank->teacher_id === $user->id, 403);

        $questionBank->delete();

        return back()->with('status', 'Soal berhasil dihapus dari Bank Soal.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuestionRequest(Request $request, User $user): array
    {
        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'grade_level' => ['nullable', 'integer', 'in:10,11,12'],
            'department' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'kisi_indicator' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'in:mudah,sedang,sulit'],
            'type' => ['required', 'in:multiple_choice,multiple_choice_complex,matching,short_answer,essay'],
            'question_text' => ['required', 'string'],
            'question_image' => ['nullable', 'image', 'max:4096'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'option_e' => ['nullable', 'string'],
            'option_image_a' => ['nullable', 'image', 'max:4096'],
            'option_image_b' => ['nullable', 'image', 'max:4096'],
            'option_image_c' => ['nullable', 'image', 'max:4096'],
            'option_image_d' => ['nullable', 'image', 'max:4096'],
            'option_image_e' => ['nullable', 'image', 'max:4096'],
            'matching_pairs' => ['nullable', 'string'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        if ($user->isGuru()) {
            $scope = $this->resolveTeacherScope($user);
            if ($scope['has_courses']) {
                if (! empty($validated['subject_id'])) {
                    abort_unless(in_array((int) $validated['subject_id'], $scope['subject_ids'], true), 403, 'Mata pelajaran tidak sesuai dengan kelas yang Anda ampu.');
                } elseif (count($scope['subject_ids']) === 1) {
                    $validated['subject_id'] = $scope['subject_ids'][0];
                }

                if (! empty($validated['grade_level']) && $scope['grade_levels'] !== []) {
                    abort_unless(in_array((int) $validated['grade_level'], $scope['grade_levels'], true), 403, 'Tingkat kelas tidak sesuai dengan kelas yang Anda ampu.');
                }
            }
        }

        return $validated;
    }

    /**
     * @return array{
     *     has_courses: bool,
     *     subject_ids: array<int, int>,
     *     grade_levels: array<int, int>,
     *     departments: Collection<int, string>,
     *     subjects: Collection<int, Subject>,
     *     classrooms: Collection<int, Classroom>
     * }
     */
    private function resolveTeacherScope(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'has_courses' => false,
                'subject_ids' => [],
                'grade_levels' => [10, 11, 12],
                'departments' => Classroom::query()
                    ->whereNotNull('department')
                    ->distinct()
                    ->pluck('department')
                    ->merge(['Umum', 'TKR', 'TKJ', 'AKL'])
                    ->map(fn ($d) => trim((string) $d))
                    ->filter()
                    ->unique()
                    ->values(),
                'subjects' => Subject::orderBy('name')->get(),
                'classrooms' => Classroom::orderBy('name')->get(),
            ];
        }

        $courses = $user->teachingCourses()
            ->with(['subject', 'classroom'])
            ->get();

        $hasCourses = $courses->isNotEmpty();
        if (! $hasCourses) {
            return [
                'has_courses' => false,
                'subject_ids' => [],
                'grade_levels' => [10, 11, 12],
                'departments' => Classroom::query()
                    ->whereNotNull('department')
                    ->distinct()
                    ->pluck('department')
                    ->map(fn ($d) => trim((string) $d))
                    ->filter()
                    ->unique()
                    ->values(),
                'subjects' => Subject::orderBy('name')->get(),
                'classrooms' => collect(),
            ];
        }

        $subjectIds = $courses->pluck('subject_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $classrooms = $courses->pluck('classroom')->filter()->unique('id')->sortBy('name')->values();

        $gradeLevels = $classrooms
            ->map(fn (Classroom $c) => Classroom::parseGradeNumber($c->grade_level, $c->name))
            ->filter(fn ($g) => in_array((int) $g, [10, 11, 12], true))
            ->map(fn ($g) => (int) $g)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $departments = $classrooms
            ->pluck('department')
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->unique()
            ->values();

        return [
            'has_courses' => true,
            'subject_ids' => $subjectIds,
            'grade_levels' => $gradeLevels !== [] ? $gradeLevels : [10, 11, 12],
            'departments' => $departments,
            'subjects' => Subject::whereIn('id', $subjectIds)->orderBy('name')->get(),
            'classrooms' => $classrooms,
        ];
    }

    /**
     * @return array{0: string|null, 1: array<string, string>}
     */
    private function storeQuestionMedia(Request $request, ?QuestionBank $existing = null): array
    {
        $questionImage = $existing?->question_image;
        if ($request->boolean('remove_question_image') && $questionImage) {
            Storage::disk('local')->delete($questionImage);
            $questionImage = null;
        }
        if ($request->hasFile('question_image')) {
            if ($questionImage) {
                Storage::disk('local')->delete($questionImage);
            }
            $questionImage = $request->file('question_image')->store('question-media/questions', 'local');
        }

        $optionImages = is_array($existing?->option_images_json) ? $existing->option_images_json : [];
        foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e'] as $letter => $lower) {
            if ($request->boolean("remove_option_image_{$lower}") && isset($optionImages[$letter])) {
                Storage::disk('local')->delete($optionImages[$letter]);
                unset($optionImages[$letter]);
            }
            if ($request->hasFile("option_image_{$lower}")) {
                if (isset($optionImages[$letter])) {
                    Storage::disk('local')->delete($optionImages[$letter]);
                }
                $optionImages[$letter] = $request->file("option_image_{$lower}")->store('question-media/options', 'local');
            }
        }

        return [$questionImage, $optionImages];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: array<mixed>|null, 1: string|null}
     */
    private function normalizeQuestionOptionsAndAnswer(array $validated): array
    {
        $type = $validated['type'];

        if (in_array($type, ['multiple_choice', 'multiple_choice_complex'], true)) {
            $options = array_filter([
                'A' => $validated['option_a'] ?? null,
                'B' => $validated['option_b'] ?? null,
                'C' => $validated['option_c'] ?? null,
                'D' => $validated['option_d'] ?? null,
                'E' => $validated['option_e'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            return [$options, $validated['correct_answer'] ?? null];
        }

        if ($type === 'matching') {
            $pairs = [];
            foreach (explode('|', (string) ($validated['matching_pairs'] ?? '')) as $idx => $pairStr) {
                $parts = explode('=', $pairStr, 2);
                if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                    $pairs[(string) ($idx + 1)] = [
                        'left' => trim($parts[0]),
                        'right' => trim($parts[1]),
                    ];
                }
            }

            $answerJson = $pairs !== []
                ? json_encode(collect($pairs)->mapWithKeys(fn ($p) => [$p['left'] => $p['right']])->all(), JSON_UNESCAPED_UNICODE)
                : ($validated['correct_answer'] ?? null);

            return [$pairs !== [] ? $pairs : null, $answerJson];
        }

        return [null, $validated['correct_answer'] ?? null];
    }
}
