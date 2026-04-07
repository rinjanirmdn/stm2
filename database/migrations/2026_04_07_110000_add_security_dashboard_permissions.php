<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';
        $permGuardCol = Schema::hasColumn('md_permissions', 'perm_guard_name') ? 'perm_guard_name' : 'guard_name';
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $roleGuardCol = Schema::hasColumn('md_roles', 'roles_guard_name') ? 'roles_guard_name' : 'guard_name';

        // Security-specific permissions needed by routes
        $newPermissions = [
            'security.dashboard',
            'security.scan',
            'security.confirm_arrival',
        ];

        // Create permissions if they don't exist
        foreach ($newPermissions as $name) {
            $exists = DB::table('md_permissions')
                ->where($permNameCol, $name)
                ->where($permGuardCol, 'web')
                ->exists();

            if (! $exists) {
                DB::table('md_permissions')->insert([
                    $permNameCol => $name,
                    $permGuardCol => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign to Security role
        $securityRole = DB::table('md_roles')
            ->where($roleNameCol, 'Security')
            ->where($roleGuardCol, 'web')
            ->first();

        if ($securityRole) {
            $permIds = DB::table('md_permissions')
                ->whereIn($permNameCol, $newPermissions)
                ->pluck('id')
                ->toArray();

            foreach ($permIds as $pId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $securityRole->id)
                    ->where('permission_id', $pId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $securityRole->id,
                        'permission_id' => $pId,
                    ]);
                }
            }
        }

        // Also assign to Admin role (so admin can access security dashboard too)
        $adminRole = DB::table('md_roles')
            ->whereRaw("LOWER({$roleNameCol}) = ?", ['admin'])
            ->where($roleGuardCol, 'web')
            ->first();

        if ($adminRole) {
            $permIds = DB::table('md_permissions')
                ->whereIn($permNameCol, $newPermissions)
                ->pluck('id')
                ->toArray();

            foreach ($permIds as $pId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $pId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $pId,
                    ]);
                }
            }
        }

        Cache::forget('users:roles:all');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';

        $permIds = DB::table('md_permissions')
            ->whereIn($permNameCol, ['security.dashboard', 'security.scan', 'security.confirm_arrival'])
            ->pluck('id')
            ->toArray();

        if (count($permIds) > 0) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table('md_permissions')->whereIn('id', $permIds)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
