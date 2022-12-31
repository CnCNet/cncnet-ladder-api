<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\PointService;
use \App\Http\Services\AuthService;
use \App\PlayerActiveHandle;
use \Carbon\Carbon;
use DB;
use \App\Commands\FindOpponent;
use \App\QmQueueEntry;
use Illuminate\Support\Facades\Cache;
use \App\SpawnOptionType;
use DateTime;

class ApiQuickMatchController extends Controller
{
    private $ladderService;
    private $gameService;
    private $playerService;
    private $pointService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->gameService = new GameService();
        $this->playerService = new PlayerService();
    }

    public function clientVersion(Request $request, $platform = null)
    {
        return json_encode(DB::table("client_version")->where("platform", $platform)->first());
    }

    public function statsRequest(Request $request, $ladderAbbrev = null)
    {
        return Cache::remember("statsRequest/$ladderAbbrev", 1, function () use ($ladderAbbrev)
        {
            $timediff = Carbon::now()->subHour()->toDateTimeString();
            $ladder_id = $this->ladderService->getLadderByGame($ladderAbbrev)->id;
            $recentMatchedPlayers = \App\QmMatchPlayer::where('created_at', '>', $timediff)
                ->where('ladder_id', '=', $ladder_id)
                ->count();
            $queuedPlayers = \App\QmMatchPlayer::where('ladder_id', '=', $ladder_id)->whereNull('qm_match_id')->count();
            $recentMatches = \App\QmMatch::where('created_at', '>', $timediff)
                ->where('ladder_id', '=', $ladder_id)
                ->count();

            $activeGames = \App\QmMatch::where('updated_at', '>', Carbon::now()->subMinute(2))
                ->where('ladder_id', '=', $ladder_id)->count();

            $past24hMatches = \App\QmMatch::where('updated_at', '>', Carbon::now()->subDay(1))
                ->where('ladder_id', '=', $ladder_id)->count();

            return [
                'recentMatchedPlayers' => $recentMatchedPlayers,
                'queuedPlayers' => $queuedPlayers,
                'past24hMatches' => $past24hMatches,
                'recentMatches' => $recentMatches,
                'activeMatches'   => $activeGames,
                'time'          => Carbon::now()
            ];
        });
    }

    /**
     * Fetch details about games thare are currently in match
     */
    public function getActiveMatches(Request $request, $ladderAbbrev = null)
    {
        $games = [];
        if ($ladderAbbrev == "all")
        {
            foreach ($this->ladderService->getLadders() as $ladder)
            {
                $res = $this->getActiveMatchesByLadder($ladder->abbreviation);
                $games[$ladder->abbreviation] = $res;
            }
        }
        else
        {
            $games[$ladderAbbrev] = $this->getActiveMatchesByLadder($ladderAbbrev);
        }

        return $games;
    }

    private function getActiveMatchesByLadder($ladderAbbrev)
    {
        $ladder = $this->ladderService->getLadderByGame($ladderAbbrev);

        if ($ladder == null)
            abort(400, "Invalid ladder provided");

        $ladder_id = $ladder->id;

        //get all QMs that whose games have spawned. (state_type_id == 5)
        $qms = $this->ladderService->getRecentSpawnedMatches($ladder_id, 20);

        //get all QMs that have finished recently.  (state_type_id == 1)
        $finishedQms = $this->ladderService->getRecentFinishedMatches($ladder_id, 20);

        $finishedQmsArr = [];
        $finishedQms->map(function ($qm) use (&$finishedQmsArr)
        {
            $finishedQmsArr[] = $qm->id;
            return;
        })->values();

        //i'm bad at sql so here is a janky work around to remove finished QMs

        //remove all QMs that have finished
        $qms2 = $qms->filter(function ($qm) use (&$finishedQmsArr)
        {
            return !in_array($qm->id, $finishedQmsArr);
        })->values();

        $games = [];

        $qm_id = -1;
        $player1 = "";
        $player2 = "";
        $player1_side = "";

        foreach ($qms2 as $qm)
        {
            $dt = new DateTime($qm->qm_match_created_at);

            //i'm bad at sql so here is a janky work around to gather both qm players and map of each qm match
            if ($qm_id != $qm->id)
            {
                if (Carbon::now()->diffInSeconds($dt) <= 120)
                {
                    $player1 = "Player 1";
                }
                else
                {
                    $player1 = $qm->player;
                }
                $qm_id = $qm->id;
                $player1_side = $qm->faction;
            }
            else
            {
                if (Carbon::now()->diffInSeconds($dt) <= 120)
                {
                    $player2 = "Player 2";
                }
                else
                {
                    $player2 = $qm->player;
                }

                $duration = Carbon::now()->diff($dt);
                $duration_formatted = $duration->format('%i mins %s sec');
                $games[] = $player1 . " (" . $player1_side . ") vs " . $player2 . " (" . $qm->faction . ") on " . trim($qm->map) . ". Playtime: " . $duration_formatted . ".";
            }
        }

        return $games;
    }

    public function mapListRequest(Request $request, $ladderAbbrev = null)
    {
        //$qmMaps = \App\QmMap::where('ladder_id', $this->ladderService->getLadderByGame($ladderAbbrev)->id)->get();
        return \App\QmMap::findMapsByLadder($this->ladderService->getLadderByGame($ladderAbbrev)->id);
    }

    public function sidesListRequest(Request $request, $ladderAbbrev = null)
    {
        $ladder = $this->ladderService->getLadderByGame($ladderAbbrev);
        $rules = $ladder->qmLadderRules()->first();
        $allowed_sides = $rules->allowed_sides();
        $sides = $ladder->sides()->get();

        return $sides->filter(function ($side) use (&$allowed_sides)
        {
            return in_array($side->local_id, $allowed_sides);
        });
    }

    public function matchRequest(Request $request, $ladderAbbrev = null, $playerName = null)
    {
        $ladder = $this->ladderService->getLadderByGame($ladderAbbrev);
        $ladder_rules = $ladder->qmLadderRules()->first();

        $check = $this->ladderService->checkPlayer($request, $playerName, $ladder);
        if ($check !== null)
        {
            return $check;
        }

        $player = $this->playerService->findPlayerByUsername($playerName, $ladder);

        if ($player == null)
        {
            return array("type" => "fail", "description" => "$playerName is not registered in $ladderAbbrev");
        }

        $user = $request->user();

        if ($user->id !== $player->user->id)
        {
            return array("type" => "fail", "description" => "Failed");
        }

        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        // Player checks - ensure nick is registered as an active handle
        $hasActiveHandle = PlayerActiveHandle::getPlayerActiveHandle($player->id, $ladder->id, $startOfMonth, $endOfMonth);
        if ($hasActiveHandle == null)
        {
            PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $player->user->id);
        }


        $ban = $player->user->getBan(true);
        if ($ban !== null)
        {
            return ['type' => 'fatal', 'message' => $ban];
        }

        $ban = \App\IpAddress::findByIP($request->getClientIp())->getBan(true);
        if ($ban !== null)
        {
            return ['type' => 'fatal', 'message' => $ban];
        }


        // Require a verified email address on file
        if (!$player->user->email_verified)
        {
            if (!$player->user->verificationSent())
            {
                $player->user->sendNewVerification();
            }

            return array(
                "type" => "fatal",
                "message" => "Quick Match now requires a verified email address to play.\n" .
                    "A verification code has been sent to {$player->user->email}.\n"
            );
        }
        $rating = $player->rating()->first()->rating;

        $qmPlayer = \App\QmMatchPlayer::where('player_id', $player->id)
            ->where('waiting', true)->first();

        switch ($request->type)
        {
            case "quit":
                if ($qmPlayer != null)
                {
                    if ($qmPlayer->qm_match_id !== null)
                    {
                        $qmPlayer->qmMatch->save();
                    }
                    if ($qmPlayer->qEntry !== null)
                        $qmPlayer->qEntry->delete();

                    $qmPlayer->delete();
                }
                return array("type" => "quit");
                break;

            case "update":

                if ($request->seed)
                {
                    $qmMatch = \App\QmMatch::where('seed', '=', $request->seed)
                        ->join('qm_match_players', 'qm_match_id', '=', 'qm_matches.id')
                        ->where('qm_match_players.player_id', '=', $player->id)
                        ->select('qm_matches.*')->first();
                    if ($qmMatch !== null)
                    {
                        switch ($request->status)
                        {
                            case 'touch':
                                $qmMatch->touch();
                                break;
                            default:
                                $qmState = new \App\QmMatchState;
                                $qmState->player_id = $player->id;
                                $qmState->qm_match_id = $qmMatch->id;
                                $qmState->state_type_id = \App\StateType::findByName($request->status)->id;
                                $qmState->save();

                                if ($qmState->state_type_id === 7) //match not ready
                                {
                                    $canceledMatch = new \App\QmCanceledMatch;
                                    $canceledMatch->qm_match_id = $qmMatch->id;
                                    $canceledMatch->player_id = $player->id;
                                    $canceledMatch->ladder_id = $qmMatch->ladder_id;
                                    $canceledMatch->save();
                                }

                                if ($request->peers !== null)
                                {
                                    foreach ($request->peers as $peer)
                                    {
                                        $con = new \App\QmConnectionStats;
                                        $con->qm_match_id = $qmMatch->id;
                                        $con->player_id = $player->id;
                                        $con->peer_id = $peer['id'];
                                        $con->ip_address_id = \App\IpAddress::getID($peer['address']);
                                        $con->port = $peer['port'];
                                        $con->rtt = $peer['rtt'];
                                        $con->save();
                                    }
                                }
                                break;
                        }
                        $qmMatch->save();
                        return ["message"  => "update qm match: " . $request->status];
                    }
                }
                return ["type" => "update"];
                break;

            case "match me up":
                // Deprecate older versions
                if ($request->version  < 1.69)
                {
                    return array(
                        "type" => "fatal",
                        "message" => "Quick Match Version {$request->version} is no longer supported.\n" .
                            "Please restart the client to get the latest updates."
                    );
                }
                $alert = "";

                /* This matchup system is restful, a player will have to check in to see if there
             * is a matchup waitin.
             * If there is already a matchup then all these top level ifs will fall through
             * and the game info will be sent.
             * Else we'll try to set up a match.
             */
                if ($qmPlayer == null)
                {
                    $qmPlayer = new \App\QmMatchPlayer();
                    $qmPlayer->player_id = $player->id;
                    $qmPlayer->ladder_id = $player->ladder_id;
                    $qmPlayer->map_bitfield = $request->map_bitfield;
                    $qmPlayer->waiting = true;

                    // color, chosen_side, actual_side and saving is done in the next if-statement
                    $qmPlayer->qm_match_id = null;
                    $qmPlayer->tunnel_id = null;

                    $addr = \App\IpAddress::findByIP($request->ip_address);
                    $qmPlayer->ip_address_id = $addr ? $addr->id : null;
                    $qmPlayer->port = $request->ip_port;

                    $addr = \App\IpAddress::findByIP($request->lan_ip);
                    $qmPlayer->lan_address_id = $addr ? $addr->id : null;
                    $qmPlayer->lan_port = $request->lan_port;

                    $addr = \App\IpAddress::findByIP($request->ipv6_address);
                    $qmPlayer->ipv6_address_id = $addr ? $addr->id : null;
                    $qmPlayer->ipv6_port = $request->ipv6_port;

                    $qmPlayer->chosen_side = $request->side;

                    $all_sides = $ladder_rules->all_sides();
                    $sides = $ladder_rules->allowed_sides();

                    if ($request->side == -1)
                    {
                        $qmPlayer->actual_side = $all_sides[rand(0, count($all_sides) - 1)];
                    }
                    else if (in_array($request->side, $sides))
                    {
                        $qmPlayer->actual_side = $request->side;
                    }
                    else
                    {
                        return array("type" => "error", "description" => "Side ({$request->side}) is not allowed");
                    }
                    if ($request->map_sides)
                        $qmPlayer->map_sides_id = \App\MapSideString::findValue(join(',', $request->map_sides))->id;

                    if ($request->version && $request->platform)
                    {
                        $qmPlayer->version_id  = \App\PlayerDataString::findValue($request->version)->id;
                        $qmPlayer->platform_id = \App\PlayerDataString::findValue($request->platform)->id;
                    }

                    if ($request->ddraw)
                        $qmPlayer->ddraw_id = \App\PlayerDataString::findValue($request->ddraw)->id;


                    // Save user IP Address
                    $player->user->ip_address_id = \App\IpAddress::getID(isset($_SERVER["HTTP_CF_CONNECTING_IP"])
                        ? $_SERVER["HTTP_CF_CONNECTING_IP"]
                        : $request->getClientIp());

                    \App\IpAddressHistory::addHistory($player->user->id, $player->user->ip_address_id);

                    \App\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ip_address_id);

                    \App\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ipv6_address_id);

                    $player->user->save();

                    foreach ($ladder->alerts as $a)
                    {
                        $lap = $a->players()->where('player_id', '=', $player->id)->first();

                        if ($lap !== null)
                        {
                            if ($lap->show)
                                $alert .= "@everyone {$a->message}<br>\n<br>\n";

                            $lap->show = false;
                        }
                        else
                        {
                            $alert .= "@everyone {$a->message}<br>\n<br>\n";
                            $lap = new \App\LadderAlertPlayer;
                            $lap->player_id = $player->id;
                            $lap->ladder_alert_id = $a->id;
                            $lap->show = true;
                        }
                        $lap->save();
                    }
                }

                foreach ($player->unSeenAlerts as $a)
                {
                    $alert .= "@{$player->username} {$a->message}<br>\n<br>\n";
                    $a->acknowledge();
                }

                if ($request->ai_dat)
                    $qmPlayer->ai_dat = $request->ai_dat;

                $qmPlayer->save();

                if ($qmPlayer->qm_match_id === null)
                {
                    $history = $ladder->currentHistory();
                    $pc = $player->playerCache($history->id);
                    $points = 0;

                    if ($pc !== null)
                        $points = $pc->points;

                    if ($qmPlayer->qEntry !== null)
                    {
                        $qEntry = $qmPlayer->qEntry;
                        $qEntry->touch();


                        if ($qEntry->ladder_history_id != $history->id)
                        {
                            $qEntry->qm_match_player_id = $qmPlayer->id;
                            $qEntry->ladder_history_id = $history->id;
                            $qEntry->rating = $player->rating->rating;
                            $qEntry->points = $points;
                            $qEntry->save();
                        }
                    }
                    else
                    {
                        $qEntry = new QmQueueEntry;
                        $qEntry->qm_match_player_id = $qmPlayer->id;
                        $qEntry->ladder_history_id = $history->id;
                        $qEntry->rating = $player->rating->rating;
                        $qEntry->points = $points;
                        $qEntry->save();
                    }

                    // Push a job to find an opponent
                    $this->dispatch(new FindOpponent($qEntry->id));

                    $qmPlayer->touch();
                    return checkback($alert);
                }
                // If we've made it this far, lets send the spawn details

                $spawnStruct = array("type" => "spawn");
                $qmPlayer->waiting = false;
                $qmMatch = \App\QmMatch::find($qmPlayer->qm_match_id);
                $spawnStruct["gameID"] = $qmMatch->game_id;
                $qmMap = $qmMatch->map;
                $map = $qmMap->map;

                $spawnStruct["spawn"]["SpawnLocations"] = array();

                srand($qmMatch->seed); // Seed the RNG for possibly random boolean options

                $spawnStruct["spawn"]["Settings"] = array_filter(
                    [
                        "UIGameMode" =>     $qmMap->game_mode, "UIMapName" =>      $qmMap->description, "MapHash" =>        $map->hash, "Seed" =>           $qmMatch->seed, "GameID" =>         $qmMatch->seed, "WOLGameID" =>      $qmMatch->seed, "Host" =>           "No", "IsSpectator" =>    "No", "Name" =>           $qmPlayer->player()->first()->username, "Port" =>           $qmPlayer->port, "Side" =>           $qmPlayer->actual_side, "Color" =>          $qmPlayer->color
                        // Filter null values
                    ],
                    function ($var)
                    {
                        return !is_null($var);
                    }
                );

                foreach ($ladder->spawnOptionValues as $sov)
                {
                    switch ($sov->spawnOption->type->id)
                    {
                        case SpawnOptionType::SPAWN_INI:
                            $spawnStruct["spawn"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                            break;
                        case SpawnOptionType::SPAWNMAP_INI:
                            $spawnStruct["spawnmap"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                            break;
                        case SpawnOptionType::PREPEND_FILE:
                            $spawnStruct["prepends"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                            break;
                        case SpawnOptionType::COPY_FILE:
                            $spawnStruct["copies"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                            break;
                        default:
                            break;
                    }
                }

                foreach ($qmMap->spawnOptionValues as $sov)
                {
                    switch ($sov->spawnOption->type->id)
                    {
                        case SpawnOptionType::SPAWN_INI:
                            $spawnStruct["spawn"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                            break;
                        case SpawnOptionType::SPAWNMAP_INI:
                            $spawnStruct["spawnmap"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                            break;
                        case SpawnOptionType::PREPEND_FILE:
                            $spawnStruct["prepends"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                            break;
                        case SpawnOptionType::COPY_FILE:
                            $spawnStruct["copies"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                            break;
                        default:
                            break;
                    }
                }

                $spawnStruct["client"]["show_map_preview"] = $ladder_rules->show_map_preview;

                // Write the Others sections
                $allPlayers = $qmMatch->players()->where('id', '<>', $qmPlayer->id)->orderBy('color', 'ASC')->get();
                $other_idx = 1;

                if (count($allPlayers) == 0)
                {
                    $qmPlayer->waiting = false;
                    $qmPlayer->save();
                    return checkback($alert);
                }

                $multi_idx = $qmPlayer->color + 1;
                $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multi_idx}"] = $qmPlayer->location;

                foreach ($allPlayers as $opn)
                {
                    $spawnStruct["spawn"]["Other{$other_idx}"] = [
                        "Name" => $opn->player()->first()->username,
                        "Side" => $opn->actual_side,
                        "Color" => $opn->color,
                        "Ip" =>   $opn->ipAddress ? $opn->ipAddress->address : "",
                        "Port" => $opn->port,
                        "IPv6" => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                        "PortV6" => $opn->ipv6_port,
                        "LanIP" => $opn->lan_address ? $opn->lan_address->address : "",
                        "LanPort" => $opn->lan_port
                    ];
                    $multi_idx = $opn->color + 1;
                    $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multi_idx}"] = $opn->location;
                    $other_idx++;

                    if (array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) && $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes")
                    {
                        //if p1 is allied and p2 is yuri, or if p1 is yuri and p2 is allied then disable SW for this match
                        if (($qmPlayer->actual_side < 5 && $opn->actual_side == 9) || ($opn->actual_side < 5 && $qmPlayer->actual_side == 9))
                        {
                            $spawnStruct["spawn"]["Settings"]["Superweapons"] = "False";
                        }
                    }
                }
                $qmPlayer->waiting = false;
                $qmPlayer->save();

                return $spawnStruct;
                break;
            default:
                return array("type" => "error", "description" => "unknown type: {$request->type}");
                break;
        }
    }

    public function getPlayerRankings($count = 50)
    {

        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('Y');

        $rankings = [];

        foreach ($this->ladderService->getLadders() as $ladder)
        {
            $history = \App\LadderHistory::where('short', '=', $month . "-" . $year)
                ->where('ladder_id', $ladder->id)
                ->first();

            if ($history == null)
                continue;

            $pc = \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->join('players as p', 'player_caches.player_id', '=', 'p.id')
                ->join('users as u', 'p.user_id', '=', 'u.id')
                ->orderBy('player_caches.points', 'DESC')
                ->select('u.discord_profile as discord_name', 'player_caches.*')
                ->limit($count)
                ->get();

            $rankings[strtoupper($ladder->abbreviation)] = $pc;
        }

        return $rankings;
    }

    public function getErroredGames($ladderAbbrev)
    {
        $ladder = \App\Ladder::where('abbreviation', $ladderAbbrev)->first();

        if ($ladder == null)
            return "Bad ladder abbreviation " . $ladderAbbrev;

        $ladderHistory = $ladder->currentHistory();

        $numErroredGames = \App\Game::join('game_reports', 'games.game_report_id', '=', 'game_reports.id')
            ->where("ladder_history_id", "=", $ladderHistory->id)
            ->where(function ($query)
            {
                $query->where('game_reports.duration', '<=', 3)
                    ->orWhere('game_reports.fps', '<=', 0);
            })
            ->where('finished', '=', 1)
            ->orderBy("games.id", "DESC")
            ->get()->count();

        $url = \App\URLHelper::getLadderUrl($ladderHistory) . '/games?errorGames=true';

        $data = [];
        $data["url"] = "https://ladder.cncnet.org" . $url;
        $data["count"] = $numErroredGames;

        return $data;
    }
}

function b_to_ini($bool)
{
    if ($bool === null) return $bool;
    if ($bool == -1) return rand(0, 1) ? "Yes" : "No"; // Pray the seed was set earlier or this will cause recons
    return $bool ? "Yes" : "No";
}

function checkback($alert)
{
    if ($alert)
        return array("type" => "please wait", "checkback" => 10, "no_sooner_than" => 5, 'warning' => $alert);
    else
        return array("type" => "please wait", "checkback" => 10, "no_sooner_than" => 5);
}
