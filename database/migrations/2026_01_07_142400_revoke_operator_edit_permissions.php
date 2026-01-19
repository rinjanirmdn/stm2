<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');
        $roleHasPermissionsTable = (string) (config('permission.table_names.role_has_permissions') ?? 'role_has_permissions');

        $roleId = DB::table($rolesTable)
            ->whereRaw('LOWER(name) = ?', ['operator'])
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionNames = [
            'slots.edit',
            'slots.update',
            'unplanned.edit',
            'unplanned.update',
        ];

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (! empty($permissionIds)) {
            DB::table($roleHasPermissionsTable)
                ->where('role_id', (int) $roleId)
                ->whereIn('permission_id', $permissionIds)
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

        $roleId = DB::table($rolesTable)
            ->whereRaw('LOWER(name) = ?', ['operator'])
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionNames = [
            'slots.edit',
            'slots.update',
            'unplanned.edit',
            'unplanned.update',
        ];

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($permissionIds as $pid) {
            $exists = DB::table($roleHasPermissionsTable)
                ->where('role_id', (int) $roleId)
                ->where('permission_id', (int) $pid)
                ->exists();

            if (! $exists) {
                DB::table($roleHasPermissionsTable)->insert([
                    'role_id' => (int) $roleId,
                    'permission_id' => (int) $pid,
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
