<?php

namespace App\Http\Middleware;

use Closure;

class HackStatApiHeaders
{
    protected $hackEndpoints = ['/api/v1/qm/ladder/*/stats'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        foreach ($this->hackEndpoints as $ep)
        {
            if ($ep !== '/')
            {
                $ep = trim($ep, '/');
            }

            if ($request->is($ep))
            {
                $response->header('content-type', 'text/html; charset=UTF-8');
            }
        }

        return $response;
    }
}
