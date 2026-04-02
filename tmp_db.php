<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$n = DB::table('notifications')->orderBy('created_at', 'desc')->limit(4)->get();
file_put_contents('tmp_db.json', json_encode($n, JSON_PRETTY_PRINT));
echo 'Done';
