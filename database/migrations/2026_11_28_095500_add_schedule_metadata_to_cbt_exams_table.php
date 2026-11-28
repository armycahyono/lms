<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table): void {
            $table->string('session_name', 80)->nullable()->after('duration_minutes');
            $table->string('room_name', 80)->nullable()->after('session_name');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table): void {
            $table->dropColumn(['session_name', 'room_name']);
        });
    }
};
