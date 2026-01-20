<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class VendorBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles & Permissions exist
        $this->ensureRolesAndPermissions();

        // 2. Create a Test Vendor using correct columns
        $vendor = Vendor::firstOrCreate(
            ['bp_code' => 'VEND001'],
            [
                'bp_name' => 'Test Transporter PT',
                'bp_type' => 'supplier',
            ]
        );

        // 3. Create a Vendor User
        $vendorUser = User::firstOrCreate(
            ['username' => 'vendor_user'],
            [
                'nik' => 'VEND12345',
                'full_name' => 'Budi Vendor',
                'email' => 'vendor@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'vendor_id' => $vendor->id,
            ]
        );
        
        // Assign vendor role
        if (!$vendorUser->hasRole('vendor')) {
            $vendorUser->assignRole('vendor');
        }

        // 4. Create an Admin User for Approval
        $adminUser = User::firstOrCreate(
            ['username' => 'booking_admin'],
            [
                'nik' => 'ADM12345',
                'full_name' => 'Admin Booking',
                'email' => 'admin.booking@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign admin role
        if (!$adminUser->hasRole('admin')) {
             $adminUser->givePermissionTo('bookings.manage');
             $adminUser->givePermissionTo('bookings.approve');
             $adminUser->givePermissionTo('bookings.reject');
             $adminUser->givePermissionTo('bookings.reschedule');
        }

        $this->command->info("Vendor User created: vendor_user / password");
        $this->command->info("Admin User created: booking_admin / password");
    }

    private function ensureRolesAndPermissions()
    {
        $permissions = [
            'bookings.index', 'bookings.create', 'bookings.view', 
            'bookings.cancel', 'bookings.confirm', 'slots.availability',
            'bookings.manage', 'bookings.approve', 'bookings.reject', 'bookings.reschedule'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate( // using custom model
                ['perm_name' => $p, 'perm_guard_name' => 'web'],
                ['perm_name' => $p, 'perm_guard_name' => 'web']
            );
        }

        $vendorRole = Role::firstOrCreate( // using custom model
            ['roles_name' => 'vendor', 'roles_guard_name' => 'web'],
            ['roles_name' => 'vendor', 'roles_guard_name' => 'web']
        );
        
        $vendorRole->syncPermissions([
            'bookings.index', 'bookings.create', 'bookings.view', 
            'bookings.cancel', 'bookings.confirm', 'slots.availability'
        ]);
        
        $adminRole = Role::where('roles_name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'bookings.manage', 'bookings.approve', 'bookings.reject', 'bookings.reschedule'
            ]);
        }
    }
}
