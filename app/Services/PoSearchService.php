<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PoSearchService
{
    public function __construct(
        private readonly SapPoService $sapPoService,
        private readonly SapSoService $sapSoService
    ) {}

    /**
     * Search PO/SO numbers with priority to SAP, fallback to local DB
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

                    if ($firstChar === '8' || strtoupper($firstTwo) === 'SO') {
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
                        'source' => 'sap',
                    ];
                }
            } catch (\Exception $e) {
                // Silent fail
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
     * Get detailed PO/SO information with vendor resolution.
     * Flow: PO API + SO API called in PARALLEL via Http::pool()
     *       → return whichever has data (PO prioritized).
     * Strict Mode: No Local DB fallback.
     */
    public function getPoDetail(string $poNumber): ?array
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return null;
        }

        $lookupNumber = $poNumber;
        $digitsOnly = preg_replace('/\D+/', '', $poNumber);
        if (is_string($digitsOnly) && strlen($digitsOnly) >= 5) {
            $lookupNumber = $digitsOnly;
        }

        // Build request configs for both PO and SO APIs
        $poConfig = $this->sapPoService->buildDetailRequestConfig($lookupNumber);
        $soConfig = $this->sapSoService->buildDetailRequestConfig($lookupNumber);

        // If neither config is available, nothing to do
        if ($poConfig === null && $soConfig === null) {
            return null;
        }

        try {
            // Fire both requests in PARALLEL using Laravel Http::pool()
            // Both HTTP calls start at the same time — total wait = max(PO_time, SO_time)
            $responses = Http::pool(function (Pool $pool) use ($poConfig, $soConfig) {
                if ($poConfig !== null) {
                    $poReq = $pool->as('po')
                        ->timeout($poConfig['timeout'])
                        ->acceptJson();
                    if ($poConfig['username'] !== '') {
                        $poReq = $poReq->withBasicAuth($poConfig['username'], $poConfig['password']);
                    }
                    if ($poConfig['sap_client'] !== '') {
                        $poReq = $poReq->withHeaders(['sap-client' => $poConfig['sap_client']]);
                    }
                    if (! $poConfig['verify_ssl']) {
                        $poReq = $poReq->withoutVerifying();
                    }
                    $poReq->get($poConfig['url']);
                }

                if ($soConfig !== null) {
                    $soReq = $pool->as('so')
                        ->timeout($soConfig['timeout'])
                        ->acceptJson();
                    if ($soConfig['username'] !== '') {
                        $soReq = $soReq->withBasicAuth($soConfig['username'], $soConfig['password']);
                    }
                    if ($soConfig['sap_client'] !== '') {
                        $soReq = $soReq->withHeaders(['sap-client' => $soConfig['sap_client']]);
                    }
                    if (! $soConfig['verify_ssl']) {
                        $soReq = $soReq->withoutVerifying();
                    }
                    $soReq->get($soConfig['url']);
                }
            });

            // Check PO response first (priority)
            if ($poConfig !== null && isset($responses['po']) && $responses['po']->successful()) {
                $poData = $this->sapPoService->parseDetailResponse($responses['po']->json(), $lookupNumber);
                if ($poData !== null) {
                    return $this->normalizePoResult($poData, $poNumber);
                }
            }

            // Check SO response
            if ($soConfig !== null && isset($responses['so']) && $responses['so']->successful()) {
                $soData = $this->sapSoService->parseDetailResponse($responses['so']->json(), $lookupNumber);
                if ($soData !== null) {
                    return $this->normalizeSoResult($soData, $poNumber);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("SAP parallel lookup failed for $poNumber (lookup=$lookupNumber): ".$e->getMessage());
        }

        // Nothing found in either API
        return null;
    }

    /**
     * Normalize PO API result to standard format
     */
    private function normalizePoResult(array $api, string $originalNumber): array
    {
        $vendorCode = (string) ($api['vendor_code'] ?? '');
        $vendorName = (string) ($api['vendor_name'] ?? '');

        // Determine direction based on SAP fields
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
            'po_number' => (string) ($api['po_number'] ?? $originalNumber),
            'plant' => (string) ($api['plant'] ?? ''),
            'doc_date' => (string) ($api['doc_date'] ?? ''),
            'warehouse_name' => (string) ($api['warehouse_name'] ?? ''),
            'vendor_code' => $vendorCode,
            'vendor_name' => $vendorName,
            'vendor_type' => $vendorType,
            'supplier_code' => (string) ($api['supplier_code'] ?? $vendorCode),
            'supplier_name' => (string) ($api['supplier_name'] ?? $vendorName),
            'customer_code' => (string) ($api['customer_code'] ?? ''),
            'customer_name' => (string) ($api['customer_name'] ?? ''),
            'direction' => $direction,
            'doc_type' => 'po',
            'source' => 'sap',
        ];
    }

    /**
     * Normalize SO API result to standard format
     */
    private function normalizeSoResult(array $api, string $originalNumber): array
    {
        return [
            'po_number' => (string) ($api['po_number'] ?? $originalNumber),
            'plant' => (string) ($api['plant'] ?? ''),
            'doc_date' => (string) ($api['doc_date'] ?? ''),
            'warehouse_name' => (string) ($api['warehouse_name'] ?? ''),
            'vendor_code' => (string) ($api['vendor_code'] ?? ''),
            'vendor_name' => (string) ($api['vendor_name'] ?? ''),
            'vendor_type' => 'customer',
            'supplier_code' => '',
            'supplier_name' => '',
            'customer_code' => (string) ($api['customer_code'] ?? ''),
            'customer_name' => (string) ($api['customer_name'] ?? ''),
            'direction' => 'outbound',
            'doc_type' => 'so',
            'source' => 'sap',
        ];
    }
}
