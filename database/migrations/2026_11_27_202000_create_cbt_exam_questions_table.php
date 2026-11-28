<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('cbt_package_id')->nullable()->after('cbt_exam_code');
            $table->timestamp('last_synced_at')->nullable()->after('is_published');
        });

        Schema::create('cbt_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['cbt_exam_id', 'question_bank_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_exam_questions');

        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->dropColumn(['cbt_package_id', 'last_synced_at']);
        });
    }
};
