<?php

namespace App\Http\Middleware;

use App\Models\LadderEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves {slug} to a LadderEvent and exposes its ladder id as `ladderId`,
 * so `restrict:canAdminLadder` can check ladder-admin rights (FR-031, NFR-006).
 * Must run before `restrict`.
 */
class ResolveLadderEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $event = LadderEvent::findBySlug((string) $request->route('slug'));
        if ($event === null)
        {
            abort(404);
        }

        $request->attributes->set('ladderEvent', $event);
        $request->merge(['ladderId' => $event->ladder_id]);

        return $next($request);
    }
}
