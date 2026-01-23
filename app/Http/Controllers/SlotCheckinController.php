<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SlotCheckinController extends Controller
{
    /**
     * Display slot check-in page
     */
    public function show($slotId)
    {
        $slot = Slot::with(['warehouse', 'plannedGate', 'actualGate'])
            ->findOrFail($slotId);

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            abort(404);
        }

        // Generate check-in URL
        $checkinUrl = route('slots.checkin.show', ['slotId' => $slot->id]);

        return view('slots.checkin', [
            'slot' => $slot,
            'checkinUrl' => $checkinUrl,
            'canCheckin' => $this->canCheckin($slot),
        ]);
    }

    /**
     * Process check-in action
     */
    public function store(Request $request, $slotId)
    {
        $slot = Slot::with(['warehouse', 'plannedGate', 'actualGate'])
            ->findOrFail($slotId);

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return response()->json([
                'success' => false,
                'message' => 'Unplanned slot tidak memiliki proses check-in/arrival'
            ], 400);
        }

        if (!$this->canCheckin($slot)) {
            return response()->json([
                'success' => false,
                'message' => 'Slot tidak dapat di-checkin pada status ini'
            ], 400);
        }

        $action = $request->get('action'); // arrival, start, complete

        try {
            switch ($action) {
                case 'arrival':
                    return $this->processArrival($slot, $request);
                case 'start':
                    return $this->processStart($slot, $request);
                case 'complete':
                    return $this->processComplete($slot, $request);
                default:
                    throw new \Exception('Invalid action');
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function canCheckin($slot)
    {
        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return false;
        }
        return in_array($slot->status, ['scheduled', 'waiting', 'in_progress']);
    }

    private function processArrival($slot, $request)
    {
        if ($slot->status !== 'scheduled') {
            throw new \Exception('Slot sudah di-arrival atau status tidak valid');
        }

        $slot->update([
            'status' => 'waiting',
            'arrival_time' => now(),
        ]);

        // Log activity
        app(\App\Services\SlotService::class)->logActivity(
            $slot->id,
            'arrival',
            'Check-in via QR Code',
            null,
            null,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Check-in arrival berhasil',
            'nextAction' => 'start'
        ]);
    }

    private function processStart($slot, $request)
    {
        if (!in_array($slot->status, ['waiting'])) {
            throw new \Exception('Slot harus dalam status waiting');
        }

        $slot->update([
            'status' => 'in_progress',
            'actual_start' => now(),
        ]);

        // Log activity
        app(\App\Services\SlotService::class)->logActivity(
            $slot->id,
            'start',
            'Start via QR Code',
            null,
            null,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot dimulai',
            'nextAction' => 'complete'
        ]);
    }

    private function processComplete($slot, $request)
    {
        if ($slot->status !== 'in_progress') {
            throw new \Exception('Slot harus dalam status in_progress');
        }

        $slot->update([
            'status' => 'completed',
            'actual_finish' => now(),
        ]);

        // Calculate actual duration
        if ($slot->actual_start) {
            $start = new \DateTime($slot->actual_start);
            $finish = new \DateTime($slot->actual_finish);
            $duration = $finish->diff($start)->i;
            $slot->actual_duration_minutes = $duration;
            $slot->save();
        }

        // Log activity
        app(\App\Services\SlotService::class)->logActivity(
            $slot->id,
            'complete',
            'Complete via QR Code',
            null,
            null,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot selesai',
            'nextAction' => null
        ]);
    }
}
