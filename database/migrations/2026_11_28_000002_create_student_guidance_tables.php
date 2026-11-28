<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_visitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_visitation_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('visit_type', 50)->nullable();
            $table->string('visit_type_label')->nullable();
            $table->text('reason')->nullable();
            $table->string('officer_name')->nullable();
            $table->date('planned_date')->nullable();
            $table->date('executed_date')->nullable();
            $table->string('status', 30)->default('planned')->index();
            $table->text('result_summary')->nullable();
            $table->text('follow_up_plan')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['student_user_id', 'planned_date'], 'sv_student_date_idx');
        });

        Schema::create('student_incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_incident_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('incident_date')->nullable()->index();
            $table->string('incident_time', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('follow_up_target', 50)->nullable();
            $table->string('disciplinary_sanction')->nullable();
            $table->boolean('parental_involvement')->default(false);
            $table->text('parental_involvement_notes')->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->string('recorder_name')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['student_user_id', 'incident_date'], 'si_student_date_idx');
        });

        Schema::create('student_permits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siap_permit_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permit_number', 50)->nullable();
            $table->date('permit_date')->nullable()->index();
            $table->date('end_date')->nullable();
            $table->string('permit_type', 50)->nullable();
            $table->string('permit_type_label')->nullable();
            $table->string('permit_category', 50)->nullable();
            $table->text('reason')->nullable();
            $table->string('entry_time', 20)->nullable();
            $table->string('departure_time', 20)->nullable();
            $table->string('return_time', 20)->nullable();
            $table->text('action_taken')->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->string('giver_name')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['student_user_id', 'permit_date'], 'sp_student_date_idx');
        });

        Schema::create('student_bk_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_type', 30)->index();
            $table->unsignedBigInteger('siap_record_id');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('record_date')->nullable()->index();
            $table->string('category')->nullable();
            $table->string('level')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('status', 30)->nullable();
            $table->string('recorded_by_name')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['record_type', 'siap_record_id'], 'sbr_type_siap_uq');
            $table->index(['student_user_id', 'record_date'], 'sbr_student_date_idx');
        });

        Schema::create('homeroom_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('follow_up_date')->index();
            $table->string('action_type', 40);
            $table->text('notes');
            $table->string('status', 20)->default('selesai')->index();
            $table->timestamps();

            $table->index(['student_user_id', 'follow_up_date'], 'hfu_student_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_follow_ups');
        Schema::dropIfExists('student_bk_records');
        Schema::dropIfExists('student_permits');
        Schema::dropIfExists('student_incidents');
        Schema::dropIfExists('student_visitations');
    }
};
