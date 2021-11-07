<?php namespace App\Http\Middleware;

use Closure;

class BlockBadBots {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = $request->getClientIp();

        if (preg_match('/Seekport Crawler/', $request->header('User-Agent'))
            || preg_match('/SemrushBot/', $request->header('User-Agent'))
            || preg_match('/MJ12bot/', $request->header('User-Agent'))
            || preg_match('/YandexBot/', $request->header('User-Agent'))
            || preg_match('/bingbot/', $request->header('User-Agent'))
            || preg_match('/Amazonbot/', $request->header('User-Agent'))
            || preg_match('/DotBot/', $request->header('User-Agent')))
        {
            return response('Unauthorized', 401);
        }
        return $next($request);
    }
}
