<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function getRoleNameColumn(): string
    {
        if (Schema::hasColumn('roles', 'roles_name')) return 'roles_name';
        return 'name';
    }

    private function getPermNameColumn(): string
    {
        if (Schema::hasColumn('permissions', 'perm_name')) return 'perm_name';
        return 'name';
    }

    public function up(): void
    {
        try {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
                return;
            }

            $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
            $modelHasPermissionsTable = (string) (config('permission.table_names.model_has_permissions') ?? 'model_has_permissions');

            $roleNameCol = $this->getRoleNameColumn();
            $permNameCol = $this->getPermNameColumn();

            $roleId = DB::table('md_roles')->where($roleNameCol, 'Section Head')->value('id');
            if (! $roleId) {
                $roleId = DB::table('md_roles')->where($roleNameCol, 'section_head')->value('id');
            }

            if (! $roleId) {
                return;
            }

            $permissionIds = DB::table('md_permissions')
                ->where(function ($query) use ($permNameCol) {
                    $query
                        ->where($permNameCol, 'like', 'users.%')
                        ->orWhere($permNameCol, 'like', 'logs.%')
                        ->orWhereIn($permNameCol, [
                            'view users',
                            'create users',
                            'edit users',
                            'delete users',
                            'view activity logs',
                        ]);
                })
                ->pluck('id')
                ->toArray();

            if (empty($permissionIds)) {
                return;
            }

            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            try {
                if (Schema::hasTable($modelHasRolesTable) && Schema::hasTable($modelHasPermissionsTable)) {
                    $sectionHeadUserIds = DB::table($modelHasRolesTable)
                        ->where('role_id', $roleId)
                        ->where('model_type', 'App\\Models\\User')
                        ->pluck('model_id')
                        ->toArray();

                    if (! empty($sectionHeadUserIds)) {
                        DB::table($modelHasPermissionsTable)
                            ->where('model_type', 'App\\Models\\User')
                            ->whereIn('model_id', $sectionHeadUserIds)
                            ->whereIn('permission_id', $permissionIds)
                            ->delete();
                    }
                }
            } catch (\Throwable $e) {
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
    }
};

