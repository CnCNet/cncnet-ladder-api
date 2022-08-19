<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;
use Closure;

class CacheLongPrivateMiddleware extends Middleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=1800, private');
        return $response;
    }
}
