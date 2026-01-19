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

        // Buat permissions untuk STM
        $permissions = [
            'dashboard.view',
            'dashboard.range_filter',

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

        // Buat role admin dengan semua permission
        $adminRole = Role::findOrCreate('Admin');
        $adminRole->givePermissionTo(Permission::all());

        // Buat role section_head dengan permission sesuai kebutuhan
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
        $sectionHeadRole->givePermissionTo($sectionHeadPermissions);

        // Buat role operator dengan permission terbatas (hanya arrival, start, complete)
        $operatorRole = Role::findOrCreate('Operator');
        $operatorRole->givePermissionTo([
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

        // Assign role admin ke user dengan username admin atau user pertama
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
