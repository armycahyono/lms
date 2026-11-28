<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subject_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_record_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attendance_date')->index();
            $table->unsignedTinyInteger('start_period')->nullable();
            $table->unsignedTinyInteger('end_period')->nullable();
            $table->string('status', 10)->default('h')->index();
            $table->string('topic')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['student_user_id', 'attendance_date'], 'ssa_student_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subject_attendances');
    }
};
