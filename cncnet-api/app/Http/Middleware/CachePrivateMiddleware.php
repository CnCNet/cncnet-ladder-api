<?php namespace App\Http\Middleware;

use Closure;

class CachePrivateMiddleware 
{
	public function handle($request, Closure $next)
	{
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=300, private');
        return $response;
	}
}
