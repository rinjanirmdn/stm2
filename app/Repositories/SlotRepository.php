<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SlotRepository
{
    /**
     * Get database-agnostic DATE_ADD expression
     */
    private function getDateAddExpression(string $date, int $minutes): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return "datetime({$date}, '+{$minutes} minutes')";
        }

        if ($driver === 'pgsql') {
            return "({$date} + ({$minutes}) * interval '1 minute')";
        }

        return "DATE_ADD({$date}, INTERVAL {$minutes} MINUTE)";
    }

    /**
     * Get database-agnostic DATE_ADD expression for column values
     */
    private function getDateAddExpressionWithColumn(string $dateColumn, string $minutesColumn): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return "datetime({$dateColumn}, '+' || {$minutesColumn} || ' minutes')";
        }

        if ($driver === 'pgsql') {
            return "({$dateColumn} + ({$minutesColumn}) * interval '1 minute')";
        }

        return "DATE_ADD({$dateColumn}, INTERVAL {$minutesColumn} MINUTE)";
    }

    /**
     * Get slot detail with related data
     */
    public function getSlotDetail(int $slotId): ?object
    {
        return DB::table('slots as s')
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->leftJoin('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('business_partner as v', 's.bp_id', '=', 'v.id')
            ->leftJoin('gates as g1', 's.planned_gate_id', '=', 'g1.id')
            ->leftJoin('gates as g2', 's.actual_gate_id', '=', 'g2.id')
            ->leftJoin('warehouses as w1', 'g1.warehouse_id', '=', 'w1.id')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id', $slotId)
            ->select([
                's.id',
                's.ticket_number',
                's.mat_doc',
                's.sj_start_number',
                's.sj_complete_number',
                's.truck_type',
                's.vehicle_number_snap',
                's.driver_number',
                's.direction',
                's.po_id',
                's.warehouse_id',
                's.bp_id',
                's.planned_gate_id',
                's.actual_gate_id',
                's.planned_start',
                's.arrival_time',
                's.actual_start',
                's.actual_finish',
                's.planned_duration',
                's.status',
                's.slot_type',
                's.is_late',
                's.late_reason',
                's.cancelled_reason',
                's.cancelled_at',
                's.moved_gate',
                's.blocking_risk',
                's.created_by',
                's.created_at',
                's.updated_at',
                't.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'v.bp_name as vendor_name',
                'g1.gate_number as planned_gate_number',
                'g1.warehouse_id as planned_gate_warehouse_id',
                'w1.wh_name as planned_gate_warehouse_name',
                'w1.wh_code as planned_gate_warehouse_code',
                'g2.gate_number as actual_gate_number',
                'td.target_duration_minutes'
            ])
            ->first();
    }

    /**
     * Get slots with filters and pagination
     */
    public function getSlotsWithFilters(array $filters = [], int $perPage = 50)
    {
        $query = DB::table('slots as s')
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->leftJoin('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('business_partner as v', 's.bp_id', '=', 'v.id')
            ->leftJoin('gates as g', 's.planned_gate_id', '=', 'g.id')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->whereRaw("COALESCE(s.slot_type, 'planned') = 'planned'")
            ->orderByDesc('s.planned_start')
            ->select([
                's.id',
                's.ticket_number',
                's.mat_doc',
                's.sj_start_number',
                's.sj_complete_number',
                's.truck_type',
                's.vehicle_number_snap',
                's.driver_number',
                's.direction',
                's.po_id',
                's.warehouse_id',
                's.bp_id',
                's.planned_gate_id',
                's.actual_gate_id',
                's.planned_start',
                's.arrival_time',
                's.actual_start',
                's.actual_finish',
                's.planned_duration',
                's.status',
                's.slot_type',
                's.is_late',
                's.late_reason',
                's.cancelled_reason',
                's.cancelled_at',
                's.moved_gate',
                's.blocking_risk',
                's.created_by',
                's.created_at',
                's.updated_at',
                't.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'v.bp_name as vendor_name',
                'g.gate_number',
                'td.target_duration_minutes'
            ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('t.po_number', 'like', $search)
                  ->orWhere('s.mat_doc', 'like', $search)
                  ->orWhere('v.bp_name', 'like', $search);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('s.status', $filters['status']);
        }

        if (!empty($filters['warehouse'])) {
            $query->where('s.warehouse_id', $filters['warehouse']);
        }

        if (!empty($filters['gate'])) {
            $query->where('s.planned_gate_id', $filters['gate']);
        }

        if (!empty($filters['direction'])) {
            $query->where('s.direction', $filters['direction']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('s.planned_start', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
        }

        if (!empty($filters['late'])) {
            if ($filters['late'] === '1') {
                $query->where(function ($q) {
                    $q->whereNotNull('s.arrival_time')
                      ->whereRaw('s.arrival_time > s.planned_start');
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('s.arrival_time')
                      ->orWhereRaw('s.arrival_time <= s.planned_start');
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get unplanned slots
     */
    public function getUnplannedSlots(int $limit = 50)
    {
        return DB::table('slots as s')
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->leftJoin('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('business_partner as v', 's.bp_id', '=', 'v.id')
            ->leftJoin('gates as g', 's.actual_gate_id', '=', 'g.id')
            ->whereRaw("COALESCE(s.slot_type, 'planned') = 'unplanned'")
            ->orderByDesc(DB::raw('COALESCE(s.arrival_time, s.planned_start)'))
            ->limit($limit)
            ->select([
                's.id',
                's.ticket_number',
                's.mat_doc',
                's.sj_start_number',
                's.sj_complete_number',
                's.truck_type',
                's.vehicle_number_snap',
                's.driver_number',
                's.direction',
                's.po_id',
                's.warehouse_id',
                's.bp_id',
                's.planned_gate_id',
                's.actual_gate_id',
                's.planned_start',
                's.arrival_time',
                's.actual_start',
                's.actual_finish',
                's.planned_duration',
                's.status',
                's.slot_type',
                's.is_late',
                's.late_reason',
                's.cancelled_reason',
                's.cancelled_at',
                's.moved_gate',
                's.blocking_risk',
                's.created_by',
                's.created_at',
                's.updated_at',
                't.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'v.bp_name as vendor_name',
                'g.gate_number'
            ])
            ->get();
    }

    /**
     * Get active slots for conflict checking
     */
    public function getActiveSlotsInPeriod(string $start, string $end, ?int $excludeSlotId = null)
    {
        $dateAddExpr = $this->getDateAddExpressionWithColumn('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->join('gates as g', 's.planned_gate_id', '=', 'g.id')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->where('s.status', '!=', 'cancelled')
            ->whereRaw('(s.planned_start <= ? AND ? < ' . $dateAddExpr . ')', [$end, $start]);

        if ($excludeSlotId) {
            $query->where('s.id', '<>', $excludeSlotId);
        }

        return $query->select([
            's.id',
            's.planned_start',
            's.planned_duration',
            's.status',
            's.planned_gate_id',
            'g.gate_number',
            'w.wh_code as warehouse_code'
        ])->get();
    }

    /**
     * Get slots by gate for conflict checking
     */
    public function getSlotsByGateInPeriod(int $gateId, string $start, string $end, ?int $excludeSlotId = null)
    {
        $dateAddExpr = $this->getDateAddExpressionWithColumn('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->where('s.status', '!=', 'cancelled')
            ->whereRaw('(s.planned_start <= ? AND ? < ' . $dateAddExpr . ')', [$end, $start]);

        if ($excludeSlotId) {
            $query->where('s.id', '<>', $excludeSlotId);
        }

        return $query->get();
    }

    /**
     * Get in-progress conflicts for a gate
     */
    public function getInProgressConflicts(int $gateId, int $excludeSlotId)
    {
        return DB::table('slots as s')
            ->where('s.actual_gate_id', $gateId)
            ->where('s.status', 'in_progress')
            ->where('s.id', '<>', $excludeSlotId)
            ->select([
                's.id',
                's.planned_start',
                's.actual_start',
                's.planned_duration'
            ])
            ->get();
    }

    /**
     * Create new slot
     */
    public function create(array $data): int
    {
        return DB::table('slots')->insertGetId($data);
    }

    /**
     * Update slot
     */
    public function update(int $slotId, array $data): bool
    {
        return DB::table('slots')->where('id', $slotId)->update($data);
    }

    /**
     * Delete slot
     */
    public function delete(int $slotId): bool
    {
        return DB::table('slots')->where('id', $slotId)->delete();
    }

    /**
     * Get slot statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = DB::table('slots as s')
            ->leftJoin('warehouses as w', 's.warehouse_id', '=', 'w.id');

        // Apply same filters as getSlotsWithFilters
        if (!empty($filters['warehouse'])) {
            $query->where('s.warehouse_id', $filters['warehouse']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('s.planned_start', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
        }

        return [
            'total' => $query->count(),
            'scheduled' => $query->clone()->where('s.status', 'scheduled')->count(),
            'in_progress' => $query->clone()->where('s.status', 'in_progress')->count(),
            'completed' => $query->clone()->where('s.status', 'completed')->count(),
            'cancelled' => $query->clone()->where('s.status', 'cancelled')->count(),
        ];
    }
}
