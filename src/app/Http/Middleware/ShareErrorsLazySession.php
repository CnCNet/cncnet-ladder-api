<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ShareErrorsLazySession extends ShareErrorsFromSession {

    private $except = ['/api/*'];

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
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
