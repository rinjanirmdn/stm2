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

        $securityPermissions = [
            // Dashboard
            'dashboard.view',
            // Planned
            'slots.index', 'slots.show', 'slots.arrival', 'slots.arrival.store',
            'slots.search_suggestions', 'slots.ajax.po_search', 'slots.ajax.po_detail',
            'slots.ajax.check_risk', 'slots.ajax.check_slot_time', 'slots.ajax.recommend_gate',
            'slots.ajax.schedule_preview',
            // Gates
            'gates.index',
            // Profile
            'profile.index',
        ];

        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $roleGuardCol = Schema::hasColumn('md_roles', 'roles_guard_name') ? 'roles_guard_name' : 'guard_name';
        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';
        $permGuardCol = Schema::hasColumn('md_permissions', 'perm_guard_name') ? 'perm_guard_name' : 'guard_name';

        foreach ($securityPermissions as $name) {
            $exists = DB::table('md_permissions')->where($permNameCol, $name)->where($permGuardCol, 'web')->exists();
            if (! $exists) {
                DB::table('md_permissions')->insert([
                    $permNameCol => $name,
                    $permGuardCol => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $securityRole = DB::table('md_roles')->where($roleNameCol, 'Security')->where($roleGuardCol, 'web')->first();
        if (! $securityRole) {
            $securityRoleID = DB::table('md_roles')->insertGetId([
                $roleNameCol => 'Security',
                $roleGuardCol => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $securityRoleID = $securityRole->id;
        }

        $secPermIds = DB::table('md_permissions')->whereIn($permNameCol, $securityPermissions)->pluck('id')->toArray();
        foreach ($secPermIds as $pId) {
            $exists = DB::table('role_has_permissions')->where('role_id', $securityRoleID)->where('permission_id', $pId)->exists();
            if (! $exists) {
                DB::table('role_has_permissions')->insert(['role_id' => $securityRoleID, 'permission_id' => $pId]);
            }
        }

        $superRole = DB::table('md_roles')->where($roleNameCol, 'Super Account')->where($roleGuardCol, 'web')->first();
        if (! $superRole) {
            $superRoleID = DB::table('md_roles')->insertGetId([
                $roleNameCol => 'Super Account',
                $roleGuardCol => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $superRoleID = $superRole->id;
        }

        $allPermIds = DB::table('md_permissions')->pluck('id')->toArray();
        foreach ($allPermIds as $pId) {
            $exists = DB::table('role_has_permissions')->where('role_id', $superRoleID)->where('permission_id', $pId)->exists();
            if (! $exists) {
                DB::table('role_has_permissions')->insert(['role_id' => $superRoleID, 'permission_id' => $pId]);
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

        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        $securityRole = DB::table('md_roles')->where($roleNameCol, 'Security')->first();
        if ($securityRole) {
            DB::table('role_has_permissions')->where('role_id', $securityRole->id)->delete();
            DB::table('md_roles')->where('id', $securityRole->id)->delete();
        }

        $superRole = DB::table('md_roles')->where($roleNameCol, 'Super Account')->first();
        if ($superRole) {
            DB::table('role_has_permissions')->where('role_id', $superRole->id)->delete();
            DB::table('md_roles')->where('id', $superRole->id)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
