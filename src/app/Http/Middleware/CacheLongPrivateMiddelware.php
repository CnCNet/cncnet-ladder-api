<?php

namespace App\Http\Middleware;

use Closure;

class CacheLongPrivateMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=1800, private');
        return $response;
    }
}
