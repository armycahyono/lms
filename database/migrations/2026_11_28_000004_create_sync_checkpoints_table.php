<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50)->unique();
            $table->string('label');
            $table->boolean('auto_sync_enabled')->default(true);
            $table->unsignedInteger('schedule_minutes')->default(30);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_mode', 20)->nullable();
            $table->json('last_summary_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_checkpoints');
    }
};
