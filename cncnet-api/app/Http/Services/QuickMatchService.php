<?php

namespace App\Http\Services;

use App\Commands\FindOpponent;
use App\Game;
use App\Ladder;
use App\LadderHistory;
use App\QmMatchPlayer;
use App\QmQueueEntry;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QuickMatchService
{
    public function onQuit($qmPlayer)
    {
        if ($qmPlayer != null)
        {
            if ($qmPlayer->qm_match_id !== null)
            {
                $qmPlayer->qmMatch->save();
            }
            if ($qmPlayer->qEntry !== null)
            {
                $qmPlayer->qEntry->delete();
            }

            $qmPlayer->delete();
        }
        return [
            "type" => "quit"
        ];
    }

    public function onUpdate($status, $player, $seed, $peers)
    {
        if ($seed)
        {
            $qmMatch = \App\QmMatch::where('seed', '=', $seed)
                ->join('qm_match_players', 'qm_match_id', '=', 'qm_matches.id')
                ->where('qm_match_players.player_id', '=', $player->id)
                ->select('qm_matches.*')
                ->first();

            if ($qmMatch !== null)
            {
                switch ($status)
                {
                    case 'touch':
                        $qmMatch->touch();
                        break;

                    default:
                        $qmState = new \App\QmMatchState;
                        $qmState->player_id = $player->id;
                        $qmState->qm_match_id = $qmMatch->id;
                        $qmState->state_type_id = \App\StateType::findByName($status)->id;
                        $qmState->save();

                        if ($qmState->state_type_id === 7) //match not ready
                        {
                            $canceledMatch = new \App\QmCanceledMatch;
                            $canceledMatch->qm_match_id = $qmMatch->id;
                            $canceledMatch->player_id = $player->id;
                            $canceledMatch->ladder_id = $qmMatch->ladder_id;
                            $canceledMatch->save();
                        }

                        if ($peers !== null)
                        {
                            foreach ($peers as $peer)
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

                return ["message"  => "update qm match: " . $status];
            }
        }
        return ["type" => "update"];
    }

    public function createQMPlayer($request, $player, $history, $ladder, $ladderRules)
    {
        $qmPlayer = new QmMatchPlayer();
        $qmPlayer->player_id = $player->id;
        $qmPlayer->ladder_id = $player->ladder_id;
        $qmPlayer->map_bitfield = $request->map_bitfield;
        $qmPlayer->tier = $player->playerHistory($history)->tier;
        $qmPlayer->waiting = true;

        # color, chosen_side, actual_side and saving is done in the next if-statement
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

        if ($request->map_sides)
        {
            $qmPlayer->map_sides_id = \App\MapSideString::findValue(join(',', $request->map_sides))->id;
        }

        if ($request->version && $request->platform)
        {
            $qmPlayer->version_id  = \App\PlayerDataString::findValue($request->version)->id;
            $qmPlayer->platform_id = \App\PlayerDataString::findValue($request->platform)->id;
        }

        if ($request->ddraw)
        {
            $qmPlayer->ddraw_id = \App\PlayerDataString::findValue($request->ddraw)->id;
        }

        // Save user IP Address
        $player->user->ip_address_id = \App\IpAddress::getID(isset($_SERVER["HTTP_CF_CONNECTING_IP"])
            ? $_SERVER["HTTP_CF_CONNECTING_IP"]
            : $request->getClientIp());

        \App\IpAddressHistory::addHistory($player->user->id, $player->user->ip_address_id);

        \App\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ip_address_id);

        \App\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ipv6_address_id);

        $player->user->save();

        $qmPlayer->save();
        return $qmPlayer;
    }

    public function checkPlayerSidesAreValid($qmPlayer, $side, $ladderRules)
    {
        $allSides = $ladderRules->all_sides();
        $sides = $ladderRules->allowed_sides();

        if ($side == -1)
        {
            $qmPlayer->actual_side = $allSides[rand(0, count($allSides) - 1)];

            return true;
        }
        else if (in_array($side, $sides))
        {
            $qmPlayer->actual_side = $side;

            return true;
        }

        return false;
    }

    public function checkForAlerts($ladder, $player)
    {
        $alert = "";
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

        foreach ($player->unSeenAlerts as $a)
        {
            $alert .= "@{$player->username} {$a->message}<br>\n<br>\n";
            $a->acknowledge();
        }

        return $alert;
    }

    public function createMatchupRequest($player, $qmPlayer, $history)
    {
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

        return $qEntry;
    }
}
