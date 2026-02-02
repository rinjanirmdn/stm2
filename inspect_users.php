<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "Columns in md_users:\n";
    $columns = Schema::getColumnListing('md_users');
    print_r($columns);
    
    echo "\nSample user:\n";
    $user = DB::table('md_users')->first();
    print_r($user);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
