<?php

namespace App\Http\Services;

use App\Game;
use App\QmMatch;
use App\QmMatchPlayer;
use App\QmQueueEntry;
use Log;


class QuickMatchService
{
    public function createQMPlayer($request, $player, $history, $ladder, $ladderRules)
    {
        $qmPlayer = new QmMatchPlayer();
        $qmPlayer->player_id = $player->id;
        $qmPlayer->ladder_id = $player->ladder_id;
        $qmPlayer->map_bitfield = $request->map_bitfield;
        $qmPlayer->tier = $player->playerHistory($history)->tier;
        $qmPlayer->waiting = true;

        if ($history->ladder->clans_allowed && $player->clan)
        {
            $qmPlayer->clan_id = $player->clan->id;
        }

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

    public function createOrUpdateQueueEntry($player, $qmPlayer, $history, $gameType)
    {
        $pc = $player->playerCache($history->id);
        $points = 0;

        if ($pc !== null)
        {
            $points = $pc->points;
        }

        if ($qmPlayer->qEntry == null)
        {
            $qEntry = new QmQueueEntry;
            $qEntry->qm_match_player_id = $qmPlayer->id;
            $qEntry->ladder_history_id = $history->id;
            $qEntry->rating = $player->rating->rating;
            $qEntry->points = $points;
            $qEntry->game_type = $gameType;
            $qEntry->save();
        }
        else
        {
            $qEntry = $qmPlayer->qEntry;
            $qEntry->touch();

            if ($qEntry->ladder_history_id != $history->id)
            {
                $qEntry->qm_match_player_id = $qmPlayer->id;
                $qEntry->ladder_history_id = $history->id;
                $qEntry->rating = $player->rating->rating;
                $qEntry->points = $points;
                $qEntry->game_type = $gameType;
                $qEntry->save();
            }
        }

        return $qEntry;
    }

    public function createQmAIMatch($qmPlayer, $userPlayerTier, $maps, $gameType)
    {
        $randomMapIndex = mt_rand(0, count($maps) - 1);

        # Create the qm_matches db entry
        $qmMatch = new QmMatch();
        $qmMatch->ladder_id = $qmPlayer->ladder_id;
        $qmMatch->qm_map_id = $maps[$randomMapIndex]->id;
        $qmMatch->seed = mt_rand(-2147483647, 2147483647);
        $qmMatch->tier = $userPlayerTier;

        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();

        $game->qm_match_id = $qmMatch->id;
        $game->save();

        $qmMap = $qmMatch->map;
        $spawn_order = explode(',', $qmMap->spawn_order);

        # Set up player specific information
        # Color will be used for spawn location
        $qmPlayer->color = 0;
        $qmPlayer->location = $spawn_order[$qmPlayer->color] - 1;
        $qmPlayer->qm_match_id = $qmMatch->id;
        $qmPlayer->tunnel_id = $qmMatch->seed + $qmPlayer->color;

        $psides = explode(',', $qmPlayer->mapSides->value);

        if (count($psides) > $qmMap->bit_idx)
        {
            $qmPlayer->actual_side = $psides[$qmMap->bit_idx];
        }

        if ($qmPlayer->actual_side < -1)
        {
            $qmPlayer->actual_side = $qmPlayer->chosen_side;
        }

        $qmPlayer->save();

        $perMS = array_values(array_filter($qmMap->sides_array(), function ($s)
        {
            return $s >= 0;
        }));

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
        }
        $qmPlayer->save();

        return $qmMatch;
    }

    public function createQmMatch($qmPlayer, $userPlayerTier, $maps, $qmOpns, $qEntry, $gameType)
    {
        $randomMapIndex = mt_rand(0, count($maps) - 1);

        # Create the qm_matches db entry
        $qmMatch = new QmMatch();
        $qmMatch->ladder_id = $qmPlayer->ladder_id;
        $qmMatch->qm_map_id = $maps[$randomMapIndex]->id;
        $qmMatch->seed = mt_rand(-2147483647, 2147483647);
        $qmMatch->tier = $userPlayerTier;

        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();

        $game->qm_match_id = $qmMatch->id;
        $game->save();

        $qmMap = $qmMatch->map;
        $spawnOrder = explode(',', $qmMap->spawn_order);

        if ($qmMap->random_spawns && $qmMap->map->spawn_count > 2) //this map uses random spawns
        {
            $spawnOrder = [];
            $numSpawns = $qmMap->map->spawn_count;
            $spawnArr = [];

            for ($i = 1; $i <= $numSpawns; $i++)
            {
                $spawnArr[] = $i;
            }

            shuffle($spawnArr); //shuffle the spawns, select 2
            $spawnOrder[0] = $spawnArr[0];
            $spawnOrder[1] = $spawnArr[1];

            Log::info("Random spawns selected for qmMap: '" . $qmMap->description . "', " . $spawnOrder[0] . "," . $spawnOrder[1]);
        }

        # Set up player specific information
        # Color will be used for spawn location
        $qmPlayer->color = 0;
        $qmPlayer->location = $spawnOrder[$qmPlayer->color] - 1;
        $qmPlayer->qm_match_id = $qmMatch->id;
        $qmPlayer->tunnel_id = $qmMatch->seed + $qmPlayer->color;

        $psides = explode(',', $qmPlayer->mapSides->value);

        if (count($psides) > $qmMap->bit_idx)
        {
            $qmPlayer->actual_side = $psides[$qmMap->bit_idx];
        }

        if ($qmPlayer->actual_side < -1)
        {
            $qmPlayer->actual_side = $qmPlayer->chosen_side;
        }

        $qmPlayer->save();

        $perMS = array_values(array_filter($qmMap->sides_array(), function ($s)
        {
            return $s >= 0;
        }));

        $color = 1;
        foreach ($qmOpns as $qOpn)
        {
            $opn = $qOpn->qmPlayer;
            $qOpn->delete();

            if ($opn === null)
            {
                $qEntry->delete();
                return;
            }

            $osides = explode(',', $opn->mapSides->value);

            if (count($osides) > $qmMap->bit_idx)
                $opn->actual_side = $osides[$qmMap->bit_idx];

            if ($opn->actual_side  < -1)
            {
                $opn->actual_side = $opn->chosen_side;
            }

            if ($opn->actual_side == -1)
            {
                $opn->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
            }
            $opn->color = $color++;
            $opn->location = $spawnOrder[$opn->color] - 1;
            $opn->qm_match_id = $qmMatch->id;
            $opn->tunnel_id = $qmMatch->seed + $opn->color;
            $opn->save();
        }

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
        }
        $qmPlayer->save();

        return $qmMatch;
    }
}
