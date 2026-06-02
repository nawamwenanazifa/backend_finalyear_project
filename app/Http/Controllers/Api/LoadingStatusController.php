<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoadingStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoadingStatusController extends Controller
{
    protected $loadingService;
    
    public function __construct(LoadingStatusService $loadingService)
    {
        $this->loadingService = $loadingService;
    }
    
    public function startOperation(Request $request)
    {
        $operationId = (string) Str::uuid();
        $operationName = $request->input('operation_name', 'Processing');
        $totalSteps = $request->input('total_steps', 100);
        
        $status = $this->loadingService->start($operationId, $operationName, $totalSteps);
        
        return response()->json([
            'operation_id' => $operationId,
            'status' => $status
        ]);
    }
    
    public function getStatus($operationId)
    {
        $status = $this->loadingService->getStatus($operationId);
        
        if (!$status) {
            return response()->json(['error' => 'Operation not found'], 404);
        }
        
        return response()->json($status);
    }
    
    public function simulateLongOperation(Request $request)
    {
        $operationId = (string) Str::uuid();
        $operationName = $request->input('operation_name', 'Processing Data');
        
        dispatch(new \App\Jobs\ProcessWithProgress($operationId, $operationName));
        
        return response()->json([
            'operation_id' => $operationId,
            'message' => 'Operation started'
        ]);
    }
}