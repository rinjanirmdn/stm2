<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $permissions = [
            'unplanned.start',
            'unplanned.start.store',
            'unplanned.complete',
            'unplanned.complete.store',
        ];

        $roleTable = 'md_roles';
        $permTable = 'md_permissions';

        foreach ($permissions as $perm) {
            $exists = DB::table($permTable)
                ->where('perm_name', $perm)
                ->where('perm_guard_name', $guard)
                ->exists();

            if (! $exists) {
                DB::table($permTable)->insert([
                    'perm_name' => $perm,
                    'perm_guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Grant these permissions to all roles that can interact with unplanned slots
        $roles = DB::table($roleTable)
            ->whereIn(DB::raw('LOWER(roles_name)'), ['admin', 'super account', 'section head', 'security', 'operator'])
            ->pluck('id');

        foreach ($roles as $roleId) {
            foreach ($permissions as $perm) {
                $permId = DB::table($permTable)
                    ->where('perm_name', $perm)
                    ->where('perm_guard_name', $guard)
                    ->value('id');

                if ($permId) {
                    $exists = DB::table('role_has_permissions')
                        ->where('permission_id', $permId)
                        ->where('role_id', $roleId)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permId,
                            'role_id' => $roleId,
                        ]);
                    }
                }
            }
        }

        // Clear Spatie permission cache
        try {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        $permissions = [
            'unplanned.start',
            'unplanned.start.store',
            'unplanned.complete',
            'unplanned.complete.store',
        ];

        $permIds = DB::table('md_permissions')
            ->whereIn('perm_name', $permissions)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('md_permissions')->whereIn('id', $permIds)->delete();
    }
};
