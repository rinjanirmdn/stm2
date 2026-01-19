<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');
        $roleHasPermissionsTable = (string) (config('permission.table_names.role_has_permissions') ?? 'role_has_permissions');

        $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';
        $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';

        $roleId = DB::table($rolesTable)
            ->whereRaw('LOWER(' . $roleNameCol . ') = ?', ['operator'])
            ->value('id');

        $permissionId = DB::table($permissionsTable)
            ->where($permNameCol, 'slots.ticket')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table($roleHasPermissionsTable)
                ->where('role_id', (int) $roleId)
                ->where('permission_id', (int) $permissionId)
                ->delete();
        }

        try {
            app('cache')
                ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
                ->forget(config('permission.cache.key'));
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');
        $roleHasPermissionsTable = (string) (config('permission.table_names.role_has_permissions') ?? 'role_has_permissions');

        $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';
        $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';

        $roleId = DB::table($rolesTable)
            ->whereRaw('LOWER(' . $roleNameCol . ') = ?', ['operator'])
            ->value('id');

        $permissionId = DB::table($permissionsTable)
            ->where($permNameCol, 'slots.ticket')
            ->value('id');

        if ($roleId && $permissionId) {
            $exists = DB::table($roleHasPermissionsTable)
                ->where('role_id', (int) $roleId)
                ->where('permission_id', (int) $permissionId)
                ->exists();

            if (! $exists) {
                DB::table($roleHasPermissionsTable)->insert([
                    'role_id' => (int) $roleId,
                    'permission_id' => (int) $permissionId,
                ]);
            }
        }

        try {
            app('cache')
                ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
                ->forget(config('permission.cache.key'));
        } catch (\Throwable $e) {
        }
    }
};
