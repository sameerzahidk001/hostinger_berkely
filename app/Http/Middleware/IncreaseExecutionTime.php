<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncreaseExecutionTime
{
    public function handle(Request $request, Closure $next): Response
    {
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        return $next($request);
    }
}
