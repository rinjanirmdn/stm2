<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SapPoService
{
    private array $dummyPurchaseOrders = [
        [
            'po_number' => '4500000001',
            'vendor_name' => 'PT Dummy Supplier 1',
            'plant' => 'WH1',
            'warehouse_name' => 'Warehouse 1',
            'doc_date' => '2025-12-01',
            'items' => [
                ['material' => 'MAT-0001', 'description' => 'Dummy Item A', 'qty' => 10, 'uom' => 'PCS'],
                ['material' => 'MAT-0002', 'description' => 'Dummy Item B', 'qty' => 5, 'uom' => 'PCS'],
            ],
        ],
        [
            'po_number' => '4500000002',
            'vendor_name' => 'PT Dummy Supplier 2',
            'plant' => 'WH2',
            'warehouse_name' => 'Warehouse 2',
            'doc_date' => '2025-12-05',
            'items' => [
                ['material' => 'MAT-0100', 'description' => 'Dummy Item C', 'qty' => 20, 'uom' => 'BOX'],
            ],
        ],
        [
            'po_number' => '4500001234',
            'vendor_name' => 'PT Example Vendor',
            'plant' => 'WH1',
            'warehouse_name' => 'Warehouse 1',
            'doc_date' => '2025-12-10',
            'items' => [
                ['material' => 'MAT-9999', 'description' => 'Dummy Item Z', 'qty' => 1, 'uom' => 'PCS'],
            ],
        ],
    ];


    /**
     * Search POs by partial number using OData $filter
     * Endpoint: ZPOA_DTL_LIST/Set?$filter=contains(PoNo,'query')&$top=50
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $baseUrl = trim((string) config('services.sap_po.base_url', ''));
        $servicePath = trim((string) config('services.sap_po.service_path', ''));
        $token = trim((string) config('services.sap_po.token', ''));
        $username = trim((string) config('services.sap_po.username', ''));
        $password = (string) config('services.sap_po.password', '');
        $timeout = (int) config('services.sap_po.timeout', 15);
        $sapClient = trim((string) config('services.sap_po.sap_client', '210'));
        $verifySsl = (bool) config('services.sap_po.verify_ssl', false);

        // NOTE:
        // This SAP Gateway endpoint rejects $filter/$top and only allows limited query options.
        // We fetch pages without server-side filtering and apply filtering client-side.
        // Some gateways are also picky about collection URLs, so we try a fallback without '/Set'.
        $urlWithSet = rtrim($baseUrl, '/') . $servicePath . '/ZPOA_DTL_LIST/Set';
        $urlWithoutSet = rtrim($baseUrl, '/') . $servicePath . '/ZPOA_DTL_LIST';

        try {
            $req = $this->buildSapRequest($timeout, $token, $username, $password, $sapClient, $verifySsl)->acceptJson();

            // Only allowed options: keep it minimal
            $queryParams = [
                '$select' => 'PoNo,DocDate,SupplierCode,SupplierName,CustomerCode,CustomerName',
            ];

            foreach ([$urlWithSet, $urlWithoutSet] as $baseListUrl) {
                $results = [];
                $seen = [];
                $maxPages = 8;
                $page = 0;
                $nextUrl = $baseListUrl;

                while ($nextUrl !== '' && $page < $maxPages && count($results) < $limit) {
                    $page++;

                    $response = $req->get($nextUrl, $nextUrl === $baseListUrl ? $queryParams : []);

                    \Log::info('SAP PO Search', [
                        'url' => $nextUrl,
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 500),
                    ]);

                    if (!$response->successful()) {
                        // Try next URL variant
                        break;
                    }

                    $json = $response->json();
                    $rows = is_array($json) ? ($json['value'] ?? []) : [];
                    if (!is_array($rows)) {
                        $rows = [];
                    }

                    foreach ($rows as $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        $poNo = (string)($item['PoNo'] ?? '');
                        if ($poNo === '') {
                            continue;
                        }

                        // Client-side contains filter
                        if (stripos($poNo, $query) === false) {
                            continue;
                        }

                        if (isset($seen[$poNo])) {
                            continue;
                        }
                        $seen[$poNo] = true;

                        $supplierCode = (string) ($item['SupplierCode'] ?? '');
                        $supplierName = (string) ($item['SupplierName'] ?? '');
                        $customerCode = (string) ($item['CustomerCode'] ?? '');
                        $customerName = (string) ($item['CustomerName'] ?? '');

                        $partnerRole = '';
                        $direction = '';
                        $partnerCode = '';
                        $partnerName = '';

                        if ($customerCode !== '') {
                            $partnerRole = 'customer';
                            $direction = 'outbound';
                            $partnerCode = $customerCode;
                            $partnerName = $customerName;
                        } else {
                            $partnerRole = 'supplier';
                            $direction = 'inbound';
                            $partnerCode = $supplierCode;
                            $partnerName = $supplierName;
                        }

                        $results[] = [
                            'po_number' => $poNo,
                            // Backward-compatible fields used across the app
                            'vendor_code' => $partnerCode,
                            'vendor_name' => $partnerName,
                            // Explicit fields (new)
                            'supplier_code' => $supplierCode,
                            'supplier_name' => $supplierName,
                            'customer_code' => $customerCode,
                            'customer_name' => $customerName,
                            'partner_role' => $partnerRole,
                            'direction' => $direction,
                            'doc_date' => (string)($item['DocDate'] ?? ''),
                            'plant' => '',
                            'warehouse_name' => '',
                            'items' => [],
                        ];

                        if (count($results) >= $limit) {
                            break;
                        }
                    }

                    $nextUrl = '';
                    if (is_array($json)) {
                        $nextUrl = (string) ($json['@odata.nextLink'] ?? $json['odata.nextLink'] ?? '');
                    }
                }

                if (!empty($results)) {
                    return $results;
                }
            }

            return [];
        } catch (\Throwable $e) {
            \Log::warning('SAP PO Search Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search POs by period using ZPO_HDR_LIST endpoint
     * @param string $period Format YYYY.MM (e.g. 2026.01)
     */
    public function searchByPeriod(string $period): array
    {
        $baseUrl = trim((string) config('services.sap_po.base_url', ''));
        $servicePath = trim((string) config('services.sap_po.service_path', ''));
        $token = trim((string) config('services.sap_po.token', ''));
        $username = trim((string) config('services.sap_po.username', ''));
        $password = (string) config('services.sap_po.password', '');
        $timeout = (int) config('services.sap_po.timeout', 15);
        $sapClient = trim((string) config('services.sap_po.sap_client', '210'));
        $verifySsl = (bool) config('services.sap_po.verify_ssl', false);

        $endpoint = "/ZPO_HDR_LIST(period='{$period}')/Set";
        $url = rtrim($baseUrl, '/') . $servicePath . $endpoint;

        try {
            $req = $this->buildSapRequest($timeout, $token, $username, $password, $sapClient, $verifySsl)->acceptJson();
            $response = $req->get($url);
            
            if ($response->successful()) {
                $json = $response->json();
                return $json['value'] ?? [];
            }
        } catch (\Throwable $e) {
            // Log::warning...
        }
        return [];
    }

    public function debugList($period = '2025.11')
    {
        return $this->searchByPeriod($period);
    }

    public function getByPoNumber(string $poNumber): ?array
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return null;
        }

        $baseUrl = trim((string) config('services.sap_po.base_url', ''));
        if ($baseUrl !== '') {
            $odataEndpoint = trim((string) config('services.sap_po.odata_detail_endpoint', ''));
            if ($odataEndpoint !== '') {
                return $this->getByPoNumberOdata($poNumber, $baseUrl, $odataEndpoint);
            }

            $endpoint = (string) config('services.sap_po.detail_endpoint', '/po/{po}');
            $endpoint = str_replace('{po}', rawurlencode($poNumber), $endpoint);
            $url = rtrim($baseUrl, '/') . $endpoint;
            $token = trim((string) config('services.sap_po.token', ''));
            $username = trim((string) config('services.sap_po.username', ''));
            $password = (string) config('services.sap_po.password', '');
            $timeout = (int) config('services.sap_po.timeout', 10);
            $sapClient = trim((string) config('services.sap_po.sap_client', ''));
            $verifySsl = (bool) config('services.sap_po.verify_ssl', true);

            try {
                $req = $this->buildSapRequest($timeout, $token, $username, $password, $sapClient, $verifySsl);

                $res = $req->get($url);
                if (! $res->successful()) {
                    return null;
                }

                $json = $res->json();
                $data = null;
                if (is_array($json) && array_key_exists('data', $json) && is_array($json['data'])) {
                    $data = $json['data'];
                } elseif (is_array($json)) {
                    $data = $json;
                }
                if (! is_array($data)) {
                    return null;
                }

                return [
                    'po_number' => (string) ($data['po_number'] ?? $data['poNumber'] ?? $data['po'] ?? $poNumber),
                    'vendor_code' => (string) ($data['vendor_code'] ?? $data['vendorCode'] ?? ''),
                    'vendor_name' => (string) ($data['vendor_name'] ?? $data['vendorName'] ?? $data['vendor'] ?? ''),
                    'plant' => (string) ($data['plant'] ?? $data['wh'] ?? ''),
                    'warehouse_name' => (string) ($data['warehouse_name'] ?? $data['warehouseName'] ?? ''),
                    'doc_date' => (string) ($data['doc_date'] ?? $data['docDate'] ?? ''),
                    'items' => is_array($data['items'] ?? null) ? $data['items'] : [],
                ];
            } catch (\Throwable $e) {
                return null;
            }
        }

        foreach ($this->dummyPurchaseOrders as $po) {
            if ((string) ($po['po_number'] ?? '') === $poNumber) {
                return $po;
            }
        }

        return null;
    }

    private function getByPoNumberOdata(string $poNumber, string $baseUrl, string $odataDetailEndpoint): ?array
    {
        $token = trim((string) config('services.sap_po.token', ''));
        $username = trim((string) config('services.sap_po.username', ''));
        $password = (string) config('services.sap_po.password', '');
        $timeout = (int) config('services.sap_po.timeout', 15);
        $sapClient = trim((string) config('services.sap_po.sap_client', '210'));
        $verifySsl = (bool) config('services.sap_po.verify_ssl', false);
        $servicePath = trim((string) config('services.sap_po.service_path', ''));

        // Replace {po} placeholder with actual PO number
        $endpoint = str_replace('{po}', $poNumber, $odataDetailEndpoint);
        $endpoint = str_replace("'__PO__'", "'" . $poNumber . "'", $endpoint);
        
        // Build full URL: base_url + service_path + endpoint
        $url = rtrim($baseUrl, '/') . $servicePath . $endpoint;

        try {
            $req = $this->buildSapRequest($timeout, $token, $username, $password, $sapClient, $verifySsl)->acceptJson();

            $res = $req->get($url);
            if (! $res->successful()) {
                return null;
            }

            $json = $res->json();
            if (! is_array($json)) {
                return null;
            }

            $rows = [];
            if (array_key_exists('value', $json) && is_array($json['value'])) {
                $rows = $json['value'];
            }

            if (empty($rows) || ! is_array($rows[0] ?? null)) {
                return null;
            }

            $first = $rows[0];
            $supplierCode = (string) ($first['SupplierCode'] ?? '');
            $supplierName = (string) ($first['SupplierName'] ?? '');
            $customerCode = (string) ($first['CustomerCode'] ?? '');
            $customerName = (string) ($first['CustomerName'] ?? '');
            $docDate = (string) ($first['DocDate'] ?? '');

            $partnerRole = '';
            $direction = '';
            $partnerCode = '';
            $partnerName = '';
            if ($customerCode !== '') {
                $partnerRole = 'customer';
                $direction = 'outbound';
                $partnerCode = $customerCode;
                $partnerName = $customerName;
            } else {
                $partnerRole = 'supplier';
                $direction = 'inbound';
                $partnerCode = $supplierCode;
                $partnerName = $supplierName;
            }

            $items = [];
            foreach ($rows as $it) {
                if (! is_array($it)) {
                    continue;
                }

                if (empty($items)) {
                    $qtyFields = [];
                    foreach ($it as $k => $v) {
                        if (stripos((string) $k, 'qty') !== false) {
                            $qtyFields[$k] = $v;
                        }
                    }

                    \Log::info('SAP PO Detail Row Sample', [
                        'po' => (string) ($first['PoNo'] ?? $poNumber),
                        'keys' => array_slice(array_keys($it), 0, 60),
                        'qty_fields' => $qtyFields,
                        'ItemNo' => $it['ItemNo'] ?? null,
                        'MaterialCode' => $it['MaterialCode'] ?? null,
                        'MaterialName' => $it['MaterialName'] ?? null,
                        'QtyPO' => $it['QtyPO'] ?? null,
                        'UnitPO' => $it['UnitPO'] ?? null,
                        'QtyGRTotal' => $it['QtyGRTotal'] ?? null,
                    ]);
                }

                $items[] = [
                    'item_no' => (string) ($it['ItemNo'] ?? ''),
                    'material' => (string) ($it['MaterialCode'] ?? ''),
                    'description' => (string) ($it['MaterialName'] ?? ''),
                    'qty' => $it['QtyPO'] ?? null,
                    'uom' => (string) ($it['UnitPO'] ?? ''),
                    'qty_gr_total' => $it['QtyGRTotal'] ?? null,
                ];
            }

            return [
                'po_number' => (string) ($first['PoNo'] ?? $poNumber),
                // Backward-compatible
                'vendor_code' => $partnerCode,
                'vendor_name' => $partnerName,
                // Explicit fields
                'supplier_code' => $supplierCode,
                'supplier_name' => $supplierName,
                'customer_code' => $customerCode,
                'customer_name' => $customerName,
                'partner_role' => $partnerRole,
                'direction' => $direction,
                'doc_date' => $docDate,
                'plant' => '',
                'warehouse_name' => '',
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildSapRequest(int $timeout, string $token, string $username, string $password, string $sapClient, bool $verifySsl)
    {
        $req = Http::timeout($timeout);

        if ($token !== '') {
            $req = $req->withToken($token);
        } elseif ($username !== '') {
            $req = $req->withBasicAuth($username, $password);
        }

        if ($sapClient !== '') {
            $req = $req->withHeaders(['sap-client' => $sapClient]);
        }

        if (! $verifySsl) {
            $req = $req->withoutVerifying();
        }

        return $req;
    }
}
