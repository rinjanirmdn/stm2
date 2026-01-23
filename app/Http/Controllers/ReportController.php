<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Services\SlotService;
use App\Services\TransactionReportService;
use App\Services\ExportService;
use App\Exports\TransactionsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        private readonly TransactionReportService $transactionService,
        private readonly ExportService $exportService
    ) {
    }
    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $like = '%' . $q . '%';

        $rows = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->where('s.status', 'completed')
            ->where(function ($sub) use ($like) {
                $sub->where('s.po_number', 'like', $like)
                    ->orWhere('s.ticket_number', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('s.vendor_name', 'like', $like)
                    ->orWhere('w.wh_name', 'like', $like);
            })
            ->select([
                's.po_number',
                's.ticket_number',
                's.mat_doc',
                's.vendor_name',
                'w.wh_name as warehouse_name',
            ])
            ->orderByRaw("CASE
                WHEN s.po_number LIKE ? THEN 1
                WHEN s.ticket_number LIKE ? THEN 2
                WHEN COALESCE(s.mat_doc, '') LIKE ? THEN 3
                WHEN s.vendor_name LIKE ? THEN 4
                WHEN w.wh_name LIKE ? THEN 5
                ELSE 6
            END", [$q . '%', $q . '%', $q . '%', $q . '%', $q . '%'])
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
            $ticket = trim((string) ($row->ticket_number ?? ''));
            $matDoc = trim((string) ($row->mat_doc ?? ''));
            $vendor = trim((string) ($row->vendor_name ?? ''));
            $warehouse = trim((string) ($row->warehouse_name ?? ''));

            if ($truck !== '' && $vendor !== '') {
                $text = $truck . ' - ' . $vendor;
                if (! in_array($text, $seen, true)) {
                    $seen[] = $text;
                    $results[] = ['text' => $text, 'highlighted' => $highlight($text)];
                }
            }

            if ($truck !== '' && ! in_array($truck, $seen, true)) {
                $seen[] = $truck;
                $results[] = ['text' => $truck, 'highlighted' => $highlight($truck)];
            }

            if ($matDoc !== '' && ! in_array($matDoc, $seen, true)) {
                $seen[] = $matDoc;
                $results[] = ['text' => $matDoc, 'highlighted' => $highlight($matDoc)];
            }

            if ($vendor !== '' && ! in_array($vendor, $seen, true)) {
                $seen[] = $vendor;
                $results[] = ['text' => $vendor, 'highlighted' => $highlight($vendor)];
            }

            if ($warehouse !== '' && ! in_array($warehouse, $seen, true)) {
                $seen[] = $warehouse;
                $results[] = ['text' => $warehouse, 'highlighted' => $highlight($warehouse)];
            }

            if ($ticket !== '' && ! in_array($ticket, $seen, true)) {
                $seen[] = $ticket;
                $results[] = ['text' => $ticket, 'highlighted' => $highlight($ticket)];
            }

            if (count($results) >= 10) {
                break;
            }
        }

        return response()->json(array_slice($results, 0, 10));
    }

    public function transactions(Request $request)
    {
        // Validate and sanitize inputs (supports multi-sort via sort[]/dir[])
        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return in_array($v, ['asc', 'desc'], true) ? $v : 'desc';
        }, $dirs));

        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'desc';

        $pageSize = (string) $request->query('page_size', 'all');
        $pageSizeAllowed = ['10', '25', '50', '100', 'all'];
        if (! in_array($pageSize, $pageSizeAllowed, true)) {
            $pageSize = 'all';
        }

        // Handle exports
        $export = trim($request->query('export', ''));
        if ($export === 'excel' || $export === 'csv') {
            return $this->handleExport($request, $export);
        }

        // Build filtered query
        $rowsQ = $this->transactionService->getTransactions($request);

        // Apply sorting
        $sortMap = $this->transactionService->getSortMap();
        $applied = 0;
        foreach ($sorts as $i => $s) {
            if (! array_key_exists($s, $sortMap)) {
                continue;
            }
            $d = $dirs[$i] ?? 'desc';
            $col = $sortMap[$s];
            if ($col instanceof \Illuminate\Database\Query\Expression) {
                $rowsQ->orderByRaw($col->getValue(DB::connection()->getQueryGrammar()) . ' ' . strtoupper($d));
            } else {
                $rowsQ->orderBy($col, $d);
            }
            $applied++;
        }
        if ($applied === 0) {
            $rowsQ->orderByRaw('COALESCE(s.actual_start, s.planned_start) DESC');
        }

        // Apply page size limit
        if ($pageSize !== 'all') {
            $rowsQ->limit((int) $pageSize);
        }

        $rows = $rowsQ->get();

        // Get filter options
        $filterOptions = $this->transactionService->getFilterOptions();

        // Extract filter values for view
        $filterValues = [];
        $this->extractFilterValues($request, $filterValues);

        return view('reports.transactions', [
            'rows' => $rows,
            'q' => trim($request->query('q', '')),
            'date_from' => trim($request->query('date_from', '')),
            'date_to' => trim($request->query('date_to', '')),
            'po' => trim($request->query('po', '')),
            'ticket' => trim($request->query('ticket', '')),
            'mat_doc' => trim($request->query('mat_doc', '')),
            'user' => trim($request->query('user', '')),
            'vendor' => trim($request->query('vendor', '')),
            'arrival_date_from' => trim($request->query('arrival_date_from', '')),
            'arrival_date_to' => trim($request->query('arrival_date_to', '')),
            'sort' => $sort,
            'dir' => $dir,
            'sorts' => $sorts,
            'dirs' => $dirs,
            'pageSize' => $pageSize,
            'pageSizeAllowed' => $pageSizeAllowed,
            'warehouses' => $filterOptions['warehouses'],
            'vendors' => $filterOptions['vendors'],
        ] + $filterValues);
    }

    /**
     * Handle export requests
     */
    private function handleExport(Request $request, string $type)
    {
        $rowsQ = $this->transactionService->getTransactions($request);
        $rows = $rowsQ->get();

        $timestamp = date('Ymd_His');

        if ($type === 'excel') {
            $filename = "transactions_report_{$timestamp}.xlsx";
            return Excel::download(new TransactionsExport($rows), $filename);
        }

        // For CSV, keep using the existing service
        $filename = $this->exportService->generateFilename('transactions', 'csv');
        return $this->exportService->exportToCsv($rows, $filename);
    }

    /**
     * Extract filter values for view
     */
    private function extractFilterValues(Request $request, array &$filterValues): void
    {
        $filterValues = [
            'statusFilter' => array_values(array_filter((array) $request->query('status', []), fn ($v) => (string) $v !== '')),
            'slotTypeFilter' => array_values(array_filter((array) $request->query('slot_type', []), fn ($v) => (string) $v !== '')),
            'directionFilter' => array_values(array_filter((array) $request->query('direction', []), fn ($v) => (string) $v !== '')),
            'lateFilter' => array_values(array_filter((array) $request->query('late', []), fn ($v) => (string) $v !== '')),
            'warehouseFilter' => array_values(array_filter((array) $request->query('warehouse_id', []), fn ($v) => (string) $v !== '')),
            'targetStatusFilter' => array_values(array_filter((array) $request->query('target_status', []), fn ($v) => (string) $v !== '')),
        ];
    }

    public function gateStatus(Request $request)
    {
        $data = $this->buildGateStatusTableData($request);

        return view('reports.gate_status', $data);
    }

    public function gatesIndex(Request $request)
    {
        $data = $this->buildGateStatusTableData($request);

        return view('gates.index', $data);
    }

    private function buildGateStatusTableData(Request $request): array
    {
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $warehouseFilter = (array) $request->query('warehouse_id', []);

        if ($dateFrom === '' && $dateTo === '') {
            $today = date('Y-m-d');
            $dateFrom = $today;
            $dateTo = $today;
        } elseif ($dateFrom !== '' && $dateTo === '') {
            $dateTo = $dateFrom;
        } elseif ($dateFrom === '' && $dateTo !== '') {
            $dateFrom = $dateTo;
        }

        $warehouseValues = array_values(array_filter($warehouseFilter, fn ($v) => (string) $v !== ''));

        $warehouses = DB::table('warehouses')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();

        $rowsQ = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('gates as g', function ($join) {
                $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                    ->on('g.warehouse_id', '=', 's.warehouse_id');
            })
            ->whereRaw("COALESCE(s.slot_type, 'planned') = 'planned'")
            ->whereNotNull('g.gate_number')
            ->select([
                'w.wh_code as warehouse_code',
                'g.gate_number',
                DB::raw("SUM(CASE WHEN s.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_count"),
                DB::raw("SUM(CASE WHEN s.status = 'arrived' THEN 1 ELSE 0 END) as arrived_count"),
                DB::raw("SUM(CASE WHEN s.status = 'waiting' THEN 1 ELSE 0 END) as waiting_count"),
                DB::raw("SUM(CASE WHEN s.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count"),
                DB::raw("SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count"),
                DB::raw("COUNT(*) as total_count"),
            ])
            ->groupBy('w.wh_code', 'g.gate_number')
            ->orderBy('w.wh_code')
            ->orderBy('g.gate_number');

        if ($dateFrom !== '' && $dateTo !== '') {
            $rowsQ->whereDate(DB::raw('COALESCE(s.actual_start, s.planned_start, s.arrival_time)'), '>=', $dateFrom)
                ->whereDate(DB::raw('COALESCE(s.actual_start, s.planned_start, s.arrival_time)'), '<=', $dateTo);
        }

        if (!empty($warehouseValues)) {
            $rowsQ->whereIn('s.warehouse_id', array_map('intval', $warehouseValues));
        }

        $rows = $rowsQ->get()->map(function ($r) {
            return [
                'warehouse_code' => (string) ($r->warehouse_code ?? ''),
                'gate_number' => (string) ($r->gate_number ?? ''),
                'scheduled_count' => (int) ($r->scheduled_count ?? 0),
                'arrived_count' => (int) ($r->arrived_count ?? 0),
                'waiting_count' => (int) ($r->waiting_count ?? 0),
                'in_progress_count' => (int) ($r->in_progress_count ?? 0),
                'completed_count' => (int) ($r->completed_count ?? 0),
                'cancelled_count' => (int) ($r->cancelled_count ?? 0),
                'total_count' => (int) ($r->total_count ?? 0),
            ];
        })->all();

        $gatesQ = DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->select([
                'g.id',
                'g.warehouse_id',
                'g.gate_number',
                'g.is_active',
                'g.is_backup',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
            ])
            ->orderBy('w.wh_code')
            ->orderBy('g.gate_number');

        if (!empty($warehouseValues)) {
            $gatesQ->whereIn('g.warehouse_id', array_map('intval', $warehouseValues));
        }

        $gates = $gatesQ->get();

        return [
            'rows' => $rows,
            'gates' => $gates,
            'warehouses' => $warehouses,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'warehouse_id' => $warehouseValues,
        ];
    }

    public function toggleGate(Request $request, int $gateId)
    {
        $gate = DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.id', $gateId)
            ->select([
                'g.id',
                'g.gate_number',
                'g.is_active',
                'g.is_backup',
                'w.wh_code as warehouse_code',
            ])
            ->first();

        if (! $gate) {
            return redirect()->back()->with('error', 'Gate not found');
        }

        if ((int) ($gate->is_backup ?? 0) !== 1) {
            return redirect()->back()->with('error', 'Only standby/backup gates can be toggled.');
        }

        $old = (int) ($gate->is_active ?? 0);
        $new = $old === 1 ? 0 : 1;
        DB::table('gates')->where('id', $gateId)->update(['is_active' => $new]);

        $slotService = app(SlotService::class);
        $label = $slotService->getGateDisplayName((string) ($gate->warehouse_code ?? ''), (string) ($gate->gate_number ?? ''));
        $activityType = $new === 1 ? 'gate_activation' : 'gate_deactivation';
        $desc = $new === 1 ? "Gate Activated: {$label}" : "Gate Deactivated: {$label}";
        $slotService->logActivity(null, $activityType, $desc, $old, $new);

        return redirect()->back()->with('success', 'Gate status updated');
    }
}
