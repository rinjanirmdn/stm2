<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
                return;
            }

            $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
            $modelHasPermissionsTable = (string) (config('permission.table_names.model_has_permissions') ?? 'model_has_permissions');

            $roleId = DB::table('roles')->where('name', 'Section Head')->value('id');
            if (! $roleId) {
                $roleId = DB::table('roles')->where('name', 'section_head')->value('id');
            }

            if (! $roleId) {
                return;
            }

            $permissionIds = DB::table('permissions')
                ->where(function ($query) {
                    $query
                        ->where('name', 'like', 'users.%')
                        ->orWhere('name', 'like', 'logs.%')
                        ->orWhereIn('name', [
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
