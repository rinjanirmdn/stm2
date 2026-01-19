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

            $guardName = 'web';

            $roleId = DB::table('roles')
                ->where('name', 'Section Head')
                ->where('guard_name', $guardName)
                ->value('id');

            if (! $roleId) {
                $roleId = DB::table('roles')
                    ->where('name', 'section_head')
                    ->where('guard_name', $guardName)
                    ->value('id');
            }

            if (! $roleId) {
                return;
            }

            $permissionId = DB::table('permissions')
                ->where('name', 'gates.toggle')
                ->where('guard_name', $guardName)
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => 'gates.toggle',
                    'guard_name' => $guardName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
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
    }
};
