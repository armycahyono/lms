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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->boolean('allow_late')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_validated_for_siap')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('type', 30)->default('multiple_choice');
            $table->longText('question_text');
            $table->json('options_json')->nullable();
            $table->text('correct_answer')->nullable();
            $table->decimal('points', 5, 2)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('answers_json')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('status', 30)->default('in_progress')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
