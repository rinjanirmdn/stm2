<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PoSearchService
{
    public function __construct(
        private readonly SapPoService $sapPoService,
        private readonly SapVendorService $sapVendorService
    ) {
    }

    /**
     * Search PO/DO numbers with priority to SAP, fallback to local DB
     */
    public function searchPo(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        // 1. Try SAP Search if query looks like specific PO number (usually 8+ digits)
        // or if using the new OData search
        $results = [];
        // 1. SAP Search - Exact Match using ZPOA_DTL_LIST
        // User must type at least 5 digits for reliable PO lookup
        $digitsOnly = preg_replace('/\D+/', '', $q);
        
        if (strlen($digitsOnly) >= 5) {
            try {
                $poDetail = $this->sapPoService->getByPoNumber($digitsOnly);
                
                if ($poDetail) {
                    // Determine direction dynamically
                    $poNumber = $poDetail['po_number'];
                    $firstChar = substr($poNumber, 0, 1);
                    $firstTwo = substr($poNumber, 0, 2);
                    $direction = 'inbound';
                    
                    if ($firstChar === '8' || strtoupper($firstTwo) === 'DO') {
                        $direction = 'outbound';
                    }

                    $results[] = [
                        'po_number' => $poNumber,
                        'vendor_name' => $poDetail['vendor_name'] ?? '',
                        'vendor_code' => $poDetail['vendor_code'] ?? '',
                        'plant' => $poDetail['plant'] ?? '',
                        'doc_date' => $poDetail['doc_date'] ?? '',
                        'warehouse_name' => $poDetail['warehouse_name'] ?? '',
                        'direction' => $direction,
                        'source' => 'sap'
                    ];
                }
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        


        // 2. Fallback: Search Local DB (For autocomplete responsiveness)
        // This is safe because we still validate details later.
        $queryBuilder = DB::table('po as p');

        $select = $this->buildPoSelectColumns();
        if (Schema::hasColumn('po', 'bp_id')) {
            $queryBuilder->leftJoin('business_partner as v', 'p.bp_id', '=', 'v.id');
            $select[] = 'v.bp_name as vendor_name';
            $select[] = 'v.bp_type as vendor_type';
            $select[] = 'v.bp_code as vendor_code';
        } elseif (Schema::hasColumn('po', 'vendor_name')) {
            $select[] = 'p.vendor_name as vendor_name';
        }

        $queryBuilder->select($select);
        $this->applySearchConditions($queryBuilder, $q);
        
        $localResults = $queryBuilder
            ->limit(10)
            ->get();
            
        $formattedLocal = $this->formatSearchResults($localResults);
        
        // Merge results: SAP first, then Local (unique POs)
        foreach ($formattedLocal as $localItem) {
            // Check existence in $results
            $exists = false;
            foreach ($results as $sapItem) {
                if ($sapItem['po_number'] == $localItem['po_number']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $results[] = $localItem;
            }
        }

        return $results;
    }

    public function searchPoSapOnly(string $query, int $limit = 20): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        try {
            $rows = $this->sapPoService->search($q, $limit);
        } catch (\Throwable $e) {
            $rows = [];
        }

        $out = [];
        foreach ($rows as $r) {
            $poNumber = trim((string) ($r['po_number'] ?? ''));
            if ($poNumber === '') {
                continue;
            }

            $out[] = [
                'po_number' => $poNumber,
                'vendor_name' => $r['vendor_name'] ?? null,
                'vendor_code' => $r['vendor_code'] ?? null,
                'vendor_type' => $r['vendor_type'] ?? null,
                'plant' => $r['plant'] ?? null,
                'doc_date' => $r['doc_date'] ?? null,
                'warehouse_name' => $r['warehouse_name'] ?? null,
                'direction' => $r['direction'] ?? null,
                'source' => 'sap',
            ];
        }

        return $out;
    }

    /**
     * Get detailed PO information with vendor resolution
     * Flow: SAP API -> Sync Vendor
     * Strict Mode: No Local DB fallback.
     */
    public function getPoDetail(string $poNumber): ?array
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return null;
        }

        // 1. Try SAP API first (Real-time data)
        try {
            $api = $this->sapPoService->getByPoNumber($poNumber);
            
            if ($api) {
                // Data found in SAP!
                $vendorCode = (string) ($api['vendor_code'] ?? '');
                $vendorName = (string) ($api['vendor_name'] ?? '');
                
                // Sync vendor to local DB if vendor code exists
                if ($vendorCode !== '') {
                    $this->sapVendorService->syncToLocal([
                        'vendor_code' => $vendorCode,
                        'vendor_name' => $vendorName
                    ]);
                }

                // Determine direction based on SAP fields:
                // - If CustomerCode exists => outbound
                // - Else SupplierCode exists => inbound
                $direction = (string) ($api['direction'] ?? '');
                if ($direction === '') {
                    $customerCode = (string) ($api['customer_code'] ?? '');
                    $supplierCode = (string) ($api['supplier_code'] ?? '');
                    if ($customerCode !== '') {
                        $direction = 'outbound';
                    } elseif ($supplierCode !== '') {
                        $direction = 'inbound';
                    }
                }

                if ($direction === '') {
                    $direction = 'inbound';
                }

                $partnerRole = (string) ($api['partner_role'] ?? '');
                $vendorType = $partnerRole !== '' ? $partnerRole : ($direction === 'outbound' ? 'customer' : 'supplier');

                return [
                    'po_number' => (string) ($api['po_number'] ?? $poNumber),
                    'plant' => (string) ($api['plant'] ?? ''),
                    'doc_date' => (string) ($api['doc_date'] ?? ''),
                    'warehouse_name' => (string) ($api['warehouse_name'] ?? ''),
                    'vendor_id' => 0, // We let Frontend handle lookup by code/name
                    'vendor_code' => $vendorCode,
                    'vendor_name' => $vendorName,
                    'vendor_type' => $vendorType,
                    'direction' => $direction,
                    'items' => is_array($api['items'] ?? null) ? $api['items'] : [],
                    'source' => 'sap'
                ];
            }
        } catch (\Exception $e) {
            // Log warning
            \Illuminate\Support\Facades\Log::warning("SAP PO lookup failed for $poNumber: " . $e->getMessage());
        }

        // 2. No Fallback
        // Strict SAP mode means if it's not in SAP, it doesn't exist.
        return null;
    }

    /**
     * Apply search conditions to query
     */
    private function applySearchConditions($query, string $q): void
    {
        if ($q !== '') {
            $isPgsql = DB::getDriverName() === 'pgsql';
            $likeOp = $isPgsql ? 'ilike' : 'like';
            $likeOpRaw = $isPgsql ? 'ILIKE' : 'LIKE';
            $qUpperLocal = strtoupper($q);
            $qNoPrefixLocal = preg_replace('/^(PO|DO)\s*/i', '', $qUpperLocal);
            $qNoPrefixLocal = trim($qNoPrefixLocal);

            $query->where(function ($sub) use ($q, $qNoPrefixLocal, $likeOp, $likeOpRaw) {
                $sub->where('p.po_number', $likeOp, '%' . $q . '%');

                if ($qNoPrefixLocal !== '' && $qNoPrefixLocal !== strtoupper($q)) {
                    $sub->orWhere('p.po_number', $likeOp, '%' . $qNoPrefixLocal . '%');
                }

                if ($qNoPrefixLocal !== '') {
                    $sub->orWhereRaw("(CASE WHEN LEFT(p.po_number, 2) IN ('PO', 'DO') THEN SUBSTRING(p.po_number, 3) ELSE p.po_number END) {$likeOpRaw} ?", ['%' . $qNoPrefixLocal . '%']);
                }
            });
        }
    }

    /**
     * Format search results
     */
    private function formatSearchResults($rows): array
    {
        $out = [];

        foreach ($rows as $r) {
            $poNumber = trim($r->po_number ?? '');
            if ($poNumber === '') {
                continue;
            }

            $vendorType = $r->vendor_type ?? null;
            $vendorCode = $r->vendor_code ?? null;
            $source = 'local';
            $direction = null;

            if ($vendorType === 'supplier') {
                $direction = 'inbound';
            } elseif ($vendorType === 'customer') {
                $direction = 'outbound';
            }

            $out[] = [
                'po_number' => $poNumber,
                'vendor_name' => $r->vendor_name ?? null,
                'vendor_code' => $vendorCode,
                'vendor_type' => $vendorType,
                'plant' => $r->plant ?? null,
                'doc_date' => $r->doc_date ?? null,
                'warehouse_name' => $r->warehouse_name ?? null,
                'direction' => $direction,
                'source' => $source
            ];
        }

        return $out;
    }

    /**
     * Find PO row with fallback logic
     */
    private function findPoRow(string $poNumber)
    {
        $select = $this->buildPoSelectColumns();

        $query = DB::table('po as p');

        if (Schema::hasColumn('po', 'bp_id')) {
            $query->leftJoin('business_partner as v', 'p.bp_id', '=', 'v.id');
            $select[] = 'v.bp_name as vendor_name';
            $select[] = 'v.bp_type as vendor_type';
            $select[] = 'v.bp_code as vendor_code';
        } elseif (Schema::hasColumn('po', 'vendor_name')) {
            $select[] = 'p.vendor_name as vendor_name';
        }

        $query->select($select);

        $row = $query->where('p.po_number', $poNumber)->first();

        if (!$row) {
            $row = $this->tryAlternativePoNumbers($poNumber, $select);
        }

        return $row;
    }

    /**
     * Build dynamic select columns for PO query
     */
    private function buildPoSelectColumns(): array
    {
        $select = ['p.po_number'];

        $columns = [
            'vendor_id' => 'vendor_id',
            'vendor_code' => 'vendor_code',
            'vendor_name' => 'vendor_name',
            'plant' => 'plant',
            'doc_date' => 'doc_date',
            'warehouse_name' => 'warehouse_name',
            'direction' => 'direction'
        ];

        foreach ($columns as $column => $alias) {
            if (Schema::hasColumn('po', $column)) {
                $select[] = "p.{$column}";
            }
        }

        return $select;
    }

    /**
     * Try alternative PO number formats
     */
    private function tryAlternativePoNumbers(string $poNumber, array $select)
    {
        $upper = strtoupper($poNumber);
        $noPrefix = preg_replace('/^(PO|DO)\s*/i', '', $upper);
        $noPrefix = trim($noPrefix);

        if ($noPrefix !== '') {
            $candidatePo = 'PO' . $noPrefix;
            $candidateDo = 'DO' . $noPrefix;

            return DB::table('po as p')
                ->select($select)
                ->whereIn('p.po_number', [$noPrefix, $candidatePo, $candidateDo])
                ->orderByRaw("CASE WHEN p.po_number = ? THEN 0 WHEN p.po_number = ? THEN 1 WHEN p.po_number = ? THEN 2 ELSE 3 END", [$upper, $candidatePo, $candidateDo])
                ->first();
        }

        return null;
    }

    /**
     * Build complete PO data with vendor resolution
     */
    private function buildPoData($row, string $poNumber): array
    {
        $data = [
            'po_number' => (string) ($row->po_number ?? ''),
            'plant' => (string) ($row->plant ?? ''),
            'doc_date' => (string) ($row->doc_date ?? ''),
            'warehouse_name' => (string) ($row->warehouse_name ?? ''),
            'vendor_name' => (string) ($row->vendor_name ?? ''),
            'vendor_type' => (string) ($row->vendor_type ?? ''),
            'direction' => str_starts_with(strtoupper($row->po_number ?? ''), 'DO') ? 'outbound' : 'inbound',
            'items' => [],
        ];

        // Try to resolve vendor by ID first
        $poVendorId = (int) ($row->vendor_id ?? 0);
        if ($poVendorId > 0) {
            $vendor = $this->resolveVendorById($poVendorId);
            if ($vendor) {
                return $this->mergeVendorData($data, $vendor);
            }
        }

        // Try to resolve vendor by code
        $vendorCode = strtoupper(trim($row->vendor_code ?? ''));
        if ($vendorCode !== '') {
            $vendor = $this->resolveVendorByCode($vendorCode);
            if ($vendor) {
                return $this->mergeVendorData($data, $vendor);
            }
        }

        // Try to resolve vendor by name
        $vendorName = trim($row->vendor_name ?? '');
        if ($vendorName !== '') {
            $vendor = $this->resolveVendorByName($vendorName);
            if ($vendor) {
                return $this->mergeVendorData($data, $vendor);
            }
        }

        return $data;
    }

    /**
     * Resolve vendor by ID
     */
    private function resolveVendorById(int $vendorId)
    {
        return DB::table('business_partner')
            ->where('id', $vendorId)
            ->select([
                'id',
                'bp_type as type',
                'bp_name as name',
                'bp_code as code',
            ])
            ->first();
    }

    /**
     * Resolve vendor by code
     */
    private function resolveVendorByCode(string $vendorCode)
    {
        return DB::table('business_partner')
            ->where('bp_code', $vendorCode)
            ->select([
                'id',
                'bp_type as type',
                'bp_name as name',
                'bp_code as code',
            ])
            ->first();
    }

    /**
     * Resolve vendor by name
     */
    private function resolveVendorByName(string $vendorName)
    {
        return DB::table('business_partner')
            ->whereRaw('LOWER(bp_name) = ?', [strtolower($vendorName)])
            ->select([
                'id',
                'bp_type as type',
                'bp_name as name',
                'bp_code as code',
            ])
            ->first();
    }

    /**
     * Merge vendor data into PO data
     */
    private function mergeVendorData(array $data, $vendor): array
    {
        $data['vendor_id'] = (int) ($vendor->id ?? 0);
        $data['vendor_type'] = (string) ($vendor->type ?? '');
        $data['vendor_name'] = (string) ($vendor->name ?? '');
        $data['vendor_code'] = (string) ($vendor->code ?? '');

        return $data;
    }
}
