<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

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
            'slots.index',
            'slots.show',
            'slots.arrival',
            'slots.arrival.store',
            'slots.search_suggestions',
            'slots.ajax.po_search',
            'slots.ajax.po_detail',
            'slots.ajax.check_risk',
            'slots.ajax.check_slot_time',
            'slots.ajax.recommend_gate',
            'slots.ajax.schedule_preview',

            // Gates
            'gates.index',

            // Profile
            'profile.index',
        ];

        foreach ($securityPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $securityRole = Role::findOrCreate('Security', 'web');
        $securityRole->syncPermissions($securityPermissions);

        $superRole = Role::findOrCreate('Super Account', 'web');
        $allPermissionNames = Permission::query()->pluck('perm_name')->toArray();
        $superRole->syncPermissions($allPermissionNames);

        Cache::forget('users:roles:all');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $securityRole = Role::where('roles_name', 'Security')->first();
        if ($securityRole) {
            $securityRole->syncPermissions([]);
            $securityRole->delete();
        }

        $superRole = Role::where('roles_name', 'Super Account')->first();
        if ($superRole) {
            $superRole->syncPermissions([]);
            $superRole->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
