<?php

use App\Http\Controllers\Admin\StudentPasswordController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/ganti-password', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('/ganti-password', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('/admin/students/{student}/reset-password', [StudentPasswordController::class, 'reset'])
            ->name('admin.students.reset-password');

        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/', [CourseController::class, 'index'])->name('index');
            Route::get('/{course}', [CourseController::class, 'show'])->name('show');
            Route::post('/{course}/open', [CourseController::class, 'open'])->name('open');
            Route::post('/{course}/archive', [CourseController::class, 'archive'])->name('archive');
            Route::post('/{course}/duplicate', [CourseController::class, 'duplicate'])->name('duplicate');

            Route::post('/{course}/modules', [CourseModuleController::class, 'store'])->name('modules.store');
            Route::post('/{course}/modules/{module}/toggle-publish', [CourseModuleController::class, 'togglePublish'])->name('modules.toggle-publish');
            Route::delete('/{course}/modules/{module}', [CourseModuleController::class, 'destroy'])->name('modules.destroy');

            Route::post('/{course}/materials', [MaterialController::class, 'store'])->name('materials.store');
            Route::get('/{course}/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
            Route::get('/{course}/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
            Route::post('/{course}/materials/{material}/complete', [MaterialController::class, 'toggleComplete'])->name('materials.complete');
            Route::post('/{course}/materials/{material}/toggle-publish', [MaterialController::class, 'togglePublish'])->name('materials.toggle-publish');
            Route::delete('/{course}/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

            Route::post('/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
            Route::get('/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
            Route::get('/{course}/assignments/{assignment}/attachment', [AssignmentController::class, 'downloadAttachment'])->name('assignments.attachment');
            Route::post('/{course}/assignments/{assignment}/toggle-publish', [AssignmentController::class, 'togglePublish'])->name('assignments.toggle-publish');
            Route::delete('/{course}/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

            Route::post('/{course}/assignments/{assignment}/submissions', [AssignmentSubmissionController::class, 'store'])->name('assignments.submissions.store');
            Route::get('/{course}/assignments/{assignment}/submissions/{submission}/download', [AssignmentSubmissionController::class, 'download'])->name('assignments.submissions.download');
            Route::post('/{course}/assignments/{assignment}/submissions/{submission}/grade', [AssignmentSubmissionController::class, 'grade'])->name('assignments.submissions.grade');

            Route::post('/{course}/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
            Route::delete('/{course}/discussions/{discussion}', [DiscussionController::class, 'destroy'])->name('discussions.destroy');

            Route::post('/{course}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
            Route::get('/{course}/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
            Route::post('/{course}/quizzes/{quiz}/questions', [QuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
            Route::post('/{course}/quizzes/{quiz}/toggle-publish', [QuizController::class, 'togglePublish'])->name('quizzes.toggle-publish');
            Route::post('/{course}/quizzes/{quiz}/start', [QuizAttemptController::class, 'start'])->name('quizzes.start');
            Route::get('/{course}/quizzes/{quiz}/attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('quizzes.attempts.show');
            Route::post('/{course}/quizzes/{quiz}/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->name('quizzes.attempts.submit');
            Route::post('/{course}/quizzes/{quiz}/attempts/{attempt}/grade', [QuizAttemptController::class, 'grade'])->name('quizzes.attempts.grade');
        });
    });
});
