<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nik' => 'admin',
                'full_name' => 'Super Administrator',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'password')),
                'role' => 'Admin',
                'role_id' => 1,
                'is_active' => true,
            ],
            [
                'nik' => 'operator',
                'full_name' => 'Operator',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'password')),
                'role' => 'Operator',
                'role_id' => 2,
                'is_active' => true,
            ],
            [
                'nik' => 'SectionHead',
                'full_name' => 'Section Head',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'password')),
                'role' => 'Section Head',
                'role_id' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['nik' => $userData['nik']],
                $userData
            );

            if ($user && !empty($userData['role']) && method_exists($user, 'assignRole')) {
                try {
                    if (! $user->hasRole($userData['role'])) {
                        $user->assignRole($userData['role']);
                    }
                } catch (\Throwable $e) {
                    // ignore if role not seeded yet
                }
            }
        }

        $this->command->info('✓ 3 users created successfully');
        $this->command->info('  - admin (Admin role)');
        $this->command->info('  - operator (Operator role)');
        $this->command->info('  - SectionHead (Section Head role)');
        $this->command->info('  Password: ' . env('DEFAULT_USER_PASSWORD', 'password'));
    }
}
