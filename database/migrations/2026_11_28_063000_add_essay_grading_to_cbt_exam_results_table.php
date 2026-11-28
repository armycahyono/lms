<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_exam_results', function (Blueprint $table): void {
            $table->string('cbt_student_name')->nullable()->after('student_id');
            $table->decimal('auto_score', 6, 2)->nullable()->after('score');
            $table->decimal('essay_score', 6, 2)->nullable()->after('auto_score');
            $table->foreignId('graded_by')->nullable()->after('finished_at')->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });

        Schema::create('cbt_exam_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cbt_exam_result_id')->constrained('cbt_exam_results')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->nullOnDelete();
            $table->unsignedInteger('question_number');
            $table->string('question_type', 40);
            $table->longText('student_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('max_points', 6, 2)->default(1);
            $table->decimal('earned_points', 6, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();

            $table->unique(['cbt_exam_result_id', 'question_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_exam_answers');

        Schema::table('cbt_exam_results', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn(['cbt_student_name', 'auto_score', 'essay_score', 'graded_at']);
        });
    }
};
