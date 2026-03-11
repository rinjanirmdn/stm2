<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Models\Slot;
use App\Services\SlotService;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\TimeCalculationService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SlotLifecycleController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService
    ) {
    }

    public function arrival(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Unplanned slots do not have an arrival process');
        }

        if ((string) ($slot->status ?? '') !== Slot::STATUS_SCHEDULED) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled slots can be arrived');
        }

        return view('slots.arrival', [
            'slot' => $slot,
        ]);
    }

    public function arrivalStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Unplanned slots do not have an arrival process');
        }

        if ((string) ($slot->status ?? '') !== Slot::STATUS_SCHEDULED) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled slots can be arrived');
        }

        $ticketNumber = trim((string) $request->input('ticket_number', ''));
        if ($ticketNumber === '') {
            return back()->withInput()->with('error', 'Ticket number is required');
        }

        $expectedTicket = trim((string) ($slot->ticket_number ?? ''));
        if ($expectedTicket !== '' && $ticketNumber !== $expectedTicket) {
            return back()->withInput()->with('error', 'Ticket number does not match this slot.');
        }

        DB::transaction(function () use ($slotId, $ticketNumber) {
            $now = date('Y-m-d H:i:s');
            DB::table('slots')->where('id', $slotId)->update([
                'arrival_time' => $now,
                'ticket_number' => $ticketNumber,
                'status' => Slot::STATUS_WAITING,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Status Changed to Waiting After Arrival');
            $this->slotService->logActivity($slotId, 'arrival_recorded', 'Arrival Recorded with Ticket ' . $ticketNumber);
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Arrival recorded');
    }

    public function ticket(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        $gateNumber = (string) ($slot->actual_gate_number ?? '');
        $gateWarehouse = (string) ($slot->actual_gate_warehouse_code ?? '');
        if ($gateNumber === '') {
            $gateNumber = (string) ($slot->planned_gate_number ?? '');
            $gateWarehouse = (string) ($slot->planned_gate_warehouse_code ?? '');
        }
        if ($gateWarehouse === '') {
            $gateWarehouse = (string) ($slot->warehouse_code ?? '');
        }
        $gateLetter = $this->slotService->getGateLetterByWarehouseAndNumber($gateWarehouse, $gateNumber);

        // Generate barcode
        $barcodeC = new \Milon\Barcode\DNS1D();
        $barcodeC->setStorPath(storage_path('app/public/'));
        $barcodePng = '';
        if (!empty($slot->ticket_number)) {
            $ticketNumber = (string) $slot->ticket_number;
            $barcodePng = (string) Cache::remember('ticket_barcode_png_' . sha1($ticketNumber), 86400, function () use ($barcodeC, $ticketNumber) {
                return (string) $barcodeC->getBarcodePNG($ticketNumber, 'C128', 2.5, 60);
            });
        }

        // Encode logo as base64 data URI so DomPDF can render it
        $logoDataUri = Cache::rememberForever('ticket_logo_data_uri', function () {
            try {
                $logoPath = public_path('img/logo-full.png');
                if (is_string($logoPath) && $logoPath !== '' && file_exists($logoPath)) {
                    return 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
                }
            } catch (\Throwable $e) {
            }
            return null;
        });

        $ticketCss = Cache::rememberForever('ticket_css_inline', function () {
            try {
                $cssPath = public_path('ticket.css');
                if (is_string($cssPath) && $cssPath !== '' && file_exists($cssPath)) {
                    return (string) file_get_contents($cssPath);
                }
            } catch (\Throwable $e) {
            }
            return '';
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('slots.ticket', [
            'slot' => $slot,
            'gateLetter' => $gateLetter,
            'barcodePng' => $barcodePng,
            'barcodeHtml' => null,
            'barcodeSvg' => null,
            'logoDataUri' => $logoDataUri,
            'ticketCss' => $ticketCss,
        ])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('chroot', public_path())
            ->setPaper([0, 0, 252, 396], 'portrait');

        return $pdf->stream('ticket-' . ($slot->ticket_number ?? $slot->id) . '.pdf');
    }

    public function start(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        if ($slotType === 'unplanned') {
            if ((string) ($slot->status ?? '') !== Slot::STATUS_WAITING) {
                return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Only waiting unplanned slots can be started');
            }
        } else {
            if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
                return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
            }

            if (empty($slot->arrival_time)) {
                return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
            }
        }

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select([
                'g.*',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
            ])
            ->get();

        $plannedDurationMinutes = $this->getPlannedDurationForStart($slot);

        $gateStatuses = [];
        $allConflict = [];
        foreach ($gates as $g) {
            $gid = (int) ($g->id ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $conflicts = $this->findInProgressConflicts($gid, $slotId);
            foreach ($conflicts as $cid) {
                $allConflict[$cid] = true;
            }
            $gateStatuses[$gid] = [
                'is_conflict' => ! empty($conflicts),
                'overlapping_slots' => $conflicts,
                'lane_utilization_pct' => ! empty($conflicts) ? 100 : 0,
            ];
        }

        $conflictSlotIds = array_keys($allConflict);
        $conflictDetails = [];
        if (!empty($conflictSlotIds)) {
            $rows = DB::table('slots')
                ->whereIn('id', $conflictSlotIds)
                ->select(['id', 'ticket_number'])
                ->get();
            foreach ($rows as $r) {
                $rid = (int) ($r->id ?? 0);
                if ($rid <= 0) continue;
                $conflictDetails[$rid] = $r;
            }
        }

        $recommendedGateId = null;
        if (!empty($slot->planned_gate_id)) {
            $pgid = (int) $slot->planned_gate_id;
            if (empty(($gateStatuses[$pgid] ?? [])['is_conflict'])) {
                $recommendedGateId = $pgid;
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) continue;
                if ((int) ($g->warehouse_id ?? 0) !== (int) ($slot->warehouse_id ?? 0)) continue;
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) continue;
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }

        $selectedGateId = $recommendedGateId;

        // Compute waiting minutes (time since arrival) for waiting_reason requirement
        $waitingMinutes = 0;
        if (!empty($slot->arrival_time)) {
            $arrivalDt = \Carbon\Carbon::parse($slot->arrival_time);
            $waitingMinutes = (int) $arrivalDt->diffInMinutes(now());
        }

        $viewName = $slotType === 'unplanned' ? 'unplanned.start' : 'slots.start';

        return view($viewName, [
            'slot' => $slot,
            'gates' => $gates,
            'plannedDurationMinutes' => $plannedDurationMinutes,
            'gateStatuses' => $gateStatuses,
            'conflictDetails' => $conflictDetails,
            'recommendedGateId' => $recommendedGateId,
            'selectedGateId' => $selectedGateId,
            'waitingMinutes' => $waitingMinutes,
        ]);
    }

    public function startStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        if ($slotType === 'unplanned') {
            if ((string) ($slot->status ?? '') !== Slot::STATUS_WAITING) {
                return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Only waiting unplanned slots can be started');
            }
        } else {
            if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
                return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
            }
            if (empty($slot->arrival_time)) {
                return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
            }
        }

        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        if (! $actualGateId) {
            return back()->withInput()->with('error', 'Actual gate is required');
        }

        $gateRow = DB::table('md_gates')->where('id', $actualGateId)->where('is_active', true)->select(['id', 'warehouse_id'])->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        if ((int) ($gateRow->warehouse_id ?? 0) !== (int) ($slot->warehouse_id ?? 0)) {
            return back()->withInput()->with('error', 'Selected gate does not belong to the slot\'s warehouse');
        }

        $conflicts = $this->findInProgressConflicts($actualGateId, $slotId);
        if (! empty($conflicts)) {
            $lines = $this->buildConflictLines($conflicts);
            return back()
                ->withInput()
                ->with('conflict_lines', $lines);
        }

        // Check if waiting > 60 min → require waiting_reason
        $waitingMinutes = 0;
        if (!empty($slot->arrival_time)) {
            $arrivalDt = \Carbon\Carbon::parse($slot->arrival_time);
            $waitingMinutes = (int) $arrivalDt->diffInMinutes(now());
        }
        $waitingReason = trim((string) $request->input('waiting_reason', ''));
        if ($waitingMinutes > 60 && $waitingReason === '') {
            return back()->withInput()->with('error', 'Waiting has exceeded 60 minutes. Please provide the reason for the long wait.');
        }

        DB::transaction(function () use ($slot, $slotId, $actualGateId, $waitingReason) {
            $now = date('Y-m-d H:i:s');
            $arrivalTime = (string) ($slot->arrival_time ?? $now);
            $isLate = 0;
            if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
                $isLate = $this->isLateByPlannedStart((string) ($slot->planned_start ?? ''), $now) ? 1 : 0;
            }

            $updateData = [
                'status' => Slot::STATUS_IN_PROGRESS,
                'arrival_time' => $arrivalTime,
                'actual_start' => $now,
                'is_late' => $isLate,
                'actual_gate_id' => $actualGateId,
            ];
            if ($waitingReason !== '') {
                $updateData['waiting_reason'] = $waitingReason;
            }

            DB::table('slots')->where('id', $slotId)->update($updateData);

            $gateMeta = $this->slotService->getGateMetaById($actualGateId);
            $gateName = $this->buildGateLabel((string) ($gateMeta['warehouse_code'] ?? ''), (string) ($gateMeta['gate_number'] ?? ''));

            if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
                if ($isLate) {
                    $this->slotService->logActivity($slotId, 'late_arrival', 'Truck Arrived Late at ' . $gateName);
                } else {
                    $this->slotService->logActivity($slotId, 'early_arrival', 'Truck Arrived on Time/Early at ' . $gateName);
                }
            }
            $this->slotService->logActivity($slotId, 'status_change', 'Booking Started at ' . $gateName);
        });

        if ($slotType === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned started');
        }

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Booking started');
    }

    public function unplannedStart(int $slotId)
    {
        return $this->start($slotId);
    }

    public function unplannedStartStore(Request $request, int $slotId)
    {
        return $this->startStore($request, $slotId);
    }

    public function complete(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        $truckTypes = $this->getTruckTypeOptions();
        $viewName = ((string) ($slot->slot_type ?? 'planned')) === 'unplanned' ? 'unplanned.complete' : 'slots.complete';

        return view($viewName, [
            'slot' => $slot,
            'truckTypes' => $truckTypes,
        ]);
    }

    public function completeStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if ((string) ($slot->status ?? '') !== 'in_progress') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only in-progress slots can be completed');
        }

        if (empty($slot->actual_start)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Cannot complete: actual start time is missing. Please start the booking first.');
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        // Truck type comes from slot data, not from form
        $truckType = (string) ($slot->truck_type ?? '');
        $vehicleNumber = trim((string) $request->input('vehicle_number', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if ($matDoc === '' || $truckType === '' || $vehicleNumber === '' || $driverNumber === '') {
            return back()->withInput()->with('error', 'All required fields must be filled');
        }

        DB::transaction(function () use ($slotId, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes) {
            $now = date('Y-m-d H:i:s');

            // Get slot info before updating
            $slotInfo = DB::table('slots')->where('id', $slotId)->first();

            if (! $slotInfo || empty($slotInfo->actual_start)) {
                throw new \RuntimeException('Cannot complete: actual start time is missing.');
            }

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'completed',
                'actual_finish' => $now,
                'mat_doc' => $matDoc,
                'truck_type' => $truckType,
                'vehicle_number_snap' => $vehicleNumber,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            // Auto-cancel obsolete scheduled slots when a slot is completed
            if ($slotInfo && $slotInfo->actual_gate_id) {
                $this->autoCancelObsoleteSlots($slotInfo->actual_gate_id, $slotInfo->actual_start, $slotInfo->actual_finish, $slotId);
            }

            $this->slotService->logActivity($slotId, 'status_change', 'Data Completed with MAT DOC ' . $matDoc . ', Truck ' . $truckType . ', Vehicle ' . $vehicleNumber . ', Driver ' . $driverNumber);
        });

        return redirect()->route('slots.index')->with('success', 'Data completed');
    }

    /**
     * Auto-cancel obsolete scheduled slots when a slot is started or completed
     */
    private function autoCancelObsoleteSlots(int $gateId, string $actualStart, ?string $actualFinish, int $excludeSlotId): void
    {
        // Get lane group for the gate
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        // For in-progress slots, estimate finish time based on planned duration
        $estimatedFinish = $actualFinish;
        if ($estimatedFinish === null) {
            $currentSlot = DB::table('slots')->where('id', $excludeSlotId)->first();
            if ($currentSlot && $currentSlot->planned_duration) {
                $finishTime = new \DateTime($actualStart);
                $finishTime->modify('+' . (int) $currentSlot->planned_duration . ' minutes');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            } else {
                $finishTime = new \DateTime($actualStart);
                $finishTime->modify('+1 hour');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            }
        }

        // Find scheduled slots that overlap with the current slot's time
        $obsoleteSlots = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->where('status', 'scheduled')
            ->where('id', '<>', $excludeSlotId)
            ->where(function($query) use ($actualStart, $estimatedFinish) {
                $query->where(function($sub) use ($actualStart, $estimatedFinish) {
                    $sub->where('planned_start', '>=', $actualStart)
                        ->where('planned_start', '<=', $estimatedFinish);
                })->orWhere(function($sub) use ($actualStart, $estimatedFinish) {
                    $sub->where('planned_start', '<=', $actualStart)
                        ->whereRaw('(' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration') . ') >= ?', [$actualStart]);
                });
            })
            ->get();

        if ($obsoleteSlots->isEmpty()) {
            return;
        }

        // Cancel obsolete scheduled slots
        foreach ($obsoleteSlots as $obsoleteSlot) {
            DB::table('slots')
                ->where('id', $obsoleteSlot->id)
                ->update([
                    'status' => 'cancelled',
                    'blocking_risk' => 0,
                    'cancelled_reason' => 'Auto-cancelled: Truck started operation earlier at same gate',
                    'cancelled_at' => now()
                ]);

            $this->slotService->logActivity(
                $obsoleteSlot->id,
                'status_change',
                'Auto-cancelled due to earlier operation start at same gate'
            );
        }
    }

    public function unplannedComplete(int $slotId)
    {
        return $this->complete($slotId);
    }

    public function unplannedCompleteStore(Request $request, int $slotId)
    {
        $result = $this->completeStore($request, $slotId);

        // Redirect to unplanned show instead of slots show
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned completed');
        }

        return $result;
    }
}
