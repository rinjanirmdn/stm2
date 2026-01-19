<?php

namespace App\Http\Controllers;

use App\Services\SapService;
use App\Services\SapPoService;
use App\Services\SapVendorService;
use Illuminate\Http\Request;

class SapController extends Controller
{
    private $sapService;
    private $sapPoService;
    private $sapVendorService;
    
    public function __construct(SapService $sapService, SapPoService $sapPoService, SapVendorService $sapVendorService)
    {
        $this->sapService = $sapService;
        $this->sapPoService = $sapPoService;
        $this->sapVendorService = $sapVendorService;
    }
    
    /**
     * Search PO in SAP (using old SapService - demo mode)
     */
    public function searchPO(Request $request)
    {
        $request->validate([
            'po_number' => 'required|string|max:50',
            'vendor_code' => 'nullable|string|max:50'
        ]);
        
        $result = $this->sapService->searchPO(
            $request->get('po_number'),
            $request->get('vendor_code')
        );
        
        return response()->json([
            'success' => $result !== null,
            'data' => $result
        ]);
    }
    
    /**
     * Get PO details from SAP using OData V4 API
     * This is the main endpoint for fetching real SAP data
     */
    public function getPODetails($poNumber)
    {
        // Use SapPoService (OData V4)
        $result = $this->sapPoService->getByPoNumber($poNumber);
        
        if ($result === null) {
            // Fallback to old SapService (demo mode)
            $result = $this->sapService->getPODetails($poNumber);
        }
        
        return response()->json([
            'success' => $result !== null,
            'data' => $result,
            'source' => $result !== null ? 'sap_odata' : 'not_found'
        ]);
    }
    
    /**
     * Search PO using OData V4 API
     */
    public function searchPOOdata(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);
        
        $result = $this->sapPoService->search(
            $request->get('q'),
            $request->get('limit', 10)
        );
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ]);
    }
    
    /**
     * Get Vendor details from SAP
     */
    public function getVendor($vendorCode)
    {
        $result = $this->sapVendorService->getByCode($vendorCode);
        
        return response()->json([
            'success' => $result !== null,
            'data' => $result
        ]);
    }
    
    /**
     * Search vendors
     */
    public function searchVendor(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:100',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);
        
        $result = $this->sapVendorService->search(
            $request->get('q'),
            $request->get('limit', 20)
        );
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ]);
    }
    
    /**
     * Sync slot status to SAP
     */
    public function syncSlot(Request $request)
    {
        $request->validate([
            'slot_id' => 'required|integer|exists:slots,id',
            'status' => 'required|string|in:scheduled,arrived,waiting,in_progress,completed,cancelled',
            'data' => 'nullable|array'
        ]);
        
        $success = $this->sapService->syncSlotStatus(
            $request->get('slot_id'),
            $request->get('status'),
            $request->get('data', [])
        );
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Sync successful' : 'Sync failed'
        ]);
    }
    
    /**
     * Check SAP connection health (all services)
     */
    public function health()
    {
        $sapHealth = $this->sapService->healthCheck();
        $vendorHealth = $this->sapVendorService->healthCheck();
        
        // Test PO API with a sample request
        $poTestResult = null;
        try {
            $start = microtime(true);
            $poTest = $this->sapPoService->getByPoNumber('4170005027');
            $poTestResult = [
                'status' => $poTest !== null ? 'connected' : 'no_data',
                'response_time_ms' => round((microtime(true) - $start) * 1000, 2),
                'sample_data' => $poTest !== null,
            ];
        } catch (\Throwable $e) {
            $poTestResult = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'services' => [
                'sap_legacy' => $sapHealth,
                'sap_po_odata' => $poTestResult,
                'sap_vendor' => $vendorHealth,
            ],
            'config' => [
                'base_url' => config('services.sap_po.base_url'),
                'service_path' => config('services.sap_po.service_path'),
                'sap_client' => config('services.sap_po.sap_client'),
                'verify_ssl' => config('services.sap_po.verify_ssl'),
                'timeout' => config('services.sap_po.timeout'),
            ]
        ]);
    }
    
    /**
     * Test SAP PO API connection with specific PO number
     */
    public function testPoConnection(Request $request)
    {
        $request->validate([
            'po_number' => 'nullable|string|max:50'
        ]);
        
        $poNumber = $request->get('po_number', '4170005027'); // Default test PO
        
        $start = microtime(true);
        $result = $this->sapPoService->getByPoNumber($poNumber);
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        
        return response()->json([
            'success' => $result !== null,
            'po_number' => $poNumber,
            'response_time_ms' => $elapsed,
            'data' => $result,
            'config' => [
                'base_url' => config('services.sap_po.base_url'),
                'service_path' => config('services.sap_po.service_path'),
                'odata_endpoint' => config('services.sap_po.odata_detail_endpoint'),
            ]
        ]);
    }
}
