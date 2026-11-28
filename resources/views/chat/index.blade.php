@extends('layouts.app')

@section('title', 'Ruang Chat & Diskusi')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Komunikasi Pembelajaran</div>
            <h2 class="page-title">Ruang Chat &amp; Diskusi</h2>
        </div>
    </div>
</div>

@livewire('chat-workspace', [
    'initialCourseId' => $initialCourseId,
    'initialRefType' => $initialRefType,
    'initialRefId' => $initialRefId,
])
@endsection
