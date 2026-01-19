<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
            $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');
            $roleHasPermissionsTable = (string) (config('permission.table_names.role_has_permissions') ?? 'role_has_permissions');

            if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($permissionsTable) || ! Schema::hasTable($roleHasPermissionsTable)) {
                return;
            }

            $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';
            $roleGuardCol = Schema::hasColumn($rolesTable, 'roles_guard_name') ? 'roles_guard_name' : 'guard_name';

            $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';
            $permGuardCol = Schema::hasColumn($permissionsTable, 'perm_guard_name') ? 'perm_guard_name' : 'guard_name';

            $guardName = 'web';

            $roleId = DB::table($rolesTable)
                ->whereRaw('LOWER(' . $roleNameCol . ') = ?', ['section head'])
                ->when(Schema::hasColumn($rolesTable, $roleGuardCol), function ($q) use ($roleGuardCol, $guardName) {
                    return $q->where($roleGuardCol, $guardName);
                })
                ->value('id');

            if (! $roleId) {
                $roleId = DB::table($rolesTable)
                    ->whereRaw('LOWER(' . $roleNameCol . ') = ?', ['section_head'])
                    ->when(Schema::hasColumn($rolesTable, $roleGuardCol), function ($q) use ($roleGuardCol, $guardName) {
                        return $q->where($roleGuardCol, $guardName);
                    })
                    ->value('id');
            }

            if (! $roleId) {
                return;
            }

            $permissionId = DB::table($permissionsTable)
                ->where($permNameCol, 'logs.index')
                ->when(Schema::hasColumn($permissionsTable, $permGuardCol), function ($q) use ($permGuardCol, $guardName) {
                    return $q->where($permGuardCol, $guardName);
                })
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table($permissionsTable)->insertGetId([
                    $permNameCol => 'logs.index',
                    $permGuardCol => $guardName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! $permissionId) {
                return;
            }

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

            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        // no-op
    }
};
