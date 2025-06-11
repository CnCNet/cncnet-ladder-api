<?php namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class CacheUltraShortPublic
{
	public function handle($request, Closure $next)
	{
        $response = $next($request);
        $response->header('Cache-Control', 'max-age=15, public');
        $response->header('Expires', Carbon::now()->addSecond(15)->format('D, d M Y H:i:s \G\M\T'));
        $response->header('Last-Modified', Carbon::now()->subSecond(5)->format('D, d M Y H:i:s \G\M\T'));
        return $response;
	}
}
