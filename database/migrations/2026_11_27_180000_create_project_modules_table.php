<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coordinator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('theme', 150);
            $table->string('phase', 30)->nullable();
            $table->text('dimensions')->nullable();
            $table->text('objectives')->nullable();
            $table->text('activities')->nullable();
            $table->text('assessment_notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_modules');
    }
};
