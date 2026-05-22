<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Disable PHP execution time limit
        set_time_limit(0);
        
        // Increase memory limit
        ini_set('memory_limit', '512M');
        
        // Increase max execution time for this request
        ini_set('max_execution_time', 300);
        
        return $next($request);
    }
}