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
        // Rename tables to use md_ prefix
        if (Schema::hasTable('users')) {
            Schema::rename('users', 'md_users');
        }
        if (Schema::hasTable('warehouses')) {
            Schema::rename('warehouses', 'md_warehouse');
        }
        if (Schema::hasTable('gates')) {
            Schema::rename('gates', 'md_gates');
        }
        if (Schema::hasTable('truck_type_durations')) {
            Schema::rename('truck_type_durations', 'md_truck');
        }
        if (Schema::hasTable('roles')) {
            Schema::rename('roles', 'md_roles');
        }
        if (Schema::hasTable('permissions')) {
            Schema::rename('permissions', 'md_permissions');
        }
        // Note: Spatie tables (model_has_permissions, model_has_roles, role_has_permissions) remain unchanged
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back to original names
        if (Schema::hasTable('md_users')) {
            Schema::rename('md_users', 'users');
        }
        if (Schema::hasTable('md_warehouse')) {
            Schema::rename('md_warehouse', 'warehouses');
        }
        if (Schema::hasTable('md_gates')) {
            Schema::rename('md_gates', 'gates');
        }
        if (Schema::hasTable('md_truck')) {
            Schema::rename('md_truck', 'truck_type_durations');
        }
        if (Schema::hasTable('md_roles')) {
            Schema::rename('md_roles', 'roles');
        }
        if (Schema::hasTable('md_permissions')) {
            Schema::rename('md_permissions', 'permissions');
        }
        // Note: Spatie tables (model_has_permissions, model_has_roles, role_has_permissions) remain unchanged
    }
};
