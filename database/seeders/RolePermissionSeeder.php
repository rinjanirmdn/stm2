<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

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

            'logs.index',
            'logs.filter',

            'sap.search_po',
            'sap.get_po_details',
            'sap.sync_slot',
            'sap.health',

            'profile.index',

            'checkin.show',
            'checkin.store',

            'login.index',
            'login.store',
            'logout',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create admin role with all permissions
        $adminRole = Role::findOrCreate('Admin');
        $adminRole->syncPermissions(Permission::all());

        // Super Admin from master roles also gets full access
        $superAdminRole = Role::findOrCreate('Super Admin');
        $superAdminRole->syncPermissions(Permission::all());

        // Create vendor role based on master role name
        $vendorRole = Role::findOrCreate('Vendor');
        $vendorRole->syncPermissions([
            'dashboard.view',
            'profile.index',
        ]);

        // Access-level mapping with existing master role: Viewer = view-only
        $viewerRole = Role::findOrCreate('Viewer');

        $viewPermissions = array_values(array_filter($permissions, function ($perm) {
            if (in_array($perm, ['profile.index', 'dashboard.range_filter'], true)) {
                return true;
            }
            return preg_match('/(\.view|\.index|\.show|\.search_suggestions|\.api_index|\.stream)$/', $perm) === 1;
        }));

        $createPermissions = array_values(array_filter($permissions, function ($perm) {
            return preg_match('/(\.create|\.store)$/', $perm) === 1;
        }));

        $editPermissions = array_values(array_filter($permissions, function ($perm) {
            return preg_match('/(\.edit|\.update)$/', $perm) === 1;
        }));

        $viewerRole->syncPermissions($viewPermissions);

        // Create section_head role with permissions as needed
        $sectionHeadRole = Role::findOrCreate('Section Head');
        $sectionHeadPermissions = array_values(array_filter($permissions, function ($perm) {
            if (str_starts_with($perm, 'users.')) {
                return false;
            }
            if (str_starts_with($perm, 'logs.')) {
                return false;
            }
            return true;
        }));
        $sectionHeadRole->syncPermissions($sectionHeadPermissions);

        // Optional mappings for existing master roles
        // Security: read-only visibility + logs filtering
        $securityRole = Role::findOrCreate('Security');
        $securityRole->syncPermissions(array_values(array_unique(array_merge($viewPermissions, [
            'logs.index',
            'logs.filter',
            'profile.index',
        ]))));

        // Super Account: same as Admin by default (can be narrowed later)
        $superAccountRole = Role::findOrCreate('Super Account');
        $superAccountRole->syncPermissions(Permission::all());

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
            'reports.transactions',
            'reports.search_suggestions',
            'gates.index',
            'profile.index',
            'checkin.show',
            'checkin.store',
        ]);

        // Assign admin role to user with username admin or first user
        $adminUser = \App\Models\User::where('nik', 'admin')->first();
        if (!$adminUser) {
            $adminUser = \App\Models\User::first();
        }

        if ($adminUser) {
            $adminUser->assignRole('Admin');
        }

        $this->command->info('Roles and permissions created successfully!');
    }
}
