<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\SlotLifecycleNotification;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\SlotService;
use App\Services\TimeCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Milon\Barcode\DNS1D;
use RuntimeException;
use Throwable;

class SlotLifecycleController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService
    ) {}

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
            throw ValidationException::withMessages(['ticket_number' => 'Ticket number is required']);
        }

        $expectedTicket = trim((string) ($slot->ticket_number ?? ''));
        if ($expectedTicket !== '' && $ticketNumber !== $expectedTicket) {
            throw ValidationException::withMessages(['ticket_number' => 'Ticket number does not match this slot.']);
        }

        // Handle backdate for Admin / Section Head
        $backdateTime = null;
        if ($request->boolean('use_backdate') && $request->filled('backdate_datetime')) {
            if ($this->isBackdateAllowed()) {
                $bd = Carbon::parse($request->input('backdate_datetime'));
                if ($bd->isFuture()) {
                    throw ValidationException::withMessages(['backdate_datetime' => 'Backdate time must be in the past.']);
                }
                $backdateTime = $bd->format('Y-m-d H:i:s');
            }
        }

        DB::transaction(function () use ($slotId, $ticketNumber, $backdateTime) {
            $now = $backdateTime ?? date('Y-m-d H:i:s');
            DB::table('slots')->where('id', $slotId)->update([
                'arrival_time' => $now,
                'ticket_number' => $ticketNumber,
                'status' => Slot::STATUS_WAITING,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Status Changed to Waiting After Arrival');
            $this->slotService->logActivity($slotId, 'arrival_recorded', 'Arrival Recorded with Ticket '.strtoupper($ticketNumber));
            if ($backdateTime) {
                $bdFmt = Carbon::parse($backdateTime)->format('d-m-Y H:i');
                $this->slotService->logActivity($slotId, 'backdate', 'Arrival Backdated to '.$bdFmt.' by '.auth()->user()->full_name);
            }
        });

        // Notify Section Head & Super Account (database only, no email)
        $this->notifyLifecycleEvent($slotId, $slot, 'arrival');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Arrival recorded successfully']);
        }

        if ($request->boolean('popup')) {
            return view('partials.popup-success', ['message' => 'Arrival recorded successfully']);
        }

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Arrival recorded');
    }

    public function ticket(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        // Cache key based on slot data that affects ticket content
        $cacheKey = 'ticket_pdf_'.$slotId.'_'.md5(json_encode([
            $slot->ticket_number ?? '',
            $slot->truck_number ?? '',
            $slot->vendor_name ?? '',
            $slot->vehicle_number_snap ?? '',
            $slot->direction ?? '',
            $slot->planned_start ?? '',
            $slot->planned_gate_number ?? '',
            $slot->actual_gate_number ?? '',
        ]));

        $pdfContent = Cache::remember($cacheKey, 3600, function () use ($slot) {
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
            $barcodeC = new DNS1D();
            $barcodeC->setStorPath(storage_path('app/public/'));
            $barcodePng = '';
            if (! empty($slot->ticket_number)) {
                $ticketNumber = (string) $slot->ticket_number;
                $barcodePng = (string) Cache::remember('ticket_barcode_png_'.sha1($ticketNumber), 86400, function () use ($barcodeC, $ticketNumber) {
                    return (string) $barcodeC->getBarcodePNG($ticketNumber, 'C128', 2.5, 60);
                });
            }

            // Encode logo as base64 data URI so DomPDF can render it
            $logoDataUri = Cache::rememberForever('ticket_logo_data_uri', function () {
                try {
                    $logoPath = public_path('img/logo-full.png');
                    if (is_string($logoPath) && $logoPath !== '' && file_exists($logoPath)) {
                        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
                    }
                } catch (Throwable $e) {
                    // ignore
                }
            });

            $ticketCss = Cache::rememberForever('ticket_css_inline', function () {
                try {
                    $cssPath = public_path('ticket.css');
                    if (is_string($cssPath) && $cssPath !== '' && file_exists($cssPath)) {
                        return (string) file_get_contents($cssPath);
                    }
                } catch (Throwable $e) {
                    // ignore
                }

                return '';
            });

            $pdf = Pdf::loadView('slots.ticket', [
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

            return $pdf->output();
        });

        $filename = 'ticket-'.($slot->ticket_number ?? $slot->id).'.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
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
        if (! empty($conflictSlotIds)) {
            $rows = DB::table('slots')
                ->whereIn('id', $conflictSlotIds)
                ->select(['id', 'ticket_number'])
                ->get();
            foreach ($rows as $r) {
                $rid = (int) ($r->id ?? 0);
                if ($rid <= 0) {
                    continue;
                }
                $conflictDetails[$rid] = $r;
            }
        }

        $recommendedGateId = null;
        if (! empty($slot->planned_gate_id)) {
            $pgid = (int) $slot->planned_gate_id;
            if (empty(($gateStatuses[$pgid] ?? [])['is_conflict'])) {
                $recommendedGateId = $pgid;
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) {
                    continue;
                }
                if ((int) ($g->warehouse_id ?? 0) !== (int) ($slot->warehouse_id ?? 0)) {
                    continue;
                }
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) {
                    continue;
                }
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }

        $selectedGateId = $recommendedGateId;

        // Compute waiting minutes (time since arrival) for waiting_reason requirement
        $waitingMinutes = 0;
        if (! empty($slot->arrival_time)) {
            $arrivalDt = Carbon::parse($slot->arrival_time);
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
                throw ValidationException::withMessages(['general' => 'Only waiting unplanned slots can be started']);
            }
        } else {
            if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
                throw ValidationException::withMessages(['general' => 'Only arrived/waiting slots can be started']);
            }
            if (empty($slot->arrival_time)) {
                throw ValidationException::withMessages(['general' => 'Please record Arrival before starting this slot']);
            }
        }

        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        if (! $actualGateId) {
            if ($request->ajax() || $request->wantsJson()) {
                throw ValidationException::withMessages(['actual_gate_id' => 'Actual gate is required']);
            }

            return back()->withInput()->with('error', 'Actual gate is required');
        }

        $gateRow = DB::table('md_gates')->where('id', $actualGateId)->where('is_active', true)->select(['id', 'warehouse_id'])->first();
        if (! $gateRow) {
            if ($request->ajax() || $request->wantsJson()) {
                throw ValidationException::withMessages(['actual_gate_id' => 'Selected gate is not active']);
            }

            return back()->withInput()->with('error', 'Selected gate is not active');
        }

        $conflicts = $this->findInProgressConflicts($actualGateId, $slotId);
        if (! empty($conflicts)) {
            $lines = $this->buildConflictLines($conflicts);

            if ($request->ajax() || $request->wantsJson()) {
                throw ValidationException::withMessages(['lane_conflict' => $lines]);
            }

            return back()
                ->withInput()
                ->with('conflict_lines', $lines);
        }

        // Update slot's warehouse_id if actual gate belongs to a different warehouse
        $actualWarehouseId = (int) ($gateRow->warehouse_id ?? 0);
        $slotWarehouseId = (int) ($slot->warehouse_id ?? 0);
        $warehouseChanged = $actualWarehouseId > 0 && $actualWarehouseId !== $slotWarehouseId;

        // Check if waiting > 60 min → require waiting_reason
        $waitingMinutes = 0;
        if (! empty($slot->arrival_time)) {
            $arrivalDt = Carbon::parse($slot->arrival_time);
            $waitingMinutes = (int) $arrivalDt->diffInMinutes(now());
        }
        $waitingReason = trim((string) $request->input('waiting_reason', ''));
        if ($waitingMinutes > 60 && $waitingReason === '') {
            throw ValidationException::withMessages(['waiting_reason' => 'Waiting has exceeded 60 minutes. Please provide the reason for the long wait.']);
        }

        // Handle backdate for Admin / Section Head
        $backdateTime = null;
        if ($request->boolean('use_backdate') && $request->filled('backdate_datetime')) {
            if ($this->isBackdateAllowed()) {
                $bd = Carbon::parse($request->input('backdate_datetime'));
                if ($bd->isFuture()) {
                    throw ValidationException::withMessages(['backdate_datetime' => 'Backdate time must be in the past.']);
                }
                $backdateTime = $bd->format('Y-m-d H:i:s');
            }
        }

        DB::transaction(function () use ($slot, $slotId, $actualGateId, $waitingReason, $backdateTime, $warehouseChanged, $actualWarehouseId) {
            $now = $backdateTime ?? date('Y-m-d H:i:s');
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
            if ($warehouseChanged) {
                $updateData['warehouse_id'] = $actualWarehouseId;
            }
            if ($waitingReason !== '') {
                $updateData['waiting_reason'] = $waitingReason;
            }

            DB::table('slots')->where('id', $slotId)->update($updateData);

            $gateMeta = $this->slotService->getGateMetaById($actualGateId);
            $gateName = strtoupper($this->buildGateLabel((string) ($gateMeta['warehouse_code'] ?? ''), (string) ($gateMeta['gate_number'] ?? '')));

            if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
                if ($isLate) {
                    $this->slotService->logActivity($slotId, 'late_arrival', 'Truck Arrived Late at '.$gateName);
                } else {
                    $this->slotService->logActivity($slotId, 'early_arrival', 'Truck Arrived on Time/Early at '.$gateName);
                }
            }
            $this->slotService->logActivity($slotId, 'status_change', 'Booking Started at '.$gateName);
            if ($warehouseChanged) {
                $this->slotService->logActivity($slotId, 'gate_change', 'Gate changed to different warehouse area: '.$gateName);
            }
            if ($backdateTime) {
                $bdFmt = Carbon::parse($backdateTime)->format('d-m-Y H:i');
                $this->slotService->logActivity($slotId, 'backdate', 'Start Backdated to '.$bdFmt.' by '.auth()->user()->full_name);
            }
        });

        // Notify Section Head & Super Account (database only, no email)
        $this->notifyLifecycleEvent($slotId, $slot, 'start', $actualGateId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Process started successfully']);
        }

        if ($slotType === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned started');
        }

        if ($request->boolean('popup')) {
            return view('partials.popup-success', ['message' => 'Process started successfully']);
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
            throw ValidationException::withMessages(['general' => 'Only in-progress slots can be completed']);
        }

        if (empty($slot->actual_start)) {
            throw ValidationException::withMessages(['general' => 'Cannot complete: actual start time is missing. Please start the booking first.']);
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        if ($truckType === '') {
            $truckType = trim((string) ($slot->truck_type ?? ''));
        }
        $vehicleNumber = trim((string) $request->input('vehicle_number', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));
        $matDocNumber = trim((string) $request->input('mat_doc_number', ''));

        $slotType = (string) ($slot->slot_type ?? 'planned');
        $requiredMissing = $truckType === '' || $vehicleNumber === '';
        // Driver number is required only for unplanned slots
        if ($slotType === 'unplanned' && $driverNumber === '') {
            $requiredMissing = true;
        }
        if ($requiredMissing) {
            throw ValidationException::withMessages(['mat_doc' => 'All required fields must be filled']);
        }

        // Handle backdate for Admin / Section Head
        $backdateTime = null;
        if ($request->boolean('use_backdate') && $request->filled('backdate_datetime')) {
            if ($this->isBackdateAllowed()) {
                $bd = Carbon::parse($request->input('backdate_datetime'));
                if ($bd->isFuture()) {
                    throw ValidationException::withMessages(['backdate_datetime' => 'Backdate time must be in the past.']);
                }
                $backdateTime = $bd->format('Y-m-d H:i:s');
            }
        }

        DB::transaction(function () use ($slotId, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $matDocNumber, $backdateTime) {
            $now = $backdateTime ?? date('Y-m-d H:i:s');

            // Get slot info before updating
            $slotInfo = DB::table('slots')->where('id', $slotId)->first();

            if (! $slotInfo || empty($slotInfo->actual_start)) {
                throw new RuntimeException('Cannot complete: actual start time is missing.');
            }

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'completed',
                'actual_finish' => $now,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'truck_type' => $truckType,
                'vehicle_number_snap' => $vehicleNumber,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'mat_doc_number' => $matDocNumber !== '' ? $matDocNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            // Auto-cancel obsolete scheduled slots when a slot is completed
            if ($slotInfo && $slotInfo->actual_gate_id) {
                $this->autoCancelObsoleteSlots($slotInfo->actual_gate_id, $slotInfo->actual_start, $now, $slotId);
            }

            $this->slotService->logActivity($slotId, 'status_change', 'Slot completed (SJ: '.strtoupper($matDoc).', Truck: '.$truckType.', Vehicle: '.strtoupper($vehicleNumber).', Driver: '.$driverNumber.')');
            if ($backdateTime) {
                $bdFmt = Carbon::parse($backdateTime)->format('d-m-Y H:i');
                $this->slotService->logActivity($slotId, 'backdate', 'Complete Backdated to '.$bdFmt.' by '.auth()->user()->full_name);
            }
        });

        // Notify Section Head & Super Account (database only, no email)
        $this->notifyLifecycleEvent($slotId, $slot, 'complete');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Process completed successfully']);
        }

        if ($request->boolean('popup')) {
            return view('partials.popup-success', ['message' => 'Process completed successfully']);
        }

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
                $finishTime = new DateTime($actualStart);
                $finishTime->modify('+'.(int) $currentSlot->planned_duration.' minutes');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            } else {
                $finishTime = new DateTime($actualStart);
                $finishTime->modify('+1 hour');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            }
        }

        // Find scheduled slots that overlap with the current slot's time
        $obsoleteSlots = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->where('status', 'scheduled')
            ->where('id', '<>', $excludeSlotId)
            ->where(function ($query) use ($actualStart, $estimatedFinish) {
                $query->where(function ($sub) use ($actualStart, $estimatedFinish) {
                    $sub->where('planned_start', '>=', $actualStart)
                        ->where('planned_start', '<=', $estimatedFinish);
                })->orWhere(function ($sub) use ($actualStart) {
                    $sub->where('planned_start', '<=', $actualStart)
                        ->whereRaw('('.$this->slotService->getDateAddExpression('planned_start', 'planned_duration').') >= ?', [$actualStart]);
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
                    'cancelled_at' => now(),
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
        if ($result instanceof RedirectResponse) {
            $successTarget = route('slots.index');
            if ($result->getTargetUrl() === $successTarget) {
                return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned completed');
            }

            return $result;
        }

        return $result;
    }

    /**
     * Check if the current user is allowed to use backdate (Admin or Section Head)
     */
    private function isBackdateAllowed(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Primary: Spatie hasRole
        if (method_exists($user, 'hasRole') && $user->hasRole(['Admin', 'Section Head'])) {
            return true;
        }

        // Fallback: role_id column (same pattern as RoleMiddleware)
        if ($user->role_id) {
            $roleName = DB::table('md_roles')->where('id', $user->role_id)->value('roles_name');
            if ($roleName && in_array($roleName, ['Admin', 'Section Head'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send database-only notification for lifecycle events (arrival, start, complete)
     * to Section Head and Super Account users.
     */
    private function notifyLifecycleEvent(int $slotId, object $slot, string $event, ?int $actualGateId = null): void
    {
        try {
            $actor = Auth::user();
            $actorName = trim((string) ($actor->name ?? $actor->full_name ?? $actor->username ?? 'System'));
            $poNumber = trim((string) ($slot->po_number ?? ''));
            $vendorName = trim((string) ($slot->vendor_name ?? ''));
            $ticketNumber = trim((string) ($slot->ticket_number ?? ''));
            $slotType = (string) ($slot->slot_type ?? 'planned');

            // Resolve gate name
            $gateName = null;
            $gateId = $actualGateId ?? ($slot->actual_gate_id ?? $slot->planned_gate_id ?? null);
            if ($gateId) {
                $gateMeta = $this->slotService->getGateMetaById((int) $gateId);
                if ($gateMeta) {
                    $gateName = strtoupper($this->buildGateLabel(
                        (string) ($gateMeta['warehouse_code'] ?? ''),
                        (string) ($gateMeta['gate_number'] ?? '')
                    ));
                }
            }

            $recipients = User::where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->whereIn(DB::raw('LOWER(roles_name)'), [
                        'section head',
                        'super admin',
                        'super administrator',
                        'super account',
                    ]);
                })
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            $notification = new SlotLifecycleNotification(
                slotId: $slotId,
                slotType: $slotType,
                event: $event,
                poNumber: $poNumber,
                vendorName: $vendorName,
                ticketNumber: $ticketNumber,
                performedBy: $actorName,
                gateName: $gateName,
            );

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(clone $notification);
                } catch (Throwable $e) {
                    Log::warning('Failed to send lifecycle notification: '.$e->getMessage(), [
                        'slot_id' => $slotId,
                        'event' => $event,
                        'recipient_id' => $recipient->id,
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch lifecycle notification: '.$e->getMessage(), [
                'slot_id' => $slotId,
                'event' => $event,
            ]);
        }
    }
}
