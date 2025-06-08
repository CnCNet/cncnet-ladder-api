<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Restrict
{

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$this->auth->user()->isGod())
        {
            $response = null;
            if ($permission === "canEditAnyLadders") # page can be viewed by any ladder mod/admin
            {
                if (!$this->auth->user()->canEditAnyLadders())
                {
                    $response = response('Unauthorized.', 401);
                }
            }
            else if ($permission === "adminRequired")  # non-ladder specific page, user must be an admin
            {
                if (!$this->auth->user()->isAdmin())
                {
                    $response = response('Unauthorized.', 401);
                }
            }
            else if ($permission === "isNewsAdmin")
            {
                if (!$this->auth->user()->isNewsAdmin())
                {
                    $response = response('Unauthorized.', 401);
                }
            }
            else # ladder-specific mod/admin privilege
            {
                $ladder = \App\Models\Ladder::find($request->ladderId);

                if ($ladder == null)
                    $ladder = \App\Models\Ladder::where('abbreviation', $request->ladderAbbreviation)->first();

                if ($permission === "canAdminLadder")
                {
                    if (
                        !$this->auth->user()->isLadderAdmin($ladder)
                        && !$this->auth->user()->isAdmin()
                    )
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }

                if ($permission === "canModLadder")
                {
                    if (!$this->auth->user()->isLadderMod($ladder))
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }

                if ($permission === "canTestLadder")
                {
                    if (!$this->auth->user()->isLadderTester($ladder))
                    {
                        $response = response('Unauthorized.', 401);
                    }
                }
            }

            if ($request->gameSchemaId !== null)
            {
                $gameSchema = \App\Models\GameObjectSchema::find($request->gameSchemaId);

                if ($permission === 'objectSchemaManager')
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
}