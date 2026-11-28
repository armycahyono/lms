<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_stimuli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedTinyInteger('grade_level')->nullable();
            $table->string('title');
            $table->longText('content_html')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreignId('stimulus_id')->nullable()->after('subject_id')->constrained('question_stimuli')->nullOnDelete();
            $table->string('department', 60)->nullable()->after('grade_level');
            $table->string('question_image')->nullable()->after('question_text');
            $table->json('option_images_json')->nullable()->after('options_json');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->foreignId('stimulus_id')->nullable()->after('quiz_id')->constrained('question_stimuli')->nullOnDelete();
            $table->string('question_image')->nullable()->after('question_text');
            $table->json('option_images_json')->nullable()->after('options_json');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stimulus_id');
            $table->dropColumn(['question_image', 'option_images_json']);
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stimulus_id');
            $table->dropColumn(['department', 'question_image', 'option_images_json']);
        });

        Schema::dropIfExists('question_stimuli');
    }
};
