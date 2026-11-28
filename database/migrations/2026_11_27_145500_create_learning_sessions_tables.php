<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('topic')->nullable();
            $table->date('session_date');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_session_id')->constrained('learning_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('hadir');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['learning_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_attendances');
        Schema::dropIfExists('learning_sessions');
    }
};
