<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SapService
{
    private $baseUrl;
    private $token;
    private $timeout;
    private $isDemo;
    
    public function __construct()
    {
        $this->baseUrl = config('services.sap.base_url', env('SAP_PO_BASE_URL'));
        $this->token = config('services.sap.token', env('SAP_PO_TOKEN'));
        $this->timeout = config('services.sap.timeout', env('SAP_PO_TIMEOUT', 10));
        
        // Demo mode if in local environment or no token configured
        $this->isDemo = config('app.env') === 'local' || 
                       config('app.env') === 'testing' || 
                       empty($this->token);
    }
    
    /**
     * Search PO in SAP system
     */
    public function searchPO($poNumber, $vendorCode = null)
    {
        if ($this->isDemo) {
            return $this->demoSearchPO($poNumber, $vendorCode);
        }
        
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . env('SAP_PO_SEARCH_ENDPOINT'), [
                    'po_number' => $poNumber,
                    'vendor_code' => $vendorCode
                ]);
                
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('SAP PO Search Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('SAP PO Search Error', [
                'error' => $e->getMessage(),
                'po_number' => $poNumber
            ]);
            
            return null;
        }
    }
    
    /**
     * Get PO details from SAP
     */
    public function getPODetails($poNumber)
    {
        if ($this->isDemo) {
            return $this->demoGetPODetails($poNumber);
        }
        
        try {
            $endpoint = str_replace('{po}', $poNumber, env('SAP_PO_DETAIL_ENDPOINT'));
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . $endpoint);
                
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('SAP PO Details Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('SAP PO Details Error', [
                'error' => $e->getMessage(),
                'po_number' => $poNumber
            ]);
            
            return null;
        }
    }
    
    /**
     * Sync slot status to SAP
     */
    public function syncSlotStatus($slotId, $status, $data = [])
    {
        if ($this->isDemo) {
            return $this->demoSyncSlotStatus($slotId, $status, $data);
        }
        
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/slot/sync', [
                    'slot_id' => $slotId,
                    'status' => $status,
                    'data' => $data,
                    'timestamp' => now()->toISOString()
                ]);
                
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SAP Slot Sync Error', [
                'error' => $e->getMessage(),
                'slot_id' => $slotId,
                'status' => $status
            ]);
            
            return false;
        }
    }
    
    /**
     * Demo/Simulation methods
     */
    private function demoSearchPO($poNumber, $vendorCode = null)
    {
        Log::info('[DEMO] SAP PO Search', [
            'po_number' => $poNumber,
            'vendor_code' => $vendorCode
        ]);
        
        // Simulate API delay
        usleep(500000); // 0.5 second
        
        // Mock response based on PO number pattern
        if (str_starts_with($poNumber, 'PO')) {
            return [
                'success' => true,
                'data' => [
                    'po_number' => $poNumber,
                    'vendor_code' => $vendorCode ?: 'V' . rand(1000, 9999),
                    'status' => 'active',
                    'created_date' => now()->subDays(30)->format('Y-m-d'),
                    'items' => [
                        [
                            'item_no' => '00010',
                            'material' => 'MAT' . rand(100000, 999999),
                            'description' => 'Material Description',
                            'quantity' => rand(10, 100),
                            'uom' => 'PC'
                        ]
                    ]
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'PO not found'
        ];
    }
    
    private function demoGetPODetails($poNumber)
    {
        Log::info('[DEMO] SAP PO Details', [
            'po_number' => $poNumber
        ]);
        
        // Simulate API delay
        usleep(750000); // 0.75 second
        
        return [
            'po_number' => $poNumber,
            'vendor_code' => 'V' . rand(1000, 9999),
            'vendor_name' => 'Demo Vendor Corp',
            'status' => 'active',
            'created_date' => now()->subDays(30)->format('Y-m-d'),
            'delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'item_no' => '00010',
                    'material' => 'MAT123456',
                    'description' => 'Demo Material Item',
                    'quantity' => 50,
                    'uom' => 'PC',
                    'net_price' => 100.50,
                    'currency' => 'USD'
                ]
            ],
            'total_value' => 5025.00,
            'currency' => 'USD'
        ];
    }
    
    private function demoSyncSlotStatus($slotId, $status, $data)
    {
        Log::info('[DEMO] SAP Slot Sync', [
            'slot_id' => $slotId,
            'status' => $status,
            'data' => $data
        ]);
        
        // Simulate API delay
        usleep(300000); // 0.3 second
        
        // 90% success rate for demo
        return rand(1, 100) <= 90;
    }
    
    /**
     * Check SAP connection health
     */
    public function healthCheck()
    {
        if ($this->isDemo) {
            return [
                'status' => 'demo_mode',
                'message' => 'Running in demo/simulation mode',
                'sap_configured' => !empty($this->baseUrl)
            ];
        }
        
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token
                ])
                ->get($this->baseUrl . '/health');
                
            return [
                'status' => $response->successful() ? 'connected' : 'error',
                'response_time' => $response->handlerStats()['total_time'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
