<?php

namespace App\Jobs;

use App\Services\LoadingStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWithProgress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    
    protected $operationId;
    protected $operationName;
    
    public function __construct($operationId, $operationName)
    {
        $this->operationId = $operationId;
        $this->operationName = $operationName;
    }
    
    public function handle(LoadingStatusService $loadingService)
    {
        $totalSteps = 10;
        $loadingService->start($this->operationId, $this->operationName, $totalSteps);
        
        for ($step = 1; $step <= $totalSteps; $step++) {
            sleep(1);
            
            $messages = [
                1 => 'Initializing...',
                2 => 'Fetching data from database...',
                3 => 'Processing records...',
                4 => 'Validating information...',
                5 => 'Updating records...',
                6 => 'Generating reports...',
                7 => 'Sending notifications...',
                8 => 'Cleaning up temporary files...',
                9 => 'Finalizing...',
                10 => 'Completing operation...',
            ];
            
            $loadingService->update(
                $this->operationId,
                $step,
                $messages[$step],
                ['processed_items' => $step * 10, 'current_step' => $step]
            );
        }
        
        $loadingService->complete($this->operationId, [
            'total_processed' => 100,
            'status' => 'success'
        ]);
    }
}