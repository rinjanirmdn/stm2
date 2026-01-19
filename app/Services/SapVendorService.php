<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengambil data Vendor dari SAP API
 * 
 * Saat ini vendor data diambil dari response PO API (SupplierCode, SupplierName)
 * Jika nanti ada service vendor terpisah dari SAP, bisa dikembangkan di sini.
 */
class SapVendorService
{
    private string $baseUrl;
    private string $servicePath;
    private string $sapClient;
    private string $token;
    private string $username;
    private string $password;
    private bool $verifySsl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = trim((string) config('services.sap_vendor.base_url', ''));
        $this->servicePath = trim((string) config('services.sap_vendor.service_path', ''));
        $this->sapClient = trim((string) config('services.sap_vendor.sap_client', '210'));
        $this->token = trim((string) config('services.sap_vendor.token', ''));
        $this->username = trim((string) config('services.sap_vendor.username', ''));
        $this->password = (string) config('services.sap_vendor.password', '');
        $this->verifySsl = (bool) config('services.sap_vendor.verify_ssl', false);
        $this->timeout = (int) config('services.sap_vendor.timeout', 15);
    }

    /**
     * Check if SAP Vendor API is configured
     */
    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->servicePath !== '';
    }

    /**
     * Construct full API URL
     */
    private function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . $this->servicePath . $endpoint;
    }

    /**
     * Build HTTP request with SAP headers and authentication
     */
    private function buildRequest()
    {
        $req = Http::timeout($this->timeout);

        if ($this->token !== '') {
            $req = $req->withToken($this->token);
        } elseif ($this->username !== '') {
            $req = $req->withBasicAuth($this->username, $this->password);
        }

        if ($this->sapClient !== '') {
            $req = $req->withHeaders(['sap-client' => $this->sapClient]);
        }

        if (!$this->verifySsl) {
            $req = $req->withoutVerifying();
        }

        return $req->acceptJson();
    }

    /**
     * Search vendors by name or code
     * 
     * @param string $query Search query
     * @param int $limit Maximum results to return
     * @return array List of vendors
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // If SAP Vendor API is not configured, return empty
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            // OData filter for vendor search
            $filter = "\$filter=contains(VendorName,'{$query}') or contains(VendorCode,'{$query}')";
            $top = "\$top={$limit}";
            $endpoint = "/VendorSet?{$filter}&{$top}";
            
            $response = $this->buildRequest()->get($this->buildUrl($endpoint));

            if (!$response->successful()) {
                Log::warning('SAP Vendor search failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return [];
            }

            $json = $response->json();
            $vendors = [];

            if (isset($json['value']) && is_array($json['value'])) {
                foreach ($json['value'] as $item) {
                    $vendors[] = $this->mapVendorResponse($item);
                }
            }

            return $vendors;
        } catch (\Throwable $e) {
            Log::error('SAP Vendor search error', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            return [];
        }
    }

    /**
     * Get vendor by code
     * 
     * @param string $vendorCode Vendor code
     * @return array|null Vendor data or null if not found
     */
    public function getByCode(string $vendorCode): ?array
    {
        $vendorCode = trim($vendorCode);
        if ($vendorCode === '') {
            return null;
        }

        // Check cache first
        $cacheKey = "sap_vendor:{$vendorCode}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // If SAP Vendor API is not configured, return null
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $endpoint = "/VendorSet('{$vendorCode}')";
            $response = $this->buildRequest()->get($this->buildUrl($endpoint));

            if (!$response->successful()) {
                Log::warning('SAP Vendor get failed', [
                    'status' => $response->status(),
                    'vendor_code' => $vendorCode,
                ]);
                return null;
            }

            $json = $response->json();
            $vendor = $this->mapVendorResponse($json);

            // Cache for 1 hour
            Cache::put($cacheKey, $vendor, 3600);

            return $vendor;
        } catch (\Throwable $e) {
            Log::error('SAP Vendor get error', [
                'error' => $e->getMessage(),
                'vendor_code' => $vendorCode,
            ]);
            return null;
        }
    }

    /**
     * Get vendor info from PO response
     * This is used when we already have vendor data from PO API
     * 
     * @param string $supplierCode Supplier code from PO
     * @param string $supplierName Supplier name from PO
     * @return array Vendor data
     */
    public function getFromPoResponse(string $supplierCode, string $supplierName): array
    {
        return [
            'vendor_code' => $supplierCode,
            'vendor_name' => $supplierName,
            'source' => 'po_api',
        ];
    }

    /**
     * Map SAP vendor response to standard format
     */
    private function mapVendorResponse(array $data): array
    {
        return [
            'vendor_code' => (string) ($data['VendorCode'] ?? $data['LIFNR'] ?? $data['SupplierCode'] ?? ''),
            'vendor_name' => (string) ($data['VendorName'] ?? $data['NAME1'] ?? $data['SupplierName'] ?? ''),
            'address' => (string) ($data['Address'] ?? $data['STRAS'] ?? ''),
            'city' => (string) ($data['City'] ?? $data['ORT01'] ?? ''),
            'phone' => (string) ($data['Phone'] ?? $data['TELF1'] ?? ''),
            'email' => (string) ($data['Email'] ?? $data['SMTP_ADDR'] ?? ''),
            'status' => (string) ($data['Status'] ?? 'ACTIVE'),
            'source' => 'sap_api',
        ];
    }

    /**
     * Fallback: Search vendors from local database
     */
    private function searchFromLocal(string $query, int $limit): array
    {
        try {
            $vendors = \App\Models\Vendor::where('bp_name', 'LIKE', "%{$query}%")
                ->orWhere('bp_code', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get();

            return $vendors->map(function ($v) {
                return [
                    'vendor_code' => $v->bp_code,
                    'vendor_name' => $v->bp_name,
                    'source' => 'local_db',
                ];
            })->toArray();
        } catch (\Throwable $e) {
            Log::error('Local vendor search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fallback: Get vendor from local database
     */
    private function getFromLocal(string $vendorCode): ?array
    {
        try {
            $vendor = \App\Models\Vendor::where('bp_code', $vendorCode)->first();

            if (!$vendor) {
                return null;
            }

            return [
                'vendor_code' => $vendor->bp_code,
                'vendor_name' => $vendor->bp_name,
                'source' => 'local_db',
            ];
        } catch (\Throwable $e) {
            Log::error('Local vendor get error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Sync vendor from SAP to local database
     * Useful for caching vendor master data
     */
    public function syncToLocal(array $vendorData): ?\App\Models\Vendor
    {
        if (empty($vendorData['vendor_code'])) {
            return null;
        }

        try {
            return \App\Models\Vendor::updateOrCreate(
                ['bp_code' => $vendorData['vendor_code']],
                [
                    'bp_name' => $vendorData['vendor_name'] ?? '',
                    'bp_type' => 'VENDOR',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Vendor sync error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Health check for SAP Vendor API
     */
    public function healthCheck(): array
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'not_configured',
                'message' => 'SAP Vendor API is not configured. Using local database as fallback.',
            ];
        }

        try {
            $response = $this->buildRequest()->get($this->buildUrl('/$metadata'));

            return [
                'status' => $response->successful() ? 'connected' : 'error',
                'http_status' => $response->status(),
                'response_time_ms' => $response->handlerStats()['total_time'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
