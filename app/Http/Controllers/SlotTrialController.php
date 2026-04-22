<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Services\SlotConflictService;
use App\Services\SlotService;
use App\Services\TimeCalculationService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Notifications\SlotCreatedByInternal;

/**
 * SlotTrialController
 *
 * Handles "Create Planned Uji Coba" — identical to the normal planned slot
 * creation flow BUT does NOT connect to the SAP API for PO validation.
 * Vendor data is resolved from the local md_bp master table.
 *
 * This controller is self-contained and does NOT modify any existing logic.
 */
class SlotTrialController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly TimeCalculationService $timeService,
        private readonly SlotConflictService $conflictService
    ) {}

    /**
     * Show the "Create Planned Uji Coba" form.
     */
    public function create()
    {
        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();
        $truckTypeDurations = DB::table('md_truck')
            ->orderBy('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->all();

        // Vendors from local md_bp master data (no SAP API)
        $vendors = DB::table('md_bp')
            ->where('is_active', true)
            ->whereIn('bp_type', ['vendor', 'customer'])
            ->orderBy('bp_name')
            ->select(['id', 'bp_code', 'bp_name', 'bp_type'])
            ->get();

        return view('slots.trial_create', [
            'warehouses' => $warehouses,
            'gates' => $gates,
            'vendors' => $vendors,
            'truckTypes' => $truckTypes,
            'truckTypeDurations' => $truckTypeDurations,
        ]);
    }

    /**
     * Store the trial planned slot (no SAP PO validation).
     */
    public function store(Request $request)
    {
        $request->validate([
            'po_number' => 'required|string|max:50',
            'direction' => 'required|in:inbound,outbound',
            'truck_type' => 'required|string|max:100',
            'bp_id' => 'nullable|integer|exists:md_bp,id',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'planned_start' => 'required|string',
            'planned_duration' => 'required|integer|min:1|max:1440',
            'vehicle_number_snap' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $poNumber = trim((string) $request->input('po_number', ''));
        $direction = (string) $request->input('direction', '');
        $plannedGateId = (int) $request->input('planned_gate_id');
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 60);
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));
        $bpId = $request->input('bp_id') ? (int) $request->input('bp_id') : null;

        // Resolve gate & warehouse
        $gateRow = DB::table('md_gates')
            ->where('id', $plannedGateId)
            ->where('is_active', true)
            ->select(['id', 'warehouse_id'])
            ->first();

        if (! $gateRow) {
            return back()->withInput()->with('error', 'Gate yang dipilih tidak aktif.');
        }

        $warehouseId = (int) $gateRow->warehouse_id;

        // Resolve vendor from local md_bp (no SAP API)
        $vendorCode = null;
        $vendorName = null;
        $vendorType = null;

        if ($bpId) {
            $bp = DB::table('md_bp')->where('id', $bpId)->first();
            if ($bp) {
                $vendorCode = $bp->bp_code;
                $vendorName = $bp->bp_name;
                $vendorType = $bp->bp_type;
            }
        }

        // Parse & validate planned start time
        try {
            $plannedStartDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Format waktu ETA tidak valid.');
        }

        $plannedEndDt = clone $plannedStartDt;
        $plannedEndDt->modify("+{$plannedDuration} minutes");

        // Check gate overlap (same logic as regular slot — non-negotiable)
        $laneGroup = $this->slotService->getGateLaneGroup($plannedGateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$plannedGateId];
        if (empty($laneGateIds)) {
            $laneGateIds = [$plannedGateId];
        }

        $startStr = $plannedStartDt->format('Y-m-d H:i:s');
        $endStr = $plannedEndDt->format('Y-m-d H:i:s');

        $overlapCount = (int) DB::table('slots')
            ->whereIn('planned_gate_id', $laneGateIds)
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereRaw('? < '.$this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
            ->whereRaw('? > planned_start', [$endStr])
            ->count();

        if ($overlapCount > 0) {
            return back()->withInput()->with('error', 'Waktu yang dipilih bentrok dengan booking lain di lane yang sama.');
        }

        // BC-window validation (same as regular)
        $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt, 0);
        if (empty($bcCheck['ok'])) {
            return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Waktu tidak valid.'));
        }

        // Insert slot — marked as uji_coba so it can be identified separately
        $slotId = 0;
        DB::transaction(function () use (
            &$slotId, $poNumber, $direction, $warehouseId,
            $plannedGateId, $plannedStart, $plannedDuration,
            $truckType, $vehicleNumber, $driverName, $driverNumber,
            $notes, $vendorCode, $vendorName, $vendorType
        ) {
            $now = date('Y-m-d H:i:s');
            $ticket = $this->slotService->generateTicketNumber($warehouseId, $plannedGateId);

            $slotId = (int) DB::table('slots')->insertGetId([
                'po_number' => $poNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_code' => $vendorCode,
                'vendor_name' => $vendorName,
                'vendor_type' => $vendorType,
                'planned_gate_id' => $plannedGateId,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDuration,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'ticket_number' => $ticket,
                'status' => 'scheduled',
                'slot_type' => 'planned',          // keep same type for compatibility
                'created_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($slotId > 0) {
                $logDesc = 'Slot uji coba dibuat'.($vendorName ? " ({$vendorName})" : '');
                if ($poNumber !== '') {
                    $logDesc .= " - Ref: {$poNumber}";
                }
                $this->slotService->logActivity($slotId, 'status_change', $logDesc);
            }
        });

        if ($slotId <= 0) {
            return back()->withInput()->with('error', 'Gagal menyimpan data. Silakan coba lagi.');
        }

        // Recompute blocking risk
        $blockingRisk = $this->slotService->calculateBlockingRisk(
            $warehouseId,
            $plannedGateId,
            $plannedStart,
            $plannedDuration,
            $slotId
        );
        DB::table('slots')->where('id', $slotId)->update([
            'blocking_risk' => $blockingRisk,
            'blocking_risk_cached_at' => now(),
        ]);

        // Notify Section Head & Super Account about new trial transaction
        try {
            $actor = Auth::user();
            $actorName = trim((string) ($actor->name ?? $actor->full_name ?? $actor->username ?? 'Admin Uji Coba'));
            
            $plannedDate = '-';
            try {
                $plannedDate = (new DateTime($plannedStart))->format('d-m-Y H:i');
            } catch (\Throwable $e) {
                // ignore
            }

            $ticketNumber = DB::table('slots')->where('id', $slotId)->value('ticket_number');

            $recipients = User::where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->whereIn(DB::raw('LOWER(roles_name)'), [
                        'section head',
                        'super account',
                    ]);
                })
                ->get();

            if ($recipients->isNotEmpty()) {
                $notification = new SlotCreatedByInternal(
                    slotId: $slotId,
                    slotType: 'planned (Uji Coba)',
                    poNumber: $poNumber,
                    vendorName: $vendorName ?? '',
                    direction: ucfirst($direction),
                    plannedDate: $plannedDate,
                    createdByName: $actorName,
                    truckType: $truckType !== '' ? $truckType : null,
                    vehicleNumber: $vehicleNumber !== '' ? $vehicleNumber : null,
                    ticketNumber: $ticketNumber,
                );

                foreach ($recipients as $recipient) {
                    $recipient->notify(clone $notification);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch trial slot notification: '.$e->getMessage(), [
                'slot_id' => $slotId,
            ]);
        }

        return redirect()->route('slots.index')->with('success', 'Planned uji coba berhasil dibuat.');
    }
}
