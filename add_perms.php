<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

$perms = [
    'master.transporters.index',
    'master.bp.index',
];

$roleNames = ['Admin', 'Section Head', 'Super Account'];

$roles = DB::table('md_roles')
    ->whereIn('roles_name', $roleNames)
    ->get();

foreach ($perms as $permName) {
    $perm = DB::table('md_permissions')->where('perm_name', $permName)->first();
    if (! $perm) {
        $permId = DB::table('md_permissions')->insertGetId([
            'perm_name' => $permName,
            'perm_guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $permId = $perm->id;
    }

    foreach ($roles as $role) {
        $exists = DB::table('role_has_permissions')
            ->where('permission_id', $permId)
            ->where('role_id', $role->id)
            ->exists();
        if (! $exists) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $role->id,
            ]);
        }
    }
}

app(PermissionRegistrar::class)->forgetCachedPermissions();

echo "Permissions added successfully!\n";
