<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table): void {
            $table->unsignedInteger('usage_count')->default(0)->after('is_shared');
            $table->unsignedInteger('total_answered')->default(0)->after('usage_count');
            $table->unsignedInteger('total_correct')->default(0)->after('total_answered');
            $table->json('option_stats_json')->nullable()->after('total_correct');
            $table->timestamp('last_used_at')->nullable()->after('option_stats_json');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->foreignId('question_bank_id')->nullable()->after('quiz_id')->constrained('question_banks')->nullOnDelete();
            $table->text('rubric_keywords')->nullable()->after('correct_answer');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->unsignedInteger('focus_loss_count')->default(0)->after('status');
            $table->timestamp('last_saved_at')->nullable()->after('focus_loss_count');
            $table->json('question_scores_json')->nullable()->after('answers_json');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->boolean('target_remedial_only')->default(false)->after('quiz_type');
            $table->foreignId('parent_quiz_id')->nullable()->after('target_remedial_only')->constrained('quizzes')->nullOnDelete();
        });

        Schema::create('question_item_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_title');
            $table->string('academic_year_name', 60)->nullable();
            $table->unsignedInteger('total_answered')->default(0);
            $table->unsignedInteger('total_correct')->default(0);
            $table->decimal('difficulty_index', 5, 2)->nullable();
            $table->json('option_distribution_json')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['question_bank_id', 'source_type', 'source_id'], 'qih_bank_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_item_histories');

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_quiz_id');
            $table->dropColumn('target_remedial_only');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn(['focus_loss_count', 'last_saved_at', 'question_scores_json']);
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('question_bank_id');
            $table->dropColumn('rubric_keywords');
        });

        Schema::table('question_banks', function (Blueprint $table): void {
            $table->dropColumn(['usage_count', 'total_answered', 'total_correct', 'option_stats_json', 'last_used_at']);
        });
    }
};
