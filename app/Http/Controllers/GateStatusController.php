<?php

namespace App\Http\Controllers;

use App\Services\GateStreamingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GateStatusController extends Controller
{
    public function __construct(
        private readonly GateStreamingService $streamingService
    ) {
    }
    /**
     * Stream real-time gate status updates
     */
    public function stream(Request $request)
    {
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($request) {
            $this->streamingService->streamGateStatuses(function ($data) {
                echo "data: " . json_encode($data) . "\n\n";
                ob_flush();
                flush();
            });
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable nginx buffering

        return $response;
    }

    /**
     * Get current gate status (non-streaming)
     */
    public function index(Request $request)
    {
        $gates = $this->streamingService->getGateStatuses();

        return view('gates.monitor', [
            'gates' => $gates,
        ]);
    }

    /**
     * Get current gate status (non-streaming API)
     */
    public function apiIndex(Request $request)
    {
        try {
            $gates = $this->streamingService->getCurrentGateStatuses();

            return response()->json([
                'gates' => $gates,
                'timestamp' => Carbon::now()->timestamp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
