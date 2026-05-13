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
        Schema::table('md_users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });

        // Option B: Immediate Enforcement for old users
        // Set password_changed_at to created_at for all existing users
        \Illuminate\Support\Facades\DB::table('md_users')->update([
            'password_changed_at' => \Illuminate\Support\Facades\DB::raw('created_at')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
