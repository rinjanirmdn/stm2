<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dynamically detect column name
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        // Get Admin role ID
        $adminRoleId = DB::table('md_roles')->where($roleNameCol, 'Admin')->value('id');

        if (!$adminRoleId) {
            echo "Admin role not found!\n";
            return;
        }

        // Get all permission IDs
        $allPermissionIds = DB::table('md_permissions')->pluck('id')->toArray();

        // Remove existing Admin permissions
        DB::table('role_has_permissions')
            ->where('role_id', $adminRoleId)
            ->delete();

        // Assign ALL permissions to Admin
        $insertData = [];
        foreach ($allPermissionIds as $permissionId) {
            $insertData[] = [
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ];
        }

        DB::table('role_has_permissions')->insert($insertData);

        echo "Admin role updated with ALL permissions!\n";
        echo "Total permissions assigned: " . count($allPermissionIds) . "\n";

        // Verify
        $adminPermissionCount = DB::table('role_has_permissions')
            ->where('role_id', $adminRoleId)
            ->count();

        echo "Admin permissions count: $adminPermissionCount\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dynamically detect column name
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        // Remove all Admin permissions
        $adminRoleId = DB::table('md_roles')->where($roleNameCol, 'Admin')->value('id');

        if ($adminRoleId) {
            DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->delete();
        }
    }
};
