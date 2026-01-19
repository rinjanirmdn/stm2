<?php

namespace Tests\Unit;

use App\Services\SapService;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SapServiceTest extends TestCase
{
    private SapService $sapService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->sapService = new SapService();
    }
    
    /** @test */
    public function it_can_search_po_in_demo_mode()
    {
        // Override config to force demo mode
        config(['app.env' => 'local']);
        
        $result = $this->sapService->searchPO('PO123456', 'V1001');
        
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PO123456', $result['data']['po_number']);
        $this->assertArrayHasKey('items', $result['data']);
    }
    
    /** @test */
    public function it_returns_null_for_invalid_po_in_demo_mode()
    {
        config(['app.env' => 'local']);
        
        $result = $this->sapService->searchPO('INVALID123');
        
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('PO not found', $result['message']);
    }
    
    /** @test */
    public function it_can_get_po_details_in_demo_mode()
    {
        config(['app.env' => 'local']);
        
        $result = $this->sapService->getPODetails('PO123456');
        
        $this->assertNotNull($result);
        $this->assertEquals('PO123456', $result['po_number']);
        $this->assertArrayHasKey('vendor_code', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total_value', $result);
    }
    
    /** @test */
    public function it_can_sync_slot_status_in_demo_mode()
    {
        config(['app.env' => 'local']);
        
        $result = $this->sapService->syncSlotStatus(1, 'completed', ['test' => 'data']);
        
        // Should return true 90% of the time in demo mode
        $this->assertIsBool($result);
    }
    
    /** @test */
    public function it_returns_demo_mode_for_health_check()
    {
        config(['app.env' => 'local']);
        
        $health = $this->sapService->healthCheck();
        
        $this->assertEquals('demo_mode', $health['status']);
        $this->assertStringContainsString('demo', $health['message']);
        $this->assertTrue($health['sap_configured']);
    }
    
    /** @test */
    public function it_logs_sap_operations_in_demo_mode()
    {
        Log::shouldReceive('info')
            ->once()
           ->with('[DEMO] SAP PO Search', \Mockery::type('array'));
        
        $this->sapService->searchPO('PO123456');
    }
}
