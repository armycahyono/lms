@extends('layouts.app')

@section('title', 'Kerjakan: ' . $quiz->title)

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">{{ $quiz->title }}</h2>
        <span class="badge bg-primary-lt">Durasi: {{ $quiz->duration_minutes }} menit</span>
    </div>
    <div class="card-body">
        <form action="{{ route('courses.quizzes.attempts.submit', [$course, $quiz, $attempt]) }}" method="POST">
            @csrf
            @foreach($quiz->questions as $idx => $question)
                <div class="mb-4 pb-3 border-bottom">
                    <div class="fw-semibold mb-2">{{ $idx + 1 }}. {{ $question->question_text }} <span class="text-muted small">({{ $question->points }} poin)</span></div>
                    @if($question->type === 'multiple_choice' && is_array($question->options_json))
                        @foreach($question->options_json as $key => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}">
                                <span class="form-check-label">{{ $key }}. {{ $label }}</span>
                            </label>
                        @endforeach
                    @else
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="form-control" placeholder="Tulis jawaban esai..."></textarea>
                    @endif
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Kirim Jawaban Kuis</button>
        </form>
    </div>
</div>
@endsection
