<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('parent_notify_mode', 30)->default('alerts_only')->after('is_active');
        });

        Schema::create('parent_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 20)->default('wali');
            $table->timestamps();

            $table->unique(['parent_user_id', 'student_user_id']);
        });

        Schema::create('school_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20);
            $table->time('check_in_time')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('siap_attendance_id')->nullable()->unique();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['student_user_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_attendances');
        Schema::dropIfExists('parent_student');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('parent_notify_mode');
        });
    }
};
