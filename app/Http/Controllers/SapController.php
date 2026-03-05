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
            2 // Search back 2 months
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
}
