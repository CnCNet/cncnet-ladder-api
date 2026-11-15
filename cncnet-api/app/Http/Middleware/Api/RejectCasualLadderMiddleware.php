<?php

namespace App\Http\Middleware\Api;

use App\Http\Services\QuickMatchService;
use Closure;

/**
 * Keeps ranked quick match requests off casual ladders, so ranked and casual players
 * never share a queue. Casual ladders are served by CasualMatchUpController.
 */
class RejectCasualLadderMiddleware
{
    private $qmService;

    public function __construct(QuickMatchService $qmService)
    {
        $this->qmService = $qmService;
    }

    public function handle($request, Closure $next)
    {
        $ladder = $request->route('ladder');

        if ($ladder !== null && $ladder->is_casual)
        {
            return $this->qmService->onFatalError($ladder->abbreviation . ' is a casual ladder and cannot be used for ranked quick match');
        }

        return $next($request);
    }
}
