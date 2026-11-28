<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('cbt_exam_code', 60)->nullable()->index();
            $table->string('title');
            $table->string('exam_type', 30)->default('PTS');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('launch_url', 500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('cbt_exam_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('status', 30)->default('selesai');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['cbt_exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_exam_results');
        Schema::dropIfExists('cbt_exams');
    }
};
