<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('vendor_code', '1100000298')->first();
if(!$user) {
    echo "User not found\n";
    exit;
}

$slots = App\Models\Slot::where('requested_by', $user->id)
    ->where(function($q) {
        $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
    })
    ->where('status', 'scheduled')
    ->count();

$brs = App\Models\BookingRequest::where('requested_by', $user->id)
    ->where('status', 'approved')
    ->whereNull('converted_slot_id')
    ->count();

echo "User: " . $user->name . "\n";
echo "Slots Scheduled (planned only): " . $slots . "\n";
echo "BRs Approved: " . $brs . "\n";
echo "Total Scheduled count: " . ($slots + $brs) . "\n";
