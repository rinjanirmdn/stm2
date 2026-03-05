<?php
$s = microtime(true);
require 'vendor/autoload.php';
$a = require 'bootstrap/app.php';
$k = $a->make(\Illuminate\Contracts\Http\Kernel::class);
echo 'Boot: ' . round((microtime(true) - $s) * 1000) . 'ms' . PHP_EOL;

$req = \Illuminate\Http\Request::create('/users', 'GET');
$s2 = microtime(true);
$resp = $k->handle($req);
echo 'Handle: ' . round((microtime(true) - $s2) * 1000) . 'ms' . PHP_EOL;
echo 'Total: ' . round((microtime(true) - $s) * 1000) . 'ms' . PHP_EOL;
echo 'Status: ' . $resp->getStatusCode() . PHP_EOL;
