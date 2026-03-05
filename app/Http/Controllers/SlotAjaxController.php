<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Services\SlotService;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\TimeCalculationService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotAjaxController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService
    ) {
    }

    public function ajaxPoSearch(Request $request)
    {
        $q = (string) $request->query('q', '');

        // Prefer SAP search for autocomplete responsiveness
        $results = $this->poSearchService->searchPoSapOnly($q, 20);
        if (empty($results)) {
            // Fallback to hybrid search if SAP search fails
            $results = $this->poSearchService->searchPo($q);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function ajaxPoDetail(string $poNumber)
    {
        try {
            $poNumber = trim($poNumber);
            if ($poNumber === '') {
                return response()->json(['success' => false, 'message' => 'PO/DO number is required']);
            }

            $po = $this->poSearchService->getPoDetail($poNumber);

            if (!$po) {
                return response()->json(['success' => false, 'message' => 'PO/DO not found']);
            }
            return response()->json(['success' => true, 'data' => $po]);
        } catch (\Throwable $e) {
            Log::warning('ajaxPoDetail failed', [
                'poNumber' => $poNumber,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to load PO/DO detail'], 200);
        }
    }

    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $tokens = preg_split('/\s+/', $q) ?: [];
        $tokens = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $tokens), fn ($t) => $t !== ''));

        $normalized = str_replace('-', ' ', $q);
        $moreTokens = preg_split('/\s+/', $normalized) ?: [];
        $moreTokens = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $moreTokens), fn ($t) => $t !== ''));

        $allTokens = array_values(array_unique(array_merge($tokens, $moreTokens)));

        // Keep original query for highlight ordering.
        $like = '%' . $q . '%';

        $rowsQ = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->where('s.status', '<>', 'completed')
            ->select([
                's.po_number as truck_number',
                's.mat_doc',
                's.vendor_name',
                'w.wh_name as warehouse_name',
            ]);

        foreach ($allTokens as $tok) {
            $tl = '%' . $tok . '%';
            $rowsQ->where(function ($sub) use ($tl) {
                $sub->where('s.po_number', 'like', $tl)
                    ->orWhere('s.mat_doc', 'like', $tl)
                    ->orWhere('s.vendor_name', 'like', $tl);
            });
        }

        $rows = $rowsQ
            ->orderByRaw("CASE
                WHEN s.po_number LIKE ? THEN 1
                WHEN COALESCE(s.mat_doc, '') LIKE ? THEN 2
                WHEN s.vendor_name LIKE ? THEN 3
                ELSE 4
            END", [$q . '%', $q . '%', $q . '%'])
            ->orderBy('s.po_number')
            ->limit(10)
            ->get();

        $highlight = function (?string $text) use ($q): string {
            $text = (string) ($text ?? '');
            if ($text === '') {
                return '';
            }

            $pos = stripos($text, $q);
            if ($pos === false) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }

            $before = substr($text, 0, $pos);
            $match = substr($text, $pos, strlen($q));
            $after = substr($text, $pos + strlen($q));

            return htmlspecialchars($before, ENT_QUOTES, 'UTF-8')
                . '<strong>' . htmlspecialchars($match, ENT_QUOTES, 'UTF-8') . '</strong>'
                . htmlspecialchars($after, ENT_QUOTES, 'UTF-8');
        };

        $results = [];
        $seen = [];

        foreach ($rows as $row) {
            $truck = trim((string) ($row->truck_number ?? ''));
            $matDoc = trim((string) ($row->mat_doc ?? ''));
            $vendor = trim((string) ($row->vendor_name ?? ''));

            // 1. Truck - Vendor
            if ($truck !== '' && $vendor !== '') {
                $text = $truck . ' - ' . $vendor;
                if (! in_array($text, $seen, true)) {
                    $seen[] = $text;
                    $results[] = [
                        'text' => $text,
                        'highlighted' => $highlight($text),
                    ];
                }
            }

            // 2. Truck only
            if ($truck !== '' && ! in_array($truck, $seen, true)) {
                $seen[] = $truck;
                $results[] = [
                    'text' => $truck,
                    'highlighted' => $highlight($truck),
                ];
            }

            // 3. MAT DOC
            if ($matDoc !== '' && ! in_array($matDoc, $seen, true)) {
                $seen[] = $matDoc;
                $results[] = [
                    'text' => $matDoc,
                    'highlighted' => $highlight($matDoc),
                ];
            }

            // 4. Vendor only
            if ($vendor !== '' && ! in_array($vendor, $seen, true)) {
                $seen[] = $vendor;
                $results[] = [
                    'text' => $vendor,
                    'highlighted' => $highlight($vendor),
                ];
            }

            if (count($results) >= 10) {
                break;
            }
        }

        return response()->json(array_slice($results, 0, 10));
    }

    public function ajaxCheckRisk(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $startStr = $startDt->format('Y-m-d H:i:s');
        $endStr = $endDt->format('Y-m-d H:i:s');

        // Use calculateBlockingRisk directly - it includes all logic including BC edge cases
        $riskLevel = $this->slotService->calculateBlockingRisk($warehouseId, $plannedGateId, $plannedStart, $plannedDurationMinutes);

        $label = 'Low';
        $badge = 'success';
        $message = 'Low risk for this time and gate combination.';

        if ($riskLevel === 1) {
            $label = 'Medium';
            $badge = 'warning';
            $message = 'Watch for potential blocking. Consider checking schedules at other gates or shifting time.';
        } elseif ($riskLevel === 2) {
            $label = 'High';
            $badge = 'danger';
            $message = 'High blocking potential. Recommended to change gate or e-DCS.';
        }

        return response()->json([
            'success' => true,
            'risk_level' => $riskLevel,
            'label' => $label,
            'badge' => $badge,
            'message' => $message,
        ]);
    }

    public function ajaxCheckSlotTime(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $gateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $response = [
            'success' => true,
            'overlap' => false,
            'message' => '',
            'suggested_start' => null,
        ];

        if ($gateId !== null) {
            $laneGroup = $this->slotService->getGateLaneGroup($gateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];
            if (empty($laneGateIds)) {
                $laneGateIds = [$gateId];
            }

            $startStr = $startDt->format('Y-m-d H:i:s');
            $endStr = $endDt->format('Y-m-d H:i:s');

            $conflicts = DB::table('slots')
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->orderBy('planned_start', 'asc')
                ->select(['planned_start', 'planned_duration'])
                ->get();

            if ($conflicts->isNotEmpty()) {
                $response['overlap'] = true;
                $response['message'] = 'Planned time overlaps with another slot on this lane.';

                $day = $startDt->format('Y-m-d');
                $latest = DB::table('slots')
                    ->whereIn('planned_gate_id', $laneGateIds)
                    ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                    ->whereRaw('DATE(planned_start) = ?', [$day])
                    ->orderByRaw($this->slotService->getDateAddExpression('planned_start', 'planned_duration') . ' DESC')
                    ->limit(1)
                    ->select(['planned_start', 'planned_duration'])
                    ->first();

                if ($latest) {
                    try {
                        $safeStart = new DateTime((string) $latest->planned_start);
                        $safeStart->modify('+' . (int) ($latest->planned_duration ?? 0) . ' minutes');
                        $response['suggested_start'] = $safeStart->format('Y-m-d H:i');
                    } catch (\Throwable $e) {
                        $response['suggested_start'] = null;
                    }
                }
            }

            if (! $response['overlap']) {
                $bcCheck = $this->slotService->validateWh2BcPlannedWindow($gateId, $startDt, $endDt);
                if (empty($bcCheck['ok'])) {
                    $response['overlap'] = true;
                    $response['message'] = (string) ($bcCheck['message'] ?? 'Planned time is not allowed for this gate');
                    $response['suggested_start'] = null;
                }
            }
        }

        return response()->json($response);
    }

    public function ajaxRecommendGate(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $startStr = $startDt->format('Y-m-d H:i:s');
        $endStr = $endDt->format('Y-m-d H:i:s');

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.warehouse_id', $warehouseId)
            ->where('g.is_active', true)
            ->orderBy('g.gate_number')
            ->select(['g.id', 'g.gate_number', 'w.wh_code as warehouse_code', 'w.wh_name as warehouse_name'])
            ->get();

        if ($gates->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No active gates for this warehouse']);
        }

        $bestGate = null;
        $bestRisk = null;

        foreach ($gates as $gate) {
            $gid = (int) $gate->id;

            $laneGroup = $this->slotService->getGateLaneGroup($gid);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gid];
            if (empty($laneGateIds)) {
                $laneGateIds = [$gid];
            }

            $overlapCount = (int) DB::table('slots')
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->count();

            if ($overlapCount > 0) {
                continue;
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($gid, $startDt, $endDt);
            if (empty($bcCheck['ok'])) {
                continue;
            }

            $risk = $this->slotService->calculateBlockingRisk($warehouseId, $gid, $plannedStart, $plannedDurationMinutes);

            $pick = false;
            if ($bestRisk === null || $risk < $bestRisk) {
                $pick = true;
            } elseif ($bestRisk !== null && $risk === $bestRisk) {
                $whCode = strtoupper(trim((string) ($gate->warehouse_code ?? '')));
                if ($whCode === 'WH2') {
                    $letterNow = $this->slotService->getGateLetterByWarehouseAndNumber($whCode, (string) ($gate->gate_number ?? ''));
                    $bestLetter = $bestGate ? $this->slotService->getGateLetterByWarehouseAndNumber($whCode, (string) ($bestGate->gate_number ?? '')) : null;
                    if ($letterNow === 'C' && $bestLetter !== 'C') {
                        $pick = true;
                    }
                }
            }

            if ($pick) {
                $bestRisk = $risk;
                $bestGate = (object) ([
                    'id' => (int) $gate->id,
                    'gate_number' => (string) ($gate->gate_number ?? ''),
                    'warehouse_code' => (string) ($gate->warehouse_code ?? ''),
                    'warehouse_name' => (string) ($gate->warehouse_name ?? ''),
                    'risk' => (int) $risk,
                ]);
            }
        }

        if ($bestGate === null) {
            return response()->json(['success' => false, 'message' => 'No available gate for this time']);
        }

        $label = 'Low';
        if ($bestGate->risk === 1) {
            $label = 'Medium';
        } elseif ($bestGate->risk === 2) {
            $label = 'High';
        }

        $gateDisplay = $this->slotService->getGateDisplayName($bestGate->warehouse_code, $bestGate->gate_number);
        $gateLabel = trim(($bestGate->warehouse_code !== '' ? ($bestGate->warehouse_code . ' - ' . $gateDisplay) : $gateDisplay));

        $note = null;
        $whCodeOut = strtoupper(trim((string) $bestGate->warehouse_code));
        if ($whCodeOut === 'WH2') {
            $letterOut = $this->slotService->getGateLetterByWarehouseAndNumber($whCodeOut, (string) $bestGate->gate_number);
            if ($letterOut === 'C') {
                $note = 'WH2: Prioritize Gate C if available because Gate B is in front/line and can block access to Gate C when operating.';
            }
        }

        return response()->json([
            'success' => true,
            'gate_id' => (int) $bestGate->id,
            'gate_label' => $gateLabel,
            'risk_level' => (int) $bestGate->risk,
            'risk_label' => $label,
            'note' => $note,
        ]);
    }

    public function ajaxSchedulePreview(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $date = (string) $request->input('date', '');

        if ($warehouseId === 0) {
            return response()->json(['success' => false, 'message' => 'Warehouse is required']);
        }

        if ($date === '') {
            $date = date('Y-m-d');
        }

        $q = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as g', 's.planned_gate_id', '=', 'g.id')
            ->where('s.warehouse_id', $warehouseId)
            ->whereRaw('DATE(s.planned_start) = ?', [$date])
            ->whereIn('s.status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('s.planned_start', 'asc')
            ->select(['s.id', 's.planned_start', 's.planned_duration', 's.status', 'g.gate_number', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code']);

        if ($plannedGateId !== null) {
            $q->where('s.planned_gate_id', $plannedGateId);
        }

        $rows = $q->get();

        $data = [];
        foreach ($rows as $row) {
            $start = (string) ($row->planned_start ?? '');
            $finish = null;
            if (! empty($row->planned_start) && ! empty($row->planned_duration)) {
                try {
                    $dt = new DateTime((string) $row->planned_start);
                    $dt->modify('+' . (int) $row->planned_duration . ' minutes');
                    $finish = $dt->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    $finish = null;
                }
            }

            $gateLabel = $this->slotService->getGateDisplayName((string) ($row->warehouse_code ?? ''), (string) ($row->gate_number ?? ''));

            $data[] = [
                'id' => (int) ($row->id ?? 0),
                'planned_start' => $start,
                'planned_finish' => $finish,
                'status' => $row->status,
                'gate' => $gateLabel,
                'warehouse' => $row->warehouse_name,
            ];
        }

        return response()->json(['success' => true, 'items' => $data]);
    }
}
