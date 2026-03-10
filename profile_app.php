<?php
require __DIR__ . '/vendor/autoload.php';

$t0 = microtime(true);

// Bootstrap the app
$app = require __DIR__ . '/bootstrap/app.php';
$t1 = microtime(true);
echo "1. Bootstrap: " . round(($t1 - $t0) * 1000) . "ms\n";

// Make kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$t2 = microtime(true);
echo "2. Kernel: " . round(($t2 - $t1) * 1000) . "ms\n";

// Try DB connection
try {
    $pdo = DB::connection()->getPdo();
    $t3 = microtime(true);
    echo "3. DB Connect: " . round(($t3 - $t2) * 1000) . "ms\n";
} catch (Throwable $e) {
    $t3 = microtime(true);
    echo "3. DB Connect FAILED (" . round(($t3 - $t2) * 1000) . "ms): " . $e->getMessage() . "\n";
}

// Handle request
$request = Illuminate\Http\Request::create('/login', 'GET');
$response = $kernel->handle($request);
$t4 = microtime(true);
echo "4. Handle /login: " . round(($t4 - $t3) * 1000) . "ms (HTTP " . $response->getStatusCode() . ")\n";

echo "\nTOTAL: " . round(($t4 - $t0) * 1000) . "ms\n";
