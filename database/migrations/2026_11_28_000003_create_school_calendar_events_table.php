<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_event_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable();
            $table->string('category_label')->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->string('start_time', 20)->nullable();
            $table->string('end_time', 20)->nullable();
            $table->boolean('is_all_day')->default(true);
            $table->boolean('is_holiday')->default(false);
            $table->string('color', 30)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar_events');
    }
};
