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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('siap_user_id')->nullable()->unique()->after('id');
            $table->string('username')->unique()->after('siap_user_id');
            $table->string('email')->nullable()->change();
            $table->string('role', 20)->default('siswa')->index()->after('password');
            $table->boolean('must_change_password')->default(false)->after('role');
            $table->boolean('is_active')->default(true)->after('must_change_password');
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nip_nisn', 50)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'siap_user_id',
                'username',
                'role',
                'must_change_password',
                'is_active',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
