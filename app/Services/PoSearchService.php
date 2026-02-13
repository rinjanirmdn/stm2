<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PoSearchService
{
    public function __construct(
        private readonly SapPoService $sapPoService
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

        $lookupPo = $poNumber;
        $digitsOnly = preg_replace('/\D+/', '', $poNumber);
        if (is_string($digitsOnly) && strlen($digitsOnly) >= 5) {
            $lookupPo = $digitsOnly;
        }

        // 1. Try SAP API first (Real-time data)
        try {
            $api = $this->sapPoService->getByPoNumber($lookupPo);

            if ($api) {
                // Data found in SAP!
                $vendorCode = (string) ($api['vendor_code'] ?? '');
                $vendorName = (string) ($api['vendor_name'] ?? '');
                
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
                    'vendor_code' => $vendorCode,
                    'vendor_name' => $vendorName,
                    'vendor_type' => $vendorType,
                    'supplier_code' => (string) ($api['supplier_code'] ?? $vendorCode),
                    'supplier_name' => (string) ($api['supplier_name'] ?? $vendorName),
                    'customer_code' => (string) ($api['customer_code'] ?? ''),
                    'customer_name' => (string) ($api['customer_name'] ?? ''),
                    'direction' => $direction,
                    'source' => 'sap'
                ];
            }
        } catch (\Exception $e) {
            // Log warning
            \Illuminate\Support\Facades\Log::warning("SAP PO lookup failed for $poNumber (lookup=$lookupPo): " . $e->getMessage());
        }

        // 2. No Fallback
        // Strict SAP mode means if it's not in SAP, it doesn't exist.
        return null;
    }

}
