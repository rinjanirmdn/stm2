<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename back to original Spatie table name
        Schema::rename('md_role_has_permissions', 'role_has_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('role_has_permissions', 'md_role_has_permissions');
    }
};
