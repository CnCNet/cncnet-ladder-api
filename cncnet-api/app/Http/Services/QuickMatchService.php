<?php

namespace App\Http\Services;

use App\Game;
use App\QmMatch;
use App\QmMatchPlayer;
use App\QmQueueEntry;
use Illuminate\Support\Facades\Log;
use App\Commands\Matchup\ClanMatchupHandler;

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

        if ($history->ladder->clans_allowed && $player->clanPlayer)
        {
            $qmPlayer->clan_id = $player->clanPlayer->clan_id;
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

        if ($history->ladder->clans_allowed) // clan ladder, use clan cache
        {
            $clanCache = $player->clanPlayer->clanCache($history->id);

            if ($clanCache !== null)
            {
                $points = $clanCache->points;
            }
        }
        else
        {
            if ($pc !== null)
            {
                $points = $pc->points;
            }
        }

        if ($qmPlayer->qEntry == null)
        {
            $qEntry = new QmQueueEntry;
            $qEntry->qm_match_player_id = $qmPlayer->id;
            $qEntry->ladder_history_id = $history->id;

            if ($history->ladder->clans_allowed) // clan ladder
            {
                // $qEntry->rating = $clanCache->rating;
                $qEntry->points = $points;
            }
            else
            {
                $qEntry->rating = $player->rating->rating;
                $qEntry->points = $points;
            }

            $qEntry->game_type = $gameType;
            $qEntry->save();
        }
        else
        {
            $qEntry = $qmPlayer->qEntry;
            $qEntry->touch();

            if ($qEntry->ladder_history_id != $history->id) //what is this conditional for?
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

    public function createQmMatch(
        $qmPlayer,
        $userPlayerTier,
        $maps,
        $otherQMQueueEntries,
        $qEntry,
        $gameType
    )
    {
        $ladder = \App\Ladder::where('id', $qmPlayer->ladder_id)->first();
        $history = $ladder->currentHistory();

        if ($ladder->qmLadderRules->use_ranked_map_picker) //use ranked map selection
        {
            $rank = $qmPlayer->player->rank($history);
            $points = $qmPlayer->player->points($history);

            $matchAnyMap = false;
            foreach ($otherQMQueueEntries as $otherQMQueueEntry)
            {
                //choose the person who has the worst rank to base our map pick off of
                $rank = max($rank, $otherQMQueueEntry->qmPlayer->player->rank($history));
                $points = min($points, $otherQMQueueEntry->qmPlayer->player->points($history));

                //true if both players allow any map
                $matchAnyMap = $otherQMQueueEntry->qmPlayer->player->user->userSettings->match_any_map
                    && $qmPlayer->player->user->userSettings->match_any_map;
            }

            $qmMapId = $this->rankedMapPicker($maps, $rank, $points, $matchAnyMap);  //select a map dependent on player rank and map tiers
        }
        else
        {
            $randomMapIdx = mt_rand(0, count($maps) - 1);
            $qmMapId = $maps[$randomMapIdx]->id;
        }

        # Create the qm_matches db entry
        $qmMatch = new QmMatch();
        $qmMatch->ladder_id = $qmPlayer->ladder_id;
        $qmMatch->qm_map_id = $qmMapId;
        $qmMatch->seed = mt_rand(-2147483647, 2147483647);
        $qmMatch->tier = $userPlayerTier;

        $actualPlayerCount = count($otherQMQueueEntries) + 1; //total player counts equals myself plus other players to be matched
        $expectedPlayerCount = $ladder->qmLadderRules->player_count;
        if ($actualPlayerCount != $expectedPlayerCount)
        {
            Log::error("Only found $actualPlayerCount players, expected $expectedPlayerCount.");
            Log::error(implode(",", $actualPlayerCount) . ", " . $qmPlayer->player->username);
            // return null
        }

        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();

        $game->qm_match_id = $qmMatch->id;
        $game->save();

        $qmMap = $qmMatch->map;
        $spawnOrder = explode(',', $qmMap->spawn_order);

        if ($qmMap->random_spawns && $qmMap->map->spawn_count > 2 && $expectedPlayerCount == 2) //this map uses 1v1 random spawns
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

        //check if team spots are configured, if this is a clan match
        $team1SpawnOrder = $qmMap->team1_spawn_order;
        $team2SpawnOrder = $qmMap->team2_spawn_order;

        $teamSpotsAssigned = false;
        if (
            $ladder->clans_allowed
            && $team1SpawnOrder && strlen(trim($team1SpawnOrder)) > 0
            && $team2SpawnOrder && strlen(trim($team2SpawnOrder)) > 0
        )
        {
            $team1SpawnOrders = explode('|', $team1SpawnOrder); //e.g. 1,2|1,3
            $team2SpawnOrders = explode('|', $team2SpawnOrder); //e.g. 3,4|2,4

            $teamSpawnIndex = mt_rand(0, count($team1SpawnOrders) - 1);

            $team1SpawnOrder = explode(',', $team1SpawnOrders[$teamSpawnIndex]); //e.g. 1,2
            $team2SpawnOrder = explode(',', $team2SpawnOrders[$teamSpawnIndex]); //e.g. 3,4

            //map each player to their clan
            $team1 = [];
            $team2 = [];
            $team1[] = $qmPlayer;

            //assign other players to correct clan (assumes there are 2 clans)
            foreach ($otherQMQueueEntries as $qmOpnEntry)
            {
                if ($qmOpnEntry->qmPlayer->clan_id == $qmPlayer->clan_id && $qmOpnEntry->qmPlayer->id != $qmPlayer->id)
                    $team1[] = $qmOpnEntry->qmPlayer;
                else
                    $team2[] = $qmOpnEntry->qmPlayer;
            }

            if (count($team1) != count($team1SpawnOrder))
            {
                Log::info("Team1: Expected " . count($team1SpawnOrder) . " players but found " . count($team1));
            }
            else if (count($team2) != count($team2SpawnOrder))
            {
                Log::info("Team2: Expected " . count($team2SpawnOrder) . " players but found " . count($team2));
            }
            else
            {
                //assign team 1 spots
                $color = 0;
                for ($i = 0; $i < count($team1SpawnOrder); $i++) //red + yellow
                {
                    $currentQmPlayer = $team1[$i];
                    $currentQmPlayer->color = $color++;
                    $currentQmPlayer->location = trim($team1SpawnOrder[$i]) - 1;
                    $currentQmPlayer->save();
                }

                //assign team 2 spots
                for ($i = 0; $i < count($team2SpawnOrder); $i++) //green + blue
                {
                    $currentQmPlayer = $team2[$i];
                    $currentQmPlayer->color = $color++;
                    $currentQmPlayer->location = trim($team2SpawnOrder[$i]) - 1;
                    $currentQmPlayer->save();
                }

                $mapName = $qmMap->map->name;
                $teamSpotsAssigned = true;
            }
        }

        # Set up player specific information
        # Color will be used for spawn location
        $qmPlayer = \App\QmMatchPlayer::where('id', $qmPlayer->id)->first();

        if (!$teamSpotsAssigned && $ladder->clans_allowed) //randomize the spawns if teams were not manually set and is a clan match
        {
            $spawnOrder = getLocationsArr($qmMap->map->spawn_count, true);
        }
        $colorsArr = getColorsArr(8, false);

        $i = 0;
        if (!$teamSpotsAssigned)
        {
            $qmPlayer->color = $colorsArr[$i];
            $qmPlayer->location = $spawnOrder[$i] - 1;
            $i++;
        }

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

        foreach ($otherQMQueueEntries as $qOpn)
        {
            $opn = $qOpn->qmPlayer;
            $opn = \App\QmMatchPlayer::where('id', $opn->id)->first();
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

            if (!$teamSpotsAssigned) //spots were not team assigned
            {
                $opn->color = $colorsArr[$i];
                $opn->location = $spawnOrder[$i] - 1;
                $i++;
            }

            $opn->qm_match_id = $qmMatch->id;
            $opn->tunnel_id = $qmMatch->seed + $opn->color;
            $opn->save();
        }

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
        }
        $qmPlayer->save();

        $playerNames = implode(",", ClanMatchupHandler::getPlayerNamesInQueue($otherQMQueueEntries));
        Log::info("Launching match with players $playerNames, " . $qmPlayer->player->username . " on map: " . $qmMatch->map->description);

        return $qmMatch;
    }

    /**
     * Given a player's rank, choose a map based on map ranked difficulties
     */
    private function rankedMapPicker($mapsArr, $rank, $points, $matchAnyMap)
    {
        $mapsArr = array_filter($mapsArr, function ($map)
        {
            return $map->difficulty && $map->difficulty > 0;
        });

        Log::info("Selecting map for rank $rank, points $points, anyMap=" . strval($matchAnyMap) . ", " . strval(count($mapsArr)) . " maps");

        $mapsRanked = [];
        foreach ($mapsArr as $map)
        {
            //difficulties: 1 = beginner, 2 = intermediate, 3 = advanced, 4 = expert
            $mapsRanked[$map->difficulty][] = $map;
        }

        $randVal = mt_rand(0, 99); //rand val between 0 and 99

        if ($matchAnyMap)
        {
            $randIdx = mt_rand(0, count($mapsArr) - 1); //any map
            $map = $mapsArr[$randIdx];
        }
        else if ($rank >= 90 || $points < 150) //90-999 or points less than 150 points
        {
            $randIdx = mt_rand(0, count($mapsRanked[1]) - 1);  //pick a beginner map
            $map = $mapsRanked[1][$randIdx];
        }
        else if ($rank >= 75 || $points < 300) //75 - 89
        {
            $beginnerAndIntermediate = array_merge($mapsRanked[1], $mapsRanked[2]);
            $randIdx = mt_rand(0, count($beginnerAndIntermediate) - 1);
            $map = $beginnerAndIntermediate[$randIdx];
        }
        else if ($rank >= 50 || $points < 400) //50-74
        {
            if ($randVal < 60) //beginner/intermediate map
            {
                $beginnerAndIntermediate = array_merge($mapsRanked[1], $mapsRanked[2]);
                $randIdx = mt_rand(0, count($beginnerAndIntermediate) - 1);
                $map = $beginnerAndIntermediate[$randIdx];
            }
            else //advanced map
            {
                $randIdx = mt_rand(0, count($mapsRanked[3]) - 1);
                $map = $mapsRanked[3][$randIdx];
            }
        }
        else if ($rank >= 20) //20 - 49
        {
            if ($randVal < 70) //beginner/intermediate/advanced map
            {
                $beginnerAndIntermediateAndAdvanced = array_merge($mapsRanked[1], $mapsRanked[2], $mapsRanked[3]);
                $randIdx = mt_rand(0, count($beginnerAndIntermediateAndAdvanced));
                $map = $beginnerAndIntermediateAndAdvanced[$randIdx];
            }
            else //expert map
            {
                $randIdx = mt_rand(0, count($mapsRanked[4]) - 1);
                $map = $mapsRanked[4][$randIdx];
            }
        }
        else
        {
            $randIdx = mt_rand(0, count($mapsArr) - 1); //any map
            $map = $mapsArr[$randIdx];
        }

        if ($map == null)
        {
            Log::error("null map chosen");
            Log::error("null map chosen, difficulty=$map->difficulty, from rank=$rank, used randIdx=$randIdx");
        }

        Log::info("Ranked map chosen=$map->description, difficulty=$map->difficulty, from rank=$rank, used randIdx=$randIdx");

        return $map->id;
    }
}

/**
 * Given $numPlayers, return an array of numbers, length of array = $numPlayers.
 * 
 * Clan ladder matches should randomize the colors. 1v1 should use red vs yellow.
 */
function getColorsArr($numPlayers, $randomize)
{
    $possibleColors = [0, 1, 2, 3, 4, 5, 6, 7];

    if ($randomize)
        shuffle($possibleColors);

    return array_slice($possibleColors, 0, $numPlayers);
}

function getLocationsArr($numPlayers, $randomize)
{
    $locations = [];
    for ($i = 0; $i < $numPlayers; $i++)
    {
        $locations[] = $i + 1;
    }

    if ($randomize)
        shuffle($locations);

    return $locations;
}
