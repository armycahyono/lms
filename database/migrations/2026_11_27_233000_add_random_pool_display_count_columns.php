<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->unsignedInteger('display_question_count')->nullable()->after('shuffle_options');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->json('question_order_json')->nullable()->after('answers_json');
        });

        Schema::table('cbt_exams', function (Blueprint $table): void {
            $table->unsignedInteger('display_question_count')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table): void {
            $table->dropColumn('display_question_count');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('question_order_json');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('display_question_count');
        });
    }
};
