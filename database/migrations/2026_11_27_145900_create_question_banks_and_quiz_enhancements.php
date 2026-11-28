<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('category', 100)->nullable()->index();
            $table->string('difficulty', 20)->default('sedang');
            $table->string('type', 30)->default('multiple_choice');
            $table->text('question_text');
            $table->json('options_json')->nullable();
            $table->string('correct_answer')->nullable();
            $table->decimal('points', 6, 2)->default(10);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->boolean('shuffle_questions')->default(false)->after('max_attempts');
            $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            $table->decimal('passing_score', 6, 2)->default(75)->after('shuffle_options');
            $table->string('quiz_type', 30)->default('reguler')->after('passing_score');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn(['shuffle_questions', 'shuffle_options', 'passing_score', 'quiz_type']);
        });
        Schema::dropIfExists('question_banks');
    }
};
