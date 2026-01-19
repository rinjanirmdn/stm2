<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');

        $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';
        $permGuardCol = Schema::hasColumn($permissionsTable, 'perm_guard_name') ? 'perm_guard_name' : 'guard_name';
        $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';
        $roleGuardCol = Schema::hasColumn($rolesTable, 'roles_guard_name') ? 'roles_guard_name' : 'guard_name';

        // Get existing permissions to avoid duplicates
        $existingPermissions = DB::table($permissionsTable)->pluck($permNameCol)->toArray();

        // Define all permissions for the system
        $allPermissions = [
            // Dashboard & Overview
            'dashboard.view',
            'dashboard.range_filter',

            // Slot Management - Planned Slots
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

            // Slot Management - Unplanned Slots
            'unplanned.index',
            'unplanned.create',
            'unplanned.store',
            'unplanned.edit',
            'unplanned.update',

            // Reports & Analytics
            'reports.transactions',
            'reports.search_suggestions',
            'reports.export',
            'reports.gate_status',
            'reports.gates.toggle',
            'reports.gates_index',

            // User Management
            'users.index',
            'users.create',
            'users.store',
            'users.edit',
            'users.update',
            'users.delete',
            'users.toggle',

            // Vendor Management
            'vendors.index',
            'vendors.create',
            'vendors.store',
            'vendors.edit',
            'vendors.update',
            'vendors.delete',
            'vendors.import',
            'vendors.import.store',

            // Truck Type Management
            'trucks.index',
            'trucks.create',
            'trucks.store',
            'trucks.edit',
            'trucks.update',
            'trucks.delete',

            // Gate Management
            'gates.index',
            'gates.stream',
            'gates.api_index',

            // System Logs
            'logs.index',
            'logs.filter',

            // SAP Integration
            'sap.search_po',
            'sap.get_po_details',
            'sap.sync_slot',
            'sap.health',

            // Profile Management
            'profile.index',

            // Check-in System
            'checkin.show',
            'checkin.store',

            // Authentication
            'login.index',
            'login.store',
            'logout',
        ];

        // Insert only permissions that don't exist
        $permissionsToInsert = array_diff($allPermissions, $existingPermissions);

        if (!empty($permissionsToInsert)) {
            $insertData = [];
            foreach ($permissionsToInsert as $permission) {
                $insertData[] = [
                    $permNameCol => $permission,
                    $permGuardCol => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table($permissionsTable)->insert($insertData);
        }

        // Create default roles if they don't exist
        $existingRoles = DB::table($rolesTable)->pluck($roleNameCol)->toArray();

        $defaultRoles = [
            'Super Admin',
            'Admin',
            'Operator',
            'Viewer',
            'Vendor'
        ];

        $rolesToInsert = array_diff($defaultRoles, $existingRoles);

        if (!empty($rolesToInsert)) {
            // Insert roles one by one to avoid duplicate key issues
            foreach ($rolesToInsert as $role) {
                DB::table($rolesTable)->insert([
                    $roleNameCol => $role,
                    $roleGuardCol => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign permissions to roles (only if role exists and doesn't have the permission)
        $this->assignPermissionsToRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');
        $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';
        $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';

        // Remove only the permissions we added in this migration
        $allPermissions = [
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

        DB::table($permissionsTable)->whereIn($permNameCol, $allPermissions)->delete();

        // Remove default roles
        $defaultRoles = ['Super Admin', 'Admin', 'Operator', 'Viewer', 'Vendor'];
        DB::table($rolesTable)->whereIn($roleNameCol, $defaultRoles)->delete();
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $permissionsTable = (string) (config('permission.table_names.permissions') ?? 'permissions');

        $permNameCol = Schema::hasColumn($permissionsTable, 'perm_name') ? 'perm_name' : 'name';
        $roleNameCol = Schema::hasColumn($rolesTable, 'roles_name') ? 'roles_name' : 'name';

        // Get role IDs
        $superAdminRole = DB::table($rolesTable)->where($roleNameCol, 'Super Admin')->first();
        $adminRole = DB::table($rolesTable)->where($roleNameCol, 'Admin')->first();
        $operatorRole = DB::table($rolesTable)->where($roleNameCol, 'Operator')->first();
        $viewerRole = DB::table($rolesTable)->where($roleNameCol, 'Viewer')->first();
        $vendorRole = DB::table($rolesTable)->where($roleNameCol, 'Vendor')->first();

        // Get all permission IDs
        $allPermissionIds = DB::table($permissionsTable)->pluck('id')->toArray();

        // Super Admin gets all permissions
        if ($superAdminRole) {
            $this->assignPermissionsToRole($superAdminRole->id, $allPermissionIds);
        }

        // Admin gets most permissions (except SAP health)
        if ($adminRole) {
            $adminPermissions = DB::table($permissionsTable)
                ->where($permNameCol, '!=', 'sap.health')
                ->pluck('id')
                ->toArray();
            $this->assignPermissionsToRole($adminRole->id, $adminPermissions);
        }

        // Operator gets slot operations only
        if ($operatorRole) {
            $operatorPermissions = DB::table($permissionsTable)
                ->whereIn($permNameCol, [
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
                    'slots.cancel',
                    'slots.cancel.store',
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
                ])
                ->pluck('id')
                ->toArray();
            $this->assignPermissionsToRole($operatorRole->id, $operatorPermissions);
        }

        // Viewer gets read-only permissions
        if ($viewerRole) {
            $viewerPermissions = DB::table($permissionsTable)
                ->whereIn($permNameCol, [
                    'dashboard.view',
                    'dashboard.range_filter',
                    'slots.index',
                    'slots.show',
                    'unplanned.index',
                    'reports.transactions',
                    'reports.search_suggestions',
                    'reports.gate_status',
                    'gates.index',
                    'gates.api_index',
                    'profile.index',
                ])
                ->pluck('id')
                ->toArray();
            $this->assignPermissionsToRole($viewerRole->id, $viewerPermissions);
        }

        // Vendor gets limited vendor-related permissions
        if ($vendorRole) {
            $vendorPermissions = DB::table($permissionsTable)
                ->whereIn($permNameCol, [
                    'dashboard.view',
                    'slots.index',
                    'slots.show',
                    'unplanned.index',
                    'vendors.index',
                    'profile.index',
                ])
                ->pluck('id')
                ->toArray();
            $this->assignPermissionsToRole($vendorRole->id, $vendorPermissions);
        }
    }

    /**
     * Assign permissions to a specific role
     */
    private function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        // Get existing permissions for this role
        $existingPermissions = DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();

        // Only assign permissions that don't exist
        $permissionsToAssign = array_diff($permissionIds, $existingPermissions);

        if (!empty($permissionsToAssign)) {
            $insertData = [];
            foreach ($permissionsToAssign as $permissionId) {
                $insertData[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ];
            }

            DB::table('role_has_permissions')->insert($insertData);
        }
    }
};
