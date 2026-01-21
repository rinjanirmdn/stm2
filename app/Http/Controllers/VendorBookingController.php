<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\Slot;
use App\Models\SlotPoItem;
use App\Models\TruckTypeDuration;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\BookingApprovalService;
use App\Services\PoSearchService;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class VendorBookingController extends Controller
{
    public function __construct(
        private readonly BookingApprovalService $bookingService,
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService
    ) {}

    private function vendorCodesMatch(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if (strcasecmp($a, $b) === 0) {
            return true;
        }

        $aDigits = preg_replace('/\D+/', '', $a);
        $bDigits = preg_replace('/\D+/', '', $b);

        if ($aDigits !== '' && $bDigits !== '') {
            $aNorm = ltrim($aDigits, '0');
            $bNorm = ltrim($bDigits, '0');
            if ($aNorm === '') $aNorm = '0';
            if ($bNorm === '') $bNorm = '0';
            return $aNorm === $bNorm;
        }

        return false;
    }

    private function getBookedQtyByItemNo(string $poNumber): array
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return [];
        }

        if (!Schema::hasTable('slot_po_items')) {
            return [];
        }

        $rows = DB::table('slot_po_items as spi')
            ->join('slots as s', 's.id', '=', 'spi.slot_id')
            ->where('spi.po_number', $poNumber)
            ->whereNotIn('s.status', [Slot::STATUS_CANCELLED])
            ->groupBy('spi.item_no')
            ->select([
                'spi.item_no',
                DB::raw('SUM(spi.qty_booked) as qty_booked'),
            ])
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $key = trim((string) ($r->item_no ?? ''));
            if ($key === '') {
                continue;
            }
            $out[$key] = (float) ($r->qty_booked ?? 0);
        }

        return $out;
    }

    private function withRemainingQty(array $po): array
    {
        $poNumber = trim((string) ($po['po_number'] ?? ''));
        $bookedByItem = $poNumber !== '' ? $this->getBookedQtyByItemNo($poNumber) : [];

        $items = is_array($po['items'] ?? null) ? $po['items'] : [];
        $hasRemaining = false;
        foreach ($items as $idx => $it) {
            if (!is_array($it)) {
                continue;
            }
            $itemNo = trim((string) ($it['item_no'] ?? ''));
            $qtyPo = $this->toFloatQty($it['qty'] ?? null);
            $booked = $itemNo !== '' ? (float) ($bookedByItem[$itemNo] ?? 0) : 0.0;
            $remaining = $qtyPo - $booked;
            if ($remaining < 0) {
                $remaining = 0.0;
            }
            if ($remaining > 0) {
                $hasRemaining = true;
            }
            $it['qty_booked'] = $booked;
            $it['remaining_qty'] = $remaining;
            $items[$idx] = $it;
        }

        $po['items'] = $items;
        $po['has_remaining'] = $hasRemaining;
        return $po;
    }

    private function toFloatQty(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }

        // Handle formatted numbers like "2,000" or "2.000" or "2.000,50" (best effort)
        $s = str_replace(' ', '', $s);

        // Remove any non-number separators except dot/comma/minus
        $s = preg_replace('/[^0-9,\.-]/', '', $s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            // Decide which is decimal separator by last occurrence
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                // "2.000,50" => thousand '.' and decimal ','
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // "2,000.50" => thousand ',' and decimal '.'
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasDot && !$hasComma) {
            // "2.000" => thousand '.'
            if (preg_match('/^\-?\d{1,3}(\.\d{3})+$/', $s)) {
                $s = str_replace('.', '', $s);
            }
        } elseif ($hasComma && !$hasDot) {
            // "2,000" => thousand ',' OR "2,5" => decimal ','
            if (preg_match('/^\-?\d{1,3}(,\d{3})+$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        }

        // Keep digits, dot and minus only
        $s = preg_replace('/[^0-9\.-]/', '', $s);

        return (float) $s;
    }

    private function getVendorCodeForUser(): string
    {
        $user = Auth::user();
        $vendorCode = $user?->vendor_code;
        return trim((string) ($vendorCode ?? ''));
    }

    private function filterPoResultsByVendor(array $results, string $vendorCode): array
    {
        $vendorCode = trim((string) $vendorCode);
        if ($vendorCode === '') {
            return [];
        }

        $out = [];
        foreach ($results as $row) {
            $poVendor = isset($row['vendor_code']) ? trim((string) $row['vendor_code']) : '';
            if ($poVendor === '') {
                continue;
            }

            if ($this->vendorCodesMatch($poVendor, $vendorCode)) {
                $out[] = $row;
            }
        }

        return array_values($out);
    }

    /**
     * Vendor Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $vendorCode = $user->vendor_code;

        // Get booking statistics
        $stats = [
            'pending_approval' => Slot::where('requested_by', $user->id)
                ->where('status', Slot::STATUS_PENDING_APPROVAL)
                ->count(),
            'pending_confirmation' => Slot::where('requested_by', $user->id)
                ->where('status', Slot::STATUS_PENDING_VENDOR_CONFIRMATION)
                ->count(),
            'scheduled' => Slot::where('requested_by', $user->id)
                ->where('status', Slot::STATUS_SCHEDULED)
                ->whereDate('planned_start', '>=', now())
                ->count(),
            'completed_this_month' => Slot::where('requested_by', $user->id)
                ->where('status', Slot::STATUS_COMPLETED)
                ->whereMonth('actual_finish', now()->month)
                ->count(),
        ];

        // Get recent bookings
        $recentBookings = Slot::where('requested_by', $user->id)
            ->with(['warehouse', 'plannedGate', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get bookings requiring action
        $actionRequired = Slot::where('requested_by', $user->id)
            ->whereIn('status', [
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
                Slot::STATUS_REJECTED,
            ])
            ->with(['warehouse', 'plannedGate'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Data for Create Booking Form (Single Page Experience)
        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ->get()->groupBy('warehouse_id');
        $truckTypes = TruckTypeDuration::select('truck_type')
            ->distinct()
            ->pluck('truck_type');

        return view('vendor.dashboard', compact('stats', 'recentBookings', 'actionRequired', 'warehouses', 'gates', 'truckTypes'));
    }

    /**
     * List vendor's bookings
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Slot::where('requested_by', $user->id)
            ->with(['warehouse', 'plannedGate', 'vendor', 'approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('planned_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_start', '<=', $request->date_to);
        }

        // Search by ticket number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_number_snap', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('vendor.bookings.index', compact('bookings'));
    }

    /**
     * Show booking creation form
     */
    public function create()
    {
        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->with('warehouse')
            ->get()
            ->groupBy('warehouse_id');
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        return view('vendor.bookings.create', compact('warehouses', 'gates', 'truckTypes'));
    }

    public function ajaxPoSearch(Request $request)
    {
        $q = (string) $request->query('q', '');
        $vendorCode = $this->getVendorCodeForUser();

        // Prefer SAP search for autocomplete responsiveness
        $results = $this->poSearchService->searchPoSapOnly($q, 20);
        if (empty($results)) {
            // Fallback to hybrid search if SAP search fails
            $results = $this->poSearchService->searchPo($q);
        }
        $results = $this->filterPoResultsByVendor($results, $vendorCode);

        // Remaining quantities are evaluated on ajaxPoDetail (when user selects a PO)
        $filtered = array_values(array_slice($results, 0, 20));

        return response()->json([
            'success' => true,
            'data' => $filtered,
        ]);
    }

    public function ajaxPoDetail(string $poNumber)
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return response()->json(['success' => false, 'message' => 'PO/DO number is required']);
        }

        $vendorCode = $this->getVendorCodeForUser();
        if ($vendorCode === '') {
            return response()->json(['success' => false, 'message' => 'Vendor is not linked']);
        }

        $po = $this->poSearchService->getPoDetail($poNumber);
        if (! $po) {
            return response()->json(['success' => false, 'message' => 'PO/DO not found']);
        }

        $poVendorCode = trim((string) ($po['vendor_code'] ?? ''));
        if ($poVendorCode === '' || ! $this->vendorCodesMatch($poVendorCode, $vendorCode)) {
            return response()->json(['success' => false, 'message' => 'PO/DO is not assigned to your vendor']);
        }

        $po = $this->withRemainingQty($po);

        return response()->json(['success' => true, 'data' => $po]);
    }

    /**
     * Store new booking request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->vendor_code) {
             return back()->withInput()->with('error', 'Account Configuration Error: Your user account is not linked to a Vendor profile.');
        }

        $request->validate([
            'po_number' => 'required|string', // Enforce here
            'po_items' => 'required|array',
            'po_items.*.qty' => 'nullable|numeric|min:0',
            'warehouse_id' => 'required|exists:warehouses,id',
            'direction' => 'required|in:inbound,outbound',
            'planned_date' => 'required|date|after_or_equal:today',
            'planned_time' => 'required|date_format:H:i',
            'planned_duration' => 'required|integer|min:30|max:480',
            'planned_gate_id' => 'nullable|exists:gates,id',
            'truck_type' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'coa_pdf' => 'required|file|mimes:pdf|max:5120',
            'surat_jalan_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';

        // Check availability
        $availability = $this->bookingService->checkAvailability(
            $request->warehouse_id,
            $request->planned_gate_id,
            $plannedStart,
            $request->planned_duration
        );

        if (!$availability['available']) {
            return back()
                ->withInput()
                ->with('error', $availability['reason']);
        }

        // Ensure PO items selection has at least one qty
        $selectedItems = $request->input('po_items', []);
        $hasQty = false;
        foreach ($selectedItems as $it) {
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
            if ($qty > 0) {
                $hasQty = true;
                break;
            }
        }
        if (!$hasQty) {
            return back()->withInput()->with('error', 'Please input at least one PO item quantity for this booking.');
        }

        // Resolve PO ID from PO Number
        // Since we validated 'required', we strictly process it.
        $poNumber = trim($request->po_number);

        // Validate against SAP remaining quantities
        $vendorCode = $this->getVendorCodeForUser();
        $poDetail = $this->poSearchService->getPoDetail($poNumber);
        if (!$poDetail) {
            return back()->withInput()->with('error', 'PO/DO not found in SAP.');
        }
        $poVendorCode = trim((string) ($poDetail['vendor_code'] ?? ''));
        if ($poVendorCode === '' || !$this->vendorCodesMatch($poVendorCode, $vendorCode)) {
            return back()->withInput()->with('error', 'PO/DO is not assigned to your vendor.');
        }
        $poDetail = $this->withRemainingQty($poDetail);

        $remainingMap = [];
        foreach (($poDetail['items'] ?? []) as $it) {
            if (!is_array($it)) continue;
            $itemNo = trim((string) ($it['item_no'] ?? ''));
            if ($itemNo === '') continue;
            $remainingMap[$itemNo] = (float) ($it['remaining_qty'] ?? 0);
        }

        foreach ($selectedItems as $itemNo => $it) {
            $itemNo = trim((string) $itemNo);
            if ($itemNo === '') continue;
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
            if ($qty <= 0) continue;
            $remain = (float) ($remainingMap[$itemNo] ?? 0);
            if ($remain <= 0) {
                return back()->withInput()->with('error', "Item {$itemNo} has no remaining quantity.");
            }
            if ($qty - $remain > 0.000001) {
                return back()->withInput()->with('error', "Item {$itemNo} quantity exceeds remaining ({$remain}).");
            }
        }

        $po = \Illuminate\Support\Facades\DB::table('po')->where('po_number', $poNumber)->first();
        
        if ($po) {
            $poId = $po->id;
        } else {
            // Auto-create (Stub)
            $poId = \Illuminate\Support\Facades\DB::table('po')->insertGetId([
                'po_number' => $poNumber,
                'mat_doc' => null,
                'truck_number' => '',
                'truck_type' => '',
                'direction' => $request->direction,
                'bp_id' => Auth::user()?->vendor_id,
                'warehouse_id' => $request->warehouse_id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            $notes = (string) $request->notes;
            $driverName = trim((string) $request->driver_name);

            $slot = $this->bookingService->createBookingRequest([
                'warehouse_id' => $request->warehouse_id,
                'po_id' => $poId, 
                'direction' => $request->direction,
                'planned_start' => $plannedStart,
                'planned_duration' => $request->planned_duration,
                'planned_gate_id' => $request->planned_gate_id,
                'truck_type' => $request->truck_type,
                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $request->driver_number,
                'notes' => $notes,
            ], Auth::user());

            $updates = [];
            if ($request->hasFile('coa_pdf')) {
                $coaFile = $request->file('coa_pdf');
                $coaName = 'coa_' . $slot->id . '_' . time() . '.pdf';
                $coaPath = $coaFile->storeAs('booking-documents/' . $slot->id, $coaName, 'public');
                $updates['coa_path'] = $coaPath;
            }
            if ($request->hasFile('surat_jalan_pdf')) {
                $sjFile = $request->file('surat_jalan_pdf');
                $sjName = 'surat_jalan_' . $slot->id . '_' . time() . '.pdf';
                $sjPath = $sjFile->storeAs('booking-documents/' . $slot->id, $sjName, 'public');
                $updates['surat_jalan_path'] = $sjPath;
            }
            if (!empty($updates)) {
                $slot->update($updates);
            }

            // Persist partial delivery per PO item
            $itemsDetailMap = [];
            foreach (($poDetail['items'] ?? []) as $it) {
                if (!is_array($it)) continue;
                $itemNo = trim((string) ($it['item_no'] ?? ''));
                if ($itemNo === '') continue;
                $itemsDetailMap[$itemNo] = $it;
            }

            foreach ($selectedItems as $itemNo => $it) {
                $itemNo = trim((string) $itemNo);
                if ($itemNo === '') continue;
                $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
                if ($qty <= 0) continue;

                $detailIt = $itemsDetailMap[$itemNo] ?? [];
                SlotPoItem::create([
                    'slot_id' => $slot->id,
                    'po_number' => $poNumber,
                    'item_no' => $itemNo,
                    'material_code' => $detailIt['material'] ?? null,
                    'material_name' => $detailIt['description'] ?? null,
                    'uom' => $detailIt['uom'] ?? null,
                    'qty_booked' => $qty,
                ]);
            }

            // Ensure data persisted
            sleep(1);

            return redirect()
                ->route('vendor.bookings.show', $slot->id)
                ->with('success', 'Booking request submitted successfully. Please wait for admin approval.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to submit booking: ' . $e->getMessage());
        }
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Find booking
        $booking = Slot::where('id', $id)
            ->where('requested_by', $user->id) 
            ->with(['warehouse', 'plannedGate', 'actualGate', 'vendor', 'requester', 'approver', 'bookingHistories.performer'])
            ->firstOrFail();

        return view('vendor.bookings.show', compact('booking'));
    }

    /**
     * Cancel pending booking
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $slot = Slot::where('id', $id)
            ->where('requested_by', $user->id)
            ->whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
                Slot::STATUS_SCHEDULED,
            ])
            ->firstOrFail();

        try {
            $this->bookingService->cancelBooking($slot, $user, $request->reason);

            return redirect()
                ->route('vendor.bookings.index')
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel booking: ' . $e->getMessage());
        }
    }

    /**
     * Show confirmation form for rescheduled booking
     */
    public function confirmForm($id)
    {
        $user = Auth::user();
        $booking = Slot::where('id', $id)
            ->where('requested_by', $user->id)
            ->where('status', Slot::STATUS_PENDING_VENDOR_CONFIRMATION)
            ->with(['warehouse', 'plannedGate', 'originalPlannedGate', 'approver'])
            ->firstOrFail();

        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->with('warehouse')
            ->get()
            ->groupBy('warehouse_id');
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        return view('vendor.bookings.confirm', compact('booking', 'warehouses', 'gates', 'truckTypes'));
    }

    /**
     * Process confirmation/rejection/counter-proposal
     */
    public function confirmStore(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:confirm,reject,propose',
            'reason' => 'required_if:action,reject|nullable|string|max:500',
            // For counter-proposal
            'planned_date' => 'required_if:action,propose|nullable|date|after_or_equal:today',
            'planned_time' => 'required_if:action,propose|nullable|date_format:H:i',
            'planned_duration' => 'required_if:action,propose|nullable|integer|min:30|max:480',
            'planned_gate_id' => 'nullable|exists:gates,id',
        ]);

        $user = Auth::user();
        $slot = Slot::where('id', $id)
            ->where('requested_by', $user->id)
            ->where('status', Slot::STATUS_PENDING_VENDOR_CONFIRMATION)
            ->firstOrFail();

        try {
            if ($request->action === 'confirm') {
                $this->bookingService->vendorConfirmReschedule($slot, $user);
                $message = 'Booking confirmed successfully.';
            } elseif ($request->action === 'reject') {
                $this->bookingService->vendorRejectReschedule($slot, $user, $request->reason);
                $message = 'Booking rejected and cancelled.';
            } else {
                // Propose new schedule
                $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';
                
                // Check availability
                $availability = $this->bookingService->checkAvailability(
                    $slot->warehouse_id,
                    $request->planned_gate_id ?? $slot->planned_gate_id,
                    $plannedStart,
                    $request->planned_duration,
                    $slot->id
                );

                if (!$availability['available']) {
                    return back()
                        ->withInput()
                        ->with('error', $availability['reason']);
                }

                $this->bookingService->vendorProposeNewSchedule($slot, $user, [
                    'planned_start' => $plannedStart,
                    'planned_duration' => $request->planned_duration,
                    'planned_gate_id' => $request->planned_gate_id ?? $slot->planned_gate_id,
                ], $request->notes ?? null);
                
                $message = 'New schedule proposed. Please wait for admin approval.';
            }

            return redirect()
                ->route('vendor.bookings.show', $slot->id)
                ->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to process: ' . $e->getMessage());
        }
    }

    /**
     * Show slot availability calendar
     */
    public function availability(Request $request)
    {
        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();
        $selectedWarehouse = $request->warehouse_id 
            ? Warehouse::find($request->warehouse_id) 
            : $warehouses->first();

        $gatesQ = Gate::where('warehouse_id', $selectedWarehouse?->id);
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->orderBy('gate_number')
            ->get();

        $selectedDate = $request->date ?? now()->format('Y-m-d');

        return view('vendor.bookings.availability', compact('warehouses', 'gates', 'selectedWarehouse', 'selectedDate'));
    }

    /**
     * AJAX: Get available slots for a date
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'gate_id' => 'nullable|exists:gates,id',
        ]);

        $slots = $this->bookingService->getAvailableSlots(
            $request->warehouse_id,
            $request->gate_id,
            $request->date
        );

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    /**
     * AJAX: Check availability for a specific time
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'planned_start' => 'required|date',
            'planned_duration' => 'required|integer|min:30',
            'gate_id' => 'nullable|exists:gates,id',
            'exclude_slot_id' => 'nullable|exists:slots,id',
        ]);

        $result = $this->bookingService->checkAvailability(
            $request->warehouse_id,
            $request->gate_id,
            $request->planned_start,
            $request->planned_duration,
            $request->exclude_slot_id
        );

        return response()->json($result);
    }

    /**
     * AJAX: Get truck type duration suggestion
     */
    public function getTruckTypeDuration(Request $request)
    {
        $truckType = $request->truck_type;
        
        $duration = TruckTypeDuration::where('truck_type', $truckType)->first();

        return response()->json([
            'success' => true,
            'duration' => $duration?->target_duration_minutes ?? 60,
        ]);
    }

    /**
     * AJAX: Get slots for calendar view
     */
    public function calendarSlots(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $warehouseId = $request->warehouse_id;

        // Get gates for this warehouse
        $gatesQ = Gate::where('warehouse_id', $warehouseId);
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->with(['warehouse'])
            ->orderBy('gate_number')
            ->get();

        // Get slots for the date
        $slots = Slot::where('warehouse_id', $warehouseId)
            ->whereDate('planned_start', $date)
            ->whereIn('status', Slot::activeStatuses())
            ->with(['vendor', 'requester'])
            ->get()
            ->groupBy('planned_gate_id');

        $calendarData = [];
        foreach ($gates as $gate) {
            $gateSlots = $slots->get($gate->id, collect());
            $calendarData[] = [
                'gate' => [
                    'id' => $gate->id,
                    'name' => $gate->name ?? ($gate->warehouse->wh_code . '-' . $gate->gate_number),
                ],
                'slots' => $gateSlots->map(fn($s) => [
                    'id' => $s->id,
                    'ticket_number' => $s->ticket_number,
                    'vendor_name' => $s->vendor?->name ?? '-',
                    'start_time' => $s->planned_start->format('H:i'),
                    'end_time' => $s->planned_start->copy()->addMinutes($s->planned_duration)->format('H:i'),
                    'duration' => $s->planned_duration,
                    'status' => $s->status,
                    'status_label' => $s->status_label,
                    'status_color' => $s->status_badge_color,
                    'direction' => $s->direction,
                ])->values(),
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'gates' => $calendarData,
        ]);
    }

    public function ticket(int $id)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $slotId = (int) $id;

        $slot = DB::table('slots as s')
            ->join('po as t', 's.po_id', '=', 't.id')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('business_partner as v', 's.bp_id', '=', 'v.id')
            ->leftJoin('gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('gates as ag', 's.actual_gate_id', '=', 'ag.id')
            ->leftJoin('warehouses as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->leftJoin('warehouses as wag', 'ag.warehouse_id', '=', 'wag.id')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id', $slotId)
            ->select([
                's.*',
                't.po_number as po_number',
                't.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'v.bp_name as vendor_name',
                'pg.gate_number as planned_gate_number',
                'ag.gate_number as actual_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
                'wag.wh_code as actual_gate_warehouse_code',
                'td.target_duration_minutes',
            ])
            ->first();

        if (! $slot) {
            return redirect()->route('vendor.bookings.index')->with('error', 'Booking not found');
        }

        if ((int) ($slot->requested_by ?? 0) !== (int) $user->id) {
            abort(403);
        }

        $barcodePng = null;
        $barcodeSvg = null;
        $barcodeHtml = null;

        if (! empty($slot->ticket_number)) {
            $barcodeDir = storage_path('framework/cache/barcodes');
            if (! is_dir($barcodeDir)) {
                @mkdir($barcodeDir, 0755, true);
            }

            try {
                $dns1d = new \Milon\Barcode\DNS1D();
                $dns1d->setStorPath($barcodeDir);

                $rawPng = $dns1d->getBarcodePNG((string) $slot->ticket_number, 'C128', 3, 80);
                if (is_string($rawPng) && $rawPng !== '') {
                    $barcodePng = preg_replace('/\s+/', '', $rawPng);
                }

                $html = $dns1d->getBarcodeHTML((string) $slot->ticket_number, 'C128', 2, 55, 'black', 0);
                if (is_string($html) && $html !== '') {
                    $barcodeHtml = $html;
                }

                if (empty($barcodePng)) {
                    $svg = $dns1d->getBarcodeSVG((string) $slot->ticket_number, 'C128', 2, 60);
                    if (is_string($svg) && $svg !== '') {
                        $barcodeSvg = preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
                    }
                }
            } catch (\Throwable $e) {
                $barcodePng = null;
                $barcodeSvg = null;
                $barcodeHtml = null;
                Log::warning('Barcode generation failed', [
                    'slot_id' => $slotId,
                    'ticket_number' => $slot->ticket_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('slots.ticket', [
            'slot' => $slot,
            'barcodePng' => $barcodePng,
            'barcodeSvg' => $barcodeSvg,
            'barcodeHtml' => $barcodeHtml,
        ])
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper([0, 0, 252, 396], 'portrait');

        return $pdf->stream('ticket-' . ($slot->ticket_number ?? 'unknown') . '.pdf');
    }
}
