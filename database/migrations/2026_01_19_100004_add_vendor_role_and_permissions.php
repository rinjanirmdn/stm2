<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
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
        
        // Create all permissions using custom model
        foreach (array_merge($vendorPermissions, $adminBookingPermissions) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        
        // Create vendor role using custom model
        $vendorRole = Role::findOrCreate('vendor', 'web');
        
        // Assign vendor permissions to vendor role
        $vendorRole->syncPermissions($vendorPermissions);
        
        // Give admin role all booking permissions
        $adminRole = Role::where('roles_name', 'admin')->first();
        if ($adminRole) {
            $currentPermissions = $adminRole->permissions->pluck('perm_name')->toArray();
            $allPermissions = array_unique(array_merge($currentPermissions, $adminBookingPermissions));
            $adminRole->syncPermissions($allPermissions);
        }
        
        // Give section_head role booking management permissions
        $sectionHeadRole = Role::where('roles_name', 'section_head')->first();
        if ($sectionHeadRole) {
            $currentPermissions = $sectionHeadRole->permissions->pluck('perm_name')->toArray();
            $allPermissions = array_unique(array_merge($currentPermissions, $adminBookingPermissions));
            $sectionHeadRole->syncPermissions($allPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Remove vendor role
        Role::where('roles_name', 'vendor')->delete();
        
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
        
        Permission::whereIn('perm_name', $permissions)->delete();
    }
};
