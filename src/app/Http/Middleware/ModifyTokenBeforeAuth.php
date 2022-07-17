<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ModifyTokenBeforeAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->server->get("HTTP_AUTHORIZATION");

        if (str_contains($token, "Bearer:"))
        {
            $token = str_replace("Bearer:", "Bearer", $token);
            $request->headers->set("authorization", $token);
        }
        return $next($request);
    }
}
