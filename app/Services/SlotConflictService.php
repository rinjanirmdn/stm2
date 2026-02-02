<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotConflictService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Find in-progress conflicts for a specific gate
     */
    public function findInProgressConflicts(int $actualGateId, int $excludeSlotId = 0): array
    {
        $laneGroup = $this->slotService->getGateLaneGroup($actualGateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [];

        if (empty($laneGateIds)) {
            $laneGateIds = [$actualGateId];
        }

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        $conflicts = $this->queryConflictingSlots($laneGateIds, $today, $now, $excludeSlotId);

        return $conflicts->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Build conflict message for display
     */
    public function buildConflictMessage(array $conflictSlotIds): array
    {
        $details = $this->loadConflictDetails($conflictSlotIds);
        $messages = [];

        foreach ($conflictSlotIds as $slotId) {
            $slotId = (int) $slotId;
            $detail = $details[$slotId] ?? null;

            if ($detail) {
                $messages[] = $this->buildConflictLine($detail);
            } else {
                $messages[] = 'Slot #' . $slotId;
            }
        }

        return $messages;
    }

    /**
     * Get conflict details for slot IDs (public method for controller access)
     */
    public function getConflictDetails(array $slotIds): array
    {
        return $this->loadConflictDetails($slotIds);
    }

    /**
     * Get conflict details for slot IDs
     */
    private function loadConflictDetails(array $slotIds): array
    {
        $slotIds = array_values(array_filter(array_map('intval', $slotIds)));

        if (empty($slotIds)) {
            return [];
        }

        $rows = DB::table('slots as s')
            ->leftJoin('md_gates as g', 's.actual_gate_id', '=', 'g.id')
            ->leftJoin('md_warehouse as w2', 'g.warehouse_id', '=', 'w2.id')
            ->whereIn('s.id', $slotIds)
            ->select([
                's.id',
                's.ticket_number',
                's.status',
                's.actual_start',
                's.po_number as truck_number',
                'g.gate_number as actual_gate_number',
                'w2.wh_code as actual_wh_code',
            ])
            ->get();

        $details = [];
        foreach ($rows as $r) {
            $details[(int) $r->id] = $r;
        }

        return $details;
    }

    /**
     * Build individual conflict line message
     */
    private function buildConflictLine($row): string
    {
        $ticket = trim($row->ticket_number ?? '');
        $po = trim($row->truck_number ?? '');
        $status = trim($row->status ?? '');
        $whCode = trim($row->actual_wh_code ?? '');
        $gateNumber = (string) ($row->actual_gate_number ?? '');

        $gateLabel = $this->buildGateLabel($whCode, $gateNumber);
        $startStr = !empty($row->actual_start) ? (string) $row->actual_start : '';

        $parts = [
            $ticket !== '' ? ('Ticket ' . $ticket) : ('Slot #' . (int) $row->id)
        ];

        if ($po !== '') {
            $parts[] = 'PO ' . $po;
        }

        if ($gateLabel !== '-') {
            $parts[] = $gateLabel;
        }

        if ($startStr !== '') {
            $parts[] = 'Mulai ' . $startStr;
        }

        if ($status !== '') {
            $parts[] = 'Status ' . strtoupper($status);
        }

        return implode(' | ', $parts);
    }

    /**
     * Build gate label for display
     */
    private function buildGateLabel(?string $warehouseCode, ?string $gateNumber): string
    {
        $wh = strtoupper(trim($warehouseCode ?? ''));
        $gateLabel = $this->slotService->getGateDisplayName($wh, (string) $gateNumber);

        if ($wh !== '' && $gateLabel !== '-') {
            return $wh . ' - ' . $gateLabel;
        }

        return $gateLabel;
    }

    /**
     * Query conflicting slots
     */
    private function queryConflictingSlots(array $laneGateIds, string $today, string $now, int $excludeSlotId)
    {
        $query = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->whereDate('actual_start', $today)
            ->where(function($query) use ($now) {
                $query->where('status', 'in_progress')
                      ->where(function($subQuery) use ($now) {
                          $subQuery->whereNull('actual_finish')
                                   ->orWhere('actual_finish', '>', $now);
                      });
            });

        if ($excludeSlotId > 0) {
            $query->where('id', '<>', $excludeSlotId);
        }

        return $query->get();
    }

    /**
     * Check if slot has potential conflicts
     */
    public function hasPotentialConflicts(int $gateId, string $startTime, string $endTime, int $excludeSlotId = 0): bool
    {
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        // Check for completed slots that would make old scheduled slots obsolete
        $this->markObsoleteScheduledSlots($laneGateIds, $startTime, $endTime, $excludeSlotId);

        $conflictingSlots = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->where('id', '<>', $excludeSlotId)
            ->where(function($query) {
                $query->where('status', 'in_progress')
                      ->orWhere('status', 'arrived')
                      ->orWhere('status', 'waiting');
            })
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($sub) use ($startTime, $endTime) {
                    // Slot starts during another slot's time
                    $sub->where('actual_start', '<=', $startTime)
                        ->whereRaw('(actual_finish IS NULL OR actual_finish > ?)', [$startTime]);
                })->orWhere(function($sub) use ($startTime, $endTime) {
                    // Slot ends during another slot's time
                    $sub->where('actual_start', '<', $endTime)
                        ->whereRaw('(actual_finish IS NULL OR actual_finish >= ?)', [$endTime]);
                })->orWhere(function($sub) use ($startTime, $endTime) {
                    // Slot completely overlaps another slot
                    $sub->where('actual_start', '>=', $startTime)
                        ->whereRaw('(actual_finish IS NULL OR actual_finish <= ?)', [$endTime]);
                });
            })
            ->count();

        return $conflictingSlots > 0;
    }

    /**
     * Get conflict suggestions for alternative gates
     */
    public function getAlternativeGates(int $warehouseId, int $excludeGateId = 0): array
    {
        $availableGates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.warehouse_id', $warehouseId)
            ->where('g.is_active', 1)
            ->where('g.id', '<>', $excludeGateId)
            ->select([
                'g.id',
                'g.gate_number',
                'w.wh_code as warehouse_code',
                'w.wh_name as warehouse_name'
            ])
            ->orderBy('g.gate_number')
            ->get();

        $alternatives = [];

        foreach ($availableGates as $gate) {
            $gateId = (int) $gate->id;
            $conflictCount = $this->getCurrentConflictCount($gateId);

            $alternatives[] = [
                'gate_id' => $gateId,
                'gate_number' => $gate->gate_number,
                'warehouse_code' => $gate->warehouse_code,
                'warehouse_name' => $gate->warehouse_name,
                'conflict_count' => $conflictCount,
                'is_recommended' => $conflictCount === 0
            ];
        }

        // Sort by conflict count (lowest first) then by gate number
        usort($alternatives, function($a, $b) {
            if ($a['conflict_count'] !== $b['conflict_count']) {
                return $a['conflict_count'] - $b['conflict_count'];
            }
            return strcasecmp($a['gate_number'], $b['gate_number']);
        });

        return $alternatives;
    }

    /**
     * Mark obsolete scheduled slots when a completed slot exists with overlapping time
     * This handles the case where a booking was made for a future date but the truck
     * arrived earlier and completed the operation, making the old booking obsolete
     */
    private function markObsoleteScheduledSlots(array $laneGateIds, string $startTime, string $endTime, int $excludeSlotId): void
    {
        // Find completed slots that overlap with the new booking time
        $completedSlots = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->where('status', 'completed')
            ->where('id', '<>', $excludeSlotId)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($sub) use ($startTime, $endTime) {
                    // Completed slot overlaps with new booking time
                    $sub->where('actual_start', '<=', $startTime)
                        ->where('actual_finish', '>=', $startTime);
                })->orWhere(function($sub) use ($startTime, $endTime) {
                    $sub->where('actual_start', '<=', $endTime)
                        ->where('actual_finish', '>=', $endTime);
                });
            })
            ->get();

        if ($completedSlots->isEmpty()) {
            return;
        }

        // For each completed slot, mark any overlapping scheduled slots as obsolete
        foreach ($completedSlots as $completedSlot) {
            DB::table('slots')
                ->whereIn('actual_gate_id', $laneGateIds)
                ->where('status', 'scheduled')
                ->where('id', '<>', $excludeSlotId)
                ->where(function($query) use ($completedSlot) {
                    $query->where('planned_start', '>=', $completedSlot->actual_start)
                          ->where('planned_start', '<=', $completedSlot->actual_finish);
                })
                ->update([
                    'status' => 'cancelled',
                    'blocking_risk' => 0,
                    'cancelled_reason' => 'Auto-cancelled: Truck arrived and completed operation earlier',
                    'cancelled_at' => now()
                ]);
        }
    }

    /**
     * Get current conflict count for a gate
     */
    private function getCurrentConflictCount(int $gateId): int
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        return DB::table('slots')
            ->where('actual_gate_id', $gateId)
            ->whereDate('actual_start', $today)
            ->where('status', 'in_progress')
            ->where(function($query) use ($now) {
                $query->whereNull('actual_finish')
                      ->orWhere('actual_finish', '>', $now);
            })
            ->count();
    }
}
