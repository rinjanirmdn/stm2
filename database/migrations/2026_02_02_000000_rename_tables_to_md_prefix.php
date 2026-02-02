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
        // Rename tables to use md_ prefix
        Schema::rename('users', 'md_users');
        Schema::rename('warehouses', 'md_warehouse');
        Schema::rename('gates', 'md_gates');
        Schema::rename('truck_type_durations', 'md_truck');
        Schema::rename('roles', 'md_roles');
        Schema::rename('permissions', 'md_permissions');
        // Note: Spatie tables (model_has_permissions, model_has_roles, role_has_permissions) remain unchanged
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back to original names
        Schema::rename('md_users', 'users');
        Schema::rename('md_warehouse', 'warehouses');
        Schema::rename('md_gates', 'gates');
        Schema::rename('md_truck', 'truck_type_durations');
        Schema::rename('md_roles', 'roles');
        Schema::rename('md_permissions', 'permissions');
        // Note: Spatie tables (model_has_permissions, model_has_roles, role_has_permissions) remain unchanged
    }
};
