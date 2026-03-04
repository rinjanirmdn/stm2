<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy column: users.role (string). System now relies on role_id and Spatie model_has_roles.
        if (Schema::hasTable('md_users') && Schema::hasColumn('md_users', 'role')) {
            Schema::table('md_users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('md_users') && ! Schema::hasColumn('md_users', 'role')) {
            Schema::table('md_users', function (Blueprint $table) {
                $table->string('role')->default('user');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user');
            });
        }
    }
};
