<?php

namespace App\Http\Middleware\Api;

use App\Http\Services\PlayerService;
use App\Http\Services\QuickMatchService;
use Closure;
use Illuminate\Support\Facades\Log;

class VerifiedEmailMiddleware
{
    private $qmService;
    private $playerService;

    public function __construct(
        QuickMatchService $qmService,
        PlayerService $playerService
    ) {

        $this->qmService = $qmService;
        $this->playerService = $playerService;
    }

    public function handle($request, Closure $next) {

        if(!$this->playerService->checkUserHasVerifiedEmail($request->user())) {
            return $this->qmService->onFatalError(
                'Quick Match now requires a verified email address to play.' . PHP_EOL .
                'A verification code has been sent to '. $request->user()->email . PHP_EOL
            );
        }
        return $next($request);
    }
}