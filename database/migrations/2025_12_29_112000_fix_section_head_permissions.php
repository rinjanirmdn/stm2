<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';

        // Get role IDs
        $adminRoleId = DB::table('md_roles')->where($roleNameCol, 'Admin')->value('id');
        $sectionHeadRoleId = DB::table('md_roles')->where($roleNameCol, 'Section Head')->value('id');

        // Get permission IDs
        $gatesIndexId = DB::table('md_permissions')->where($permNameCol, 'gates.index')->value('id');
        $trucksIndexId = DB::table('md_permissions')->where($permNameCol, 'trucks.index')->value('id');
        $logsIndexId = DB::table('md_permissions')->where($permNameCol, 'logs.index')->value('id');
        $vendorsCreateId = DB::table('md_permissions')->where($permNameCol, 'vendors.create')->value('id');
        $vendorsStoreId = DB::table('md_permissions')->where($permNameCol, 'vendors.store')->value('id');
        $vendorsEditId = DB::table('md_permissions')->where($permNameCol, 'vendors.edit')->value('id');
        $vendorsUpdateId = DB::table('md_permissions')->where($permNameCol, 'vendors.update')->value('id');
        $vendorsDeleteId = DB::table('md_permissions')->where($permNameCol, 'vendors.delete')->value('id');
        $vendorsImportId = DB::table('md_permissions')->where($permNameCol, 'vendors.import')->value('id');
        $vendorsImportStoreId = DB::table('md_permissions')->where($permNameCol, 'vendors.import.store')->value('id');
        $trucksCreateId = DB::table('md_permissions')->where($permNameCol, 'trucks.create')->value('id');
        $trucksStoreId = DB::table('md_permissions')->where($permNameCol, 'trucks.store')->value('id');
        $trucksEditId = DB::table('md_permissions')->where($permNameCol, 'trucks.edit')->value('id');
        $trucksUpdateId = DB::table('md_permissions')->where($permNameCol, 'trucks.update')->value('id');
        $trucksDeleteId = DB::table('md_permissions')->where($permNameCol, 'trucks.delete')->value('id');

        echo "Admin role ID: $adminRoleId\n";
        echo "Section Head role ID: $sectionHeadRoleId\n";
        echo "Gates index permission ID: $gatesIndexId\n";

        // Add missing permissions to Section Head
        if ($sectionHeadRoleId && $gatesIndexId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $sectionHeadRoleId)
                ->where('permission_id', $gatesIndexId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $sectionHeadRoleId,
                    'permission_id' => $gatesIndexId,
                ]);
                echo "Added gates.index to Section Head\n";
            }
        }

        // Add other missing permissions to Section Head
        $sectionHeadPermissions = [
            $trucksIndexId, $logsIndexId,
            $vendorsCreateId, $vendorsStoreId, $vendorsEditId, $vendorsUpdateId, $vendorsDeleteId, $vendorsImportId, $vendorsImportStoreId,
            $trucksCreateId, $trucksStoreId, $trucksEditId, $trucksUpdateId, $trucksDeleteId,
        ];

        foreach ($sectionHeadPermissions as $permId) {
            if ($sectionHeadRoleId && $permId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $sectionHeadRoleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $sectionHeadRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // Verify final permissions
        echo "\nSection Head final permissions:\n";
        $finalPerms = DB::table('role_has_permissions')
            ->join('md_permissions', 'role_has_permissions.permission_id', '=', 'md_permissions.id')
            ->where('role_has_permissions.role_id', $sectionHeadRoleId)
            ->pluck('md_permissions.'.$permNameCol)
            ->toArray();

        foreach ($finalPerms as $perm) {
            echo "- $perm\n";
        }

        echo "\nTotal Section Head permissions: ".count($finalPerms)."\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';

        // Remove the permissions we added
        $sectionHeadRoleId = DB::table('md_roles')->where($roleNameCol, 'Section Head')->value('id');

        if ($sectionHeadRoleId) {
            $permissionsToRemove = [
                'gates.index',
                'trucks.index',
                'logs.index',
                'vendors.create',
                'vendors.store',
                'vendors.edit',
                'vendors.update',
                'vendors.delete',
                'vendors.import',
                'vendors.import.store',
                'trucks.create',
                'trucks.store',
                'trucks.edit',
                'trucks.update',
                'trucks.delete',
            ];

            $permissionIds = DB::table('md_permissions')
                ->whereIn($permNameCol, $permissionsToRemove)
                ->pluck('id')
                ->toArray();

            DB::table('role_has_permissions')
                ->where('role_id', $sectionHeadRoleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
