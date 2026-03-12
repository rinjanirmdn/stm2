<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        // Clean up roles - keep only Admin, Operator, Section Head
        $rolesToKeep = ['Admin', 'Operator', 'Section Head'];

        // Get roles to delete
        $rolesToDelete = DB::table('md_roles')
            ->whereNotIn($roleNameCol, $rolesToKeep)
            ->pluck('id')
            ->toArray();

        if (! empty($rolesToDelete)) {
            // Remove role assignments first
            DB::table('role_has_permissions')
                ->whereIn('role_id', $rolesToDelete)
                ->delete();

            // Remove user role assignments
            DB::table('model_has_roles')
                ->whereIn('role_id', $rolesToDelete)
                ->delete();

            // Delete the roles
            DB::table('md_roles')
                ->whereIn('id', $rolesToDelete)
                ->delete();
        }

        // Update existing role names if needed
        DB::table('md_roles')
            ->where($roleNameCol, 'Super Admin')
            ->update([$roleNameCol => 'Admin']);

        DB::table('md_roles')
            ->where($roleNameCol, 'section_head')
            ->update([$roleNameCol => 'Section Head']);

        $rolesTable = config('permission.table_names.roles', 'roles');

        // Add role_id foreign key to users table
        Schema::table('users', function (Blueprint $table) use ($rolesTable) {
            // Add role_id column if it doesn't exist
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('role');
            }

            // Add foreign key constraint
            $table->foreign('role_id')
                ->references('id')
                ->on($rolesTable)
                ->onDelete('set null');
        });

        // Update existing users to use role_id based on their current role string
        $roleMapping = [
            'admin' => DB::table('md_roles')->where($roleNameCol, 'Admin')->value('id'),
            'operator' => DB::table('md_roles')->where($roleNameCol, 'Operator')->value('id'),
            'Section Head' => DB::table('md_roles')->where($roleNameCol, 'Section Head')->value('id'),
        ];

        $usersTable = \Illuminate\Support\Facades\Schema::hasTable('md_users') ? 'md_users' : 'users';

        foreach ($roleMapping as $roleName => $roleId) {
            if ($roleId) {
                DB::table($usersTable)
                    ->where('role', $roleName)
                    ->update(['role_id' => $roleId]);
            }
        }

        // Re-assign permissions to the 3 roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraint
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        // Remove role_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });
    }

    /**
     * Assign permissions to the 3 roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Clear existing role permissions
        DB::table('role_has_permissions')->delete();

        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        // Get role IDs
        $adminRole = DB::table('md_roles')->where($roleNameCol, 'Admin')->first();
        $operatorRole = DB::table('md_roles')->where($roleNameCol, 'Operator')->first();
        $sectionHeadRole = DB::table('md_roles')->where($roleNameCol, 'Section Head')->first();

        // Get all permission IDs
        $allPermissionIds = DB::table('md_permissions')->pluck('id')->toArray();

        $permNameCol = Schema::hasColumn('md_permissions', 'perm_name') ? 'perm_name' : 'name';

        // Admin gets all permissions
        if ($adminRole) {
            $this->assignPermissionsToRole($adminRole->id, $allPermissionIds);
        }

        // Section Head gets most permissions (except user management and some system functions)
        if ($sectionHeadRole) {
            $sectionHeadPermissions = DB::table('md_permissions')
                ->whereNotIn($permNameCol, [
                    'users.create',
                    'users.store',
                    'users.delete',
                    'users.toggle',
                    'sap.health',
                ])
                ->pluck('id')
                ->toArray();
            $this->assignPermissionsToRole($sectionHeadRole->id, $sectionHeadPermissions);
        }

        // Operator gets slot operations only
        if ($operatorRole) {
            $operatorPermissions = DB::table('md_permissions')
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
                    'unplanned.create',
                    'unplanned.store',
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
    }

    /**
     * Assign permissions to a specific role
     */
    private function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        $insertData = [];
        foreach ($permissionIds as $permissionId) {
            $insertData[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ];
        }

        DB::table('role_has_permissions')->insert($insertData);
    }
};
