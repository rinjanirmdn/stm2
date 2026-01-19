<?php

namespace App\Services;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotService
{
    /**
     * Get database-specific DATE_ADD expression
     */
    public function getDateAddExpression(string $date, $interval): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: datetime(column, '+' || interval || ' minutes')
            return "datetime({$date}, '+' || {$interval} || ' minutes')";
        }

        if ($driver === 'pgsql') {
            return "({$date} + ({$interval}) * interval '1 minute')";
        }

        // MySQL/PostgreSQL: DATE_ADD(column, INTERVAL interval MINUTE)
        return "DATE_ADD({$date}, INTERVAL {$interval} MINUTE)";
    }

    public function getTimestampDiffMinutesExpression(string $startExpr, string $endExpr): string
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
     * Check if a time slot overlaps with existing slots in the same lane
     */
    public function checkLaneOverlap(array $gateIds, string $start, string $end, ?int $excludeSlotId = null): int
    {
        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->whereIn('s.planned_gate_id', $gateIds)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > s.planned_start', [$end]);

        if ($excludeSlotId) {
            $query->where('s.id', '<>', $excludeSlotId);
        }

        return $query->count();
    }

    /**
     * Calculate planned finish time from start time and duration
     */
    public function computePlannedFinish(?string $plannedStart, ?int $plannedDurationMinutes): ?string
    {
        if ($plannedStart === null || $plannedStart === '' || !$plannedDurationMinutes || $plannedDurationMinutes <= 0) {
            return null;
        }
        try {
            $dt = new DateTime($plannedStart);
            $dt->modify('+' . (int) $plannedDurationMinutes . ' minutes');
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get next available slot time for a specific gate
     */
    public function getNextAvailableTime(int $gateId, string $startTime, int $durationMinutes): ?string
    {
        $endTime = $this->getDateAddExpression($startTime, $durationMinutes);

        $conflict = DB::table('slots')
            ->where('actual_gate_id', $gateId)
            ->where('status', '!=', 'completed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereRaw('? < planned_start AND planned_start < ?', [$startTime, $endTime])
                    ->orWhereRaw('? < planned_finish AND planned_finish < ?', [$startTime, $endTime])
                    ->orWhereRaw('planned_start <= ? AND ? <= planned_finish', [$startTime, $endTime]);
            })
            ->orderBy('planned_start')
            ->first();

        if (!$conflict) {
            return $startTime;
        }

        // Return the end time of the conflicting slot
        return $conflict->planned_finish;
    }

    /**
     * Calculate optimal gate assignment based on current load
     */
    public function calculateOptimalGate(int $warehouseId, string $direction): ?int
    {
        $gates = DB::table('gates')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('gate_number')
            ->get();

        $minLoad = PHP_INT_MAX;
        $optimalGate = null;

        foreach ($gates as $gate) {
            $activeCount = DB::table('slots')
                ->where('actual_gate_id', $gate->id)
                ->where('status', '!=', 'completed')
                ->count();

            if ($activeCount < $minLoad) {
                $minLoad = $activeCount;
                $optimalGate = $gate->id;
            }
        }

        return $optimalGate;
    }

    public function logActivity(?int $slotId, string $activityType, string $description, $oldValue = null, $newValue = null, ?int $userId = null): bool
    {
        $createdBy = $userId ?? Auth::id();

        $allowedTypes = [
            'gate_change',
            'status_change',
            'late_arrival',
            'early_arrival',
            'waiting_time',
            'gate_activation',
            'gate_deactivation',
        ];

        $mappedTypes = [
            'arrival_recorded' => 'status_change',
            'arrival_updated' => 'status_change',
        ];

        $activityType = $mappedTypes[$activityType] ?? $activityType;
        if (! in_array($activityType, $allowedTypes, true)) {
            $activityType = 'status_change';
        }

        return DB::table('activity_logs')->insert([
            'slot_id' => $slotId,
            'activity_type' => $activityType,
            'description' => $description,
            'created_by' => $createdBy,
        ]);
    }

    public function getGateLetterByWarehouseAndNumber(string $warehouseCode, string $gateNumber): ?string
    {
        $warehouseCode = strtoupper(trim($warehouseCode));

        $raw = trim((string) $gateNumber);
        $numeric = preg_replace('/\D+/', '', $raw);
        $gateNorm = $numeric !== '' ? $numeric : $raw;

        if ($warehouseCode === 'WH1' && ($gateNorm === '1' || strtoupper($raw) === 'A')) {
            return 'A';
        }

        if ($warehouseCode === 'WH2' && ($gateNorm === '2' || strtoupper($raw) === 'B')) {
            return 'B';
        }

        if ($warehouseCode === 'WH2' && ($gateNorm === '3' || strtoupper($raw) === 'C')) {
            return 'C';
        }

        return null;
    }

    public function getGateDisplayName(?string $warehouseCode, ?string $gateNumber): string
    {
        if ($gateNumber === null || $gateNumber === '') {
            return '-';
        }

        $raw = trim((string) $gateNumber);
        $numeric = preg_replace('/\D+/', '', $raw);
        $gateNorm = $numeric !== '' ? $numeric : $raw;

        $letter = $this->getGateLetterByWarehouseAndNumber((string) $warehouseCode, (string) $gateNorm);
        if ($letter !== null) {
            return 'Gate ' . $letter;
        }

        return 'Gate ' . $gateNorm;
    }

    public function buildLaneGroupFromMeta(string $warehouseCode, string $gateNumber, ?int $fallbackId = null): ?string
    {
        $warehouseCode = strtoupper(trim($warehouseCode));
        $raw = trim((string) $gateNumber);
        $numeric = preg_replace('/\D+/', '', $raw);
        $gateNorm = $numeric !== '' ? $numeric : $raw;

        $letter = $this->getGateLetterByWarehouseAndNumber($warehouseCode, (string) $gateNorm);

        $gateKey = $letter !== null ? $letter : $gateNorm;

        if ($warehouseCode !== '' && $gateKey !== '') {
            return $warehouseCode . '_GATE_' . $gateKey;
        }

        return $fallbackId !== null ? 'GATE_' . $fallbackId : null;
    }

    public function getGateMetaById(int $gateId): ?array
    {
        $row = DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.id', $gateId)
            ->select(['g.gate_number', 'w.wh_code as warehouse_code'])
            ->first();

        if (! $row) {
            return null;
        }

        $warehouseCode = strtoupper(trim((string) ($row->warehouse_code ?? '')));
        $gateNumber = (string) ($row->gate_number ?? '');
        $letter = $this->getGateLetterByWarehouseAndNumber($warehouseCode, $gateNumber);

        return [
            'warehouse_code' => $warehouseCode,
            'gate_number' => $gateNumber,
            'letter' => $letter,
        ];
    }

    public function getGateLaneGroup(int $gateId): ?string
    {
        $meta = $this->getGateMetaById($gateId);
        if (! $meta) {
            return null;
        }

        return $this->buildLaneGroupFromMeta((string) ($meta['warehouse_code'] ?? ''), (string) ($meta['gate_number'] ?? ''), $gateId);
    }

    public function getGateIdsByLaneGroup(string $laneGroup): array
    {
        if ($laneGroup === '') {
            return [];
        }

        $rows = DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->select(['g.id', 'g.gate_number', 'w.wh_code as warehouse_code'])
            ->get();

        $ids = [];
        foreach ($rows as $row) {
            $group = $this->buildLaneGroupFromMeta((string) ($row->warehouse_code ?? ''), (string) ($row->gate_number ?? ''), (int) $row->id);
            if ($group === $laneGroup) {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }

    public function getGateIdByWarehouseCodeAndLetter(string $warehouseCode, string $letter): ?int
    {
        $warehouseCode = strtoupper(trim($warehouseCode));
        $letter = strtoupper(trim($letter));
        if ($warehouseCode === '' || $letter === '') {
            return null;
        }

        $rows = DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->where('w.wh_code', $warehouseCode)
            ->where('g.is_active', 1)
            ->select(['g.id', 'g.gate_number'])
            ->get();

        foreach ($rows as $row) {
            $l = $this->getGateLetterByWarehouseAndNumber($warehouseCode, (string) ($row->gate_number ?? ''));
            if ($l === $letter) {
                return (int) $row->id;
            }
        }

        return null;
    }

    public function validateWh2BcPlannedWindow(int $plannedGateId, DateTime $plannedStartDt, DateTime $plannedEndDt, ?int $excludeSlotId = null): array
    {
        $out = ['ok' => true, 'message' => ''];

        $meta = $this->getGateMetaById($plannedGateId);
        if (! $meta) {
            return $out;
        }

        if (($meta['warehouse_code'] ?? '') !== 'WH2') {
            return $out;
        }

        $letter = $meta['letter'] ?? null;
        if ($letter !== 'B' && $letter !== 'C') {
            return $out;
        }

        $otherLetter = $letter === 'B' ? 'C' : 'B';
        $otherGateId = $this->getGateIdByWarehouseCodeAndLetter('WH2', $otherLetter);
        if (! $otherGateId) {
            return $out;
        }

        $start = $plannedStartDt->format('Y-m-d H:i:s');
        $end = $plannedEndDt->format('Y-m-d H:i:s');

        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $q = DB::table('slots as s')
            ->where('planned_gate_id', $otherGateId)
            ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > planned_start', [$end])
            ->orderBy('planned_start', 'asc')
            ->select(['id', 'planned_start', 'planned_duration']);

        if ($excludeSlotId !== null) {
            $q->where('id', '<>', $excludeSlotId);
        }

        $rows = $q->get();
        if ($rows->isEmpty()) {
            return $out;
        }

        $newStartTs = $plannedStartDt->getTimestamp();
        $newEndTs = $plannedEndDt->getTimestamp();

        foreach ($rows as $row) {
            try {
                $otherStart = new DateTime((string) $row->planned_start);
            } catch (Exception $e) {
                continue;
            }
            $otherEnd = clone $otherStart;
            $otherEnd->modify('+' . (int) ($row->planned_duration ?? 0) . ' minutes');

            $otherStartTs = $otherStart->getTimestamp();
            $otherEndTs = $otherEnd->getTimestamp();

            if ($letter === 'C') {
                if (! ($newStartTs <= $otherStartTs && $newEndTs >= $otherEndTs)) {
                    return [
                        'ok' => false,
                        'message' => 'WH2 Gate B/C rule: Gate C must start at or before Gate B and finish at or after Gate B when times overlap.',
                    ];
                }
            } else {
                if (! ($otherStartTs <= $newStartTs && $otherEndTs >= $newEndTs)) {
                    return [
                        'ok' => false,
                        'message' => 'WH2 Gate B/C rule: Gate C must start at or before Gate B and finish at or after Gate B when times overlap.',
                    ];
                }
            }
        }

        return $out;
    }

    public function generateTicketNumber(int $warehouseId, ?int $gateId = null): string
    {
        $groupLetter = 'T';

        if ($gateId) {
            $row = DB::table('gates as g')
                ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
                ->where('g.id', $gateId)
                ->select(['g.gate_number', 'w.wh_code as warehouse_code'])
                ->first();

            if ($row) {
                $whCode = (string) ($row->warehouse_code ?? '');
                $gateNumber = (string) ($row->gate_number ?? '');

                $raw = trim($gateNumber);
                $numeric = preg_replace('/\D+/', '', $raw);
                $gateNorm = $numeric !== '' ? $numeric : strtoupper($raw);

                // Format yang diinginkan:
                // A = WH1 G1
                // B = WH2 G1
                // C = WH2 G2
                if ($whCode === 'WH1' && ($gateNorm === '1' || strtoupper($raw) === 'A')) {
                    $groupLetter = 'A';
                } elseif ($whCode === 'WH2' && ($gateNorm === '1' || strtoupper($raw) === 'B')) {
                    $groupLetter = 'B';
                } elseif ($whCode === 'WH2' && ($gateNorm === '2' || strtoupper($raw) === 'C')) {
                    $groupLetter = 'C';
                } elseif ($whCode === 'WH1') {
                    $groupLetter = 'A';
                } elseif ($whCode === 'WH2') {
                    // fallback default WH2 -> Gate 1 group
                    $groupLetter = 'B';
                }
            }
        }

        if ($groupLetter === 'T') {
            $row = DB::table('warehouses')->where('id', $warehouseId)->select(['wh_code'])->first();
            if ($row) {
                $whCode = (string) ($row->wh_code ?? '');
                if ($whCode === 'WH1') {
                    $groupLetter = 'A';
                }

                if ($whCode === 'WH2') {
                    $groupLetter = 'B';
                }
            }
        }

        $yearPart = date('y');
        $monthNum = (int) date('n');
        $monthLetter = chr(ord('A') + max(0, $monthNum - 1));

        $prefix = $groupLetter . $yearPart . $monthLetter;

        $lastTicket = DB::table('slots')
            ->where('ticket_number', 'like', $prefix . '%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $seq = 1;
        if (is_string($lastTicket) && preg_match('/(\d{4})$/', $lastTicket, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all slots for a specific gate on the same day (for time matching logic)
     */
    private function getAllSlotsForGateOnSameDay(?int $gateId, string $start, ?int $excludeSlotId = null): \Illuminate\Support\Collection
    {
        if (!$gateId) {
            return collect([]);
        }

        // Get only the date part for same day comparison
        $dateOnly = substr($start, 0, 10); // Y-m-d format

        $query = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereDate('s.planned_start', $dateOnly);

        if ($excludeSlotId) {
            $query->where('s.id', '<>', $excludeSlotId);
        }

        return $query->select(['s.planned_start', 's.planned_duration'])->get();
    }

    /**
     * Get existing slots for a specific gate within time range
     */
    private function getExistingSlotsForGate(?int $gateId, string $start, string $end, ?int $excludeSlotId = null): \Illuminate\Support\Collection
    {
        if (!$gateId) {
            return collect([]);
        }

        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > s.planned_start', [$end]);

        if ($excludeSlotId) {
            $query->where('s.id', '<>', $excludeSlotId);
        }

        return $query->select(['s.planned_start', 's.planned_duration'])->get();
    }

    public function calculateBlockingRisk(int $warehouseId, ?int $plannedGateId, string $plannedStart, int $plannedDurationMinutes, ?int $excludeSlotId = null): int
    {
        // If excludeSlotId is provided, check if it's cancelled - if so, return low risk
        if ($excludeSlotId) {
            $slotStatus = DB::table('slots')->where('id', $excludeSlotId)->value('status');
            if ($slotStatus === 'cancelled') {
                return 0; // Low risk for cancelled slots
            }
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (Exception $e) {
            return 0;
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $start = $startDt->format('Y-m-d H:i:s');
        $end = $endDt->format('Y-m-d H:i:s');

        $bcLetter = null;
        $bcOtherGateId = null;
        if ($plannedGateId) {
            $meta = $this->getGateMetaById($plannedGateId);
            if ($meta && ($meta['warehouse_code'] ?? '') === 'WH2') {
                $letter = $meta['letter'] ?? null;
                if ($letter === 'B' || $letter === 'C') {
                    $bcLetter = $letter;
                    $otherLetter = $letter === 'B' ? 'C' : 'B';
                    $bcOtherGateId = $this->getGateIdByWarehouseCodeAndLetter('WH2', $otherLetter);
                }
            }
        }

        // WH2 Gate B/C flexible blocking rules
        if ($plannedGateId && ($bcLetter === 'B' || $bcLetter === 'C')) {
            $bcCheck = $this->validateWh2BcPlannedWindow($plannedGateId, $startDt, $endDt, $excludeSlotId);
            if (empty($bcCheck['ok'])) {
                return 2; // High risk jika tidak memenuhi aturan
            }

            // Logika khusus untuk blocking risk berdasarkan kondisi yang ada
            if ($bcLetter === 'C') {
                // Untuk Gate C, cek apakah ada overlapping dengan Gate B
                $existingBSlots = $this->getExistingSlotsForGate($bcOtherGateId, $start, $end, $excludeSlotId);

                if ($existingBSlots->isNotEmpty()) {
                    foreach ($existingBSlots as $bSlot) {
                        $bStart = new DateTime($bSlot->planned_start);
                        $bEnd = clone $bStart;
                        $bEnd->modify('+' . (int) $bSlot->planned_duration . ' minutes');

                        // Kondisi 2 & 3: Jam masuk/keluar bersamaan
                        if (($bStart->format('H:i') === $startDt->format('H:i')) ||
                            ($bEnd->format('H:i') === $endDt->format('H:i'))) {
                            return 1; // Medium risk
                        }

                        // Kondisi 4: Durasi Gate C lebih lama dari Gate B
                        if ($plannedDurationMinutes > (int) $bSlot->planned_duration) {
                            return 0; // Low risk
                        }

                        // Jika ada overlapping waktu (tapi tidak jam masuk/keluar sama persis)
                        // dan durasi Gate C tidak lebih lama, tetap Medium karena Gate C di belakang
                        return 1; // Medium risk
                    }
                }

                // Default untuk Gate C jika tidak ada overlapping
                return 0; // Low risk
            }

            if ($bcLetter === 'B') {
                // Untuk Gate B, cek apakah ada overlapping dengan Gate C
                $existingCSlots = $this->getExistingSlotsForGate($bcOtherGateId, $start, $end, $excludeSlotId);

                if ($existingCSlots->isNotEmpty()) {
                    foreach ($existingCSlots as $cSlot) {
                        $cStart = new DateTime($cSlot->planned_start);
                        $cEnd = clone $cStart;
                        $cEnd->modify('+' . (int) $cSlot->planned_duration . ' minutes');

                        // Kondisi 2 & 3: Jam masuk/keluar bersamaan
                        if (($cStart->format('H:i') === $startDt->format('H:i')) ||
                            ($cEnd->format('H:i') === $endDt->format('H:i'))) {
                            return 0; // Low risk untuk Gate B
                        }

                        // Kondisi 4: Durasi Gate C lebih lama dari Gate B
                        if ((int) $cSlot->planned_duration > $plannedDurationMinutes) {
                            return 0; // Low risk untuk Gate B
                        }

                        // Jika ada overlapping waktu lainnya, tetap Low karena Gate B di depan
                        return 0; // Low risk
                    }
                }

                // Default untuk Gate B
                return 0; // Low risk
            }
        }

        // WH1 Gate A special logic - medium risk if entry/exit time matches existing slot
        if ($plannedGateId) {
            $meta = $this->getGateMetaById($plannedGateId);

            if ($meta && ($meta['warehouse_code'] ?? '') === 'WH1' && ($meta['letter'] ?? '') === 'A') {
                // Get ALL slots for Gate A on the same day (not just overlapping)
                $existingASlots = $this->getAllSlotsForGateOnSameDay($plannedGateId, $start, $excludeSlotId);

                if ($existingASlots->isNotEmpty()) {
                    foreach ($existingASlots as $aSlot) {
                        $aStart = new DateTime($aSlot->planned_start);
                        $aEnd = clone $aStart;
                        $aEnd->modify('+' . (int) $aSlot->planned_duration . ' minutes');

                        $newEntryTime = $startDt->format('H:i');
                        $newExitTime = $endDt->format('H:i');
                        $existingEntryTime = $aStart->format('H:i');
                        $existingExitTime = $aEnd->format('H:i');

                        // Check if entry time matches existing exit time OR exit time matches existing entry time
                        if (($aStart->format('H:i') === $endDt->format('H:i')) ||  // New entry = existing exit
                            ($aEnd->format('H:i') === $startDt->format('H:i'))) {   // New exit = existing entry
                            return 1; // Medium risk for Gate A
                        }
                    }
                }
            }
        }

        $overlapGate = 0;
        if ($plannedGateId) {
            $laneGroup = $this->getGateLaneGroup($plannedGateId);
            $laneGateIds = $laneGroup ? $this->getGateIdsByLaneGroup($laneGroup) : [];
            if (empty($laneGateIds)) {
                $laneGateIds = [$plannedGateId];
            }

            $overlapGate = $this->checkLaneOverlap($laneGateIds, $start, $end, $excludeSlotId);
        }

        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $overlapWarehouseQuery = DB::table('slots as s')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > planned_start', [$end]);

        if ($excludeSlotId) {
            $overlapWarehouseQuery->where('s.id', '<>', $excludeSlotId);
        }

        // WH2 Gate B/C rule: do not count the paired gate as "warehouse overlap".
        // Risk for this pair is handled explicitly via $bcEdgeRaw.
        if ($bcOtherGateId) {
            $overlapWarehouseQuery->where('s.planned_gate_id', '<>', $bcOtherGateId);
        }

        $overlapWarehouse = (int) $overlapWarehouseQuery->count();

        $bcEdgeRaw = 0;
        if ($bcLetter === 'C' && $bcOtherGateId) {
            // Gate C is "behind" Gate B.
            // Medium risk ONLY when:
            // - Gate B finishes exactly when Gate C starts (edge touch), OR
            // - Gate B has the exact same time window as Gate C (same start AND same end).
            // If Gate C contains Gate B (e.g., starts same but finishes later), keep it Low.

            $otherDateAddExpr = $this->getDateAddExpression('o.planned_start', 'o.planned_duration');

            $edgeTouchOrSameWindow = (int) DB::table('slots as o')
                ->where('o.planned_gate_id', $bcOtherGateId)
                ->whereIn('o.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
                ->where(function ($q) use ($start, $end, $otherDateAddExpr) {
                    $q->whereRaw("{$otherDateAddExpr} = ?", [$start]) // Gate B finishes when Gate C starts
                      ->orWhere(function ($q2) use ($start, $end, $otherDateAddExpr) {
                          $q2->where('o.planned_start', '=', $start)
                             ->whereRaw("{$otherDateAddExpr} = ?", [$end]);
                      });
                })
                ->count();

            if ($edgeTouchOrSameWindow > 0) {
                $bcEdgeRaw = 1;
            }
        }

        $warehouseCode = null;
        $row = DB::table('warehouses')->where('id', $warehouseId)->select(['wh_code'])->first();
        if ($row) {
            $warehouseCode = $row->wh_code ?? null;
        }

        $overlapWH1 = 0;
        if ($warehouseCode === 'WH2') {
            $wh1 = DB::table('warehouses')->where('wh_code', 'WH1')->select(['id'])->first();
            if ($wh1) {
                $overlapWH1 = (int) DB::table('slots as s')
                    ->where('warehouse_id', (int) $wh1->id)
                    ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
                    ->whereRaw("? < {$dateAddExpr}", [$start])
                    ->whereRaw('? > planned_start', [$end])
                    ->count();
            }
        }

        $raw = 0;
        $raw += $overlapGate * 2;
        $raw += $overlapWarehouse * 1;
        $raw += $bcEdgeRaw;

        if ($warehouseCode === 'WH2' && $overlapWarehouse > 0 && $overlapWH1 > 0) {
            $raw += 2;
        }

        if ($raw <= 0) {
            return 0;  // Low
        }
        if ($raw <= 3) {
            return 1;  // Medium
        }
        return 2;      // High
    }
}
