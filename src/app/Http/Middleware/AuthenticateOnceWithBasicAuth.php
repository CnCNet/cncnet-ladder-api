<?php namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthenticateOnceWithBasicAuth 
{
    public function handle($request, Closure $next)
    {
        return Auth::onceBasic() ?: $next($request);
    }
}
