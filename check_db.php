<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Database: " . DB::connection()->getDatabaseName() . "\n";
echo "Driver: " . DB::connection()->getDriverName() . "\n";
echo "Configured Schema: " . config('database.connections.pgsql.schema', 'public') . "\n";

// Get schema from env or config
$schema = config('database.connections.pgsql.schema', 'slot_time_management');

echo "Listing tables in schema '$schema':\n";
try {
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$schema]);
    if (empty($tables)) {
         echo "No tables found in schema '$schema'. Trying 'public'...\n";
         $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
         $schema = 'public'; // switch to public if found there
    }
    
    foreach ($tables as $t) {
        echo "- " . $t->table_name . "\n";
    }
} catch (\Exception $e) {
    echo "Error listing tables: " . $e->getMessage() . "\n";
}

$targetTables = array_slice($argv, 1);
if (empty($targetTables)) {
    $targetTables = ['users', 'gates', 'warehouses'];
}

foreach ($targetTables as $table) {
    echo "\nChecking table: $table\n";
    try {
        // Try simple check first
        if (Schema::hasTable($table)) {
            $columns = Schema::getColumnListing($table);
            echo "Columns (via Schema):\n";
            foreach ($columns as $col) {
                echo "- $col\n";
            }
        } else {
             // Try explicit schema prefix
             $prefixed = $schema . '.' . $table;
             if (Schema::hasTable($prefixed)) {
                $columns = Schema::getColumnListing($prefixed);
                echo "Columns (via Schema with prefix $prefixed):\n";
                foreach ($columns as $col) {
                    echo "- $col\n";
                }
             } else {
                 // Try information_schema manually
                 $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?", [$schema, $table]);
                 if (!empty($columns)) {
                     echo "Columns (via information_schema):\n";
                     foreach ($columns as $col) {
                         echo "- " . $col->column_name . "\n";
                     }
                 } else {
                     echo "Table '$table' really seems missing in schema '$schema'.\n";
                 }
             }
        }
    } catch (\Exception $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n";
    }
}
