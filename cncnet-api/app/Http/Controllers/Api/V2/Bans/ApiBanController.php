<?php

namespace App\Http\Controllers\Api\V2\Bans;

use App\Http\Controllers\Controller;
use App\Http\Services\AuthService;
use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Models\PlayerActiveHandle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiBanController extends Controller
{
    private $authService;
    private $ladderService;
    private $playerService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->playerService = new PlayerService;
        $this->ladderService = new LadderService;
    }

    public function getBans()
    {
        return [
            [
                "user" => "cncnet",
                "ident" => "a964ad",
                "host" => "gamesurge-d3a0cd5b.res.spectrum.com",
                "kickBan" => true
            ],
            [
                "user" => null,
                "ident" => "t364ad",
                "host" => null,
                "kickBan" => false
            ],
            [
                "user" => null,
                "ident" => "",
                "host" => "*.res.spectrum.com",
                "kickBan" => false
            ],
            [
                "user" => "cncnet-moderator",
                "ident" => null,
                "host" => null,
                "kickBan" => true
            ],
        ];
    }
}
