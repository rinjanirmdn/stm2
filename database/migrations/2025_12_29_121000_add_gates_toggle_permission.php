<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function getRoleNameColumn(): string
    {
        if (Schema::hasColumn('md_roles', 'roles_name')) return 'roles_name';
        return 'name';
    }

    private function getRoleGuardColumn(): string
    {
        if (Schema::hasColumn('md_roles', 'roles_guard_name')) return 'roles_guard_name';
        return 'guard_name';
    }

    private function getPermNameColumn(): string
    {
        if (Schema::hasColumn('md_permissions', 'perm_name')) return 'perm_name';
        return 'name';
    }

    private function getPermGuardColumn(): string
    {
        if (Schema::hasColumn('md_permissions', 'perm_guard_name')) return 'perm_guard_name';
        return 'guard_name';
    }

    public function up(): void
    {
        $guardName = 'web';

        $permNameCol = $this->getPermNameColumn();
        $permGuardCol = $this->getPermGuardColumn();
        $roleNameCol = $this->getRoleNameColumn();
        $roleGuardCol = $this->getRoleGuardColumn();

        $permissionId = DB::table('md_permissions')
            ->where($permNameCol, 'gates.toggle')
            ->when(Schema::hasColumn('md_permissions', $permGuardCol), fn($q) => $q->where($permGuardCol, $guardName))
            ->value('id');

        if (! $permissionId) {
            $insertData = [
                $permNameCol => 'gates.toggle',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('md_permissions', $permGuardCol)) {
                $insertData[$permGuardCol] = $guardName;
            }
            $permissionId = DB::table('md_permissions')->insertGetId($insertData);
        }

        $adminRoleId = DB::table('md_roles')
            ->where($roleNameCol, 'Admin')
            ->when(Schema::hasColumn('md_roles', $roleGuardCol), fn($q) => $q->where($roleGuardCol, $guardName))
            ->value('id');

        if ($adminRoleId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
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

    public function down(): void
    {
        $guardName = 'web';

        $permNameCol = $this->getPermNameColumn();
        $permGuardCol = $this->getPermGuardColumn();

        $permissionId = DB::table('md_permissions')
            ->where($permNameCol, 'gates.toggle')
            ->when(Schema::hasColumn('md_permissions', $permGuardCol), fn($q) => $q->where($permGuardCol, $guardName))
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('md_permissions')->where('id', $permissionId)->delete();
        }

        try {
            app('cache')
                ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
                ->forget(config('permission.cache.key'));
        } catch (\Throwable $e) {
        }
    }
};


