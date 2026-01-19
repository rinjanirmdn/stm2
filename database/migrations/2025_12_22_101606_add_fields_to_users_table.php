<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasUsername = Schema::hasColumn('users', 'username');
        $hasRole = Schema::hasColumn('users', 'role');
        $hasIsActive = Schema::hasColumn('users', 'is_active');

        Schema::table('users', function (Blueprint $table) use ($hasUsername, $hasRole, $hasIsActive) {
            if (! $hasUsername) {
                $table->string('username')->nullable()->after('id');
            }
            if (! $hasRole) {
                $table->string('role')->default('user')->after('email');
            }
            if (! $hasIsActive) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        // Backfill username for existing rows (pgsql safe)
        try {
            DB::statement("UPDATE users SET username = COALESCE(NULLIF(nik, ''), 'user_' || id::text) WHERE username IS NULL OR username = ''");
        } catch (\Throwable $e) {
            // ignore
        }

        // Enforce NOT NULL and unique constraint after backfill
        try {
            DB::statement('ALTER TABLE users ALTER COLUMN username SET NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_unique ON users (username)');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
