<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */

    private $except = ['/api/*'];

    public function handle($request, Closure $next)
    {
        if ($this->inExceptArray($request))
        {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    private function inExceptArray($request)
    {
        foreach ($this->except as $except) 
        {
            if ($except !== '/') 
            {
                $except = trim($except, '/');
            }
            if ($request->is($except)) 
            {
                return true;
            }
        }
        return false;
    }
}
