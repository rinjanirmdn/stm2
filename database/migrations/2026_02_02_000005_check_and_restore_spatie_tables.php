<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check current table names and rename if needed
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        $tableNames = array_map(fn($t) => $t->table_name, $tables);
        
        // Rename md_model_has_permissions -> model_has_permissions
        if (in_array('md_model_has_permissions', $tableNames) && !in_array('model_has_permissions', $tableNames)) {
            Schema::rename('md_model_has_permissions', 'model_has_permissions');
            echo "Renamed md_model_has_permissions to model_has_permissions\n";
        }
        
        // Rename md_model_has_roles -> model_has_roles  
        if (in_array('md_model_has_roles', $tableNames) && !in_array('model_has_roles', $tableNames)) {
            Schema::rename('md_model_has_roles', 'model_has_roles');
            echo "Renamed md_model_has_roles to model_has_roles\n";
        }
        
        // Rename md_role_has_permissions -> role_has_permissions
        if (in_array('md_role_has_permissions', $tableNames) && !in_array('role_has_permissions', $tableNames)) {
            Schema::rename('md_role_has_permissions', 'role_has_permissions');
            echo "Renamed md_role_has_permissions to role_has_permissions\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
