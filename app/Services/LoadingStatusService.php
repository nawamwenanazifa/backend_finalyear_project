<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LoadingStatusService
{
    protected $cacheKey = 'loading_status_';
    
    public function start($operationId, $operationName, $totalSteps = 100)
    {
        $status = [
            'operation_id' => $operationId,
            'operation_name' => $operationName,
            'status' => 'started',
            'current_step' => 0,
            'total_steps' => $totalSteps,
            'percentage' => 0,
            'message' => "Starting {$operationName}...",
            'details' => [],
            'started_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        
        Cache::put($this->cacheKey . $operationId, $status, now()->addMinutes(10));
        return $status;
    }
    
    public function update($operationId, $currentStep, $message = null, $details = [])
    {
        $status = Cache::get($this->cacheKey . $operationId);
        
        if (!$status) {
            return null;
        }
        
        $percentage = round(($currentStep / $status['total_steps']) * 100);
        
        $status['current_step'] = $currentStep;
        $status['percentage'] = $percentage;
        $status['message'] = $message ?? $status['message'];
        $status['details'] = array_merge($status['details'], $details);
        $status['updated_at'] = now()->toIso8601String();
        
        if ($percentage >= 100) {
            $status['status'] = 'completed';
            $status['message'] = 'Operation completed successfully!';
        } else {
            $status['status'] = 'in_progress';
        }
        
        Cache::put($this->cacheKey . $operationId, $status, now()->addMinutes(10));
        
        try {
            broadcast(new \App\Events\LoadingStatusUpdated($status))->toOthers();
        } catch (\Exception $e) {
            // Ignore broadcasting errors if not set up yet
        }
        
        return $status;
    }
    
    public function complete($operationId, $result = null)
    {
        $status = Cache::get($this->cacheKey . $operationId);
        
        if ($status) {
            $status['status'] = 'completed';
            $status['percentage'] = 100;
            $status['message'] = 'Completed successfully!';
            $status['completed_at'] = now()->toIso8601String();
            $status['result'] = $result;
            
            Cache::put($this->cacheKey . $operationId, $status, now()->addMinutes(5));
            
            try {
                broadcast(new \App\Events\LoadingStatusUpdated($status))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcasting errors
            }
        }
        
        return $status;
    }
    
    public function fail($operationId, $error)
    {
        $status = Cache::get($this->cacheKey . $operationId);
        
        if ($status) {
            $status['status'] = 'failed';
            $status['message'] = 'Operation failed';
            $status['error'] = $error;
            $status['failed_at'] = now()->toIso8601String();
            
            Cache::put($this->cacheKey . $operationId, $status, now()->addMinutes(5));
            
            try {
                broadcast(new \App\Events\LoadingStatusUpdated($status))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcasting errors
            }
        }
        
        return $status;
    }
    
    public function getStatus($operationId)
    {
        return Cache::get($this->cacheKey . $operationId);
    }
}