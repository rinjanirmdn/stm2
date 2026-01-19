<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlotFilterService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Build filtered slots query
     */
    public function filterSlots(Request $request)
    {
        $query = $this->buildBaseQuery();

        $this->applySearchFilters($query, $request);
        $this->applySpecificFilters($query, $request);
        $this->applyDateFilters($query, $request);
        $this->applyStatusFilters($query, $request);
        $this->applyWarehouseFilters($query, $request);
        $this->applyLateFilters($query, $request);
        $this->applyBlockingFilters($query, $request);
        $this->applyTargetStatusFilters($query, $request);

        return $query;
    }

    /**
     * Apply sorting to query
     */
    public function applySorting($query, string $sort, string $dir)
    {
        return $this->applySortingMulti($query, $sort !== '' ? [$sort] : [], $dir !== '' ? [$dir] : []);
    }

    public function applySortingMulti($query, array $sorts, array $dirs)
    {
        $sortMap = $this->getSortMap();
        $applied = 0;

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(fn ($v) => $this->validateSortDirection(trim((string) $v)), $dirs));

        foreach ($sorts as $i => $sort) {
            if (! array_key_exists($sort, $sortMap)) {
                continue;
            }

            $dir = $dirs[$i] ?? 'desc';
            $col = $sortMap[$sort];

            if ($col instanceof \Illuminate\Database\Query\Expression) {
                $query->orderByRaw($col->getValue(DB::connection()->getQueryGrammar()) . ' ' . strtoupper($dir));
            } else {
                $query->orderBy($col, $dir);
            }
            $applied++;
        }

        if ($applied === 0) {
            $query->orderByDesc('s.planned_start');
        }

        return $query;
    }

    public function getSortMap(): array
    {
        $leadMinutesExpr = $this->slotService->getTimestampDiffMinutesExpression('COALESCE(s.actual_start, s.arrival_time)', 's.actual_finish');
        $lateAddExpr = $this->slotService->getDateAddExpression('s.planned_start', 15);

        return [
            'po' => 't.po_number',
            'mat_doc' => 's.mat_doc',
            'vendor' => 'v.bp_name',
            'warehouse' => 'w.wh_name',
            'gate' => 'g.gate_number',
            'direction' => 's.direction',
            'arrival' => 's.arrival_time',
            'planned' => 's.planned_start',
            'status' => 's.status',
            'blocking' => 'COALESCE(s.blocking_risk, 0)',
            'lead_time' => DB::raw($leadMinutesExpr),
            'late' => DB::raw("CASE WHEN s.arrival_time IS NOT NULL AND s.arrival_time > {$lateAddExpr} THEN 1 WHEN s.arrival_time IS NULL AND s.status = 'completed' AND COALESCE(s.is_late, false) = true THEN 1 ELSE 0 END"),
        ];
    }

    /**
     * Apply page size limit
     */
    public function applyPageSize($query, string $pageSize)
    {
        if ($pageSize !== 'all') {
            $query->limit((int) $pageSize);
        }

        return $query;
    }

    /**
     * Build base query for slots
     */
    private function buildBaseQuery()
    {
        return DB::table('slots as s')
            ->select([
                's.*',
                't.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'v.bp_name as vendor_name',
                'g.gate_number',
                'td.target_duration_minutes',
            ])
            ->join('po as t', 's.po_id', '=', 't.id')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('business_partner as v', 's.bp_id', '=', 'v.id')
            ->leftJoin('gates as g', function($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                     ->on('s.warehouse_id', '=', 'g.warehouse_id');
            })
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->whereRaw("COALESCE(s.slot_type, 'planned') <> 'unplanned'");
    }

    /**
     * Apply general search filters
     */
    private function applySearchFilters($query, Request $request)
    {
        $search = trim($request->query('q', ''));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('t.po_number', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('v.bp_name', 'like', $like)
                    ->orWhere('w.wh_name', 'like', $like)
                    ->orWhere('s.direction', 'like', $like)
                    ->orWhere('s.status', 'like', $like);
            });
        }

        // Specific field searches
        $truckSearch = trim($request->query('truck', ''));
        if ($truckSearch !== '') {
            $query->where('t.po_number', 'like', '%' . $truckSearch . '%');
        }

        $vendorSearch = trim($request->query('vendor', ''));
        if ($vendorSearch !== '') {
            $query->where('v.bp_name', 'like', '%' . $vendorSearch . '%');
        }

        $matDocSearch = trim($request->query('mat_doc', ''));
        if ($matDocSearch !== '') {
            $query->where('s.mat_doc', 'like', '%' . $matDocSearch . '%');
        }
    }

    /**
     * Apply specific filters
     */
    private function applySpecificFilters($query, Request $request)
    {
        $arrivalFrom = trim($request->query('arrival_from', ''));
        $arrivalTo = trim($request->query('arrival_to', ''));

        if ($arrivalFrom !== '') {
            $query->whereDate('s.arrival_time', '>=', $arrivalFrom);
        }

        if ($arrivalTo !== '') {
            $query->whereDate('s.arrival_time', '<=', $arrivalTo);
        }

        $leadTimeMin = trim($request->query('lead_time_min', ''));
        $leadTimeMax = trim($request->query('lead_time_max', ''));

        $leadExpr = $this->slotService->getTimestampDiffMinutesExpression('COALESCE(s.actual_start, s.arrival_time)', 's.actual_finish');

        if ($leadTimeMin !== '' && is_numeric($leadTimeMin)) {
            $query->whereRaw($leadExpr . ' >= ?', [(int) $leadTimeMin]);
        }

        if ($leadTimeMax !== '' && is_numeric($leadTimeMax)) {
            $query->whereRaw($leadExpr . ' <= ?', [(int) $leadTimeMax]);
        }
    }

    /**
     * Apply date filters
     */
    private function applyDateFilters($query, Request $request)
    {
        $dateFrom = trim($request->query('date_from', ''));
        $dateTo = trim($request->query('date_to', ''));

        if ($dateFrom !== '' && $dateTo !== '') {
            $query->whereBetween('s.planned_start', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom !== '') {
            $query->whereDate('s.planned_start', '>=', $dateFrom);
        } elseif ($dateTo !== '') {
            $query->whereDate('s.planned_start', '<=', $dateTo);
        }
    }

    /**
     * Apply status filters
     */
    private function applyStatusFilters($query, Request $request)
    {
        $gateFilter = (array) $request->query('gate', []);
        $statusFilter = (array) $request->query('status', []);
        $directionFilter = (array) $request->query('direction', []);

        $gateValues = array_values(array_filter($gateFilter, fn ($v) => (string) $v !== ''));
        if (!empty($gateValues)) {
            $query->whereIn('g.gate_number', $gateValues);
        }

        $statusValues = array_values(array_filter($statusFilter, fn ($v) => (string) $v !== ''));
        if (!empty($statusValues)) {
            $query->whereIn('s.status', $statusValues);
        }

        $dirValues = array_values(array_filter($directionFilter, fn ($v) => (string) $v !== ''));
        if (!empty($dirValues)) {
            $query->whereIn('s.direction', $dirValues);
        }
    }

    /**
     * Apply warehouse filters
     */
    private function applyWarehouseFilters($query, Request $request)
    {
        $warehouseFilter = (array) $request->query('warehouse_id', []);
        $warehouseValues = array_values(array_filter($warehouseFilter, fn ($v) => (string) $v !== ''));

        if (!empty($warehouseValues)) {
            $query->whereIn('s.warehouse_id', array_map('intval', $warehouseValues));
        }
    }

    /**
     * Apply late/on-time filters
     */
    private function applyLateFilters($query, Request $request)
    {
        $lateFilter = (array) $request->query('late', []);
        $lateValues = array_values(array_filter($lateFilter, fn ($v) => (string) $v !== ''));

        $needLate = in_array('late', $lateValues, true);
        $needOnTime = in_array('on_time', $lateValues, true);

        if ($needLate xor $needOnTime) {
            if ($needLate) {
                $query->where(function ($sub) {
                    $sub->where(function ($a) {
                        $a->whereNotNull('s.arrival_time')
                          ->whereRaw('s.arrival_time > ' . $this->slotService->getDateAddExpression('s.planned_start', 15));
                    })->orWhere(function ($b) {
                        $b->whereNull('s.arrival_time')
                          ->where('s.status', 'completed')
                          ->where('s.is_late', true);
                    });
                });
            } else {
                $query->where(function ($sub) {
                    $sub->where(function ($a) {
                        $a->whereNotNull('s.arrival_time')
                          ->whereRaw('s.arrival_time <= ' . $this->slotService->getDateAddExpression('s.planned_start', 15));
                    })->orWhere(function ($b) {
                        $b->whereNull('s.arrival_time')
                          ->where('s.status', 'completed')
                          ->where(function ($c) {
                              $c->where('s.is_late', false)->orWhereNull('s.is_late');
                          });
                    });
                });
            }
        }
    }

    /**
     * Apply blocking risk filters
     */
    private function applyBlockingFilters($query, Request $request)
    {
        $blockingFilter = (array) $request->query('blocking', []);
        $blockingValues = array_values(array_filter($blockingFilter, fn ($v) => (string) $v !== ''));

        $needHigh = in_array('high', $blockingValues, true);
        $needMedium = in_array('medium', $blockingValues, true);
        $needLow = in_array('low', $blockingValues, true);

        if ($needHigh || $needMedium || $needLow) {
            $query->where(function ($sub) use ($needHigh, $needMedium, $needLow) {
                if ($needHigh) {
                    $sub->orWhereRaw('COALESCE(s.blocking_risk, 0) >= 2');
                }
                if ($needMedium) {
                    $sub->orWhereRaw('COALESCE(s.blocking_risk, 0) = 1');
                }
                if ($needLow) {
                    $sub->orWhereRaw('COALESCE(s.blocking_risk, 0) = 0');
                }
            });
        }
    }

    /**
     * Apply target achievement filters
     */
    private function applyTargetStatusFilters($query, Request $request)
    {
        $targetStatusArr = (array) $request->query('target_status', []);
        $targetValues = array_values(array_filter($targetStatusArr, fn ($v) => (string) $v !== ''));

        if (!empty($targetValues)) {
            $needAchieve = in_array('achieve', $targetValues, true);
            $needNotAchieve = in_array('not_achieve', $targetValues, true);

            if ($needAchieve xor $needNotAchieve) {
                $leadExpr = $this->slotService->getTimestampDiffMinutesExpression('COALESCE(s.actual_start, s.arrival_time)', 's.actual_finish');

                if ($needAchieve) {
                    $query->whereRaw("td.target_duration_minutes IS NOT NULL AND s.actual_finish IS NOT NULL AND COALESCE(s.actual_start, s.arrival_time) IS NOT NULL AND {$leadExpr} <= td.target_duration_minutes + 15");
                } else {
                    $query->whereRaw("td.target_duration_minutes IS NOT NULL AND s.actual_finish IS NOT NULL AND COALESCE(s.actual_start, s.arrival_time) IS NOT NULL AND {$leadExpr} > td.target_duration_minutes + 15");
                }
            }
        }
    }

    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        return [
            'warehouses' => DB::table('warehouses')->select(['id', 'wh_name as name', 'wh_code as code'])->orderBy('wh_name')->get(),
            'gates' => DB::table('gates as g')
                ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
                ->select(['g.gate_number', 'g.warehouse_id', 'w.wh_code as warehouse_code'])
                ->orderBy('w.wh_code')
                ->orderBy('g.gate_number')
                ->get(),
        ];
    }

    /**
     * Validate and sanitize page size
     */
    public function validatePageSize(string $pageSize): string
    {
        $pageSizeAllowed = ['10', '25', '50', '100', 'all'];

        if (!in_array($pageSize, $pageSizeAllowed, true)) {
            return 'all';
        }

        return $pageSize;
    }

    /**
     * Validate and sanitize sort direction
     */
    public function validateSortDirection(string $dir): string
    {
        if (!in_array($dir, ['asc', 'desc'], true)) {
            return 'desc';
        }

        return $dir;
    }
}
