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
        // Directly rename all Spatie tables back to original names
        try {
            Schema::rename('md_model_has_permissions', 'model_has_permissions');
        } catch (\Throwable $e) {
            // Table might not exist or already renamed
        }

        try {
            Schema::rename('md_model_has_roles', 'model_has_roles');
        } catch (\Throwable $e) {
            // Table might not exist or already renamed
        }

        try {
            Schema::rename('md_role_has_permissions', 'role_has_permissions');
        } catch (\Throwable $e) {
            // Table might not exist or already renamed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the renames
        try {
            Schema::rename('model_has_permissions', 'md_model_has_permissions');
        } catch (\Throwable $e) {
        }

        try {
            Schema::rename('model_has_roles', 'md_model_has_roles');
        } catch (\Throwable $e) {
        }

        try {
            Schema::rename('role_has_permissions', 'md_role_has_permissions');
        } catch (\Throwable $e) {
        }
    }
};
