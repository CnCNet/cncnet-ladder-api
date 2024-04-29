<?php

namespace App\Http\Middleware\Api;

use App\Http\Services\QuickMatchService;
use Closure;
use Illuminate\Support\Facades\Log;

class ShadowBanMiddleware
{
    private $qmService;

    public function __construct(QuickMatchService $qmService) {
        $this->qmService = $qmService;

    }

    public function handle($request, Closure $next) {
        $user = $request->user();
        $ip = $request->getClientIp();
        $qmClientId = $request->hwid;

        if($user->checkForShadowBan($ip, $qmClientId)) {
            Log::info("Shadow banned: " . $user->name);
            return $this->qmService->onCheckback();
        }

        return $next($request);
    }
}