<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// Check enum type
$enumVals = DB::select("SELECT enumlabel FROM pg_enum JOIN pg_type ON pg_enum.enumtypid = pg_type.oid WHERE typname = 'activity_logs_activity_type' ORDER BY enumsortorder");
echo 'Enum values: ';
foreach ($enumVals as $v) {
    echo $v->enumlabel.', ';
}
echo PHP_EOL;

// Check column data type
$colType = DB::select("SELECT data_type, udt_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'activity_logs' AND column_name = 'activity_type'");
echo 'Column type: '.($colType[0]->data_type ?? 'unknown').' / '.($colType[0]->udt_name ?? 'unknown').PHP_EOL;
