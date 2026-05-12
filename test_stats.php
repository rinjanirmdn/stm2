<?php

use App\Models\BookingRequest;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::where('vendor_code', '1100000298')->first();
if (! $user) {
    echo "User not found\n";
    exit;
}

$slots = Slot::where('requested_by', $user->id)
    ->where(function ($q) {
        $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
    })
    ->where('status', 'scheduled')
    ->count();

$brs = BookingRequest::where('requested_by', $user->id)
    ->where('status', 'approved')
    ->whereNull('converted_slot_id')
    ->count();

echo 'User: '.$user->name."\n";
echo 'Slots Scheduled (planned only): '.$slots."\n";
echo 'BRs Approved: '.$brs."\n";
echo 'Total Scheduled count: '.($slots + $brs)."\n";
