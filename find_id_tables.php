<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

$tables = Schema::getTableListing();
foreach ($tables as $table) {
    $columns = Schema::getColumnListing($table);
    if (in_array('id', $columns)) {
        echo "$table\n";
    }
}
