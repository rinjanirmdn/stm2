<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('vendor_code', '1100000298')->first();
$rangeStart = '2026-05-01';
$rangeEnd = '2026-05-31';

$slots = App\Models\Slot::where('requested_by', $user->id)
    ->where(function($q) {
        $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
    })
    ->whereDate('planned_start', '>=', $rangeStart)
    ->whereDate('planned_start', '<=', $rangeEnd)
    ->where('status', 'scheduled')
    ->get();

echo "PLANNED SLOTS IN MAY:\n";
foreach($slots as $s) {
    echo "- Slot ID: {$s->id}, Ticket: {$s->ticket_number}, Type: {$s->slot_type}\n";
}

$unplanned = App\Models\Slot::where('requested_by', $user->id)
    ->where('slot_type', 'unplanned')
    ->whereDate('planned_start', '>=', $rangeStart)
    ->whereDate('planned_start', '<=', $rangeEnd)
    ->where('status', 'scheduled')
    ->get();

echo "\nUNPLANNED SLOTS IN MAY:\n";
foreach($unplanned as $s) {
    echo "- Slot ID: {$s->id}, Ticket: {$s->ticket_number}, Type: {$s->slot_type}\n";
}

$brs = App\Models\BookingRequest::where('requested_by', $user->id)
    ->where('status', 'approved')
    ->whereNull('converted_slot_id')
    ->whereDate('planned_start', '>=', $rangeStart)
    ->whereDate('planned_start', '<=', $rangeEnd)
    ->get();

echo "\nBRs IN MAY:\n";
foreach($brs as $b) {
    echo "- BR ID: {$b->id}, Req: {$b->request_number}\n";
}
