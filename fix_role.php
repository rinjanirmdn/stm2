<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

try {
    $user = User::where('username', 'vendor_user')->first();
    if (!$user) {
        die("User vendor_user not found\n");
    }

    // Pastikan role vendor ada
    if (!Role::where('roles_name', 'vendor')->exists()) {
        echo "Role 'vendor' tidak ditemukan di DB. Creating...\n";
        // Gunakan model custom Role
        \App\Models\Role::create(['roles_name' => 'vendor', 'roles_guard_name' => 'web']);
    }

    $user->syncRoles(['vendor']);
    echo "SUCCESS: User '{$user->username}' sekarang punya role: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    
    // Fix juga untuk admin biar aman
    $admin = User::where('username', 'booking_admin')->first();
    if ($admin) {
        // Admin mungkin butuh permission management bookings
        // Asumsi role 'admin' atau permission langsung
        // Kita beri permission spesifik saja
        $admin->givePermissionTo(['bookings.manage', 'bookings.approve', 'bookings.reject', 'bookings.reschedule']);
        echo "SUCCESS: User '{$admin->username}' permissions updated.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
