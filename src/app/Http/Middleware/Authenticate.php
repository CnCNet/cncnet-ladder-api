<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson())
        {
            return route('login');
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $route = $request->route();
        $actions = $route->getAction();

        if ($this->auth->guest())
        {
            if ($request->ajax() || $request->wantsJson())
            {
                return response('Unauthorized.', 401);
            }
            else if (isset($actions["guestsAllowed"]))
            {
                return $next($request);
            }
            else
            {
                return redirect()->guest('auth/login');
            }
        }

        if (!$this->auth->user()->isGod())
        {
            $response = null;
            if (isset($actions["canEditAnyLadders"]))
            {
                if ($this->auth->user()->canEditAnyLadders() != $actions["canEditAnyLadders"])
                {
                    $response = response('Unauthorized.', 401);
                }
            }

            if ($request->ladderId !== null)
            {
                $ladder = \App\Ladder::find($request->ladderId);

                if (isset($actions["canAdminLadder"]))
                {
                    if ($actions["canAdminLadder"] != $this->auth->user()->isLadderAdmin($ladder))
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }

                if (isset($actions["canModLadder"]))
                {
                    if ($actions["canModLadder"] != $this->auth->user()->isLadderMod($ladder))
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }

                if (isset($actions["canTestLadder"]))
                {
                    if ($actions["canTestLadder"] != $this->auth->user()->isLadderTester($ladder))
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }
            }

            if ($request->gameSchemaId !== null)
            {
                $gameSchema = \App\GameObjectSchema::find($request->gameSchemaId);

                if (isset($actions['objectSchemaManager']))
                {
                    $gameSchema->managers()->where('user_id', '=', $this->auth->user()->id);
                }
            }

            if ($response !== null)
            {
                return $response;
            }
        }

        return $next($request);
    }

    /*
    protected function handle($request, Closure $next)
    {
       
    }
    */
}
