<?php

use App\Http\Controllers\AcademicYearSwitchController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\SiapSyncController;
use App\Http\Controllers\Admin\StudentPasswordController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\CbtExamController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\GlobalAssignmentController;
use App\Http\Controllers\HomeroomController;
use App\Http\Controllers\LearningAttendanceController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ProjectModuleController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RemedialEnrichmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeachingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/preview-desain', function () {
    return view('preview.design-concepts');
})->name('design.preview');

Route::get('/preview-header', function () {
    return view('preview.header-concepts');
})->name('header.preview');

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
        Route::post('/academic-year/switch', [AcademicYearSwitchController::class, 'switch'])->name('academic-year.switch');

        Route::get('/tugas', [GlobalAssignmentController::class, 'index'])->name('tasks.index');
        Route::post('/tugas/buat', [GlobalAssignmentController::class, 'storeBatchAssignment'])->name('tasks.store-batch');
        Route::get('/kuis', [CbtExamController::class, 'index'])->defaults('defaultTab', 'quizzes')->name('quizzes.index');
        Route::post('/kuis/buat', [GlobalAssignmentController::class, 'storeBatchQuiz'])->name('quizzes.store-batch');
        Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/progres', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/ujian', [CbtExamController::class, 'index'])->name('exams.index');
        Route::get('/ujian/buat', [CbtExamController::class, 'create'])->name('exams.create');
        Route::post('/ujian', [CbtExamController::class, 'store'])->name('exams.store');
        Route::get('/ujian/{cbtExam}', [CbtExamController::class, 'show'])->name('exams.show');
        Route::get('/ujian/{cbtExam}/edit', [CbtExamController::class, 'edit'])->name('exams.edit');
        Route::put('/ujian/{cbtExam}', [CbtExamController::class, 'update'])->name('exams.update');
        Route::delete('/ujian/{cbtExam}', [CbtExamController::class, 'destroy'])->name('exams.destroy');
        Route::get('/ujian/{cbtExam}/pilih-soal', [CbtExamController::class, 'pickQuestions'])->name('exams.questions.pick');
        Route::post('/ujian/{cbtExam}/questions', [CbtExamController::class, 'attachQuestions'])->name('exams.questions.attach');
        Route::delete('/ujian/{cbtExam}/questions/{questionBank}', [CbtExamController::class, 'detachQuestion'])->name('exams.questions.detach');
        Route::post('/ujian/{cbtExam}/push-cbt', [CbtExamController::class, 'pushToCbt'])->name('exams.push-cbt');
        Route::post('/ujian/{cbtExam}/pull-schedule', [CbtExamController::class, 'pullScheduleFromCbt'])->name('exams.pull-schedule');
        Route::post('/ujian/{cbtExam}/pull-cbt', [CbtExamController::class, 'pullResultsFromCbt'])->name('exams.pull-cbt');
        Route::post('/ujian/{cbtExam}/results', [CbtExamController::class, 'storeResult'])->name('exams.results.store');
        Route::post('/ujian/hasil/{cbtExamResult}/koreksi', [CbtExamController::class, 'gradeResult'])->name('exams.results.grade');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export/courses', [ReportController::class, 'exportCourses'])->name('reports.export.courses');
        Route::get('/laporan/export/journals', [ReportController::class, 'exportJournals'])->name('reports.export.journals');
        Route::get('/direktori', [DirectoryController::class, 'index'])->name('directory.index');
        Route::get('/wali-kelas/analisa', [HomeroomController::class, 'index'])->name('homeroom.index');
        Route::get('/wali-kelas/analisa/{student}', [HomeroomController::class, 'show'])->name('homeroom.show');
        Route::post('/wali-kelas/analisa/{student}/tindak-lanjut', [HomeroomController::class, 'storeFollowUp'])->name('homeroom.follow-ups.store');
        Route::get('/bank-soal', [QuestionBankController::class, 'index'])->name('question-bank.index');
        Route::post('/bank-soal', [QuestionBankController::class, 'store'])->name('question-bank.store');
        Route::put('/bank-soal/{questionBank}', [QuestionBankController::class, 'update'])->name('question-bank.update');
        Route::get('/bank-soal/media/{path}', [QuestionBankController::class, 'media'])->where('path', '.*')->name('question-bank.media');
        Route::get('/bank-soal/template-excel', [QuestionBankController::class, 'downloadTemplate'])->name('question-bank.template.download');
        Route::get('/bank-soal/template-word', [QuestionBankController::class, 'downloadWordTemplate'])->name('question-bank.template-word.download');
        Route::post('/bank-soal/import-preview', [QuestionBankController::class, 'importPreview'])->name('question-bank.import.preview');
        Route::post('/bank-soal/import-confirm', [QuestionBankController::class, 'importConfirm'])->name('question-bank.import.confirm');
        Route::post('/bank-soal/{questionBank}/toggle-share', [QuestionBankController::class, 'toggleShare'])->name('question-bank.toggle-share');
        Route::get('/bank-soal/{questionBank}/analisis', [QuestionBankController::class, 'analysis'])->name('question-bank.analysis');
        Route::delete('/bank-soal/{questionBank}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');
        Route::get('/perangkat-ajar', [TeachingPlanController::class, 'index'])->name('teaching-plans.index');
        Route::post('/perangkat-ajar', [TeachingPlanController::class, 'store'])->name('teaching-plans.store');
        Route::get('/perangkat-ajar/{teachingPlan}', [TeachingPlanController::class, 'show'])->name('teaching-plans.show');
        Route::put('/perangkat-ajar/{teachingPlan}', [TeachingPlanController::class, 'update'])->name('teaching-plans.update');
        Route::post('/perangkat-ajar/{teachingPlan}/toggle-share', [TeachingPlanController::class, 'toggleShare'])->name('teaching-plans.toggle-share');
        Route::post('/perangkat-ajar/{teachingPlan}/copy-from', [TeachingPlanController::class, 'copyFrom'])->name('teaching-plans.copy-from');
        Route::get('/perangkat-ajar/{teachingPlan}/template-excel', [TeachingPlanController::class, 'downloadTemplate'])->name('teaching-plans.template.download');
        Route::post('/perangkat-ajar/{teachingPlan}/import-preview', [TeachingPlanController::class, 'importPreview'])->name('teaching-plans.import.preview');
        Route::post('/perangkat-ajar/{teachingPlan}/import-confirm', [TeachingPlanController::class, 'importConfirm'])->name('teaching-plans.import.confirm');
        Route::post('/perangkat-ajar/{teachingPlan}/objectives', [TeachingPlanController::class, 'storeObjective'])->name('teaching-plans.objectives.store');
        Route::put('/perangkat-ajar/{teachingPlan}/objectives/{objective}', [TeachingPlanController::class, 'updateObjective'])->name('teaching-plans.objectives.update');
        Route::delete('/perangkat-ajar/{teachingPlan}/objectives/{objective}', [TeachingPlanController::class, 'destroyObjective'])->name('teaching-plans.objectives.destroy');
        Route::post('/perangkat-ajar/{teachingPlan}/lesson-plans', [TeachingPlanController::class, 'storeLessonPlan'])->name('teaching-plans.lesson-plans.store');
        Route::put('/perangkat-ajar/{teachingPlan}/lesson-plans/{lessonPlan}', [TeachingPlanController::class, 'updateLessonPlan'])->name('teaching-plans.lesson-plans.update');
        Route::get('/perangkat-ajar/{teachingPlan}/lesson-plans/{lessonPlan}/download', [TeachingPlanController::class, 'downloadLessonPlan'])->name('teaching-plans.lesson-plans.download');
        Route::delete('/perangkat-ajar/{teachingPlan}/lesson-plans/{lessonPlan}', [TeachingPlanController::class, 'destroyLessonPlan'])->name('teaching-plans.lesson-plans.destroy');
        Route::post('/perangkat-ajar/{teachingPlan}/publish-parallel', [TeachingPlanController::class, 'publishToCourses'])->name('teaching-plans.publish-parallel');
        Route::prefix('/orang-tua')->name('parent.')->group(function (): void {
            Route::get('/', [ParentPortalController::class, 'index'])->name('index');
            Route::get('/presensi', [ParentPortalController::class, 'attendance'])->name('attendance');
            Route::post('/presensi/resync', [ParentPortalController::class, 'resyncAttendance'])->name('attendance.resync');
            Route::get('/belajar', [ParentPortalController::class, 'learning'])->name('learning');
            Route::get('/rekaman', [ParentPortalController::class, 'records'])->name('records');
            Route::get('/komunikasi', [ParentPortalController::class, 'communications'])->name('communications');
            Route::get('/profil', [ParentPortalController::class, 'profile'])->name('profile');
            Route::post('/profil/preferensi', [ParentPortalController::class, 'updatePreferences'])->name('preferences.update');
        });
        Route::get('/projek-p5', [ProjectModuleController::class, 'index'])->name('projects.index');
        Route::post('/projek-p5', [ProjectModuleController::class, 'store'])->name('projects.store');
        Route::get('/projek-p5/{projectModule}/download', [ProjectModuleController::class, 'download'])->name('projects.download');
        Route::delete('/projek-p5/{projectModule}', [ProjectModuleController::class, 'destroy'])->name('projects.destroy');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifikasi/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/attachments/{chatMessage}', [ChatController::class, 'downloadAttachment'])->name('chat.attachments.download');

        Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profil/identitas', [ProfileController::class, 'updateIdentity'])->name('profile.identity.update');
        Route::post('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/profil/notepad', [ProfileController::class, 'updateNotepad'])->name('profile.notepad.update');

        Route::post('/announcements', [AnnouncementController::class, 'storeSchool'])->name('announcements.school.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::post('/admin/students/{student}/reset-password', [StudentPasswordController::class, 'reset'])
            ->name('admin.students.reset-password');
        Route::post('/admin/siap/sync', [SiapSyncController::class, 'sync'])
            ->name('admin.siap.sync');
        Route::get('/admin/pengaturan', [SystemSettingController::class, 'index'])
            ->name('admin.settings.index');
        Route::post('/admin/pengaturan/sync-group', [SystemSettingController::class, 'syncGroup'])
            ->name('admin.settings.sync-group');
        Route::post('/admin/pengaturan/checkpoints/{checkpoint}/toggle', [SystemSettingController::class, 'toggleCheckpoint'])
            ->name('admin.settings.checkpoints.toggle');
        Route::get('/admin/backup', [BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/admin/backup', [BackupController::class, 'store'])->name('admin.backups.store');
        Route::get('/admin/backup/{filename}/unduh', [BackupController::class, 'download'])->name('admin.backups.download');
        Route::delete('/admin/backup/{filename}', [BackupController::class, 'destroy'])->name('admin.backups.destroy');

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

            Route::post('/{course}/announcements', [AnnouncementController::class, 'storeCourse'])->name('announcements.store');
            Route::post('/{course}/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
            Route::post('/{course}/sessions', [LearningAttendanceController::class, 'storeSession'])->name('sessions.store');
            Route::post('/{course}/sessions/{session}/toggle', [LearningAttendanceController::class, 'toggleSession'])->name('sessions.toggle');
            Route::post('/{course}/sessions/{session}/check-in', [LearningAttendanceController::class, 'checkIn'])->name('sessions.check-in');
            Route::post('/{course}/remedial-enrichments', [RemedialEnrichmentController::class, 'store'])->name('remedial-enrichments.store');
            Route::delete('/{course}/remedial-enrichments/{remedialEnrichment}', [RemedialEnrichmentController::class, 'destroy'])->name('remedial-enrichments.destroy');

            Route::post('/{course}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
            Route::get('/{course}/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
            Route::post('/{course}/quizzes/{quiz}/toggle-publish', [QuizController::class, 'togglePublish'])->name('quizzes.toggle-publish');
            Route::delete('/{course}/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
            Route::post('/{course}/quizzes/{quiz}/questions', [QuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
            Route::get('/{course}/quizzes/{quiz}/questions/{question}/grade', [QuizController::class, 'gradeByQuestion'])->name('quizzes.questions.grade');
            Route::post('/{course}/quizzes/{quiz}/questions/{question}/grade', [QuizAttemptController::class, 'gradeQuestion'])->name('quizzes.questions.grade.store');
            Route::get('/{course}/quizzes/{quiz}/pilih-soal', [QuizController::class, 'pickQuestions'])->name('quizzes.pick-questions');
            Route::post('/{course}/quizzes/{quiz}/import-bank', [QuizController::class, 'importFromBank'])->name('quizzes.import-bank');
            Route::post('/{course}/quizzes/{quiz}/toggle-publish', [QuizController::class, 'togglePublish'])->name('quizzes.toggle-publish');
            Route::post('/{course}/quizzes/{quiz}/start', [QuizAttemptController::class, 'start'])->name('quizzes.start');
            Route::get('/{course}/quizzes/{quiz}/attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('quizzes.attempts.show');
            Route::post('/{course}/quizzes/{quiz}/attempts/{attempt}/autosave', [QuizAttemptController::class, 'autosave'])->name('quizzes.attempts.autosave');
            Route::post('/{course}/quizzes/{quiz}/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->name('quizzes.attempts.submit');
            Route::post('/{course}/quizzes/{quiz}/attempts/{attempt}/grade', [QuizAttemptController::class, 'grade'])->name('quizzes.attempts.grade');
        });
    });
});
