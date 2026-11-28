<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->string('phase', 30)->nullable();
            $table->string('title');
            $table->text('capaian_pembelajaran')->nullable();
            $table->string('kktp_type', 20)->default('interval');
            $table->unsignedSmallInteger('kktp_min_score')->default(75);
            $table->text('kktp_notes')->nullable();
            $table->unsignedSmallInteger('effective_weeks')->nullable();
            $table->unsignedSmallInteger('total_jp')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'subject_id', 'grade_level', 'teacher_id'], 'teaching_plans_unique_scope');
        });

        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_plan_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('jp_allocation')->default(2);
            $table->string('target_month', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('learning_goals')->nullable();
            $table->text('activities')->nullable();
            $table->text('assessment_plan')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->foreignId('learning_objective_id')->nullable()->after('teacher_id')->constrained()->nullOnDelete();
            $table->text('journal_summary')->nullable()->after('topic');
            $table->text('reflection')->nullable()->after('journal_summary');
            $table->boolean('is_achieved')->default(true)->after('reflection');
        });

        Schema::table('learning_attendances', function (Blueprint $table) {
            $table->string('source', 30)->default('manual')->after('status');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->string('category', 30)->default('materi')->after('type');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assessment_type', 30)->default('formatif')->after('course_id');
            $table->foreignId('learning_objective_id')->nullable()->after('assessment_type')->constrained()->nullOnDelete();
            $table->text('rubric_notes')->nullable()->after('instructions');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('assessment_type', 30)->default('formatif')->after('course_id');
            $table->foreignId('learning_objective_id')->nullable()->after('assessment_type')->constrained()->nullOnDelete();
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->string('kisi_indicator')->nullable()->after('category');
        });

        Schema::create('remedial_enrichments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->nullable()->constrained()->nullOnDelete();
            $table->string('program_type', 20);
            $table->decimal('initial_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('activity_notes')->nullable();
            $table->string('status', 20)->default('planned');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remedial_enrichments');

        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn('kisi_indicator');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_objective_id');
            $table->dropColumn('assessment_type');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_objective_id');
            $table->dropColumn(['assessment_type', 'rubric_notes']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('learning_attendances', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_objective_id');
            $table->dropColumn(['journal_summary', 'reflection', 'is_achieved']);
        });

        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('learning_objectives');
        Schema::dropIfExists('teaching_plans');
    }
};
