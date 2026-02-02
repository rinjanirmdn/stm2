<?php

namespace App\Services;

use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportService
{
    private function getDateAddExpression(string $dateExpr, int $minutes): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return "datetime({$dateExpr}, '+{$minutes} minutes')";
        }

        if ($driver === 'pgsql') {
            return "({$dateExpr} + ({$minutes}) * interval '1 minute')";
        }

        return "DATE_ADD({$dateExpr}, INTERVAL {$minutes} MINUTE)";
    }

    private function getTimestampDiffMinutesExpression(string $startExpr, string $endExpr): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return "((strftime('%s', {$endExpr}) - strftime('%s', {$startExpr})) / 60.0)";
        }

        if ($driver === 'pgsql') {
            return "(EXTRACT(EPOCH FROM ({$endExpr} - {$startExpr})) / 60.0)";
        }

        return "TIMESTAMPDIFF(MINUTE, {$startExpr}, {$endExpr})";
    }

    /**
     * Get transaction data with filters
     */
    public function getTransactions(Request $request)
    {
        $query = $this->buildBaseTransactionQuery();

        $this->applyTransactionFilters($query, $request);

        return $query;
    }

    /**
     * Apply transaction-specific filters
     */
    public function applyTransactionFilters($query, Request $request)
    {
        $this->applyBasicFilters($query, $request);
        $this->applyDateFilters($query, $request);
        $this->applyStatusFilters($query, $request);
        $this->applyPerformanceFilters($query, $request);

        return $query;
    }

    /**
     * Calculate lead time metrics for a slot
     */
    public function calculateLeadTimeMetrics($slot): array
    {
        $metrics = [
            'lead_time_minutes' => null,
            'waiting_minutes' => null,
            'processing_minutes' => null,
        ];

        try {
            $arrival = !empty($slot->arrival_time) ? (string) $slot->arrival_time : null;
            $start = !empty($slot->actual_start) ? (string) $slot->actual_start : null;
            $finish = !empty($slot->actual_finish) ? (string) $slot->actual_finish : null;

            // Calculate waiting time (arrival to start)
            if ($arrival && $start) {
                $aDt = new \DateTime($arrival);
                $sDt = new \DateTime($start);
                $diffW = $aDt->diff($sDt);
                $metrics['waiting_minutes'] = ($diffW->days * 24 * 60) + ($diffW->h * 60) + $diffW->i;
            }

            // Calculate processing time (start to finish)
            if ($start && $finish) {
                $sDt = new \DateTime($start);
                $fDt = new \DateTime($finish);
                $diffP = $sDt->diff($fDt);
                $metrics['processing_minutes'] = ($diffP->days * 24 * 60) + ($diffP->h * 60) + $diffP->i;
            }

            // Calculate total lead time (arrival to finish)
            if ($arrival && $finish) {
                $aDt = new \DateTime($arrival);
                $fDt = new \DateTime($finish);
                $diffL = $aDt->diff($fDt);
                $metrics['lead_time_minutes'] = ($diffL->days * 24 * 60) + ($diffL->h * 60) + $diffL->i;
            }
        } catch (\Throwable $e) {
            // Return null values on error
        }

        return $metrics;
    }

    /**
     * Get target achievement status
     */
    public function getTargetAchievement($slot): string
    {
        $targetMinutes = isset($slot->target_duration_minutes) ? (int) $slot->target_duration_minutes : 0;
        $leadTime = $this->calculateLeadTimeMetrics($slot)['lead_time_minutes'];

        if ($targetMinutes > 0 && $leadTime !== null) {
            return $leadTime <= ($targetMinutes + 15) ? 'achieve' : 'not_achieve';
        }

        return '';
    }

    /**
     * Get late status
     */
    public function getLateStatus($slot): string
    {
        return !empty($slot->is_late) ? 'late' : 'on_time';
    }

    /**
     * Build base transaction query
     */
    private function buildBaseTransactionQuery()
    {
        return DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_users as u', 's.created_by', '=', 'u.id')
            ->leftJoin('md_truck as td', 's.truck_type', '=', 'td.truck_type')
            ->select([
                's.*',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
                'u.name as created_by_name',
                'u.email as created_by_email',
                'td.target_duration_minutes',
            ])
            ->whereIn('s.status', ['completed', 'cancelled']);
    }

    /**
     * Apply basic search filters
     */
    private function applyBasicFilters($query, Request $request)
    {
        $search = trim($request->query('q', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('s.po_number', 'like', $like)
                    ->orWhere('s.ticket_number', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('s.vendor_name', 'like', $like)
                    ->orWhere('w.wh_name', 'like', $like)
                    ->orWhere('s.truck_type', 'like', $like)
                    ->orWhere('s.vehicle_number_snap', 'like', $like)
                    ->orWhere('s.driver_number', 'like', $like)
                    ->orWhere('u.nik', 'like', $like);
            });
        }

        // Specific field searches
        $poSearch = trim($request->query('po', ''));
        if ($poSearch !== '') {
            $query->where('s.po_number', 'like', '%' . $poSearch . '%');
        }

        $ticketSearch = trim($request->query('ticket', ''));
        if ($ticketSearch !== '') {
            $query->where('s.ticket_number', 'like', '%' . $ticketSearch . '%');
        }

        $matDocSearch = trim($request->query('mat_doc', ''));
        if ($matDocSearch !== '') {
            $query->where('s.mat_doc', 'like', '%' . $matDocSearch . '%');
        }

        $userSearch = trim($request->query('user', ''));
        if ($userSearch !== '') {
            $query->where('u.nik', 'like', '%' . $userSearch . '%');
        }

        $vendorSearch = trim($request->query('vendor', ''));
        if ($vendorSearch !== '') {
            $query->where('s.vendor_name', 'like', '%' . $vendorSearch . '%');
        }
    }

    /**
     * Apply date filters
     */
    private function applyDateFilters($query, Request $request)
    {
        $dateFrom = trim($request->query('date_from', ''));
        $dateTo = trim($request->query('date_to', ''));

        // Use COALESCE to include canceled transactions without actual_arrival
        if ($dateFrom !== '') {
            $query->where(function($q) use ($dateFrom) {
                $q->whereDate('s.arrival_time', '>=', $dateFrom)
                  ->orWhereDate('s.created_at', '>=', $dateFrom);
            });
        }
        if ($dateTo !== '') {
            $query->where(function($q) use ($dateTo) {
                $q->whereDate('s.arrival_time', '<=', $dateTo)
                  ->orWhereDate('s.created_at', '<=', $dateTo);
            });
        }

        // Arrival date specific filters
        $arrivalDateFrom = trim($request->query('arrival_date_from', ''));
        $arrivalDateTo = trim($request->query('arrival_date_to', ''));

        if ($arrivalDateFrom !== '') {
            $query->whereDate('s.arrival_time', '>=', $arrivalDateFrom);
        }
        if ($arrivalDateTo !== '') {
            $query->whereDate('s.arrival_time', '<=', $arrivalDateTo);
        }

        $arrivalPresence = trim($request->query('arrival_presence', ''));
        if ($arrivalPresence === 'has') {
            $query->whereNotNull('s.arrival_time');
        } elseif ($arrivalPresence === 'empty') {
            $query->whereNull('s.arrival_time');
        }
    }

    /**
     * Apply status and type filters
     */
    private function applyStatusFilters($query, Request $request)
    {
        $slotTypeArray = (array) $request->query('slot_type', []);
        $statusArray = (array) $request->query('status', []);
        $directionArray = (array) $request->query('direction', []);
        $warehouseArray = (array) $request->query('warehouse_id', []);

        $slotTypeValues = array_values(array_filter($slotTypeArray, fn ($v) => (string) $v !== ''));
        if (!empty($slotTypeValues)) {
            $query->whereIn('s.slot_type', $slotTypeValues);
        }

        $statusValues = array_values(array_filter($statusArray, fn ($v) => (string) $v !== ''));
        if (!empty($statusValues)) {
            $query->whereIn('s.status', $statusValues);
        }

        $directionValues = array_values(array_filter($directionArray, fn ($v) => (string) $v !== ''));
        if (!empty($directionValues)) {
            $query->whereIn('s.direction', $directionValues);
        }

        $warehouseValues = array_values(array_filter($warehouseArray, fn ($v) => (string) $v !== ''));
        if (!empty($warehouseValues)) {
            $query->whereIn('s.warehouse_id', array_map('intval', $warehouseValues));
        }
    }

    /**
     * Apply performance filters (lead time, target achievement, late status)
     */
    private function applyPerformanceFilters($query, Request $request)
    {
        $leadTimeMin = trim($request->query('lead_time_min', ''));
        $leadTimeMax = trim($request->query('lead_time_max', ''));

        $leadExpr = $this->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_finish');
        if ($leadTimeMin !== '' && is_numeric($leadTimeMin)) {
            $query->whereRaw($leadExpr . ' >= ?', [(int) $leadTimeMin]);
        }
        if ($leadTimeMax !== '' && is_numeric($leadTimeMax)) {
            $query->whereRaw($leadExpr . ' <= ?', [(int) $leadTimeMax]);
        }

        // Target achievement filter
        $targetStatusArr = (array) $request->query('target_status', []);
        $targetValues = array_values(array_filter($targetStatusArr, fn ($v) => (string) $v !== ''));

        if (!empty($targetValues)) {
            $needAchieve = in_array('achieve', $targetValues, true);
            $needNotAchieve = in_array('not_achieve', $targetValues, true);

            if ($needAchieve xor $needNotAchieve) {
                $leadExpr = $this->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_finish');

                if ($needAchieve) {
                    $query->whereRaw("td.target_duration_minutes IS NOT NULL AND s.actual_finish IS NOT NULL AND COALESCE(s.actual_start, s.arrival_time) IS NOT NULL AND {$leadExpr} <= td.target_duration_minutes + 15");
                } else {
                    $query->whereRaw("td.target_duration_minutes IS NOT NULL AND s.actual_finish IS NOT NULL AND COALESCE(s.actual_start, s.arrival_time) IS NOT NULL AND {$leadExpr} > td.target_duration_minutes + 15");
                }
            }
        }

        // Late status filter
        $lateArray = (array) $request->query('late', []);
        $lateValues = array_values(array_filter($lateArray, fn ($v) => (string) $v !== ''));

        if (!empty($lateValues)) {
            $needLate = in_array('late', $lateValues, true);
            $needOnTime = in_array('on_time', $lateValues, true);

            if ($needLate xor $needOnTime) {
                $plannedExpr = "(COALESCE(s.slot_type, 'planned') = 'planned' AND s.arrival_time IS NOT NULL)";
                $lateAddExpr = $this->getDateAddExpression('s.planned_start', 15);
                $arrivalLateExpr = $plannedExpr . " AND s.arrival_time > {$lateAddExpr}";
                $arrivalOnTimeExpr = $plannedExpr . " AND s.arrival_time <= {$lateAddExpr}";
                $fallbackLateExpr = "((s.arrival_time IS NULL OR COALESCE(s.slot_type, 'planned') <> 'planned') AND s.is_late = true)";
                $fallbackOnTimeExpr = "((s.arrival_time IS NULL OR COALESCE(s.slot_type, 'planned') <> 'planned') AND (s.is_late = false OR s.is_late IS NULL))";

                if ($needLate) {
                    $query->whereRaw('(' . $arrivalLateExpr . ' OR ' . $fallbackLateExpr . ')');
                } else {
                    $query->whereRaw('(' . $arrivalOnTimeExpr . ' OR ' . $fallbackOnTimeExpr . ')');
                }
            }
        }
    }

    /**
     * Get sort mapping for transactions
     */
    public function getSortMap(): array
    {
        $leadExpr = $this->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_finish');
        $lateAddExpr = $this->getDateAddExpression('s.planned_start', 15);

        return [
            'po' => 's.po_number',
            'ticket' => 's.ticket_number',
            'mat_doc' => 's.mat_doc',
            'vendor' => 's.vendor_name',
            'warehouse' => 'w.wh_name',
            'direction' => 's.direction',
            'arrival' => 's.arrival_time',
            'lead_time' => DB::raw($leadExpr),
            'late' => DB::raw("CASE WHEN (COALESCE(s.slot_type, 'planned') = 'planned' AND s.arrival_time IS NOT NULL) AND s.arrival_time > {$lateAddExpr} THEN 1 WHEN ((s.arrival_time IS NULL OR COALESCE(s.slot_type, 'planned') <> 'planned') AND COALESCE(s.is_late, false) = true) THEN 1 ELSE 0 END"),
            'user' => 'u.name',
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        return [
            'warehouses' => DB::table('md_warehouse')->select(['id', 'wh_name as name', 'wh_code as code'])->orderBy('wh_name')->get(),
            'vendors' => collect(),
        ];
    }
}
