<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\Material;
use App\Models\MaterialProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $query = Course::query()
            ->with(['classroom', 'subject', 'academicYear', 'teachers'])
            ->withCount(['students', 'materials', 'assignments']);

        if ($user->isSiswa()) {
            $query->where('status', 'active')
                ->whereHas('students', fn ($q) => $q->where('users.id', $user->id)->where('course_enrollments.status', 'active'));
        } elseif ($user->isGuru() && ! $user->hasStructuralRole('kepsek', $activeYearId) && ! $user->hasStructuralRole('waka_kurikulum', $activeYearId)) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereIn('classroom_id', $user->assignments()->where('role_type', 'wali_kelas')->pluck('classroom_id'));
            });
        }

        return view('courses.index', [
            'courses' => $query->latest('id')->get(),
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $user = $request->user();
        $canManage = Gate::allows('manage', $course);

        $course->load([
            'classroom',
            'subject',
            'academicYear',
            'teachers',
            'modules' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->orderBy('sort_order'),
            'modules.materials' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->orderBy('sort_order'),
            'assignments' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->latest('id'),
            'quizzes' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->latest('id'),
            'discussions' => fn ($q) => $q->whereNull('discussable_type')->whereNull('parent_id')->with(['user', 'replies.user'])->latest('id'),
        ]);

        $completedMaterialIds = $user->isSiswa()
            ? MaterialProgress::where('student_id', $user->id)
                ->whereIn('material_id', $course->materials()->pluck('id'))
                ->pluck('material_id')
                ->all()
            : [];

        $duplicableCourses = $canManage
            ? Course::where('id', '!=', $course->id)
                ->whereHas('modules')
                ->with(['classroom', 'subject', 'academicYear'])
                ->get()
            : collect();

        $unassignedMaterials = $course->materials()
            ->whereNull('module_id')
            ->when(! $canManage, fn ($sub) => $sub->where('is_published', true))
            ->orderBy('sort_order')
            ->get();

        return view('courses.show', [
            'course' => $course,
            'canManage' => $canManage,
            'completedMaterialIds' => $completedMaterialIds,
            'duplicableCourses' => $duplicableCourses,
            'unassignedMaterials' => $unassignedMaterials,
        ]);
    }

    public function open(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $enrolledCount = DB::transaction(function () use ($course): int {
            $course->update([
                'status' => 'active',
                'opened_at' => $course->opened_at ?? now(),
            ]);

            $studentIds = ClassroomStudent::where('classroom_id', $course->classroom_id)
                ->where('status', 'active')
                ->pluck('student_user_id');

            $count = 0;
            foreach ($studentIds as $studentId) {
                CourseEnrollment::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'enrolled_at' => now(),
                        'status' => 'active',
                    ]
                );
                $count++;
            }

            return $count;
        });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'course.opened',
            'subject_type' => Course::class,
            'subject_id' => $course->id,
            'properties_json' => ['enrolled_students' => $enrolledCount],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Kelas dibuka dan {$enrolledCount} siswa rombel otomatis didaftarkan.");
    }

    public function archive(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $course->update(['status' => 'archived']);

        return back()->with('status', 'Kelas berhasil diarsipkan.');
    }

    public function duplicate(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'source_course_id' => ['required', 'integer', 'exists:courses,id', 'not_in:'.$course->id],
        ]);

        $sourceCourse = Course::with(['modules.materials'])->findOrFail($validated['source_course_id']);

        DB::transaction(function () use ($sourceCourse, $course): void {
            foreach ($sourceCourse->modules as $sourceModule) {
                $newModule = CourseModule::create([
                    'course_id' => $course->id,
                    'title' => $sourceModule->title,
                    'description' => $sourceModule->description,
                    'sort_order' => $sourceModule->sort_order,
                    'is_published' => false,
                ]);

                foreach ($sourceModule->materials as $sourceMaterial) {
                    $newFilePath = null;
                    if ($sourceMaterial->file_path && Storage::disk('local')->exists($sourceMaterial->file_path)) {
                        $newFilePath = 'materials/'.uniqid('copy_', true).'_'.basename($sourceMaterial->file_path);
                        Storage::disk('local')->copy($sourceMaterial->file_path, $newFilePath);
                    }

                    Material::create([
                        'module_id' => $newModule->id,
                        'course_id' => $course->id,
                        'title' => $sourceMaterial->title,
                        'type' => $sourceMaterial->type,
                        'content' => $sourceMaterial->content,
                        'file_path' => $newFilePath,
                        'external_url' => $sourceMaterial->external_url,
                        'sort_order' => $sourceMaterial->sort_order,
                        'is_published' => false,
                    ]);
                }
            }
        });

        return back()->with('status', "Modul dan materi berhasil diduplikasi dari {$sourceCourse->title} (status draft).");
    }
}
