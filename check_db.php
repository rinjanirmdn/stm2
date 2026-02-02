<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/bootstrap/app.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking users table...\n";
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
    if (empty($columns)) {
        echo "Table 'users' found in information_schema but no columns? Or table not found.\n";
    } else {
        echo "Columns in users table:\n";
        foreach ($columns as $col) {
            echo "- " . $col->column_name . "\n";
        }
    }

    echo "\nChecking vendors table...\n";
    $v_columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'vendors'");
    if (empty($v_columns)) {
        echo "Table 'vendors' NOT found or empty columns.\n";
    } else {
        echo "Columns in vendors table:\n";
        foreach ($v_columns as $col) {
            echo "- " . $col->column_name . "\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
