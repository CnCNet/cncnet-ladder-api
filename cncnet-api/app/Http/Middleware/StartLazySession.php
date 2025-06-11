<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\SessionManager;

class StartLazySession extends StartSession {

    private $except = ['/api/*'];
    private $auth;
    /**
	 * Create a new session middleware.
	 *
	 * @param  \Illuminate\Session\SessionManager  $manager
	 * @return void
	 */
	public function __construct(Guard $auth, SessionManager $manager)
	{
        $this->auth = $auth;
        parent::__construct($manager);
	}


	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$this->sessionHandled = true;

		// If a session driver has been configured, we will need to start the session here
		// so that the data is ready for an application. Note that the Laravel sessions
		// do not make use of PHP "native" sessions in any way since they are crappy.
		if ($this->sessionConfigured() && !$this->inExceptArray($request))
		{
			$session = $this->startSession($request);

			$request->setLaravelSession($session);
		}

		$response = $next($request);

		// Again, if the session has been configured we will need to close out the session
		// so that the attributes may be persisted to some storage medium. We will also
		// add the session identifier cookie to the application response headers now.
		if ($this->sessionConfigured() && !$this->inExceptArray($request))
		{
			$this->storeCurrentUrl($request, $session);

			$this->collectGarbage($session);

			$this->addCookieToResponse($response, $session);
		}
		return $response;
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
