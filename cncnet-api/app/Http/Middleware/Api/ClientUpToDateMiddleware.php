<?php

namespace App\Http\Middleware\Api;

use App\Http\Services\LadderService;
use App\Http\Services\QuickMatchService;
use Closure;

class ClientUpToDateMiddleware
{

    private $qmService;
    private $ladderService;

    public function __construct(
        QuickMatchService $qmService,
        LadderService $ladderService,
    ) {

        $this->qmService = $qmService;
        $this->ladderService = $ladderService;

    }

    public function handle($request, Closure $next) {

        // Deprecate older versions
        if ($this->qmService->checkQMClientRequiresUpdate($request->route('ladder'), $request->version) === true) {
            return $this->qmService->onFatalError(
                "This version of the client is no longer supported, please restart the CnCNet client to get the latest updates"
            );
        }

        return $next($request);
    }
}