<?php

namespace App\Http\Controllers\Api\V2\Bans;

use App\Http\Controllers\Controller;
use App\Http\Services\AuthService;
use App\Http\Services\IrcBanService;
use App\Http\Services\IrcWarningService;
use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Models\PlayerActiveHandle;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
