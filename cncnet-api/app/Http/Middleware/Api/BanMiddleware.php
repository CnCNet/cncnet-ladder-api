<?php

namespace App\Http\Middleware\Api;

use App\Http\Services\PlayerService;
use App\Http\Services\QuickMatchService;
use Closure;
use Illuminate\Support\Facades\Log;

class BanMiddleware
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
        $ban = $this->playerService->checkUserForBans($request->user(), $request->getClientIp(), $request->hwid);
        if(isset($ban)) {
            return $this->qmService->onFatalError($ban);
        }
        return $next($request);
    }
}