<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for STM
        $permissions = [
            'dashboard.view',
            'dashboard.range_filter',

            'bookings.index',
            'bookings.show',
            'bookings.approve',
            'bookings.reject',
            'bookings.reschedule',
            'bookings.ajax.calendar',
            'bookings.ajax.pending_count',
            'bookings.ajax.reminders',
            'bookings.ajax.check_gate',

            'slots.index',
            'slots.create',
            'slots.store',
            'slots.show',
            'slots.edit',
            'slots.update',
            'slots.delete',
            'slots.arrival',
            'slots.arrival.store',
            'slots.start',
            'slots.start.store',
            'slots.complete',
            'slots.complete.store',
            'slots.cancel',
            'slots.cancel.store',
            'slots.ticket',
            'slots.search_suggestions',
            'slots.ajax.po_search',
            'slots.ajax.po_detail',
            'slots.ajax.check_risk',
            'slots.ajax.check_slot_time',
            'slots.ajax.recommend_gate',
            'slots.ajax.schedule_preview',

            'unplanned.index',
            'unplanned.create',
            'unplanned.store',
            'unplanned.edit',
            'unplanned.update',
            'unplanned.delete',
            'unplanned.show',
            'unplanned.start',
            'unplanned.start.store',
            'unplanned.complete',
            'unplanned.complete.store',

            'reports.transactions',
            'reports.search_suggestions',
            'reports.export',
            'reports.gate_status',
            'reports.gates.toggle',
            'reports.gates_index',

            'users.index',
            'users.create',
            'users.store',
            'users.edit',
            'users.update',
            'users.delete',
            'users.toggle',

            'vendors.index',
            'vendors.create',
            'vendors.store',
            'vendors.edit',
            'vendors.update',
            'vendors.delete',
            'vendors.import',
            'vendors.import.store',

            'trucks.index',
            'trucks.create',
            'trucks.store',
            'trucks.edit',
            'trucks.update',
            'trucks.delete',

            'gates.index',
            'gates.stream',
            'gates.api_index',
            'gates.toggle',
            'gates.availability',
            'gates.ajax.available_slots',
            'gates.ajax.disabled_times',

            'logs.index',
            'logs.filter',

            'sap.search_po',
            'sap.get_po_details',
            'sap.sync_slot',
            'sap.health',

            'profile.index',
            'profile.change_password',

            'vendor.dashboard',
            'vendor.bookings.index',
            'vendor.bookings.create',
            'vendor.bookings.store',
            'vendor.bookings.show',
            'vendor.bookings.ticket',
            'vendor.bookings.cancel',
            'vendor.availability',
            'vendor.ajax.available_slots',
            'vendor.ajax.check_availability',
            'vendor.ajax.truck_type_duration',
            'vendor.ajax.calendar_slots',
            'vendor.ajax.po_search',
            'vendor.ajax.po_detail',

            'notifications.index',
            'notifications.markAsRead',
            'notifications.readAll',
            'notifications.clearAll',
            'notifications.latest',

            'checkin.show',
            'checkin.store',

            'login.index',
            'login.store',
            'logout',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Cleanup obsolete permissions: remove anything not declared above
        try {
            $toDelete = Permission::query()->whereNotIn('name', $permissions)->get();
            foreach ($toDelete as $perm) {
                try {
                    $perm->roles()->detach();
                } catch (\Throwable $e) {
                    // no-op
                }
                $perm->delete();
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // Create admin role with all permissions
        $adminRole = Role::findOrCreate('Admin');
        $adminRole->syncPermissions(Permission::all());

        // Create vendor role based on master role name
        $vendorRole = Role::findOrCreate('Vendor');
        $vendorRole->syncPermissions([
            'profile.index',
            'vendor.dashboard',
            'vendor.bookings.index',
            'vendor.bookings.create',
            'vendor.bookings.store',
            'vendor.bookings.show',
            'vendor.bookings.ticket',
            'vendor.bookings.cancel',
            'vendor.availability',
            'vendor.ajax.available_slots',
            'vendor.ajax.check_availability',
            'vendor.ajax.truck_type_duration',
            'vendor.ajax.calendar_slots',
            'vendor.ajax.po_search',
            'vendor.ajax.po_detail',
            'notifications.index',
            'notifications.markAsRead',
            'notifications.readAll',
            'notifications.clearAll',
            'notifications.latest',
        ]);

        // Display Account: dashboard menu only + profile
        $displayAccountRole = Role::findOrCreate('Display Account');
        $displayAccountRole->syncPermissions([
            'dashboard.view',
            'profile.index',
        ]);

        // Create section_head role with permissions as needed
        $sectionHeadRole = Role::findOrCreate('Section Head');
        $sectionHeadPermissions = array_values(array_filter($permissions, function ($perm) {
            if (str_starts_with($perm, 'users.')) {
                return false;
            }

            return true;
        }));
        $sectionHeadRole->syncPermissions($sectionHeadPermissions);

        // Optional mappings for existing master roles
        // Security (satpam): only ticket scanning flow at truck arrival
        $securityRole = Role::findOrCreate('Security');
        $securityRole->syncPermissions([
            'profile.index',
            'slots.index',
            'slots.show',
            'slots.arrival',
            'slots.arrival.store',
            'gates.index',
            'gates.api_index',
            'gates.stream',
            'gates.ajax.available_slots',
            'gates.ajax.disabled_times',
        ]);

        // Create operator role with limited permissions (only arrival, start, complete)
        $operatorRole = Role::findOrCreate('Operator');
        $operatorRole->syncPermissions([
            'dashboard.view',
            'dashboard.range_filter',
            'slots.index',
            'slots.show',
            'slots.arrival',
            'slots.arrival.store',
            'slots.start',
            'slots.start.store',
            'slots.complete',
            'slots.complete.store',
            'slots.search_suggestions',
            'slots.ajax.po_search',
            'slots.ajax.po_detail',
            'slots.ajax.check_risk',
            'slots.ajax.check_slot_time',
            'slots.ajax.recommend_gate',
            'slots.ajax.schedule_preview',
            'unplanned.index',
            'unplanned.show',
            'unplanned.start',
            'unplanned.start.store',
            'unplanned.complete',
            'unplanned.complete.store',
            'reports.transactions',
            'reports.search_suggestions',
            'trucks.index',
            'gates.index',
            'logs.index',
            'logs.filter',
            'profile.index',
            'checkin.show',
            'checkin.store',
        ]);

        // Super Account: all permissions except users.*
        $superAccountRole = Role::findOrCreate('Super Account');
        $superAccountPermissions = array_values(array_filter($permissions, function ($perm) {
            return ! str_starts_with($perm, 'users.');
        }));
        $superAccountRole->syncPermissions($superAccountPermissions);

        // Enforce role whitelist: keep only required roles
        $rolesToKeep = [
            'Admin',
            'Section Head',
            'Operator',
            'Security',
            'Vendor',
            'Display Account',
            'Super Account',
        ];

        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
        $roleHasPermissionsTable = (string) (config('permission.table_names.role_has_permissions') ?? 'role_has_permissions');

        $keepRoleIds = Role::query()->whereIn('roles_name', $rolesToKeep)->pluck('id')->all();
        $rolesToDelete = Role::query()->whereNotIn('roles_name', $rolesToKeep)->pluck('id')->all();

        // Migrate known legacy roles
        $adminRoleId = Role::query()->where('roles_name', 'Admin')->value('id');
        $displayRoleId = Role::query()->where('roles_name', 'Display Account')->value('id');

        $superAdminRoleId = Role::query()->where('roles_name', 'Super Admin')->value('id');
        if ($superAdminRoleId && $adminRoleId) {
            DB::table($modelHasRolesTable)->where('role_id', $superAdminRoleId)->update(['role_id' => $adminRoleId]);
        }

        $viewerRoleId = Role::query()->where('roles_name', 'Viewer')->value('id');
        if ($viewerRoleId && $displayRoleId) {
            DB::table($modelHasRolesTable)->where('role_id', $viewerRoleId)->update(['role_id' => $displayRoleId]);
        }

        // For any remaining deleted roles, fallback migrate to Display Account (minimal access)
        if (! empty($rolesToDelete) && $displayRoleId) {
            DB::table($modelHasRolesTable)->whereIn('role_id', $rolesToDelete)->update(['role_id' => $displayRoleId]);
        }

        if (! empty($rolesToDelete)) {
            DB::table($roleHasPermissionsTable)->whereIn('role_id', $rolesToDelete)->delete();
            Role::query()->whereIn('id', $rolesToDelete)->delete();
        }

        // Assign admin role to user with username admin or first user
        $adminUser = \App\Models\User::where('nik', 'admin')->first();
        if (! $adminUser) {
            $adminUser = \App\Models\User::first();
        }

        if ($adminUser) {
            $adminUser->assignRole('Admin');
        }

        $this->command->info('Roles and permissions created successfully!');
    }
}
