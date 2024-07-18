<?php

namespace App\Http\Controllers\Api\V2\Bans;

use App\Http\Controllers\Controller;
use App\Http\Services\IrcBanService;
use App\Http\Services\IrcWarningService;

class ApiBanController extends Controller
{
    protected IrcBanService $ircBanService;
    protected IrcWarningService $ircWarningService;

    public function __construct(IrcBanService $ircBanService, IrcWarningService $ircWarningService)
    {
        $this->ircBanService = $ircBanService;
        $this->ircWarningService = $ircWarningService;
    }

    public function getBans()
    {
        return $this->ircBanService->getActiveBans();
    }

    public function getWarnings()
    {
        return $this->ircWarningService->getActiveWarnings();
    }
}
