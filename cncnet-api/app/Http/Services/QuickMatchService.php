<?php

namespace App\Http\Services;

use App\Extensions\Qm\Matchup\ClanMatchupHandler;
use App\Models\Game;
use App\Models\IpAddress;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\PlayerGameReport;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

        $addr = IpAddress::findByIP($request->ip_address);
        $qmPlayer->ip_address_id = $addr ? $addr->id : null;
        $qmPlayer->port = $request->ip_port;

        $addr = IpAddress::findByIP($request->lan_ip);
        $qmPlayer->lan_address_id = $addr ? $addr->id : null;
        $qmPlayer->lan_port = $request->lan_port;

        $addr = IpAddress::findByIP($request->ipv6_address);
        $qmPlayer->ipv6_address_id = $addr ? $addr->id : null;
        $qmPlayer->ipv6_port = $request->ipv6_port;

        $qmPlayer->chosen_side = $request->side;

        if ($request->map_sides)
        {
            $qmPlayer->map_sides_id = \App\Models\MapSideString::findValue(join(',', $request->map_sides))->id;
        }

        if ($request->version && $request->platform)
        {
            $qmPlayer->version_id  = \App\Models\PlayerDataString::findValue($request->version)->id;
            $qmPlayer->platform_id = \App\Models\PlayerDataString::findValue($request->platform)->id;
        }

        if ($request->ddraw)
        {
            $qmPlayer->ddraw_id = \App\Models\PlayerDataString::findValue($request->ddraw)->id;
        }

        // Save user IP Address
        $player->user->ip_address_id = \App\Models\IpAddress::getID(isset($_SERVER["HTTP_CF_CONNECTING_IP"])
            ? $_SERVER["HTTP_CF_CONNECTING_IP"]
            : $request->getClientIp());

        \App\Models\IpAddressHistory::addHistory($player->user->id, $player->user->ip_address_id);

        \App\Models\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ip_address_id);

        \App\Models\IpAddressHistory::addHistory($player->user->id, $qmPlayer->ipv6_address_id);

        $player->user->save();

        // Is player an observer?
        if ($player->user->userSettings->is_observer)
        {
            Log::debug("Player ** Is observing Game: " . $player->username);
            $qmPlayer->is_observer = true;
        }
        else
        {
            // Log::debug("Player ** Is NOT observing Game: " . $player->username);
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
                $lap = new \App\Models\LadderAlertPlayer;
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

        if (empty($alert)) return null;

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

    public function fetchQmQueueEntry(LadderHistory $history, ?QmQueueEntry $qmQueueEntry = null): \Illuminate\Database\Eloquent\Collection|array
    {
        return QmQueueEntry::query()
            ->when(isset($qmQueueEntry), fn ($q) => $q->where('qm_match_player_id', '!=', $qmQueueEntry->qmPlayer->id))
            ->where('ladder_history_id', '=', $history->id)
            ->get();
    }

    /**
     * Find all opponents that can be matched with the current player in queue.
     * We exclude observers
     * @param QmQueueEntry $currentQmQueueEntry
     * @param QmQueueEntry[]|Collection $opponents
     * @return QmQueueEntry[]|Collection
     */
    public function getMatchableOpponents(QmQueueEntry $currentQmQueueEntry, Collection $opponents): Collection
    {

        $history = $currentQmQueueEntry->ladderHistory;
        $ladder = $history->ladder;
        $currentTier = $currentQmQueueEntry->qmPlayer->player->user->getUserLadderTier($ladder)->tier;

        $matchableOpponents = collect();

        foreach ($opponents as $opponent)
        {
            if (!isset($opponent->qmPlayer)) continue;

            // If the opponent is an observer we skip him
            if ($opponent->qmPlayer?->isObserver())
            {
                continue;
            }

            $oppTier = $opponent->qmPlayer->player->user->getUserLadderTier($ladder)->tier;

            // If players are not in the same league (same tier), then we don't match them together
            // Check the ladder is meant to be using tiers.
            if ($ladder->qmLadderRules->tier2_rating > 0)
            {
                if ($currentTier !== $oppTier)
                {
                    // Except if any of them have both_tiers feature enabled.
                    // Check both as either player could be tier 1
                    if (
                        ($oppTier === 1 && $opponent->qmPlayer->player->user->canUserPlayBothTiers($ladder))
                        ||
                        ($currentTier === 1 && $currentQmQueueEntry->qmPlayer->player->user->canUserPlayBothTiers($ladder))
                    )
                    {
                        // Players can match so we can continue with the rest of the process
                        Log::info("PlayerMatchupHandler ** Players in different tiers for ladder BUT LeaguePlayer Settings have ruled them to play  "
                            . $ladder->abbreviation . "- P1:" . $opponent->qmPlayer->player->username . " (Tier: " . $oppTier . ") VS  P2:"
                            . $currentQmQueueEntry->qmPlayer->player->username . " (Tier: " . $currentTier . ")");
                    }
                    else
                    {
                        // Player cannot match so we skip it
                        Log::info("PlayerMatchupHandler ** Players in different tiers for ladder " . $ladder->abbreviation
                            . "- P1:" . $opponent->qmPlayer->player->username . " (Tier: " . $oppTier . ") VS  P2:"
                            . $currentQmQueueEntry->qmPlayer->player->username . " (Tier: " . $currentTier . ")");
                        continue;
                    }
                }
            }


            // Checks players point filter settings
            if (
                $currentQmQueueEntry->qmPlayer->player->user->userSettings->disabledPointFilter
                && $opponent->qmPlayer->player->user->userSettings->disabledPointFilter
            )
            {
                // Do both players rank meet the minimum rank required for no pt filter to apply
                if (
                    abs($currentQmQueueEntry->qmPlayer->player->rank($history) - $opponent->qmPlayer->player->rank($history))
                    <=
                    $ladder->qmLadderRules->point_filter_rank_threshold
                )
                {
                    // Both players have the point filter disabled, we will ignore the point filter and match them
                    $matchableOpponents->add($opponent);
                    continue;
                }
            }


            // (updated_at - created_at) / 60 = seconds duration player has been waiting in queue
            $pointsTime = ((strtotime($currentQmQueueEntry->updated_at) - strtotime($currentQmQueueEntry->created_at))) * $ladder->qmLadderRules->points_per_second;

            // is the opponent within the point filter
            if ($pointsTime + $ladder->qmLadderRules->max_points_difference > abs($currentQmQueueEntry->points - $opponent->points))
            {
                $matchableOpponents->add($opponent);
            }
        }

        return $matchableOpponents;
    }


    /**
     * Find all opponents within point range.
     * Does not take into account 'disable point filter'
     * We exclude observers
     * @param QmQueueEntry $currentQmQueueEntry
     * @param QmQueueEntry[]|Collection $opponents
     * @return QmQueueEntry[]|Collection
     */
    public function getEntriesInPointRange(QmQueueEntry $currentQmQueueEntry, Collection $opponents): Collection
    {
        $history = $currentQmQueueEntry->ladderHistory;
        $ladder = $history->ladder;
        $pointsPerSecond = $ladder->qmLadderRules->points_per_second;
        $maxPointsDifference = $ladder->qmLadderRules->max_points_difference;

        $matchableOpponents = collect();

        foreach ($opponents as $opponent)
        {
            if (!isset($opponent->qmPlayer))
            {
                continue;
            }

            // If the opponent is an observer we skip him
            if ($opponent->qmPlayer?->isObserver())
            {
                continue;
            }

            // (updated_at - created_at) / 60 = seconds duration player has been waiting in queue
            $pointsTime = ((strtotime($currentQmQueueEntry->updated_at) - strtotime($currentQmQueueEntry->created_at))) * $pointsPerSecond;

            // is the opponent within the point filter
            if ($pointsTime + $maxPointsDifference > abs($currentQmQueueEntry->points - $opponent->points))
            {
                $matchableOpponents->add($opponent);
            }
        }

        return $matchableOpponents;
    }

    /**
     * Get maps in common of all the given players for the given ladder
     * @param Ladder $ladder
     * @param QmQueueEntry[]|Collection $players
     * @return QmMap[]
     */
    public function getCommonMapsForPlayers(Ladder $ladder, Collection $players): Collection
    {

        $qmMaps = $ladder->mapPool->maps;

        $is_rejected_bit = -2;

        $commonQmMaps = collect([]);
        foreach ($qmMaps as $qmMap)
        {
            $rejectedMap = false;
            foreach ($players as $player)
            {
                $playerMapSides = $player->qmPlayer->map_side_array();
                if (!(
                    array_key_exists($qmMap->bit_idx, $playerMapSides) // if the map exists in the player map side
                    && $playerMapSides[$qmMap->bit_idx] > $is_rejected_bit // and if the map is not refjected by the player
                    && in_array($playerMapSides[$qmMap->bit_idx], $qmMap->sides_array()) // and if the side chose by the player is allowed in the map
                ))
                {
                    // this means the player reject this map
                    $rejectedMap = true;
                    break;
                }
            }
            if (!$rejectedMap)
            {
                $commonQmMaps[] = $qmMap;
            }
        }

        return $commonQmMaps;
    }

    /**
     * @param array $maps
     * @param QmQueueEntry[]|Collection $players
     * @return Collection
     */
    public function filterOutRecentsMaps(LadderHistory $history, Collection $maps, Collection $players): Collection
    {

        $maps = collect($maps);

        foreach ($players as $player)
        {
            /** @var Collection $playerGameReports */
            $playerGameReports = $player->qmPlayer->player->playerGames()
                ->where("ladder_history_id", "=", $history->id)
                ->where("disconnected", "=", 0)
                ->where("no_completion", "=", 0)
                ->where("draw", "=", 0)
                ->orderBy('created_at', 'DESC')
                ->limit($history->ladder->qmLadderRules->reduce_map_repeats)
                ->get();

            $recentMapsHash = $playerGameReports
                ->map(fn (PlayerGameReport $item) => $item->game->map)
                ->filter()
                ->pluck('hash')
                ->toArray();

            $maps = $maps->filter(fn (QmMap $map) => !in_array($map->map->hash, $recentMapsHash));
        }

        return $maps;
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

    private function pickQmMapId(Collection $otherQMQueueEntries, QmLadderRules $ladderRules, QmMatchPlayer $qmPlayer, LadderHistory $history, Collection $qmMaps)
    {
        $qmMapId = -1;
        if ($ladderRules->use_ranked_map_picker) // consider a player's ladder rank when selecting a map
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

            $qmMapId = $this->rankedMapPicker($qmMaps, $rank, $points, $matchAnyMap);  //select a map dependent on player rank and map tiers
        }
        else if ($ladderRules->use_elo_map_picker) // consider a player's ELO when selecting a map
        {
            $matchAnyMap = false;
            $myEloRating = $qmPlayer->player->user->getOrCreateLiveUserRating()->rating;

            foreach ($otherQMQueueEntries as $otherQMQueueEntry)
            {
                // choose the person who has the lowest elo rating to base our map pick off of
                $minEloRating = min($myEloRating, $otherQMQueueEntry->qmPlayer->player->user->getOrCreateLiveUserRating()->rating);

                // true if both players allow any map
                $matchAnyMap = $otherQMQueueEntry->qmPlayer->player->user->userSettings->match_any_map
                    && $qmPlayer->player->user->userSettings->match_any_map;
            }

            $qmMapId = $this->eloMapPicker($qmMaps, $minEloRating, $matchAnyMap);  //select a map dependent on playe elo
        }

        else
        {
            $qmMapsWeighted = [];

            foreach ($qmMaps as $qmMap)
            {
                $weight = $qmMap->weight; //defaults to 1

                for ($i = 0; $i < $weight; $i++)
                {
                    $qmMapsWeighted[] = $qmMap; //add maps to the pool additional times depending on their weight
                }
            }

            $randomMapIdx = mt_rand(0, count($qmMapsWeighted) - 1);
            $qmMapId = $qmMapsWeighted[$randomMapIdx]->id;
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
        Log::debug("QuickMatchService ** setClanSpawns: " . $qmPlayer->player->username);

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

                $teams[$otherQmQueueEntry->qmPlayer->clan_id][] = $otherQmQueueEntry->qmPlayer;
            }

            // Get the values (sub-arrays) from the $teams array
            $teamValues = array_values($teams);

            // Assign the values to separate variables
            $team1 = $teamValues[0];
            $team2 = $teamValues[1];


            if (count($team1) != count($team1SpawnOrder))
            {
                Log::debug("Team1: Expected " . count($team1SpawnOrder) . " players but found " . count($team1));
            }
            else if (count($team2) != count($team2SpawnOrder))
            {
                Log::debug("Team2: Expected " . count($team2SpawnOrder) . " players but found " . count($team2));
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

                Log::debug("QuickMatchService ** Team Spots Assigned Successfully: " . $teamSpotsAssigned);
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

                Log::debug("QuickMatchService ** Assigning Spot for " . $qmPlayer->player->username . "Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
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
                    Log::debug("QuickMatchService ** Spawn Order Output:" . $so . " i: " . $i);
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

                    Log::debug("QuickMatchService ** Assigning Spot for " . $otherQmPlayer->player->username . "Color: " . $otherQmPlayer->color .  " Location: " . $otherQmPlayer->location);
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
        $spawnOrder = explode(',', $qmMap->spawn_order);

        Log::debug("QuickMatchService ** set1v1QmSpawns: " . $qmPlayer->player->username . " Playing " . $qmMap->map->name);
        Log::debug("QuickMatchService ** set1v1QmSpawns: Qm Map" . $qmMap->description);

        if (
            $qmMap->random_spawns == true
            && $qmMap->map->spawn_count > 2
            && ($expectedPlayerQueueCount == ($matchHasObserver ? 3 : 2))
        )
        {
            Log::debug("QuickMatchService ** set1v1QmSpawns: Random Spawns for " . $qmMap->description);

            # This map uses 1v1 random spawns
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

            Log::debug("QuickMatchService ** Random spawns selected for qmMap: '" . $qmMap->description . "', " . $spawnOrder[0] . "," . $spawnOrder[1]);
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

            Log::debug("QuickMatchService ** Assigning Spot for " . $qmPlayer->player->username . "Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
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

                Log::debug("ApiQuickMatchController ** Assigning Spot for " . $otherQmPlayer->player->username . "Color: " . $otherQmPlayer->color .  " Location: " . $otherQmPlayer->location);
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

    public function createQmMatch(
        QmMatchPlayer $qmPlayer,
        int $currentUserTier,
        Collection $maps,
        Collection $otherQmQueueEntries,
        QmQueueEntry $qEntry,
        int $gameType
    )
    {
        $ladder = Ladder::where('id', $qmPlayer->ladder_id)->first();
        $history = $ladder->currentHistory();

        $qmMapId = $this->pickQmMapId(
            $otherQmQueueEntries,
            $ladder->qmLadderRules,
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

        // Log::debug("ApiQuickMatchController ** createQmMatch: Observer Present: " . $matchHasObserver);
        Log::debug("ApiQuickMatchController ** createQmMatch: Player counts " . $currentQueuePlayerCount . "/" . $expectedPlayerQueueCount);


        # Create the qm_matches db entry
        $qmMatch = new QmMatch();
        $qmMatch->ladder_id = $qmPlayer->ladder_id;
        $qmMatch->qm_map_id = $qmMapId;
        $qmMatch->seed = mt_rand(-2147483647, 2147483647);
        $qmMatch->tier = $currentUserTier;


        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();
        $game->qm_match_id = $qmMatch->id;
        $game->save();


        # Set up player specific information
        # Color will be used for spawn location
        $qmPlayer = \App\Models\QmMatchPlayer::where('id', $qmPlayer->id)->first();
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

        Log::debug("Launching match with players $playerNames, " . $qmPlayer->player->username . " on map: " . $qmMatch->map->description);

        return $qmMatch;
    }

    public function createTeamQmMatch(LadderHistory $history, Collection $maps, Collection $teamAPlayers, Collection $teamBPlayers, Collection $observers, $gameType): QmMatch
    {

        $ladder = $history->ladder;
        $currentQmQueueEntry = $teamAPlayers->first();

        $qmMapId = $this->chooseQmMapId($teamAPlayers->merge($teamBPlayers), $ladder->qmLadderRules->use_ranked_map_picker, $history, $maps);

        $matchHasObserver = $observers->count() > 0;

        $currentQueuePlayerCount = $teamAPlayers->count() + $teamBPlayers->count();
        $expectedPlayerQueueCount = $currentQueuePlayerCount + $observers->count();

        Log::info("ApiQuickMatchController ** createQmMatch: Observer Present: " . $matchHasObserver ? 'Yes' : 'No');
        Log::info("ApiQuickMatchController ** createQmMatch: Player counts " . $currentQueuePlayerCount . "/" . $expectedPlayerQueueCount);

        # Create the qm_matches db entry
        $qmMatch = QmMatch::create([
            'ladder_id' => $history->ladder_id,
            'qm_map_id' => $qmMapId,
            'seed' => mt_rand(-2147483647, 2147483647),
            'tier' => $currentQmQueueEntry->qmPlayer->player->user->getUserLadderTier($history->ladder)->tier,
        ]);

        # Create the Game
        $game = Game::genQmEntry($qmMatch, $gameType);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();
        $game->qm_match_id = $qmMatch->id;
        $game->save();

        $qmMap = $qmMatch->map;

        // team1_spawn_order is a string with format of "0,0" or "1,2", etc - represents starting spawns of that team

        $spawns = new Collection;
        if ($qmMap->random_spawns) // random spawns could be LvR, TvB, or corners - random spots given for every player
        {
            // populate array with values 1 to n, n = number of players in the match
            $spawnArr = array_map(fn($num) => $num, range(1, $ladder->qmLadderRules->player_count));

            // shuffle the spawns
            shuffle($spawnArr);

            // divide the spawns among both teams
            $half = count($spawnArr) / 2;
            $spawns = collect([array_slice($spawnArr, 0, $half), array_slice($spawnArr, $half)]);
        }
        else // use set spawn order. If 0,0 is set for each team, corners spawns will be applied
        {
            $spawns = collect([$qmMap->team1_spawn_order, $qmMap->team2_spawn_order])->shuffle();
        }

        $colors = 0;

        if ($spawns->count() < 2)
        {
            Log::error('[QuickMatchService::createTeamQmMatch] spawns for team maps not set correctly. Columns team1_spawn_order and team2_spawn_order on qm_maps with id : ' . $qmMap->id . ' are not set.');
            throw new Exception('spawns for team maps not set correctly. Columns team1_spawn_order and team2_spawn_order on qm_maps with id : ' . $qmMap->id . ' are not set.');
        }

        $this->setTeamSpawns('A', $spawns[0], $teamAPlayers, $qmMatch, $colors);
        $this->setTeamSpawns('B', $spawns[1], $teamBPlayers, $qmMatch, $colors);

        $this->setObserversSpawns($observers, $qmMatch, $colors);

        return $qmMatch;
    }

    private function setTeamSpawns(string $team, string $spawnOrders, Collection $teamPlayers, QmMatch $qmMatch, int &$colors)
    {

        Log::debug('[QuickMatchService::setTeamSpawns]');
        $spawnOrder = array_map(fn ($i) => intval($i), explode(',', $spawnOrders));
        $qmMap = $qmMatch->map;


        Log::debug('[QuickMatchService::setTeamSpawns] $spawnOrder ' . json_encode($spawnOrder));

        $mapAllowedSides = array_values(array_filter($qmMap->sides_array(), fn ($s) => $s >= 0));

        foreach ($teamPlayers->values() as $i => $player)
        {

            Log::debug('[QuickMatchService::setTeamSpawns] trying to set spawn for player ' . $player->id . ' with i : ' . $i . ' and color ' . $colors);

            $qmPlayer = $player->qmPlayer;
            $player->delete();

            $qmPlayer->color = $colors++;
            $qmPlayer->location = $spawnOrder[$i] - 1;

            $osides = explode(',', $qmPlayer->mapSides->value);

            if (count($osides) > $qmMap->bit_idx)
            {
                $qmPlayer->actual_side = $osides[$qmMap->bit_idx];
            }


            if ($qmPlayer->actual_side  < -1)
            {
                $qmPlayer->actual_side = $qmPlayer->chosen_side;
            }

            if ($qmPlayer->actual_side == -1)
            {
                $qmPlayer->actual_side = $mapAllowedSides[mt_rand(0, count($mapAllowedSides) - 1)];
            }

            $qmPlayer->qm_match_id = $qmMatch->id;
            $qmPlayer->tunnel_id = $qmMatch->seed + $qmPlayer->color;
            $qmPlayer->team = $team;

            $qmPlayer->save();
        }
    }

    private function setObserversSpawns(Collection $observers, QmMatch $qmMatch, int &$colors)
    {

        foreach ($observers->values() as $i => $observer)
        {
            $qmObserver = $observer->qmPlayer;
            $observer->delete();

            $qmObserver->color = $colors++;
            $qmObserver->location = -1;
            $qmObserver->qm_match_id = $qmMatch->id;
            $qmObserver->tunnel_id = $qmMatch->seed + $qmObserver->color;
            $qmObserver->save();
        }
    }

    private function chooseQmMapId(Collection $qmQueueEntries, bool $useRankedMapPicker, LadderHistory $history, Collection $qmMaps)
    {
        //use ranked map selection
        if ($useRankedMapPicker)
        {

            $rank = PHP_INT_MIN;
            $points = PHP_INT_MAX;

            $matchAnyMap = true;
            foreach ($qmQueueEntries as $otherQMQueueEntry)
            {
                //choose the person who has the worst rank to base our map pick off of
                $rank = max($rank, $otherQMQueueEntry->qmPlayer->player->rank($history));
                $points = min($points, $otherQMQueueEntry->qmPlayer->player->points($history));

                //true if both players allow any map
                $matchAnyMap = $otherQMQueueEntry->qmPlayer->player->user->userSettings->match_any_map
                    && $matchAnyMap;
            }

            return $this->rankedMapPicker($qmMaps, $rank, $points, $matchAnyMap);  //select a map dependent on player rank and map tiers
        }

        $qmMapsWeighted = [];
        foreach ($qmMaps as $qmMap)
        {
            $weight = $qmMap->weight; //defaults to 1

            for ($i = 0; $i < $weight; $i++)
            {
                $qmMapsWeighted[] = $qmMap; //add maps to the pool additional times depending on their weight
            }
        }

        $randomMapIdx = mt_rand(0, count($qmMapsWeighted) - 1);
        $qmMapId = $qmMapsWeighted[$randomMapIdx]->id;

        return $qmMapId;
    }

    /**
     * Given a player's rank, choose a map based on map ranked difficulties
     * @param mixed $mapsArr
     * @param mixed $rank
     * @param mixed $points
     * @param mixed $matchAnyMap
     * @return mixed
     */
    private function rankedMapPicker(Collection $maps, int $rank, int $points, bool $matchAnyMap)
    {
        $maps = $maps->filter(fn ($map) => $map->map_tier && $map->map_tier > 0)->values();

        Log::debug("Selecting map for rank $rank, points $points, anyMap=" . $matchAnyMap . ", " . $maps->count() . " maps");

        $mapsRanked = [];
        foreach ($maps as $map)
        {
            //difficulties: 1 = beginner, 2 = intermediate, 3 = advanced, 4 = expert
            $mapsRanked[$map->map_tier][] = $map;
        }

        $randVal = mt_rand(0, 99); //rand val between 0 and 99

        try
        {
            if ($matchAnyMap)
            {
                $randIdx = mt_rand(0, $maps->count() - 1); //any map
                $map = $maps[$randIdx];
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
                $randIdx = mt_rand(0, $maps->count() - 1); //any map
                $map = $maps[$randIdx];
            }
        }
        catch (Exception $ex)
        {
            // Safety until its fixed

            Log::debug("Error in rankedMapPicker: " . $ex->getMessage());
            $randIdx = mt_rand(0, $maps->count() - 1); //any map
            $map = $maps[$randIdx];
        }

        if ($map == null)
        {
            Log::error("null map chosen");
            Log::error("null map chosen, map_tier=$map->map_tier, from rank=$rank, used randIdx=$randIdx");
        }

        Log::debug("Ranked map chosen=$map->description, map_tier=$map->map_tier, from rank=$rank, used randIdx=$randIdx");

        return $map->id;
    }


    /**
     * Given a player's ELO, choose a map based on map ranked difficulties
     * @param mixed $mapsArr array of QM maps
     * @param mixed $elo elo threshold
     * @param mixed $matchAnyMap if true, match on a random map
     * @return mixed qmMapId of the map to be played
     */
    private function eloMapPicker(Collection $maps, int $elo, bool $matchAnyMap)
    {
        $maps = $maps->filter(fn ($map) => $map->map_tier && $map->map_tier > 0)->values();

        Log::debug("Selecting map for elo $elo, anyMap=" . strval($matchAnyMap) . ", " . strval($maps->count()) . " maps");

        // group maps by tier
        $tier1Maps = [];
        foreach ($maps as $map)
        {
            if ($map->map_tier == 1)
                $tier1Maps[] = $map;
        }

        try
        {
            // TODO set the ELO threshold as a qm ladder rules configurable value
            $eloThreshold = 1200;

            if (!$matchAnyMap && $elo < $eloThreshold && count($tier1Maps) > 0) // use a tier 1 map, if ELO < 1200
            {
                $randIdx = mt_rand(0, count($tier1Maps) - 1);  // pick a tier 1 map
                $map = $tier1Maps[$randIdx];
            }
            else
            {
                $randIdx = mt_rand(0, $maps->count() - 1); //any map
                $map = $maps[$randIdx];
            }
        }
        catch (Exception $ex)
        {
            Log::error("Error in eloMapPicker: " . $ex->getMessage());
            Log::error($ex);
            $randIdx = mt_rand(0, $maps->count() - 1); //any map
            $map = $maps[$randIdx];
        }

        if ($map == null)
        {
            Log::error("null map chosen");
            Log::error("null map chosen, map_tier=$map->map_tier, from elo=$elo, used randIdx=$randIdx");
        }

        Log::debug("Elo map chosen=$map->description, map_tier=$map->map_tier, from elo=$elo, used randIdx=$randIdx");

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

    public function findPossibleMatches($currentPlayerId, $currentPlayerRank, $opponentsRating): array
    {
        $possibleMatches = [];
        for ($i = 0; $i < count($opponentsRating); $i++)
        {
            $teamA = [
                'player1' => $currentPlayerId,
                'player2' => $opponentsRating[$i]['id'],
                'team_elo' => $currentPlayerRank + $opponentsRating[$i]['rank'],
                'elo_gap' => abs($currentPlayerRank - $opponentsRating[$i]['rank'])
            ];

            for ($j = 0; $j < count($opponentsRating); $j++)
            {
                if ($opponentsRating[$j]['id'] == $teamA['player2']) continue;
                $player1B = $opponentsRating[$j]['id'];

                for ($k = 0; $k < count($opponentsRating); $k++)
                {
                    if ($opponentsRating[$k]['id'] == $player1B || $opponentsRating[$k]['id'] == $teamA['player2']) continue;
                    $player2B = $opponentsRating[$k]['id'];

                    $teamB = [
                        'player1' => $player1B,
                        'player2' => $player2B,
                        'team_elo' => $opponentsRating[$j]['rank'] + $opponentsRating[$k]['rank'],
                        'elo_gap' => abs($opponentsRating[$j]['rank'] - $opponentsRating[$k]['rank'])
                    ];

                    $diff = abs($teamA['team_elo'] - $teamB['team_elo']);
                    $gap = $teamA['elo_gap'] + $teamB['elo_gap'];
                    $possibleMatches[] = [
                        'teamA' => $teamA,
                        'teamB' => $teamB,
                        'teams_elo_diff' => $diff,
                        'elo_gap_sum' => $gap,
                        'match_ranking' => $diff + $gap
                    ];
                }
            }
        }
        return $possibleMatches;
    }
    public function findBestMatch($possibleMatches): array
    {

        $minRanking = PHP_INT_MAX;
        $bestBatch = null;
        foreach ($possibleMatches as $match)
        {
            if ($match['match_ranking'] < $minRanking)
            {
                $minRanking = $match['match_ranking'];
                $bestBatch = $match;
            }
        }

        return $bestBatch;
    }

    /**
     * @param QmQueueEntry $currentPlayer
     * @param Collection $matchableOpponents
     * @param LadderHistory $history
     * @return QmQueueEntry[][]|Collection[]
     */
    public function getBestMatch2v2ForPlayer(QmQueueEntry $currentPlayer, Collection $matchableOpponents, LadderHistory $history): array
    {

        $players = $matchableOpponents->concat([$currentPlayer]);

        $opponentsRating = [];
        $currentPlayerRank = $currentPlayer->qmPlayer->player->rank($history);
        foreach ($matchableOpponents as $opponent)
        {
            $opponentsRating[] = [
                'id' => $opponent->id,
                'rank' => $opponent->qmPlayer->player->rank($history)
            ];
        }

        // find all possible matches
        $possibleMatches = $this->findPossibleMatches(
            $currentPlayer->id,
            $currentPlayerRank,
            $opponentsRating
        );

        $best = $this->findBestMatch($possibleMatches);

        $g = function ($players, $match, $team)
        {
            return $players
                ->filter(fn (QmQueueEntry $qmQueueEntry) => in_array($qmQueueEntry->id, [
                    $match[$team]['player1'], $match[$team]['player2']
                ]));
        };

        $teamAPlayers = $g($players, $best, 'teamA');
        $teamBPlayers = $g($players, $best, 'teamB');

        return [$teamAPlayers, $teamBPlayers];
    }

    public function checkQMClientRequiresUpdate(Ladder $ladder, $version)
    {
        # YR Games check
        if ($ladder->game == "yr")
        {
            if ($version < 1.79)
            {
                return true;
            }

            return false;
        }

        # RA/TS Games check
        if ($ladder->game == "ra" || $ladder->game == "ts")
        {
            if ($version < 1.69)
            {
                return true;
            }

            return false;
        }

        return false;
    }

    public function onFatalError(string $error)
    {
        return response()->json([
            "type" => "fatal",
            "message" => $error
        ]);
    }

    public function onCheckback($alert = null)
    {
        $response = [
            "type" => "please wait",
            "checkback" => 10,
            "no_sooner_than" => 5
        ];

        if (isset($alert))
        {
            $response['warning'] = $alert;
        }

        return response()->json($response);
    }
}
