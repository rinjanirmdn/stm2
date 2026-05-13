<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = Schema::getTableListing();
foreach ($tables as $table) {
    $columns = Schema::getColumnListing($table);
    if (in_array('id', $columns)) {
        echo "$table\n";
    }
}
