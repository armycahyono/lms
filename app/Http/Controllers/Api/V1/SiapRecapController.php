<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiapRecapController extends Controller
{
    public function gradeRecap(Request $request): JsonResponse
    {
        $academicYearSiapId = $request->query('academic_year_siap_id');
        $classroomSiapId = $request->query('classroom_siap_id');

        $submissions = AssignmentSubmission::query()
            ->where('is_validated_for_siap', true)
            ->whereNotNull('score')
            ->with([
                'student:id,siap_user_id,username,name',
                'assignment:id,course_id,title,max_score',
                'assignment.course:id,academic_year_id,classroom_id,subject_id,title',
                'assignment.course.academicYear:id,siap_id,name,semester',
                'assignment.course.classroom:id,siap_id,name',
                'assignment.course.subject:id,siap_id,code,name',
            ])
            ->when($academicYearSiapId, fn ($q) => $q->whereHas('assignment.course.academicYear', fn ($sub) => $sub->where('siap_id', $academicYearSiapId)))
            ->when($classroomSiapId, fn ($q) => $q->whereHas('assignment.course.classroom', fn ($sub) => $sub->where('siap_id', $classroomSiapId)))
            ->get();

        return response()->json([
            'data' => $submissions->map(fn (AssignmentSubmission $submission) => [
                'submission_id' => $submission->id,
                'student_siap_id' => $submission->student?->siap_user_id,
                'nisn' => $submission->student?->username,
                'student_name' => $submission->student?->name,
                'academic_year_siap_id' => $submission->assignment?->course?->academicYear?->siap_id,
                'classroom_siap_id' => $submission->assignment?->course?->classroom?->siap_id,
                'subject_siap_id' => $submission->assignment?->course?->subject?->siap_id,
                'assignment_title' => $submission->assignment?->title,
                'score' => (float) $submission->score,
                'max_score' => $submission->assignment?->max_score,
                'graded_at' => $submission->graded_at?->toIso8601String(),
            ]),
        ]);
    }

    public function classActivitySummary(Request $request): JsonResponse
    {
        $academicYearSiapId = $request->query('academic_year_siap_id');

        $courses = Course::query()
            ->where('status', 'active')
            ->with(['academicYear:id,siap_id,name,semester', 'classroom:id,siap_id,name', 'subject:id,siap_id,code,name'])
            ->withCount([
                'students as enrolled_students_count' => fn ($q) => $q
                    ->where('course_enrollments.status', 'active')
                    ->where('users.is_active', true),
                'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                'assignments as published_assignments_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->when($academicYearSiapId, fn ($q) => $q->whereHas('academicYear', fn ($sub) => $sub->where('siap_id', $academicYearSiapId)))
            ->get();

        return response()->json([
            'data' => $courses->map(fn (Course $course) => [
                'course_id' => $course->id,
                'title' => $course->title,
                'academic_year_siap_id' => $course->academicYear?->siap_id,
                'classroom_siap_id' => $course->classroom?->siap_id,
                'classroom_name' => $course->classroom?->name,
                'subject_siap_id' => $course->subject?->siap_id,
                'subject_name' => $course->subject?->name,
                'enrolled_students_count' => $course->enrolled_students_count,
                'published_materials_count' => $course->published_materials_count,
                'published_assignments_count' => $course->published_assignments_count,
            ]),
        ]);
    }
}
