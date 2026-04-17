<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SapSoService
{
    /**
     * Build the detail request config (URL + auth) for an SO number.
     * Used by PoSearchService for parallel Http::pool() calls.
     *
     * @return array{url: string, username: string, password: string, sap_client: string, timeout: int, verify_ssl: bool}|null
     */
    public function buildDetailRequestConfig(string $soNumber): ?array
    {
        $soNumber = trim($soNumber);
        if ($soNumber === '') {
            return null;
        }

        $baseUrl = trim((string) config('services.sap_so.base_url', ''));
        $servicePath = trim((string) config('services.sap_so.service_path', ''));
        $odataEndpoint = trim((string) config('services.sap_so.odata_detail_endpoint', ''));

        if ($baseUrl === '' || $odataEndpoint === '') {
            return null;
        }

        $endpoint = str_replace('{so}', $soNumber, $odataEndpoint);
        $url = rtrim($baseUrl, '/').$servicePath.$endpoint;

        return [
            'url' => $url,
            'username' => trim((string) config('services.sap_so.username', '')),
            'password' => (string) config('services.sap_so.password', ''),
            'sap_client' => trim((string) config('services.sap_so.sap_client', '210')),
            'timeout' => (int) config('services.sap_so.timeout', 15),
            'verify_ssl' => (bool) config('services.sap_so.verify_ssl', false),
        ];
    }

    /**
     * Parse SO OData JSON response into normalized array.
     * Used by PoSearchService after parallel Http::pool() call.
     */
    public function parseDetailResponse(?array $json, string $soNumber): ?array
    {
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
        $custName = trim((string) ($first['CustName'] ?? ''));
        $docDate = (string) ($first['DocDate'] ?? '');
        $soNo = (string) ($first['SONo'] ?? $soNumber);

        return [
            'po_number' => $soNo,
            'vendor_code' => '',
            'vendor_name' => $custName,
            'supplier_code' => '',
            'supplier_name' => '',
            'customer_code' => '',
            'customer_name' => $custName,
            'partner_role' => 'customer',
            'direction' => 'outbound',
            'doc_date' => $docDate,
            'doc_type' => 'so',
            'plant' => '',
            'warehouse_name' => '',
        ];
    }

    /**
     * Get SO detail by SO number using OData V4 API
     * Endpoint: ZSD_SO_DETAIL(so_no='{so}')/Set
     *
     * @return array|null Normalized detail array, or null if not found
     */
    public function getBySoNumber(string $soNumber): ?array
    {
        $soNumber = trim($soNumber);
        if ($soNumber === '') {
            return null;
        }

        $baseUrl = trim((string) config('services.sap_so.base_url', ''));
        $servicePath = trim((string) config('services.sap_so.service_path', ''));
        $odataEndpoint = trim((string) config('services.sap_so.odata_detail_endpoint', ''));
        $username = trim((string) config('services.sap_so.username', ''));
        $password = (string) config('services.sap_so.password', '');
        $timeout = (int) config('services.sap_so.timeout', 15);
        $sapClient = trim((string) config('services.sap_so.sap_client', '210'));
        $verifySsl = (bool) config('services.sap_so.verify_ssl', false);

        if ($baseUrl === '' || $odataEndpoint === '') {
            return null;
        }

        // Replace {so} placeholder with actual SO number
        $endpoint = str_replace('{so}', $soNumber, $odataEndpoint);
        $url = rtrim($baseUrl, '/').$servicePath.$endpoint;

        try {
            $req = $this->buildSapRequest($timeout, $username, $password, $sapClient, $verifySsl)
                ->acceptJson();

            $res = $req->get($url);

            \Log::info('SAP SO Detail', [
                'url' => $url,
                'status' => $res->status(),
                'body' => substr($res->body(), 0, 500),
            ]);

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
            $custName = trim((string) ($first['CustName'] ?? ''));
            $docDate = (string) ($first['DocDate'] ?? '');
            $soNo = (string) ($first['SONo'] ?? $soNumber);

            return [
                'po_number' => $soNo,
                // Backward-compatible: vendor_name = customer for SO
                'vendor_code' => '',
                'vendor_name' => $custName,
                // Explicit fields
                'supplier_code' => '',
                'supplier_name' => '',
                'customer_code' => '',
                'customer_name' => $custName,
                'partner_role' => 'customer',
                'direction' => 'outbound',
                'doc_date' => $docDate,
                'doc_type' => 'so',
                'plant' => '',
                'warehouse_name' => '',
            ];
        } catch (\Throwable $e) {
            \Log::warning('SAP SO Detail Error', [
                'soNumber' => $soNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildSapRequest(int $timeout, string $username, string $password, string $sapClient, bool $verifySsl)
    {
        $req = Http::timeout($timeout);

        if ($username !== '') {
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
