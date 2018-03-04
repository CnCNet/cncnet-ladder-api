<?php namespace App\Http\Middleware;

use Closure;

class CachePublicMiddleware 
{
	public function handle($request, Closure $next)
	{
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=300, public');
        return $response;
	}
}
