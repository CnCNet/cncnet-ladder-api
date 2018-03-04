<?php namespace App\Http\Middleware;

use Closure;

class CacheLongPublicMiddleware 
{
	public function handle($request, Closure $next)
	{
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=3600, public');
        return $response;
	}
}
