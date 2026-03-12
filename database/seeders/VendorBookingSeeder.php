<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                'full_name' => 'Vendor User',
                'email' => 'vendor@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'vendor_id' => $vendor->id,
            ]
        );

        // Assign vendor role
        $vendorUser->assignRole('Vendor');

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
        $adminUser->assignRole('Admin');
        $adminUser->givePermissionTo('bookings.reschedule');

        $this->command->info('Vendor User created: vendor_user / password');
        $this->command->info('Admin User created: booking_admin / password');
    }

    private function ensureRolesAndPermissions() {}
}
