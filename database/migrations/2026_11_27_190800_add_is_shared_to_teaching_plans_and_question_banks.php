<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('total_jp');
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn('is_shared');
        });

        Schema::table('teaching_plans', function (Blueprint $table) {
            $table->dropColumn('is_shared');
        });
    }
};
