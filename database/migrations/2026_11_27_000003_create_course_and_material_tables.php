<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'classroom_id', 'subject_id'], 'courses_year_classroom_subject_unique');
        });

        Schema::create('course_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('siap_assignment_id')->nullable()->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'teacher_id']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20)->default('text')->index();
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_progress');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_teachers');
        Schema::dropIfExists('courses');
    }
};
