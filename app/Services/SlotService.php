<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Milon\Barcode\DNS1D;
use Throwable;

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
            $query->where('s.id_slots', '<>', $excludeSlotId);
        }

        return $query->count();
    }

    public function countWarehouseOverlap(int $warehouseId, array $statuses, string $start, string $end, ?int $excludeSlotId = null): int
    {
        $warehouseId = (int) $warehouseId;
        if ($warehouseId <= 0) {
            return 0;
        }

        $statuses = array_values(array_filter(array_map('strval', $statuses), fn ($s) => trim($s) !== ''));
        if (empty($statuses)) {
            return 0;
        }

        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->where('s.warehouse_id', $warehouseId)
            ->whereIn('s.status', $statuses)
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > s.planned_start', [$end]);

        if ($excludeSlotId) {
            $query->where('s.id_slots', '<>', $excludeSlotId);
        }

        return $query->count();
    }

    /**
     * Calculate planned finish time from start time and duration
     */
    public function computePlannedFinish(?string $plannedStart, ?int $plannedDurationMinutes): ?string
    {
        if ($plannedStart === null || $plannedStart === '' || $plannedDurationMinutes === null || $plannedDurationMinutes < 0) {
            return null;
        }
        try {
            $dt = new DateTime($plannedStart);
            $dt->modify('+'.(int) $plannedDurationMinutes.' minutes');

            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get next available e-DCS for a specific gate
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

        if (! $conflict) {
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
        $gates = DB::table('md_gates')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('gate_number')
            ->get();

        $minLoad = PHP_INT_MAX;
        $optimalGate = null;

        foreach ($gates as $gate) {
            $activeCount = DB::table('slots')
                ->where('actual_gate_id', $gate->id_gates)
                ->where('status', '!=', 'completed')
                ->count();

            if ($activeCount < $minLoad) {
                $minLoad = $activeCount;
                $optimalGate = $gate->id_gates;
            }
        }

        return $optimalGate;
    }

    public function logActivity(?int $slotId, string $activityType, string $description, $oldValue = null, $newValue = null, ?int $userId = null, ?string $feature = null): bool
    {
        $createdBy = $userId ?? Auth::id();

        if (is_array($oldValue) || is_object($oldValue)) {
            $oldValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (is_array($newValue) || is_object($newValue)) {
            $newValue = json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Capitalize description properly
        $description = $this->capitalizeDescription($description);

        // Map old activity types → new system (insert / update / delete / auth)
        $typeFeatureMap = [
            'gate_change' => ['update', 'Planned'],
            'status_change' => ['update', null],    // feature determined by caller
            'late_arrival' => ['update', null],
            'early_arrival' => ['update', null],
            'waiting_time' => ['update', null],
            'gate_activation' => ['update', 'Gate Management'],
            'gate_deactivation' => ['update', 'Gate Management'],
            'backdate' => ['update', null],
            'arrival_recorded' => ['update', null],
            'arrival_updated' => ['update', null],
            'create' => ['insert', null],
            'insert' => ['insert', null],
            'edit' => ['update', null],
            'update' => ['update', null],
            'crud' => ['update', null],
            'delete' => ['delete', null],
        ];

        $mapped = $typeFeatureMap[$activityType] ?? ['update', null];
        $activityType = $mapped[0];

        // Normalize feature name (handle legacy names)
        if ($feature === 'Planned') {
            $feature = 'Planned';
        }
        if ($feature === 'Unplanned') {
            $feature = 'Unplanned';
        }
        if ($feature === 'Booking Requests' || $feature === 'Booking') {
            $feature = 'Booking Requests';
        }

        // Use explicitly passed feature, then mapped feature, then fallback 'System'
        $feature = $feature ?? $mapped[1] ?? 'System';

        $payload = [];

        if (Schema::hasColumn('activity_logs', 'description')) {
            $payload['description'] = $description;
        }

        if (Schema::hasColumn('activity_logs', 'sj_no')) {
            $payload['sj_no'] = null;
        }

        if (Schema::hasColumn('activity_logs', 'po_number')) {
            $payload['po_number'] = null;
        }

        if (Schema::hasColumn('activity_logs', 'slot_id')) {
            $payload['slot_id'] = $slotId;
        }

        if (Schema::hasColumn('activity_logs', 'activity_type')) {
            $payload['activity_type'] = $activityType;
        } elseif (Schema::hasColumn('activity_logs', 'type')) {
            $payload['type'] = $activityType;
        }

        if (Schema::hasColumn('activity_logs', 'feature')) {
            $payload['feature'] = $feature;
        }

        if (Schema::hasColumn('activity_logs', 'created_by')) {
            $payload['created_by'] = $createdBy;
        } elseif (Schema::hasColumn('activity_logs', 'user_id')) {
            $payload['user_id'] = $createdBy;
        }

        if (Schema::hasColumn('activity_logs', 'old_value')) {
            $payload['old_value'] = $oldValue;
        }

        if (Schema::hasColumn('activity_logs', 'new_value')) {
            $payload['new_value'] = $newValue;
        }

        // Some environments (especially older/manual DB schemas) may not have Laravel timestamps.
        if (Schema::hasColumn('activity_logs', 'created_at')) {
            $payload['created_at'] = now();
        }
        if (Schema::hasColumn('activity_logs', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return DB::table('activity_logs')->insert($payload);
    }

    /**
     * Capitalize description with proper title case
     * First letter of each word capitalized, except conjunctions
     */
    private function capitalizeDescription(string $description): string
    {
        // Split description into template part and data part (in parentheses)
        // e.g. "Arrival Recorded with Ticket A26E0001" -> capitalize words but preserve data tokens
        // Data tokens: anything that looks like a code (contains digits+letters mixed, or is all-uppercase)
        $words = explode(' ', $description);
        $capitalized = [];

        $conjunctions = ['and', 'or', 'but', 'for', 'nor', 'on', 'at', 'to', 'from', 'with', 'in'];

        foreach ($words as $index => $word) {
            if (trim($word) === '') {
                $capitalized[] = $word;

                continue;
            }

            // Preserve data tokens: contains digits, is all-uppercase, or looks like a code/identifier
            if (preg_match('/\d/', $word) || preg_match('/^[A-Z0-9_\-\/\.#]+$/', $word) || preg_match('/^[A-Z]{2,}/', $word)) {
                $capitalized[] = $word;

                continue;
            }

            // Always capitalize first word
            if ($index === 0) {
                $capitalized[] = ucfirst($word);

                continue;
            }

            // Check if word is a conjunction (case insensitive)
            $lowerWord = strtolower($word);
            if (in_array($lowerWord, $conjunctions, true)) {
                $capitalized[] = strtolower($word);
            } else {
                $capitalized[] = ucfirst($word);
            }
        }

        return implode(' ', $capitalized);
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
            return 'N/A';
        }

        $raw = trim((string) $gateNumber);
        $numeric = preg_replace('/\D+/', '', $raw);
        $gateNorm = $numeric !== '' ? $numeric : $raw;

        $letter = $this->getGateLetterByWarehouseAndNumber((string) $warehouseCode, (string) $gateNorm);
        if ($letter !== null) {
            // Map internal letter (A/B/C) to display number (1/2/3)
            $letterToNumber = ['A' => '1', 'B' => '2', 'C' => '3'];
            $displayNumber = $letterToNumber[$letter] ?? $letter;

            return 'Gate '.$displayNumber;
        }

        return 'Gate '.strtoupper($gateNorm);
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
            return $warehouseCode.'_GATE_'.$gateKey;
        }

        return $fallbackId !== null ? 'GATE_'.$fallbackId : null;
    }

    public function getGateMetaById(int $gateId): ?array
    {
        $row = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id_wh')
            ->where('g.id_gates', $gateId)
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

        $rows = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id_wh')
            ->where('g.is_active', 1)
            ->select(['g.id_gates', 'g.gate_number', 'w.wh_code as warehouse_code'])
            ->get();

        $ids = [];
        foreach ($rows as $row) {
            $group = $this->buildLaneGroupFromMeta((string) ($row->warehouse_code ?? ''), (string) ($row->gate_number ?? ''), (int) $row->id_gates);
            if ($group === $laneGroup) {
                $ids[] = (int) $row->id_gates;
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

        $rows = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id_wh')
            ->where('w.wh_code', $warehouseCode)
            ->where('g.is_active', 1)
            ->select(['g.id_gates', 'g.gate_number'])
            ->get();

        foreach ($rows as $row) {
            $l = $this->getGateLetterByWarehouseAndNumber($warehouseCode, (string) ($row->gate_number ?? ''));
            if ($l === $letter) {
                return (int) $row->id_gates;
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
            ->select(['id_slots', 'planned_start', 'planned_duration']);

        if ($excludeSlotId !== null) {
            $q->where('id_slots', '<>', $excludeSlotId);
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
            $otherEnd->modify('+'.(int) ($row->planned_duration ?? 0).' minutes');

            $otherStartTs = $otherStart->getTimestamp();
            $otherEndTs = $otherEnd->getTimestamp();

            if ($letter === 'C') {
                if (! ($newStartTs <= $otherStartTs && $newEndTs >= $otherEndTs)) {
                    return [
                        'ok' => false,
                        'message' => 'WH2 Gate 3/2 rule: Gate 3 must start at or before Gate 2 and finish at or after Gate 2 when times overlap.',
                    ];
                }
            } else {
                if (! ($otherStartTs <= $newStartTs && $otherEndTs >= $newEndTs)) {
                    return [
                        'ok' => false,
                        'message' => 'WH2 Gate 3/2 rule: Gate 3 must start at or before Gate 2 and finish at or after Gate 2 when times overlap.',
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
            $row = DB::table('md_gates as g')
                ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id_wh')
                ->where('g.id_gates', $gateId)
                ->select(['g.gate_number', 'w.wh_code as warehouse_code'])
                ->first();

            if ($row) {
                $whCode = (string) ($row->warehouse_code ?? '');
                $gateNumber = (string) ($row->gate_number ?? '');

                $raw = trim($gateNumber);
                $numeric = preg_replace('/\D+/', '', $raw);
                $gateNorm = $numeric !== '' ? $numeric : strtoupper($raw);

                // Format yang diinginkan:
                // 1 = WH1 Gate 1
                // 2 = WH2 Gate 2
                // 3 = WH2 Gate 3
                if ($whCode === 'WH1' && ($gateNorm === '1' || strtoupper($raw) === 'A')) {
                    $groupLetter = 'A';
                } elseif ($whCode === 'WH2' && ($gateNorm === '2' || strtoupper($raw) === 'B')) {
                    $groupLetter = 'B';
                } elseif ($whCode === 'WH2' && ($gateNorm === '3' || strtoupper($raw) === 'C')) {
                    $groupLetter = 'C';
                } elseif ($whCode === 'WH1') {
                    $groupLetter = 'A';
                } elseif ($whCode === 'WH2') {
                    // fallback default WH2 -> Gate 2 group
                    $groupLetter = 'B';
                }
            }
        }

        if ($groupLetter === 'T') {
            $row = DB::table('md_warehouse')->where('id_wh', $warehouseId)->select(['wh_code'])->first();
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

        $prefix = $groupLetter.$yearPart.$monthLetter;

        $lastTicket = DB::table('slots')
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $seq = 1;
        if (is_string($lastTicket) && preg_match('/(\d{4})$/', $lastTicket, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all slots for a specific gate on the same day (for time matching logic)
     */
    private function getAllSlotsForGateOnSameDay(?int $gateId, string $start, ?int $excludeSlotId = null): Collection
    {
        if (! $gateId) {
            return collect([]);
        }

        // Get only the date part for same day comparison
        $dateOnly = substr($start, 0, 10); // Y-m-d format

        $query = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereDate('s.planned_start', $dateOnly);

        if ($excludeSlotId) {
            $query->where('s.id_slots', '<>', $excludeSlotId);
        }

        return $query->select(['s.planned_start', 's.planned_duration'])->get();
    }

    /**
     * Get existing slots for a specific gate within time range
     */
    private function getExistingSlotsForGate(?int $gateId, string $start, string $end, ?int $excludeSlotId = null): Collection
    {
        if (! $gateId) {
            return collect([]);
        }

        $dateAddExpr = $this->getDateAddExpression('s.planned_start', 's.planned_duration');

        $query = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > s.planned_start', [$end]);

        if ($excludeSlotId) {
            $query->where('s.id_slots', '<>', $excludeSlotId);
        }

        return $query->select(['s.planned_start', 's.planned_duration'])->get();
    }

    public function calculateBlockingRisk(int $warehouseId, ?int $plannedGateId, string $plannedStart, int $plannedDurationMinutes, ?int $excludeSlotId = null): int
    {
        // If excludeSlotId is provided, check if it's cancelled - if so, return low risk
        if ($excludeSlotId) {
            $slotStatus = DB::table('slots')->where('id_slots', $excludeSlotId)->value('status');
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
        $endDt->modify('+'.(int) $plannedDurationMinutes.' minutes');

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

        // WH2 Gate 2/3 flexible blocking rules
        if ($plannedGateId && ($bcLetter === 'B' || $bcLetter === 'C')) {
            $bcCheck = $this->validateWh2BcPlannedWindow($plannedGateId, $startDt, $endDt, $excludeSlotId);
            if (empty($bcCheck['ok'])) {
                return 2; // High risk when rule requirements are not met
            }

            // Special blocking-risk logic based on existing conditions
            if ($bcLetter === 'C') {
                // For Gate 3, check whether it overlaps with Gate 2
                $existingBSlots = $this->getExistingSlotsForGate($bcOtherGateId, $start, $end, $excludeSlotId);

                if ($existingBSlots->isNotEmpty()) {
                    foreach ($existingBSlots as $bSlot) {
                        $bStart = new DateTime($bSlot->planned_start);
                        $bEnd = clone $bStart;
                        $bEnd->modify('+'.(int) $bSlot->planned_duration.' minutes');

                        // Condition 2 & 3: same entry/exit time
                        if (($bStart->format('H:i') === $startDt->format('H:i')) ||
                            ($bEnd->format('H:i') === $endDt->format('H:i'))) {
                            return 1; // Medium risk
                        }

                        // Condition 4: Gate 3 duration is longer than Gate 2
                        if ($plannedDurationMinutes > (int) $bSlot->planned_duration) {
                            return 0; // Low risk
                        }

                        // If there is another overlap (but entry/exit times are not exactly the same)
                        // and Gate 3 duration is not longer, keep Medium because Gate 3 is behind
                        return 1; // Medium risk
                    }
                }

                // Default for Gate 3 if there is no overlap
                return 0; // Low risk
            }

            if ($bcLetter === 'B') {
                // For Gate 2, check whether it overlaps with Gate 3
                $existingCSlots = $this->getExistingSlotsForGate($bcOtherGateId, $start, $end, $excludeSlotId);

                if ($existingCSlots->isNotEmpty()) {
                    foreach ($existingCSlots as $cSlot) {
                        $cStart = new DateTime($cSlot->planned_start);
                        $cEnd = clone $cStart;
                        $cEnd->modify('+'.(int) $cSlot->planned_duration.' minutes');

                        // Condition 2 & 3: same entry/exit time
                        if (($cStart->format('H:i') === $startDt->format('H:i')) ||
                            ($cEnd->format('H:i') === $endDt->format('H:i'))) {
                            return 0; // Low risk for Gate 2
                        }

                        // Condition 4: Gate 3 duration is longer than Gate 2
                        if ((int) $cSlot->planned_duration > $plannedDurationMinutes) {
                            return 0; // Low risk for Gate 2
                        }

                        // For other overlap cases, keep Low because Gate 2 is in front
                        return 0; // Low risk
                    }
                }

                // Default for Gate 2
                return 0; // Low risk
            }
        }

        // WH1 Gate 1 special logic - medium risk if entry/exit time matches existing slot
        if ($plannedGateId) {
            $meta = $this->getGateMetaById($plannedGateId);

            if ($meta && ($meta['warehouse_code'] ?? '') === 'WH1' && ($meta['letter'] ?? '') === 'A') {
                // Get ALL slots for Gate 1 on the same day (not just overlapping)
                $existingASlots = $this->getAllSlotsForGateOnSameDay($plannedGateId, $start, $excludeSlotId);

                if ($existingASlots->isNotEmpty()) {
                    foreach ($existingASlots as $aSlot) {
                        $aStart = new DateTime($aSlot->planned_start);
                        $aEnd = clone $aStart;
                        $aEnd->modify('+'.(int) $aSlot->planned_duration.' minutes');

                        $newEntryTime = $startDt->format('H:i');
                        $newExitTime = $endDt->format('H:i');
                        $existingEntryTime = $aStart->format('H:i');
                        $existingExitTime = $aEnd->format('H:i');

                        // Check if entry time matches existing exit time OR exit time matches existing entry time
                        if (($aStart->format('H:i') === $endDt->format('H:i')) ||  // New entry = existing exit
                            ($aEnd->format('H:i') === $startDt->format('H:i'))) {   // New exit = existing entry
                            return 1; // Medium risk for Gate 1
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
            $overlapWarehouseQuery->where('s.id_slots', '<>', $excludeSlotId);
        }

        // WH2 Gate 2/3 rule: do not count the paired gate as "warehouse overlap".
        // Risk for this pair is handled explicitly via $bcEdgeRaw.
        if ($bcOtherGateId) {
            $overlapWarehouseQuery->where('s.planned_gate_id', '<>', $bcOtherGateId);
        }

        $overlapWarehouse = (int) $overlapWarehouseQuery->count();

        $bcEdgeRaw = 0;
        if ($bcLetter === 'C' && $bcOtherGateId) {
            // Gate 3 is "behind" Gate 2.
            // Medium risk ONLY when:
            // - Gate 2 finishes exactly when Gate 3 starts (edge touch), OR
            // - Gate 2 has the exact same time window as Gate 3 (same start AND same end).
            // If Gate 3 contains Gate 2 (e.g., starts same but finishes later), keep it Low.

            $otherDateAddExpr = $this->getDateAddExpression('o.planned_start', 'o.planned_duration');

            $edgeTouchOrSameWindow = (int) DB::table('slots as o')
                ->where('o.planned_gate_id', $bcOtherGateId)
                ->whereIn('o.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
                ->where(function ($q) use ($start, $end, $otherDateAddExpr) {
                    $q->whereRaw("{$otherDateAddExpr} = ?", [$start]) // Gate 2 finishes when Gate 3 starts
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
        $row = DB::table('md_warehouse')->where('id_wh', $warehouseId)->select(['wh_code'])->first();
        if ($row) {
            $warehouseCode = $row->wh_code ?? null;
        }

        $overlapWH1 = 0;
        if ($warehouseCode === 'WH2') {
            $wh1 = DB::table('md_warehouse')->where('wh_code', 'WH1')->select(['id_wh'])->first();
            if ($wh1) {
                $overlapWH1 = (int) DB::table('slots as s')
                    ->where('warehouse_id', (int) $wh1->id_wh)
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

    public function generateTicketPdfContent(int $slotId): ?string
    {
        $slot = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id_wh')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id_gates')
            ->leftJoin('md_gates as ag', 's.actual_gate_id', '=', 'ag.id_gates')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id_wh')
            ->leftJoin('md_warehouse as wag', 'ag.warehouse_id', '=', 'wag.id_wh')
            ->where('s.id_slots', $slotId)
            ->select([
                's.*',
                's.id_slots',
                's.po_number as po_number',
                's.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
                'pg.gate_number as planned_gate_number',
                'ag.gate_number as actual_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
                'wag.wh_code as actual_gate_warehouse_code',
            ])
            ->first();

        if (! $slot) {
            return null;
        }

        // For outbound, if vendor_name is empty, try fetching customer_name from SAP
        if (empty($slot->vendor_name) && strtolower((string) ($slot->direction ?? '')) === 'outbound') {
            $poNumber = trim((string) ($slot->po_number ?? ''));
            if ($poNumber !== '') {
                try {
                    $poSearchService = app(PoSearchService::class);
                    $poDetail = $poSearchService->getPoDetail($poNumber);
                    if (is_array($poDetail)) {
                        $cn = trim((string) ($poDetail['customer_name'] ?? ''));
                        if ($cn === '') {
                            $cn = trim((string) ($poDetail['vendor_name'] ?? ''));
                        }
                        if ($cn !== '') {
                            $slot->vendor_name = $cn;
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

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

        return Cache::remember($cacheKey, 3600, function () use ($slot) {
            $gateNumber = (string) ($slot->actual_gate_number ?? '');
            $gateWarehouse = (string) ($slot->actual_gate_warehouse_code ?? '');
            if ($gateNumber === '') {
                $gateNumber = (string) ($slot->planned_gate_number ?? '');
                $gateWarehouse = (string) ($slot->planned_gate_warehouse_code ?? '');
            }
            if ($gateWarehouse === '') {
                $gateWarehouse = (string) ($slot->warehouse_code ?? '');
            }
            $gateLetter = $this->getGateLetterByWarehouseAndNumber($gateWarehouse, $gateNumber);

            $barcodeC = new DNS1D();
            $barcodeC->setStorPath(storage_path('app/public/'));
            $barcodePng = '';
            if (! empty($slot->ticket_number)) {
                $ticketNumber = (string) $slot->ticket_number;
                $barcodePng = (string) Cache::remember('ticket_barcode_png_'.sha1($ticketNumber), 86400, function () use ($barcodeC, $ticketNumber) {
                    return (string) $barcodeC->getBarcodePNG($ticketNumber, 'C128', 2.5, 60);
                });
            }

            $logoDataUri = Cache::rememberForever('ticket_logo_data_uri', function () {
                try {
                    $logoPath = public_path('img/logo-full.png');
                    if (is_string($logoPath) && $logoPath !== '' && file_exists($logoPath)) {
                        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
                    }
                } catch (Throwable $e) {
                }
            });

            $ticketCss = Cache::rememberForever('ticket_css_inline', function () {
                try {
                    $cssPath = public_path('ticket.css');
                    if (is_string($cssPath) && $cssPath !== '' && file_exists($cssPath)) {
                        return (string) file_get_contents($cssPath);
                    }
                } catch (Throwable $e) {
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
    }
}
