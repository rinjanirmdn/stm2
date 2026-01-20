<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\VendorImportRequest;

class VendorController extends Controller
{
    private $sapVendorService;

    public function __construct(
        \App\Services\SapVendorService $sapVendorService
    ) {
        $this->sapVendorService = $sapVendorService;
    }

    /**
     * AJAX Search for vendors (used in Create Slot form)
     * Searches from local database (synced from SAP via PO access)
     */
    public function ajaxSearch(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $type = trim((string) $request->input('type', '')); // supplier or customer

        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Search from local database (synced from SAP)
        $query = DB::table('business_partner')
            ->select('id', 'bp_code as code', 'bp_name as name', 'bp_type as type')
            ->where(function($sub) use ($q) {
                $sub->where('bp_code', 'like', '%' . $q . '%')
                    ->orWhere('bp_name', 'like', '%' . $q . '%');
            });

        // Filter by type if specified
        if ($type === 'supplier') {
            $query->whereIn('bp_type', ['supplier', 'VENDOR', 'Supplier']);
        } elseif ($type === 'customer') {
            $query->whereIn('bp_type', ['customer', 'CUSTOMER', 'Customer']);
        }

        $results = $query->orderBy('bp_name')->limit(20)->get();

        $data = $results->map(function($row) {
            return [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => strtolower($row->type ?? 'supplier'),
                'source' => 'sap_synced' // Data originally from SAP, cached locally
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function index(Request $request)
    {
        $allowedTypes = ['supplier', 'customer'];
        $pageSizeAllowed = ['10', '25', '50', 'all'];

        $code = trim((string) $request->query('code', ''));
        $name = trim((string) $request->query('name', ''));
        $type = trim((string) $request->query('type', ''));
        $dateFrom = trim((string) $request->query('created_from', ''));
        $dateTo = trim((string) $request->query('created_to', ''));

        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $allowedSorts = [
            'code' => 'bp_code',
            'name' => 'bp_name',
            'type' => 'bp_type',
            'created_at' => 'created_at',
        ];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return in_array($v, ['asc', 'desc'], true) ? $v : 'asc';
        }, $dirs));

        $validatedSorts = [];
        $validatedDirs = [];
        foreach ($sorts as $i => $s) {
            if (! array_key_exists($s, $allowedSorts)) {
                continue;
            }
            $validatedSorts[] = $s;
            $validatedDirs[] = $dirs[$i] ?? 'asc';
        }
        $sorts = $validatedSorts;
        $dirs = $validatedDirs;

        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'asc';

        $vendorsQ = DB::table('business_partner')
            ->select([
                'id',
                'bp_name as name',
                'bp_code as code',
                'bp_type as type',
                'created_at',
            ]);

        if ($code !== '') {
            $vendorsQ->where('bp_code', 'like', '%' . $code . '%');
        }
        if ($name !== '') {
            $vendorsQ->where('bp_name', 'like', '%' . $name . '%');
        }
        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $vendorsQ->where('bp_type', $type);
        }
        if ($dateFrom !== '' && $dateTo !== '') {
            $vendorsQ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom !== '') {
            $vendorsQ->whereDate('created_at', '>=', $dateFrom);
        } elseif ($dateTo !== '') {
            $vendorsQ->whereDate('created_at', '<=', $dateTo);
        }

        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                $d = $dirs[$i] ?? 'asc';
                $vendorsQ->orderBy($allowedSorts[$s], $d);
            }
            $vendors = $vendorsQ->orderByDesc('created_at')->orderByDesc('id')->get();
        } else {
            // Default ordering when no sort specified
            $vendors = $vendorsQ
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();
        }

        return view('vendors.index', [
            'vendors' => $vendors,
            'pageSizeAllowed' => $pageSizeAllowed,
            'allowedTypes' => $allowedTypes,
            // expose filters/sort to view
            'v_code' => $code,
            'v_name' => $name,
            'v_type' => ($type !== '' && in_array($type, $allowedTypes, true)) ? $type : '',
            'created_from' => $dateFrom,
            'created_to' => $dateTo,
            'sort' => $sort,
            'dir' => $dir,
            'sorts' => $sorts,
            'dirs' => $dirs,
        ]);
    }

    public function import(Request $request)
    {
        return view('vendors.import');
    }

    public function importStore(VendorImportRequest $request)
    {
        $validated = $request->validated();
        $vendorType = $validated['vendor_type'];
        $file = $validated['csv_file'];

        if (! $request->hasFile('csv_file')) {
            return back()->withInput()->with('error', 'File tidak valid atau gagal di-upload.');
        }

        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return back()->withInput()->with('error', 'File tidak bisa dibaca.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withInput()->with('error', 'File tidak bisa dibaca.');
        }

        $inserted = 0;
        $skipped = 0;

        $firstLine = fgetcsv($handle, 0, ',');
        if ($firstLine === false) {
            fclose($handle);
            return back()->withInput()->with('error', 'File kosong.');
        }

        $maybeHeader = array_map(fn ($v) => strtolower(trim((string) $v)), $firstLine);
        $isHeader = in_array('code', $maybeHeader, true) || in_array('vendor_code', $maybeHeader, true);

        $processRow = function (array $row) use ($vendorType, &$inserted, &$skipped) {
            $code = isset($row[0]) ? strtoupper(trim((string) $row[0])) : '';
            $name = isset($row[1]) ? trim((string) $row[1]) : '';

            if ($code === '' || $name === '') {
                $skipped++;
                return;
            }

            try {
                $exists = (int) DB::table('business_partner')->where('bp_code', $code)->count() > 0;
                if ($exists) {
                    $skipped++;
                    return;
                }

                DB::table('business_partner')->insert([
                    'bp_code' => $code,
                    'bp_name' => $name,
                    'bp_type' => $vendorType,
                ]);
                $inserted++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        };

        if (! $isHeader) {
            $processRow($firstLine);
        }

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $processRow($data);
        }

        fclose($handle);

        return redirect()
            ->route('vendors.import')
            ->with('success', "Import selesai. Berhasil: {$inserted}, dilewati (kosong/duplikat/error): {$skipped}.");
    }
}
