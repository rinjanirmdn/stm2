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

    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $baseUrl = trim((string) config('services.sap_po.base_url', ''));
        if ($baseUrl !== '') {
            $url = rtrim($baseUrl, '/') . (string) config('services.sap_po.search_endpoint', '/po/search');
            $token = trim((string) config('services.sap_po.token', ''));
            $username = trim((string) config('services.sap_po.username', ''));
            $password = (string) config('services.sap_po.password', '');
            $timeout = (int) config('services.sap_po.timeout', 10);
            $sapClient = trim((string) config('services.sap_po.sap_client', ''));
            $verifySsl = (bool) config('services.sap_po.verify_ssl', true);

            try {
                $req = $this->buildSapRequest($timeout, $token, $username, $password, $sapClient, $verifySsl);

                $res = $req->get($url, [
                    'q' => $query,
                    'limit' => $limit,
                ]);

                if (! $res->successful()) {
                    return [];
                }

                $json = $res->json();
                $items = [];
                if (is_array($json) && array_key_exists('items', $json) && is_array($json['items'])) {
                    $items = $json['items'];
                } elseif (is_array($json)) {
                    $items = $json;
                }

                $out = [];
                foreach ($items as $it) {
                    if (! is_array($it)) {
                        continue;
                    }
                    $poNumber = trim((string) ($it['po_number'] ?? $it['poNumber'] ?? $it['po'] ?? ''));
                    if ($poNumber === '') {
                        continue;
                    }
                    $vendorCode = (string) ($it['vendor_code'] ?? $it['vendorCode'] ?? $it['vendor_id'] ?? $it['vendorId'] ?? '');
                    $vendorName = (string) ($it['vendor_name'] ?? $it['vendorName'] ?? $it['vendor'] ?? '');
                    $plant = (string) ($it['plant'] ?? $it['wh'] ?? '');

                    $label = $poNumber;
                    if (trim($vendorName) !== '') {
                        $label .= ' - ' . trim($vendorName);
                    }

                    $out[] = [
                        'po_number' => $poNumber,
                        'label' => $label,
                        'vendor_code' => $vendorCode,
                        'vendor_name' => $vendorName,
                        'plant' => $plant,
                    ];

                    if (count($out) >= $limit) {
                        break;
                    }
                }

                return $out;
            } catch (\Throwable $e) {
                return [];
            }
        }

        $out = [];
        foreach ($this->dummyPurchaseOrders as $po) {
            $poNumber = (string) ($po['po_number'] ?? '');
            $vendorName = (string) ($po['vendor_name'] ?? '');
            $plant = (string) ($po['plant'] ?? '');

            $hay = strtolower($poNumber . ' ' . $vendorName . ' ' . $plant);
            if (strpos($hay, strtolower($query)) === false) {
                continue;
            }

            $out[] = [
                'po_number' => $poNumber,
                'label' => $poNumber . ' - ' . $vendorName,
                'vendor_name' => $vendorName,
                'plant' => $plant,
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
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
                    'vendor_code' => (string) ($data['vendor_code'] ?? $data['vendorCode'] ?? $data['vendor_id'] ?? $data['vendorId'] ?? ''),
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
            $vendorCode = (string) ($first['SupplierCode'] ?? '');
            $vendorName = (string) ($first['SupplierName'] ?? '');
            $docDate = (string) ($first['DocDate'] ?? '');

            $items = [];
            foreach ($rows as $it) {
                if (! is_array($it)) {
                    continue;
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
                'vendor_code' => $vendorCode,
                'vendor_name' => $vendorName,
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
