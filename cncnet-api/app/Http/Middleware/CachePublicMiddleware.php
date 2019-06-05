<?php namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class CachePublicMiddleware
{
	public function handle($request, Closure $next)
	{
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=300, public');
        $response->header('Expires', Carbon::now()->addMinute(5)->format('D, d M Y H:i:s \G\M\T'));
        $response->header('Last-Modified', Carbon::now()->subMinute(1)->format('D, d M Y H:i:s \G\M\T'));
        return $response;
	}
}
