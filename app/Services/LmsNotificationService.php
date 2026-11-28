<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LmsNotification;
use App\Models\User;

class LmsNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyUser(User $user, string $type, string $title, string $message, array $data = []): LmsNotification
    {
        return LmsNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data_json' => $data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyCourseStudents(Course $course, string $type, string $title, string $message, array $data = []): int
    {
        $studentIds = $course->students()
            ->where('course_enrollments.status', 'active')
            ->pluck('users.id')
            ->all();

        if ($studentIds === []) {
            return 0;
        }

        $now = now();
        $encodedData = $data !== [] ? json_encode($data) : null;
        $rows = array_map(fn (int $studentId): array => [
            'user_id' => $studentId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data_json' => $encodedData,
            'created_at' => $now,
            'updated_at' => $now,
        ], $studentIds);

        LmsNotification::insert($rows);

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyAllActiveUsers(string $type, string $title, string $message, array $data = [], ?int $exceptUserId = null): int
    {
        $userIds = User::query()
            ->where('is_active', true)
            ->when($exceptUserId !== null, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->pluck('id')
            ->all();

        if ($userIds === []) {
            return 0;
        }

        $now = now();
        $encodedData = $data !== [] ? json_encode($data) : null;
        $rows = array_map(fn (int $userId): array => [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data_json' => $encodedData,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        LmsNotification::insert($rows);

        return count($rows);
    }
}
