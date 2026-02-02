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
        // Restore Spatie pivot table names if they were renamed
        if (Schema::hasTable('md_model_has_permissions')) {
            Schema::rename('md_model_has_permissions', 'model_has_permissions');
        }
        if (Schema::hasTable('md_model_has_roles')) {
            Schema::rename('md_model_has_roles', 'model_has_roles');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This would rename back if needed
    }
};
