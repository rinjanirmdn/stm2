<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

trait SlotHelperTrait
{
    private function buildGateLabel(?string $warehouseCode, ?string $gateNumber): string
    {
        $wh = strtoupper(trim((string) $warehouseCode));
        $gateLabel = $this->slotService->getGateDisplayName($wh, (string) $gateNumber);
        if ($wh !== '' && $gateLabel !== '-') {
            return $wh.' - '.$gateLabel;
        }

        return $gateLabel;
    }

    private function minutesDiff(?string $start, ?string $end): ?int
    {
        return $this->timeService->minutesDiff($start, $end);
    }

    private function isLateByPlannedStart(?string $plannedStart, string $actualTime): bool
    {
        return $this->timeService->isLateByPlannedStart($plannedStart, $actualTime);
    }

    private function getPlannedDurationForStart(object $slot): int
    {
        return $this->timeService->getPlannedDurationForStart($slot);
    }

    private function findInProgressConflicts(int $actualGateId, int $excludeSlotId = 0): array
    {
        return $this->conflictService->findInProgressConflicts($actualGateId, $excludeSlotId);
    }

    private function buildConflictLines(array $slotIds): array
    {
        return $this->conflictService->buildConflictMessage($slotIds);
    }

    private function getTruckTypeOptions(): array
    {
        return $this->timeService->getTruckTypeOptions();
    }

    private function loadSlotDetailRow(int $slotId): ?object
    {
        $slot = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_users as ru', 's.requested_by', '=', 'ru.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_gates as ag', 's.actual_gate_id', '=', 'ag.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->leftJoin('md_warehouse as wag', 'ag.warehouse_id', '=', 'wag.id')
            ->leftJoin('md_truck as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id', $slotId)
            ->select([
                's.*',
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

        if ($slot) {
            // Load photos from slot_photos table (new DB storage)
            $dbPhotos = DB::table('slot_photos')
                ->where('slot_id', $slotId)
                ->select(['id', 'phase', 'filename'])
                ->orderBy('id')
                ->get();

            $startPhotos = [];
            $completePhotos = [];
            foreach ($dbPhotos as $p) {
                $photoObj = (object) ['id' => $p->id, 'filename' => $p->filename];
                if ($p->phase === 'start') {
                    $startPhotos[] = $photoObj;
                } elseif ($p->phase === 'complete') {
                    $completePhotos[] = $photoObj;
                }
            }

            // Fallback: if no DB photos found, check legacy path columns
            if (empty($startPhotos) && ! empty($slot->start_photo_path)) {
                $startPhotos = $this->resolvePhotoPathColumn($slot->start_photo_path, $slotId, 'start');
            }
            if (empty($completePhotos) && ! empty($slot->complete_photo_path)) {
                $completePhotos = $this->resolvePhotoPathColumn($slot->complete_photo_path, $slotId, 'complete');
            }

            $slot->start_photos = ! empty($startPhotos) ? $startPhotos : null;
            $slot->complete_photos = ! empty($completePhotos) ? $completePhotos : null;
        }

        return $slot;
    }

    /**
     * Resolve a photo_path column value into photo objects.
     *
     * The column might contain:
     *  - JSON array of DB photo IDs: '[1,2,3]' (new system)
     *  - JSON array of file paths: '["documentation/start/abc.jpg"]'
     *  - Plain file path string: 'documentation/start/abc.jpg'
     *
     * @return array Array of photo objects with either 'id' or 'legacy_path'
     */
    private function resolvePhotoPathColumn(mixed $value, int $slotId, string $phase): array
    {
        $items = $this->normalizePhotoPaths($value);
        if (! $items) {
            return [];
        }

        $photos = [];
        foreach ($items as $item) {
            $item = (string) $item;

            // If it's purely numeric, it might be a DB photo ID
            if (ctype_digit($item)) {
                // Only accept if this photo belongs to this slot AND correct phase
                $row = DB::table('slot_photos')
                    ->where('id', (int) $item)
                    ->where('slot_id', $slotId)
                    ->where('phase', $phase)
                    ->select(['id', 'filename'])
                    ->first();
                if ($row) {
                    $photos[] = (object) ['id' => $row->id, 'filename' => $row->filename];
                }
                // Skip if ID doesn't match slot+phase
                continue;
            }

            // Must look like a real file path (contains '/' or has a file extension)
            if (str_contains($item, '/') || preg_match('/\.\w{2,4}$/', $item)) {
                $photos[] = (object) ['id' => null, 'filename' => basename($item), 'legacy_path' => $item];
            }
            // Otherwise skip invalid values
        }

        return $photos;
    }

    /**
     * Normalize photo path values from DB into a proper PHP array.
     *
     * Handles mixed formats in the database:
     *  - null → null
     *  - JSON array string: '["path/a.jpg","path/b.jpg"]' → ['path/a.jpg', 'path/b.jpg']
     *  - Plain string: 'path/a.jpg' → ['path/a.jpg']
     */
    private function normalizePhotoPaths(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $value = (string) $value;

        // Try JSON decode first (handles '["path1","path2"]' format)
        if (str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }

        // Plain string path (legacy single-photo format)
        return [$value];
    }
}
