<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check vendor user 27
$vendor = App\Models\User::find(27);
if ($vendor) {
    echo "Vendor ID: {$vendor->id}\n";
    echo "Name: {$vendor->full_name}\n";
    echo "Email: " . ($vendor->email ?: 'NO EMAIL SET') . "\n";
    echo "Vendor Code: " . ($vendor->vendor_code ?: '-') . "\n";
    
    // Test sending notification directly
    if ($vendor->email) {
        echo "\nSending test email to vendor ({$vendor->email})...\n";
        try {
            Illuminate\Support\Facades\Mail::raw(
                'This is a test notification from e-DCS. If you received this, email notifications are working!',
                function ($message) use ($vendor) {
                    $message->to($vendor->email)
                            ->subject('Test e-DCS Notification');
                }
            );
            echo "Email sent successfully to {$vendor->email}!\n";
        } catch (Throwable $e) {
            echo "SEND ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\nVendor has NO EMAIL set - cannot send notification!\n";
    }
} else {
    echo "Vendor user 27 not found!\n";
}
