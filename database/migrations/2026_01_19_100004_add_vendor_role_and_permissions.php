<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add vendor role and related permissions for booking approval workflow.
     */
    public function up(): void
    {
        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create vendor permissions
        $vendorPermissions = [
            'bookings.index',
            'bookings.create',
            'bookings.view',
            'bookings.cancel',
            'bookings.confirm',
            'slots.availability',
        ];

        // Create admin booking management permissions
        $adminBookingPermissions = [
            'bookings.manage',
            'bookings.approve',
            'bookings.reject',
            'bookings.reschedule',
        ];

        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $roleGuardCol = Schema::hasColumn('md_roles', 'roles_guard_name') ? 'roles_guard_name' : 'guard_name';
        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';
        $permGuardCol = Schema::hasColumn('md_permissions', 'perm_guard_name') ? 'perm_guard_name' : 'guard_name';

        // Create all permissions using DB builder
        foreach (array_merge($vendorPermissions, $adminBookingPermissions) as $name) {
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

        // Create vendor role using DB builder
        $vendorRole = DB::table('md_roles')->where($roleNameCol, 'vendor')->where($roleGuardCol, 'web')->first();
        if (! $vendorRole) {
            $vendorId = DB::table('md_roles')->insertGetId([
                $roleNameCol => 'vendor',
                $roleGuardCol => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $vendorRole = DB::table('md_roles')->where('id', $vendorId)->first();
        }

        // Assign vendor permissions to vendor role
        $vendorPermIds = DB::table('md_permissions')->whereIn($permNameCol, $vendorPermissions)->pluck('id')->toArray();
        foreach ($vendorPermIds as $pId) {
            $exists = DB::table('role_has_permissions')->where('role_id', $vendorRole->id)->where('permission_id', $pId)->exists();
            if (! $exists) {
                DB::table('role_has_permissions')->insert(['role_id' => $vendorRole->id, 'permission_id' => $pId]);
            }
        }

        // Give admin role all booking permissions
        $adminBookingPermIds = DB::table('md_permissions')->whereIn($permNameCol, $adminBookingPermissions)->pluck('id')->toArray();
        $adminRole = DB::table('md_roles')->where($roleNameCol, 'admin')->first();

        if ($adminRole) {
            foreach ($adminBookingPermIds as $pId) {
                $exists = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $pId)->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert(['role_id' => $adminRole->id, 'permission_id' => $pId]);
                }
            }
        }

        // Give section_head role booking management permissions
        $sectionHeadRole = DB::table('md_roles')->where($roleNameCol, 'section_head')->first();
        if ($sectionHeadRole) {
            foreach ($adminBookingPermIds as $pId) {
                $exists = DB::table('role_has_permissions')->where('role_id', $sectionHeadRole->id)->where('permission_id', $pId)->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert(['role_id' => $sectionHeadRole->id, 'permission_id' => $pId]);
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';
        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';

        // Remove vendor role
        $vendorRole = DB::table('md_roles')->where($roleNameCol, 'vendor')->first();
        if ($vendorRole) {
            DB::table('role_has_permissions')->where('role_id', $vendorRole->id)->delete();
            DB::table('md_roles')->where('id', $vendorRole->id)->delete();
        }

        // Remove permissions
        $permissions = [
            'bookings.index',
            'bookings.create',
            'bookings.view',
            'bookings.cancel',
            'bookings.confirm',
            'slots.availability',
            'bookings.manage',
            'bookings.approve',
            'bookings.reject',
            'bookings.reschedule',
        ];

        $permIds = DB::table('md_permissions')->whereIn($permNameCol, $permissions)->pluck('id')->toArray();
        if (! empty($permIds)) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table('md_permissions')->whereIn('id', $permIds)->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
