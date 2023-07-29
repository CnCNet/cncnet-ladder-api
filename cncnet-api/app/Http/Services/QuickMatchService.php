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
    public function createQMPlayer($request, $player, $history)
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

        // Is player observer?
        // @TODO: Just for tests until we do this by "Observer" faction
        if (
            $player->username === "Thomas338"
        )
        {
            Log::info("Player ** Is observing Game: " . $player->username);
            $qmPlayer->is_observer = true;
        }
        else
        {
            Log::info("Player ** Is NOT observing Game: " . $player->username);
        }

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

    private function setQmPlayerObserverColorLocation($qmPlayer)
    {
        $qmPlayer->color = 5;
        $qmPlayer->location = -1;
        $qmPlayer->save();
    }

    private function pickQmMapId($otherQMQueueEntries, $useRankedMapPicker, $qmPlayer, $history, $maps)
    {
        $qmMapId = -1;
        if ($useRankedMapPicker) //use ranked map selection
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

        return $qmMapId;
    }

    private function checkMatchForObserver($qmPlayer, $otherQMQueueEntries)
    {
        # Check ourselves
        $hasObservers = $qmPlayer->isObserver() == true;

        # Check other players
        foreach ($otherQMQueueEntries as $otherQMQueueEntry)
        {
            if ($otherQMQueueEntry->qmPlayer->isObserver() == true)
            {
                $hasObservers = true;
                break;
            }
        }

        return $hasObservers;
    }

    private function setClanSpawns($otherQmQueueEntries, $ladder, $qmMap, $qmMatch, $qmPlayer,  $perMS, $qEntry)
    {
        Log::info("QuickMatchService ** setClanSpawns: " . $qmPlayer->player->username);


        // Check if team spots are configured, if this is a clan match
        $team1SpawnOrder = explode(',', $qmMap->team1_spawn_order); // e.g. 1,2
        $team2SpawnOrder = explode(',', $qmMap->team2_spawn_order); // e.g. 3,4

        // Default to random spots
        $teamSpotsAssigned = false;
        $spawnOrder = $this->getLocationsArr($qmMap->map->spawn_count, true);


        if (
            count($team1SpawnOrder) == $ladder->qmLadderRules->player_count / 2 &&
            count($team2SpawnOrder) == $ladder->qmLadderRules->player_count / 2
        )
        {
            // Initialize team arrays
            // Map each player to their clan

            $team1 = [];
            $team2 = [];
            $teams = [];

            /* What we need
            $teams = [
                "1" => [
                    "playerA" => [],
                    "playerB" => []
                ],
                "2" => [
                    "playerC" => [],
                    "playerD" => []
                ]
            ];
            */

            // Group up players into teams first
            if ($qmPlayer->isObserver() == false)
            {
                // If we're not an obs, make sure we're in the teams array
                $teams[$qmPlayer->clan_id][] = $qmPlayer;
            }

            foreach ($otherQmQueueEntries as $otherQmQueueEntry)
            {
                if ($otherQmQueueEntry->qmPlayer->isObserver())
                {
                    // Don't add observers to teams
                    continue;
                }

                $teams[$otherQmQueueEntry->qmPlayer->clan_id][] = $otherQmQueueEntry;
            }

            // Get the values (sub-arrays) from the $teams array
            $teamValues = array_values($teams);

            // Assign the values to separate variables
            $team1 = $teamValues[0];
            $team2 = $teamValues[1];


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
                // Assign team 1 spots
                $color = 0;
                for ($i = 0; $i < count($team1SpawnOrder); $i++) //red + yellow
                {
                    $currentQmPlayer = $team1[$i];
                    $currentQmPlayer->color = $color++;
                    $currentQmPlayer->location = trim($team1SpawnOrder[$i]) - 1;
                    $currentQmPlayer->save();
                }

                // Assign team 2 spots
                for ($i = 0; $i < count($team2SpawnOrder); $i++) //green + blue
                {
                    $currentQmPlayer = $team2[$i];
                    $currentQmPlayer->color = $color++;
                    $currentQmPlayer->location = trim($team2SpawnOrder[$i]) - 1;
                    $currentQmPlayer->save();
                }

                $teamSpotsAssigned = true;

                Log::info("QuickMatchService ** Team Spots Assigned Successfully: " . $teamSpotsAssigned);
            }
        }

        if ($teamSpotsAssigned == false)
        {
            $colorsArr = $this->getColorsArr(8, false);

            $i = 0;

            if ($qmPlayer->isObserver() == false)
            {
                $qmPlayer->color = $colorsArr[$i];
                $qmPlayer->location = $spawnOrder[$i] - 1;
                $qmPlayer->save();
                $i++;

                Log::info("QuickMatchService ** Assigning Spot for " . $qmPlayer->player->username . "Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
            }
        }

        foreach ($otherQmQueueEntries as $otherQmQueueEntry)
        {
            $otherQmPlayer = QmMatchPlayer::where('id', $otherQmQueueEntry->qmPlayer->id)->first();
            $otherQmQueueEntry->delete();

            if ($otherQmPlayer === null)
            {
                $qEntry->delete();
                return;
            }

            $osides = explode(',', $otherQmPlayer->mapSides->value);

            if (count($osides) > $qmMap->bit_idx)
            {
                $otherQmPlayer->actual_side = $osides[$qmMap->bit_idx];
            }

            if ($otherQmPlayer->actual_side  < -1)
            {
                $otherQmPlayer->actual_side = $otherQmPlayer->chosen_side;
            }

            if ($otherQmPlayer->actual_side == -1)
            {
                $otherQmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
            }

            if ($teamSpotsAssigned == false) //spots were not team assigned
            {
                foreach ($spawnOrder as $so)
                {
                    Log::info("QuickMatchService ** Spawn Order Output:" . $so . " i: " . $i);
                }
                if ($otherQmPlayer->isObserver() == true)
                {
                    $this->setQmPlayerObserverColorLocation($otherQmPlayer);
                }
                else
                {
                    $otherQmPlayer->color = $colorsArr[$i];
                    $otherQmPlayer->location = $spawnOrder[$i] - 1;
                    $i++;

                    Log::info("QuickMatchService ** Assigning Spot for " . $otherQmPlayer->player->username . "Color: " . $otherQmPlayer->color .  " Location: " . $otherQmPlayer->location);
                }
            }

            $otherQmPlayer->qm_match_id = $qmMatch->id;
            $otherQmPlayer->tunnel_id = $qmMatch->seed + $otherQmPlayer->color;
            $otherQmPlayer->save();
        }

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
        }

        $qmPlayer->save();
    }

    private function set1v1QmSpawns($otherQmQueueEntries, $qmMatch, $qmPlayer, $expectedPlayerQueueCount, $matchHasObserver, $qmMap, $perMS, $qEntry)
    {
        Log::info("QuickMatchService ** set1v1QmSpawns: " . $qmPlayer->player->username);

        $spawnOrder = explode(',', $qmMap->spawn_order);

        if (
            $qmMap->random_spawns
            && $qmMap->map->spawn_count > 2
            && $expectedPlayerQueueCount == $matchHasObserver ? 3 : 2
        )
        {
            # This map uses 1v1 random spawns
            $numSpawns = $qmMap->map->spawn_count;
            $spawnArr = [];

            for ($i = 1; $i <= $numSpawns; $i++)
            {
                $spawnArr[] = $i;
            }

            shuffle($spawnArr); //shuffle the spawns, select 2
            $spawnOrder[0] = $spawnArr[0];
            $spawnOrder[1] = $spawnArr[1];

            Log::info("QuickMatchService ** Random spawns selected for qmMap: '" . $qmMap->description . "', " . $spawnOrder[0] . "," . $spawnOrder[1]);
        }


        # Assign colour & spawn locations for current QM player
        # Then again for other players below
        $colorsArr = $this->getColorsArr(8, false);
        $i = 0;

        if ($qmPlayer->isObserver() == false)
        {
            $qmPlayer->color = $colorsArr[$i];
            $qmPlayer->location = $spawnOrder[$i] - 1;
            $qmPlayer->save();
            $i++;

            Log::info("QuickMatchService ** Assigning Spot for " . $qmPlayer->player->username . "Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
        }

        foreach ($otherQmQueueEntries as $otherQmQueueEntry)
        {
            $otherQmPlayer = QmMatchPlayer::where("id", $otherQmQueueEntry->qmPlayer->id)->first();
            $otherQmQueueEntry->delete();

            if ($otherQmPlayer === null)
            {
                $qEntry->delete();
                return;
            }

            $osides = explode(',', $otherQmPlayer->mapSides->value);

            if (count($osides) > $qmMap->bit_idx)
                $otherQmPlayer->actual_side = $osides[$qmMap->bit_idx];

            if ($otherQmPlayer->actual_side  < -1)
            {
                $otherQmPlayer->actual_side = $otherQmPlayer->chosen_side;
            }

            if ($otherQmPlayer->actual_side == -1)
            {
                $otherQmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
            }

            if ($otherQmPlayer->isObserver() == true)
            {
                $this->setQmPlayerObserverColorLocation($otherQmPlayer);
            }
            else
            {
                $otherQmPlayer->color = $colorsArr[$i];
                $otherQmPlayer->location = $spawnOrder[$i] - 1;
                $i++;

                Log::info("ApiQuickMatchController ** Assigning Spot for " . $otherQmPlayer->player->username . "Color: " . $otherQmPlayer->color .  " Location: " . $otherQmPlayer->location);
            }

            $otherQmPlayer->qm_match_id = $qmMatch->id;
            $otherQmPlayer->tunnel_id = $qmMatch->seed + $otherQmPlayer->color;
            $otherQmPlayer->save();
        }
    }

    public function createQmMatch(
        $qmPlayer,
        $userPlayerTier,
        $maps,
        $otherQmQueueEntries,
        $qEntry,
        $gameType
    )
    {
        $ladder = \App\Ladder::where('id', $qmPlayer->ladder_id)->first();
        $history = $ladder->currentHistory();

        $qmMapId = $this->pickQmMapId(
            $otherQmQueueEntries,
            $ladder->qmLadderRules->use_ranked_map_picker,
            $qmPlayer,
            $history,
            $maps
        );

        $matchHasObserver = $this->checkMatchForObserver(
            $qmPlayer,
            $otherQmQueueEntries
        );

        $currentQueuePlayerCount = count($otherQmQueueEntries) + 1; // Total player counts equals myself plus other players to be matched
        $expectedPlayerQueueCount = $matchHasObserver ? $ladder->qmLadderRules->player_count + 1 :  $ladder->qmLadderRules->player_count;

        Log::info("ApiQuickMatchController ** createQmMatch: Observer Present: " . $matchHasObserver);
        Log::info("ApiQuickMatchController ** createQmMatch: Player counts " . $currentQueuePlayerCount . "/" . $expectedPlayerQueueCount);


        # Create the qm_matches db entry
        $qmMatch = new QmMatch();
        $qmMatch->ladder_id = $qmPlayer->ladder_id;
        $qmMatch->qm_map_id = $qmMapId;
        $qmMatch->seed = mt_rand(-2147483647, 2147483647);
        $qmMatch->tier = $userPlayerTier;


        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();
        $game->qm_match_id = $qmMatch->id;
        $game->save();



        # Set up player specific information
        # Color will be used for spawn location
        $qmPlayer = \App\QmMatchPlayer::where('id', $qmPlayer->id)->first();
        $qmPlayer->qm_match_id = $qmMatch->id;
        $qmPlayer->tunnel_id = $qmMatch->seed + $qmPlayer->color;
        $qmMap = $qmMatch->map;

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

        if ($qmPlayer->isObserver() == true)
        {
            $this->setQmPlayerObserverColorLocation($qmPlayer);
        }

        # These both really really really need refactoring 
        if ($ladder->clans_allowed)
        {
            $this->setClanSpawns(
                $otherQmQueueEntries,
                $ladder,
                $qmMap,
                $qmMatch,
                $qmPlayer,
                $perMS,
                $qEntry
            );
        }
        else
        {
            $this->set1v1QmSpawns(
                $otherQmQueueEntries,
                $qmMatch,
                $qmPlayer,
                $expectedPlayerQueueCount,
                $matchHasObserver,
                $qmMap,
                $perMS,
                $qEntry
            );
        }


        $playerNames = implode(",", ClanMatchupHandler::getPlayerNamesInQueue($otherQmQueueEntries));

        Log::info("Launching match with players $playerNames, " . $qmPlayer->player->username . " on map: " . $qmMatch->map->description);

        return $qmMatch;
    }


    /**
     * Given a player's rank, choose a map based on map ranked difficulties
     * @param mixed $mapsArr 
     * @param mixed $rank 
     * @param mixed $points 
     * @param mixed $matchAnyMap 
     * @return mixed 
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


    /**
     * Given $numPlayers, return an array of numbers, length of array = $numPlayers.
     * Clan ladder matches should randomize the colors. 1v1 should use red vs yellow.
     * @param mixed $numPlayers 
     * @param mixed $randomize 
     * @return int[] 
     */
    private function getColorsArr($numPlayers, $randomize)
    {
        $possibleColors = [0, 1, 2, 3, 4, 5, 6, 7];

        if ($randomize)
        {
            shuffle($possibleColors);
        }

        return array_slice($possibleColors, 0, $numPlayers);
    }


    /**
     * 
     * @param mixed $numPlayers 
     * @param mixed $randomize 
     * @return array 
     */
    private function getLocationsArr($numPlayers, $randomize)
    {
        $locations = [];
        for ($i = 0; $i < $numPlayers; $i++)
        {
            $locations[] = $i + 1;
        }

        if ($randomize)
        {
            shuffle($locations);
        }

        return $locations;
    }
}
