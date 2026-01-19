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

    private function getRoleGuardColumn(): string
    {
        if (Schema::hasColumn('roles', 'roles_guard_name')) return 'roles_guard_name';
        return 'guard_name';
    }

    private function getPermNameColumn(): string
    {
        if (Schema::hasColumn('permissions', 'perm_name')) return 'perm_name';
        return 'name';
    }

    private function getPermGuardColumn(): string
    {
        if (Schema::hasColumn('permissions', 'perm_guard_name')) return 'perm_guard_name';
        return 'guard_name';
    }

    public function up(): void
    {
        try {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
                return;
            }

            $guardName = 'web';

            $roleNameCol = $this->getRoleNameColumn();
            $roleGuardCol = $this->getRoleGuardColumn();
            $permNameCol = $this->getPermNameColumn();
            $permGuardCol = $this->getPermGuardColumn();

            $roleId = DB::table('roles')
                ->where($roleNameCol, 'Section Head')
                ->when(Schema::hasColumn('roles', $roleGuardCol), fn($q) => $q->where($roleGuardCol, $guardName))
                ->value('id');

            if (! $roleId) {
                $roleId = DB::table('roles')
                    ->where($roleNameCol, 'section_head')
                    ->when(Schema::hasColumn('roles', $roleGuardCol), fn($q) => $q->where($roleGuardCol, $guardName))
                    ->value('id');
            }

            if (! $roleId) {
                return;
            }

            $permissionId = DB::table('permissions')
                ->where($permNameCol, 'gates.toggle')
                ->when(Schema::hasColumn('permissions', $permGuardCol), fn($q) => $q->where($permGuardCol, $guardName))
                ->value('id');

            if (! $permissionId) {
                $insertData = [
                    $permNameCol => 'gates.toggle',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('permissions', $permGuardCol)) {
                    $insertData[$permGuardCol] = $guardName;
                }
                $permissionId = DB::table('permissions')->insertGetId($insertData);
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

