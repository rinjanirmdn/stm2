<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ScheduleTimelineService
{
    public function __construct(
        private readonly SlotService $slotService,
        private readonly TimeCalculationService $timeService
    ) {}

    /**
     * Get schedule data for a specific date
     */
    public function getSchedule(string $date, string $from = '', string $to = ''): array
    {
        $scheduleQ = $this->buildScheduleQuery($date);

        $timeExpr = 'TIME(COALESCE(s.actual_start, s.planned_start))';
        if (DB::getDriverName() === 'pgsql') {
            $timeExpr = 'CAST(COALESCE(s.actual_start, s.planned_start) AS time)';
        }

        if ($from !== '') {
            $scheduleQ->whereRaw($timeExpr . ' >= ?', [$from]);
        }
        if ($to !== '') {
            $scheduleQ->whereRaw($timeExpr . ' <= ?', [$to]);
        }

        $scheduleRows = $scheduleQ
            ->orderByRaw('COALESCE(s.actual_start, s.planned_start) ASC')
            ->get();

        return $this->formatScheduleData($scheduleRows);
    }

    /**
     * Build schedule query
     */
    private function buildScheduleQuery(string $date)
    {
        return DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as g', function ($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                     ->on('s.warehouse_id', '=', 'g.warehouse_id');
            })
            ->where(function($q) use ($date) {
                $q->whereDate('s.actual_start', $date)
                  ->orWhereDate('s.planned_start', $date);
            })
            ->where(function($q) {
                $q->whereNull('s.slot_type')
                  ->orWhere('s.slot_type', '!=', 'unplanned');
            })
            ->whereNotIn('s.status', ['pending_approval', 'pending_vendor_confirmation', 'cancelled'])
            ->select([
                's.id',
                's.status',
                's.is_late',
                's.planned_start',
                's.planned_duration',
                's.blocking_risk',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'g.gate_number',
                's.vendor_name as vendor_name',
            ]);
    }

    /**
     * Get timeline blocks for visualization
     */
    public function getTimelineBlocks(string $date): array
    {
        $timelineRows = $this->buildTimelineQuery($date);
        $timelineBlocksByGate = [];

        foreach ($timelineRows as $r) {
            $gateId = (int) ($r->gate_id ?? 0);
            if ($gateId <= 0) {
                continue;
            }

            $blocks = $this->createTimelineBlocks($r);
            foreach ($blocks as $block) {
                $timelineBlocksByGate[$gateId][] = $block;
            }
        }

        $this->applyComputedPriority($timelineBlocksByGate, $date);

        // Sort blocks by start time for each gate
        foreach ($timelineBlocksByGate as $gateId => $blocks) {
            usort($blocks, function ($a, $b) {
                return (int) ($a['left'] ?? 0) <=> (int) ($b['left'] ?? 0);
            });
            $timelineBlocksByGate[$gateId] = $blocks;
        }

        return $timelineBlocksByGate;
    }

    private function applyComputedPriority(array &$timelineBlocksByGate, string $date): void
    {
        $wh2PlannedGroups = [];

        foreach ($timelineBlocksByGate as $gateId => $blocks) {
            foreach ($blocks as $idx => $b) {
                if (($b['lane'] ?? '') !== 'planned') {
                    continue;
                }

                $slotStatus = (string) ($b['slot_status'] ?? '');
                $waitingMinutes = (int) ($b['waiting_minutes'] ?? 0);
                if ($slotStatus === 'waiting' && $waitingMinutes > 60) {
                    $timelineBlocksByGate[$gateId][$idx]['priority'] = $this->priorityMax(
                        (string) ($b['priority'] ?? 'Low'),
                        'High'
                    );
                }

                $whCode = strtoupper((string) ($b['warehouse_code'] ?? ''));
                if ($whCode !== 'WH2') {
                    continue;
                }
                $plannedStart = (string) ($b['planned_start'] ?? '');
                if ($plannedStart === '') {
                    continue;
                }

                $groupKey = $whCode . '|' . $plannedStart;
                $wh2PlannedGroups[$groupKey][] = [$gateId, $idx];
            }
        }

        foreach ($wh2PlannedGroups as $groupKey => $refs) {
            $hasB = false;
            $hasC = false;
            $arrivalRefs = [];

            foreach ($refs as $ref) {
                [$gateId, $idx] = $ref;
                $b = $timelineBlocksByGate[$gateId][$idx] ?? null;
                if (!$b) continue;

                $gateNum = strtoupper(trim((string) ($b['gate_number'] ?? '')));
                if ($gateNum === 'B') $hasB = true;
                if ($gateNum === 'C') $hasC = true;

                $slotStatus = (string) ($b['slot_status'] ?? '');
                $arrival = (string) ($b['arrival_time'] ?? '');
                if ($slotStatus === 'waiting' && $arrival !== '') {
                    try {
                        $arrivalRefs[] = [$gateId, $idx, (new \DateTime($arrival))->getTimestamp()];
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }

            // Rule: WH2, when planned_start is the same and both B and C exist, C should be prioritized.
            if ($hasB && $hasC) {
                foreach ($refs as $ref) {
                    [$gateId, $idx] = $ref;
                    $b = $timelineBlocksByGate[$gateId][$idx] ?? null;
                    if (!$b) continue;
                    $gateNum = strtoupper(trim((string) ($b['gate_number'] ?? '')));
                    if ($gateNum === 'C') {
                        $timelineBlocksByGate[$gateId][$idx]['priority'] = $this->priorityMax(
                            (string) ($b['priority'] ?? 'Low'),
                            'High'
                        );
                    } elseif ($gateNum === 'B') {
                        $timelineBlocksByGate[$gateId][$idx]['priority'] = $this->priorityMax(
                            (string) ($b['priority'] ?? 'Low'),
                            'Medium'
                        );
                    }
                }
            }

            // Arrival-based boost for waiting: earlier arrival gets higher priority within the same planned_start group.
            if (count($arrivalRefs) >= 2) {
                usort($arrivalRefs, function ($x, $y) {
                    return (int) ($x[2] ?? 0) <=> (int) ($y[2] ?? 0);
                });

                // earliest arrival => +1 rank, others unchanged
                [$g0, $i0] = $arrivalRefs[0];
                $b0 = $timelineBlocksByGate[$g0][$i0] ?? null;
                if ($b0) {
                    $timelineBlocksByGate[$g0][$i0]['priority'] = $this->priorityBump((string) ($b0['priority'] ?? 'Low'), 1);
                }
            }
        }
    }

    private function priorityRank(string $p): int
    {
        $t = strtolower(trim($p));
        if ($t === 'high') return 3;
        if ($t === 'medium') return 2;
        return 1;
    }

    private function priorityFromRank(int $r): string
    {
        if ($r >= 3) return 'High';
        if ($r === 2) return 'Medium';
        return 'Low';
    }

    private function priorityMax(string $a, string $b): string
    {
        return $this->priorityFromRank(max($this->priorityRank($a), $this->priorityRank($b)));
    }

    private function priorityBump(string $p, int $delta): string
    {
        $r = $this->priorityRank($p) + (int) $delta;
        return $this->priorityFromRank(min(3, max(1, $r)));
    }

    /**
     * Get schedule statistics
     */
    public function getScheduleStats(string $date): array
    {
        $stats = [
            'total_slots' => 0,
            'completed_slots' => 0,
            'in_progress_slots' => 0,
            'scheduled_slots' => 0,
            'arrived_slots' => 0,
            'waiting_slots' => 0,
            'late_slots' => 0,
            'on_time_slots' => 0,
        ];

        try {
            $scheduleStats = DB::table('slots as s')
                ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
                ->leftJoin('gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->where(function($q) use ($date) {
                    $q->whereDate('s.actual_start', $date)
                      ->orWhereDate('s.planned_start', $date);
                })
                ->selectRaw('
                    COUNT(*) as total_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as completed_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as in_progress_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as scheduled_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as arrived_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as waiting_slots,
                    SUM(CASE WHEN s.status = ? AND (s.is_late = true) THEN 1 ELSE 0 END) as late_slots,
                    SUM(CASE WHEN s.status = ? AND (s.is_late = false OR s.is_late IS NULL) THEN 1 ELSE 0 END) as on_time_slots
                ', ['completed', 'in_progress', 'scheduled', 'arrived', 'waiting', 'completed', 'completed'])
                ->first();

            if ($scheduleStats) {
                $stats = [
                    'total_slots' => (int) ($scheduleStats->total_slots ?? 0),
                    'completed_slots' => (int) ($scheduleStats->completed_slots ?? 0),
                    'in_progress_slots' => (int) ($scheduleStats->in_progress_slots ?? 0),
                    'scheduled_slots' => (int) ($scheduleStats->scheduled_slots ?? 0),
                    'arrived_slots' => (int) ($scheduleStats->arrived_slots ?? 0),
                    'waiting_slots' => (int) ($scheduleStats->waiting_slots ?? 0),
                    'late_slots' => (int) ($scheduleStats->late_slots ?? 0),
                    'on_time_slots' => (int) ($scheduleStats->on_time_slots ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            // Return default stats on error
        }

        return $stats;
    }

    /**
     * Get upcoming slots for next hours
     */
    public function getUpcomingSlots(string $date, int $hours = 4): array
    {
        $currentTime = date('H:i:s');
        $endTime = date('H:i:s', strtotime("+{$hours} hours"));

        $timeExpr = 'TIME(COALESCE(s.actual_start, s.planned_start))';
        if (DB::getDriverName() === 'pgsql') {
            $timeExpr = 'CAST(COALESCE(s.actual_start, s.planned_start) AS time)';
        }

        $upcoming = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as g', function ($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                    ->on('s.warehouse_id', '=', 'g.warehouse_id');
            })
            ->where(function($q) use ($date) {
                $q->whereDate('s.actual_start', $date)
                  ->orWhereDate('s.planned_start', $date);
            })
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting'])
            ->whereRaw($timeExpr . ' BETWEEN ? AND ?', [$currentTime, $endTime])
            ->orderByRaw('COALESCE(s.actual_start, s.planned_start) ASC')
            ->select([
                's.id',
                's.status',
                's.planned_start',
                's.planned_duration',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'g.gate_number',
                's.vendor_name as vendor_name',
            ])
            ->limit(20)
            ->get();

        return $this->formatScheduleData($upcoming);
    }

    /**
     * Get delayed slots (late arrival)
     */
    public function getDelayedSlots(string $date): array
    {
        $lateExpr = $this->slotService->getDateAddExpression('s.planned_start', 15);

        $delayed = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as g', function ($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                    ->on('s.warehouse_id', '=', 'g.warehouse_id');
            })
            ->where(function($q) use ($date) {
                $q->whereDate('s.actual_start', $date)
                  ->orWhereDate('s.planned_start', $date);
            })
            ->whereRaw("s.arrival_time > {$lateExpr}")
            ->orderBy('s.arrival_time', 'desc')
            ->select([
                's.id',
                's.status',
                's.planned_start',
                's.arrival_time',
                's.planned_duration',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'g.gate_number',
                's.vendor_name as vendor_name',
            ])
            ->limit(10)
            ->get();

        return $this->formatDelayedData($delayed);
    }

    /**
            })
            ->where(function($q) {
                $q->whereNull('s.slot_type')
                  ->orWhere('s.slot_type', '!=', 'unplanned');
            })
            ->whereNotIn('s.status', ['pending_approval', 'pending_vendor_confirmation', 'cancelled'])
            ->select([
                's.id',
                's.status',
                's.is_late',
                's.planned_start',
                's.planned_duration',
                's.blocking_risk',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'g.gate_number',
                's.vendor_name as vendor_name',
            ]);
    }

    /**
     * Build timeline query
     */
    private function buildTimelineQuery(string $date)
    {
        return DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as g', function ($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                    ->on('s.warehouse_id', '=', 'g.warehouse_id');
            })
            ->where(function($q) use ($date) {
                $q->whereDate('s.actual_start', $date)
                  ->orWhereDate('s.planned_start', $date);
            })
            ->where(function($q) {
                $q->whereNull('s.slot_type')
                  ->orWhere('s.slot_type', '!=', 'unplanned');
            })
            ->whereNotIn('s.status', ['pending_approval', 'pending_vendor_confirmation', 'cancelled'])
            ->select([
                's.id',
                's.direction',
                's.status',
                's.is_late',
                's.planned_start',
                's.planned_duration',
                's.actual_start',
                's.actual_finish',
                's.arrival_time',
                's.blocking_risk',
                's.po_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'g.id as gate_id',
                'g.gate_number',
                's.vendor_name as vendor_name',
                's.vendor_type as vendor_type',
            ])
            ->get();
    }

    /**
     * Format schedule data for display
     */
    private function formatScheduleData($rows): array
    {
        $schedule = [];

        foreach ($rows as $r) {
            $eta = !empty($r->planned_start) ? date('H:i', strtotime((string) $r->planned_start)) : '-';
            $estFinish = '-';

            if (!empty($r->planned_start) && !empty($r->planned_duration)) {
                try {
                    $dt = new \DateTime((string) $r->planned_start);
                    $dt->modify('+' . (int) $r->planned_duration . ' minutes');
                    $estFinish = $dt->format('H:i');
                } catch (\Throwable $e) {
                    $estFinish = '-';
                }
            }

            $status = (string) ($r->status ?? '');
            $statusClass = 'st-status-idle';
            if ($status === 'in_progress') {
                $statusClass = 'st-status-processing';
            } elseif ($status === 'completed') {
                $statusClass = !empty($r->is_late) ? 'st-status-late' : 'st-status-on-time';
            }

            $gateLabel = $this->slotService->getGateDisplayName((string) ($r->warehouse_code ?? ''), (string) ($r->gate_number ?? ''));

            $priority = $this->getPriorityLevel($r);
            $performance = $this->getPerformanceStatus($r);

            $schedule[] = [
                'id' => (int) ($r->id ?? 0),
                'po_number' => (string) ($r->po_number ?? ''),
                'vendor_name' => (string) ($r->vendor_name ?? '-'),
                'warehouse_name' => (string) ($r->warehouse_name ?? ''),
                'warehouse_code' => (string) ($r->warehouse_code ?? ''),
                'gate_label' => $gateLabel,
                'eta' => $eta,
                'status' => $status,
                'status_class' => $statusClass,
                'est_finish' => $estFinish,
                'priority' => $priority,
                'performance' => $performance,
            ];
        }

        return $schedule;
    }

    /**
     * Format delayed data
     */
    private function formatDelayedData($rows): array
    {
        $delayed = [];

        foreach ($rows as $r) {
            $delayMinutes = 0;
            if (!empty($r->planned_start) && !empty($r->arrival_time)) {
                try {
                    $planned = new \DateTime((string) $r->planned_start);
                    $arrival = new \DateTime((string) $r->arrival_time);
                    $delay = $arrival->diff($planned);
                    $delayMinutes = ($delay->days * 24 * 60) + ($delay->h * 60) + $delay->i;
                } catch (\Throwable $e) {
                    $delayMinutes = 0;
                }
            }

            $gateLabel = $this->slotService->getGateDisplayName((string) ($r->warehouse_code ?? ''), (string) ($r->gate_number ?? ''));

            $delayed[] = [
                'id' => (int) ($r->id ?? 0),
                'po_number' => (string) ($r->po_number ?? ''),
                'vendor_name' => (string) ($r->vendor_name ?? '-'),
                'warehouse_name' => (string) ($r->warehouse_name ?? ''),
                'gate_label' => $gateLabel,
                'planned_time' => !empty($r->planned_start) ? date('H:i', strtotime((string) $r->planned_start)) : '-',
                'arrival_time' => !empty($r->arrival_time) ? date('H:i', strtotime((string) $r->arrival_time)) : '-',
                'delay_minutes' => $delayMinutes,
                'status' => (string) ($r->status ?? ''),
            ];
        }

        return $delayed;
    }

    /**
     * Create timeline block
     */
    private function createTimelineBlocks($r): array
    {
        $blocks = [];
        $status = (string) ($r->status ?? 'scheduled');
        $priority = $this->getPriorityLevel($r);
        $performance = $this->getPerformanceStatus($r);
        $gateLabel = $this->slotService->getGateDisplayName((string) ($r->warehouse_code ?? ''), (string) ($r->gate_number ?? ''));

        $plannedEndStr = '';
        if (!empty($r->planned_start) && (int) ($r->planned_duration ?? 0) > 0) {
            $plannedEndStr = (string) ($this->timeService->calculateEstimatedFinish(
                (string) $r->planned_start,
                (int) ($r->planned_duration ?? 0)
            ) ?? '');
        }

        $waitingMinutes = 0;
        if (!empty($r->arrival_time) && !empty($r->actual_start)) {
            try {
                $adt = new \DateTime((string) $r->arrival_time);
                $sdt = new \DateTime((string) $r->actual_start);
                $waitingMinutes = (int) floor(max(0, ($sdt->getTimestamp() - $adt->getTimestamp())) / 60);
            } catch (\Throwable $e) {
                $waitingMinutes = 0;
            }
        } elseif ($status === 'waiting' && !empty($r->arrival_time) && empty($r->actual_start)) {
            try {
                $adt = new \DateTime((string) $r->arrival_time);
                $now = new \DateTime();
                $waitingMinutes = (int) floor(max(0, ($now->getTimestamp() - $adt->getTimestamp())) / 60);
            } catch (\Throwable $e) {
                $waitingMinutes = 0;
            }
        }

        $achieveLabel = '';
        if ($status === 'completed' && $performance !== '') {
            $achieveLabel = ($performance === 'late') ? 'Not Achieve' : 'Achieve';
        }

        $base = [
            'id' => (int) ($r->id ?? 0),
            'po_number' => (string) ($r->po_number ?? ''),
            'vendor_name' => (string) ($r->vendor_name ?? '-'),
            'vendor_type' => (string) ($r->vendor_type ?? ''),
            'direction' => (string) ($r->direction ?? ''),
            'slot_status' => $status,
            'warehouse_name' => (string) ($r->warehouse_name ?? ''),
            'warehouse_code' => (string) ($r->warehouse_code ?? ''),
            'gate_id' => (int) ($r->gate_id ?? 0),
            'gate_number' => (string) ($r->gate_number ?? ''),
            'gate_label' => $gateLabel,
            'planned_start' => !empty($r->planned_start) ? (string) $r->planned_start : '',
            'planned_duration' => (int) ($r->planned_duration ?? 0),
            'planned_end' => $plannedEndStr,
            'arrival_time' => !empty($r->arrival_time) ? (string) $r->arrival_time : '',
            'actual_start' => !empty($r->actual_start) ? (string) $r->actual_start : '',
            'actual_finish' => !empty($r->actual_finish) ? (string) $r->actual_finish : '',
            'priority' => $priority,
            'performance' => $performance,
            'waiting_minutes' => $waitingMinutes,
            'achieve_label' => $achieveLabel,
        ];

        if ($status !== 'cancelled' && !empty($r->planned_start) && (int) ($r->planned_duration ?? 0) > 0) {
            $plannedDuration = (int) ($r->planned_duration ?? 0);
            $plannedPos = $this->timeService->getTimeSlotPosition((string) $r->planned_start, $plannedDuration);
            $blocks[] = array_merge($base, [
                'lane' => 'planned',
                'status' => 'planned',
                'eta' => $plannedPos['eta'] ?? '-',
                'est_finish' => $plannedPos['est_finish'] ?? '-',
                'left' => $plannedPos['left'],
                'width' => $plannedPos['width'],
            ]);
        }

        $now = new \DateTime();
        $slotDateStr = '';
        foreach (['planned_start', 'actual_start', 'arrival_time'] as $f) {
            if (!empty($r->{$f})) {
                try {
                    $slotDateStr = (new \DateTime((string) $r->{$f}))->format('Y-m-d');
                    break;
                } catch (\Throwable $e) {
                    $slotDateStr = '';
                }
            }
        }
        $isToday = ($slotDateStr !== '' && $slotDateStr === $now->format('Y-m-d'));

        $actualLaneStatus = '';
        $actualStartStr = '';
        $actualEndStr = '';

        if (in_array($status, ['waiting', 'arrived'], true)) {
            if (!empty($r->arrival_time)) {
                $actualLaneStatus = 'waiting';
                $actualStartStr = (string) $r->arrival_time;
                if ($plannedEndStr !== '') {
                    $actualEndStr = $plannedEndStr;
                } elseif (!empty($r->actual_start)) {
                    $actualEndStr = (string) $r->actual_start;
                } elseif (!empty($r->actual_finish)) {
                    $actualEndStr = (string) $r->actual_finish;
                } elseif ($isToday) {
                    $actualEndStr = $now->format('Y-m-d H:i:s');
                }
            }
        } elseif ($status === 'in_progress') {
            if (!empty($r->actual_start)) {
                $actualLaneStatus = 'in_progress';
                $actualStartStr = (string) $r->actual_start;
                if ($plannedEndStr !== '') {
                    $actualEndStr = $plannedEndStr;
                } elseif (!empty($r->actual_finish)) {
                    $actualEndStr = (string) $r->actual_finish;
                } elseif ($isToday) {
                    $actualEndStr = $now->format('Y-m-d H:i:s');
                }
            }
        } elseif ($status === 'completed') {
            if (!empty($r->arrival_time) && !empty($r->actual_finish)) {
                $actualLaneStatus = 'completed';
                $actualStartStr = (string) $r->arrival_time;
                $actualEndStr = (string) $r->actual_finish;
            }
        }

        if ($actualLaneStatus !== '' && $actualStartStr !== '') {
            $duration = 0;

            $plannedDurationForEnd = (int) ($r->planned_duration ?? 0);
            $usePlannedEndForDuration = in_array($actualLaneStatus, ['waiting', 'in_progress'], true)
                && !empty($r->planned_start)
                && $plannedDurationForEnd > 0;

            if ($usePlannedEndForDuration) {
                $startPos = $this->timeService->getTimeSlotPosition($actualStartStr, 1);
                $plannedStartPos = $this->timeService->getTimeSlotPosition((string) $r->planned_start, 1);
                $startMin = (int) ($startPos['left'] ?? 0);
                $endMin = (int) ($plannedStartPos['left'] ?? 0) + $plannedDurationForEnd;
                $endMin = min(24 * 60, max(0, $endMin));
                $duration = (int) max(0, $endMin - $startMin);
            } elseif ($actualEndStr !== '') {
                try {
                    $sdt = new \DateTime($actualStartStr);
                    $edt = new \DateTime($actualEndStr);
                    $duration = (int) floor(max(0, ($edt->getTimestamp() - $sdt->getTimestamp())) / 60);
                } catch (\Throwable $e) {
                    $duration = 0;
                }
            }
            if ($duration <= 0) {
                $duration = (int) ($r->planned_duration ?? 15);
                if ($duration <= 0) {
                    $duration = 15;
                }
            }

            $actualPos = $this->timeService->getTimeSlotPosition($actualStartStr, $duration);
            $blocks[] = array_merge($base, [
                'lane' => 'actual',
                'status' => $actualLaneStatus,
                'eta' => $actualPos['eta'] ?? '-',
                'est_finish' => $actualPos['est_finish'] ?? '-',
                'left' => $actualPos['left'],
                'width' => $actualPos['width'],
            ]);
        }

        return $blocks;
    }

    /**
     * Get priority level based on blocking risk
     */
    private function getPriorityLevel($r): string
    {
        $blockingRisk = (int) ($r->blocking_risk ?? 0);
        if ($blockingRisk >= 2) {
            return 'High';
        } elseif ($blockingRisk === 1) {
            return 'Medium';
        }
        return 'Low';
    }

    /**
     * Get performance status
     */
    private function getPerformanceStatus($r): string
    {
        $status = (string) ($r->status ?? '');
        if ($status === 'completed') {
            if (!empty($r->planned_start) && (int) ($r->planned_duration ?? 0) > 0 && !empty($r->actual_finish)) {
                try {
                    $pStart = new \DateTime((string) $r->planned_start);
                    $pEnd = (clone $pStart)->modify('+' . (int) $r->planned_duration . ' minutes');
                    $aEnd = new \DateTime((string) $r->actual_finish);
                    return ($aEnd->getTimestamp() > $pEnd->getTimestamp()) ? 'late' : 'ontime';
                } catch (\Throwable $e) {
                    // fallback below
                }
            }
            return !empty($r->is_late) ? 'late' : 'ontime';
        }
        return '';
    }

    /**
     * Get timeline summary statistics
     */
    public function getTimelineSummary(string $date): array
    {
        $blocks = $this->getTimelineBlocks($date);

        $summary = [
            'total_gates' => count($blocks),
            'total_blocks' => 0,
            'completed_blocks' => 0,
            'in_progress_blocks' => 0,
            'scheduled_blocks' => 0,
            'avg_duration' => 0,
        ];

        $totalDuration = 0;
        $blockCount = 0;

        foreach ($blocks as $gateId => $gateBlocks) {
            $summary['total_blocks'] += count($gateBlocks);

            foreach ($gateBlocks as $block) {
                $blockCount++;
                $totalDuration += $block['width'] ?? 0;

                $status = $block['status'] ?? '';
                if ($status === 'completed') {
                    $summary['completed_blocks']++;
                } elseif ($status === 'in_progress') {
                    $summary['in_progress_blocks']++;
                } elseif ($status === 'scheduled') {
                    $summary['scheduled_blocks']++;
                }
            }
        }

        $summary['avg_duration'] = $blockCount > 0 ? round($totalDuration / $blockCount, 2) : 0;

        return $summary;
    }
}
