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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_id')->unique();
            $table->string('name');
            $table->string('semester', 20);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_id')->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('grade_level', 30)->nullable();
            $table->string('department', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_id')->unique();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('role_type', 40)->index();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'academic_year_id', 'role_type', 'classroom_id'], 'user_assignments_unique');
        });

        Schema::create('classroom_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->unique(['classroom_id', 'student_user_id']);
        });

        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_class_histories');
        Schema::dropIfExists('classroom_students');
        Schema::dropIfExists('user_assignments');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('academic_years');
    }
};
