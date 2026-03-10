<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting PDF generation test...\n";
    $cssUrl = 'file:///' . str_replace('\\', '/', public_path('ticket.css'));
    echo "CSS URL: $cssUrl\n";
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{$cssUrl}">
</head>
<body>
    <div class="ticket-container">
        Hello
    </div>
</body>
</html>
HTML;
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setOption('isRemoteEnabled', true);
    $output = $pdf->output();
    echo "Success! Length: " . strlen($output) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
