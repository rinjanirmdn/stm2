<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\BookingRequest;
use App\Models\BookingRequestItem;
use App\Models\Slot;
use App\Models\TruckTypeDuration;
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

    private function countPendingRequestOverlapGlobal(string $start, string $end, ?int $excludeRequestId = null): int
    {
        $dateAddExpr = $this->slotService->getDateAddExpression('br.planned_start', 'br.planned_duration');

        $query = DB::table('booking_requests as br')
            ->where('br.status', BookingRequest::STATUS_PENDING)
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > br.planned_start', [$end]);

        if ($excludeRequestId) {
            $query->where('br.id', '<>', $excludeRequestId);
        }

        return (int) $query->count();
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

        try {
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
        } catch (\Throwable $e) {
            return [];
        }

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
            'pending_approval' => BookingRequest::where('requested_by', $user->id)
                ->where('status', BookingRequest::STATUS_PENDING)
                ->count(),
            'scheduled' => BookingRequest::where('requested_by', $user->id)
                ->where('status', BookingRequest::STATUS_APPROVED)
                ->count(),
            'completed_this_month' => Slot::where('requested_by', $user->id)
                ->where('status', Slot::STATUS_COMPLETED)
                ->whereMonth('actual_finish', now()->month)
                ->count(),
        ];

        // Get recent bookings
        $recentBookings = BookingRequest::where('requested_by', $user->id)
            ->with(['convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $actionRequired = collect();

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

        $baseQuery = BookingRequest::where('requested_by', $user->id);

        // Filter by date range
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('planned_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('planned_start', '<=', $request->date_to);
        }

        // Search by ticket number
        if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        // Counts for status tabs (independent from current status filter & pagination)
        $counts = [
            'pending' => (clone $baseQuery)->where('status', BookingRequest::STATUS_PENDING)->count(),
            'scheduled' => (clone $baseQuery)->where('status', BookingRequest::STATUS_APPROVED)->count(),
            'completed' => 0,
            'all' => (clone $baseQuery)->count(),
        ];

        $query = (clone $baseQuery)
            ->with(['convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate', 'approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('vendor.bookings.index', compact('bookings', 'counts'));
    }

    /**
     * Show booking creation form
     */
    public function create()
    {
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();
        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $defaultWarehouseId = $warehousesQ->orderBy('id')->value('id');

        return view('vendor.bookings.create', compact('truckTypes', 'defaultWarehouseId'));
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
            'planned_date' => 'required|date|after_or_equal:today',
            'planned_time' => 'required|date_format:H:i',
            'truck_type' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'coa_pdf' => 'required|file|mimes:pdf|max:10240',
            'surat_jalan_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';
        $plannedDuration = $this->resolvePlannedDuration($request->truck_type);
        if ($plannedDuration === null) {
            return back()->withInput()->with('error', 'Please select a truck type to determine duration.');
        }

        $plannedEnd = $this->slotService->computePlannedFinish($plannedStart, $plannedDuration);
        if ($plannedEnd === null) {
            return back()->withInput()->with('error', 'Invalid planned schedule.');
        }

        // Blocking GLOBAL: pending request blocks all vendors for the same time range
        $pendingOverlap = $this->countPendingRequestOverlapGlobal($plannedStart, $plannedEnd, null);
        if ($pendingOverlap > 0) {
            return back()->withInput()->with('error', 'Waktu ini sedang diblokir karena menunggu konfirmasi tim WH');
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
        $direction = $this->resolveDirection($poDetail);

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

        try {
            $notes = (string) $request->notes;
            $driverName = trim((string) $request->driver_name);

            $bookingRequest = BookingRequest::create([
                'request_number' => null,
                'requested_by' => $user->id,
                'po_number' => $poNumber,
                'supplier_code' => $poDetail['supplier_code'] ?? null,
                'supplier_name' => $poDetail['supplier_name'] ?? ($poDetail['vendor_name'] ?? null),
                'doc_date' => !empty($poDetail['doc_date']) ? $poDetail['doc_date'] : null,
                'direction' => $direction,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDuration,
                'truck_type' => $request->truck_type,
                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $request->driver_number,
                'notes' => $notes !== '' ? $notes : null,
                'status' => BookingRequest::STATUS_PENDING,
            ]);

            // Store documents
            $updates = [];
            if ($request->hasFile('coa_pdf')) {
                $coaFile = $request->file('coa_pdf');
                $coaName = 'coa_' . $bookingRequest->id . '_' . time() . '.pdf';
                $coaPath = $coaFile->storeAs('booking-documents/' . $bookingRequest->id, $coaName, 'public');
                $updates['coa_path'] = $coaPath;
            }
            if ($request->hasFile('surat_jalan_pdf')) {
                $sjFile = $request->file('surat_jalan_pdf');
                $sjName = 'surat_jalan_' . $bookingRequest->id . '_' . time() . '.pdf';
                $sjPath = $sjFile->storeAs('booking-documents/' . $bookingRequest->id, $sjName, 'public');
                $updates['surat_jalan_path'] = $sjPath;
            }
            if (!empty($updates)) {
                $bookingRequest->update($updates);
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
                BookingRequestItem::create([
                    'booking_request_id' => $bookingRequest->id,
                    'po_number' => $poNumber,
                    'item_no' => $itemNo,
                    'material_code' => $detailIt['material'] ?? null,
                    'material_name' => $detailIt['description'] ?? null,
                    'qty_po' => $detailIt['qty'] ?? null,
                    'unit_po' => $detailIt['uom'] ?? null,
                    'qty_gr_total' => $detailIt['qty_gr_total'] ?? null,
                    'qty_requested' => $qty,
                ]);
            }

            return redirect()
                ->route('vendor.bookings.show', $bookingRequest->id)
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
        $booking = BookingRequest::where('id', $id)
            ->where('requested_by', $user->id)
            ->with(['items', 'approver', 'convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate', 'convertedSlot.actualGate'])
            ->firstOrFail();

        return view('vendor.bookings.show', compact('booking'));
    }

    /**
     * Cancel pending booking
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = BookingRequest::where('id', $id)
            ->where('requested_by', $user->id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->firstOrFail();

        try {
            $booking->update([
                'status' => BookingRequest::STATUS_CANCELLED,
                'approval_notes' => $request->reason,
            ]);

            return redirect()
                ->route('vendor.bookings.index')
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel booking: ' . $e->getMessage());
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
        $selectedWarehouse = $warehousesQ->orderBy('id')->first();

        $selectedDate = $request->date ?? now()->format('Y-m-d');

        return view('vendor.bookings.availability', compact('selectedWarehouse', 'selectedDate'));
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
            ->where('status', Slot::STATUS_PENDING_APPROVAL)
            ->with(['requester'])
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
                    'vendor_name' => $s->vendor_name ?? '-',
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
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('gates as ag', 's.actual_gate_id', '=', 'ag.id')
            ->leftJoin('warehouses as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->leftJoin('warehouses as wag', 'ag.warehouse_id', '=', 'wag.id')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id', $slotId)
            ->select([
                's.*',
                's.po_number as po_number',
                's.po_number as truck_number',
                's.po_number as po_number',
                's.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
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
