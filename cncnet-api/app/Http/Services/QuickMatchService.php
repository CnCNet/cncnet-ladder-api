<?php

namespace App\Http\Services;

use App\Services\FactionPolicyService;
use App\Http\Services\TwitchService;
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

    private TwitchService $twitchService;

    public function __construct()
    {
        $this->twitchService = new TwitchService();
    }

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

        // Store preferred colors. If not set, players get yellow/red like usual.
        if (isset($request->colors) && is_array($request->colors))
        {
            $qmPlayer->colors_pref = json_encode(array_values($request->colors));
        }
        if (isset($request->colors_opponent) && is_array($request->colors_opponent))
        {
            $qmPlayer->colors_opponent_pref = json_encode(array_values($request->colors_opponent));
        }

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
        $this->handleObserver($qmPlayer, $player);

        $qmPlayer->save();
        return $qmPlayer;
    }

    /**
     * Checks if the given player should be set as an observer and validates
     * their Twitch status. If the player is flagged as an observer, this method
     * verifies they have a valid Twitch username and are currently live on Twitch.
     * 
     * If validation fails, the $qmPlayer record is deleted and a RuntimeException
     * is thrown to stop further processing.
     * 
     * @param \App\Models\QmMatchPlayer $qmPlayer The quick match player instance being created or updated.
     * @param \App\Models\Player $player The player instance associated with the user.
     * 
     * @throws \RuntimeException if Twitch username is missing or user is not live.
     * 
     * @return void
     */
    public function handleObserver($qmPlayer, $player): void
    {
        // First check if user toggled on to observe
        if (!optional($player->user->userSettings)->is_observer)
        {
            return;
        }

        if ($player->user->isAdmin())
        {
            Log::debug('Admin player bypassing Twitch live check to observe game.', [
                'player_username' => $player->username,
                'user_id' => $player->user->id,
            ]);

            $qmPlayer->is_observer = true;
            return;
        }

        // Retrieve the Twitch username from the user's profile, null-safe
        $twitchUsername = optional($player->user)->twitch_profile;

        // Validate Twitch username presence
        if (empty($twitchUsername))
        {
            Log::warning('Observer player missing Twitch username.', [
                'player_username' => $player->username,
                'user_id' => $player->user->id ?? null,
            ]);

            $qmPlayer->delete();
            throw new \RuntimeException('To observe games, you must have a valid Twitch username defined in your Account Settings.');
        }

        // Check if the Twitch user is currently live
        if (!$this->twitchService->isUserLive($twitchUsername))
        {
            Log::info('Twitch user not live for observer.', [
                'twitch_username' => $twitchUsername,
                'player_username' => $player->username,
            ]);

            $qmPlayer->delete();
            throw new \RuntimeException('To observe games, you must be live on Twitch.');
        }

        // Log that the player passed all observer checks
        Log::debug('Player is observing game.', [
            'player_username' => $player->username,
            'twitch_username' => $twitchUsername,
        ]);

        $qmPlayer->is_observer = true;
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
            ->when(isset($qmQueueEntry), fn($q) => $q->where('qm_match_player_id', '!=', $qmQueueEntry->qmPlayer->id))
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

        $matchableOpponents = collect();

        foreach ($opponents as $opponent)
        {
            if (!isset($opponent->qmPlayer)) continue;

            // If the opponent is an observer we skip him
            if ($opponent->qmPlayer?->isObserver())
            {
                continue;
            }

            // Checks players point filter settings
            if (
                $currentQmQueueEntry->qmPlayer->player->user->userSettings->disabledPointFilter
                && $opponent->qmPlayer->player->user->userSettings->disabledPointFilter
            )
            {
                // Both players have the point filter disabled, we will ignore the point filter and match them
                $matchableOpponents->add($opponent);
                continue;
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
     * Filters out opponents who have Yuri or random map sides selected if the user's settings 
     * and rank qualify for the restriction.
     *
     * @param QmQueueEntry $currentQmQueueEntry The current player's queue entry, containing ladder and user settings.
     * @param Collection $opponents A collection of opponents to evaluate and potentially filter.
     * 
     * @return Collection Filtered collection of matchable opponents.
     */
    public function removeYuriPlayers(QmQueueEntry $currentQmQueueEntry, Collection $opponents): Collection
    {
        $history = $currentQmQueueEntry->ladderHistory;
        $ladder = $history->ladder;

        // If not in Yuri mode, return opponents as is
        if ($ladder->abbreviation !== 'yr')
        {
            return $opponents;
        }

        $playerName = $currentQmQueueEntry->qmPlayer->player->username;
        $matchableOpponents = collect();
        $filteredOpponents = [];

        Log::debug("Filtering out Yuri players for $playerName");

        foreach ($opponents as $opponent)
        {
            $playerMapSides = $opponent->qmPlayer->map_side_array();

            // Validate map sides
            if (!is_array($playerMapSides))
            {
                Log::warning("Invalid map sides for opponent: " . json_encode($opponent));
                continue;
            }

            if (in_array(9, $playerMapSides) || in_array(-1, $playerMapSides))
            { // Yuri or random
                $opponentName = $opponent->qmPlayer->player->username;
                $filteredOpponents[] = $opponentName;
            }
            else
            {
                $matchableOpponents->add($opponent);
            }
        }

        // Log summary of filtered opponents
        if (!empty($filteredOpponents))
        {
            Log::debug("$playerName: Filtered out yuri opponents: " . implode(', ', $filteredOpponents));
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
        $playerName = $currentQmQueueEntry->qmPlayer?->player?->username;
        $currentPointFilter = $currentQmQueueEntry->qmPlayer->player->user->userSettings->disabledPointFilter;

        $matchableOpponents = collect();

        Log::debug("queueEntry=$currentQmQueueEntry->id, name=$playerName:, pointFilter=$currentPointFilter, Opponents in queue $opponents");

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

            // did both players diable point filter and are within 1,000 pts, and both players have at least 400 pts
            else if (
                $currentPointFilter
                && $opponent->qmPlayer->player->user->userSettings->disabledPointFilter
                && abs($currentQmQueueEntry->points - $opponent->points) < 1000
                && $currentQmQueueEntry->points > 400
                && $opponent->points > 400
            )
            {
                $matchableOpponents->add($opponent);
            }
        }

        $numMatchableOpponents = count($matchableOpponents);
        Log::debug("queueEntry=$currentQmQueueEntry->id, name=$playerName: matchableOpponents=$numMatchableOpponents");

        return $matchableOpponents;
    }

    public function getEntriesInSameTier(Ladder $ladder, QmQueueEntry $currentQmQueueEntry, Collection $opponents): Collection
    {
        if ($ladder->qmLadderRules->tier2_rating <= 0) // no tier rules in place, all can match
        {
            return $opponents;
        }

        // get current user's tier
        $currentTier = $currentQmQueueEntry->qmPlayer->player->user->getUserLadderTier($ladder)->tier;

        $matchableOpponents = collect();

        foreach ($opponents as $opponent)
        {
            $oppTier = $opponent->qmPlayer->player->user->getUserLadderTier($ladder)->tier;
            $myName = $currentQmQueueEntry->qmPlayer->player->username;
            $oppName = $opponent->qmPlayer->player->username;

            // If players are not in the same league (same tier), then we don't match them together
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
                    Log::info("QuickMatchService ** getEntriesInSameTier: Players in different tiers for ladder BUT LeaguePlayer Settings have ruled them to play  "
                        . $ladder->abbreviation . " - P1: '$myName' (Tier: $currentTier) VS P2: '$oppName' (Tier: $oppTier)");

                    $matchableOpponents->add($opponent);
                }
                else
                {
                    // Player cannot match so we skip it
                    Log::info("QuickMatchService ** getEntriesInSameTier: Players in different tiers for ladder " .  $ladder->abbreviation
                        . " - P1: '$myName' (Tier: $currentTier) VS P2:"
                        . $oppName . " (Tier: $oppTier)");
                }
            }
            else // same tier, can match
            {
                Log::debug("QuickMatchService ** getEntriesInSameTier: Players in same tier for ladder "
                    . $ladder->abbreviation . " - P1: '$myName' (Tier: $currentTier) VS P2: '$oppName' (Tier: $oppTier)");
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
                ->map(fn(PlayerGameReport $item) => $item->game->map)
                ->filter()
                ->pluck('hash')
                ->toArray();

            $maps = $maps->filter(fn(QmMap $map) => !in_array($map->map->hash, $recentMapsHash));
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

        $mapSides = array_values(array_filter($qmMap->sides_array(), fn($s) => $s >= 0));
        $ladderSides = $qmMatch->ladder->qmLadderRules->all_sides();
        $allowedForRandom = array_values(array_intersect($mapSides, $ladderSides));
        if (empty($allowedForRandom))
        {
            $allowedForRandom = $mapSides;
        }

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $allowedForRandom[mt_rand(0, count($allowedForRandom) - 1)];
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
            $myEloRating = $qmPlayer->player->user->getEffectiveUserRatingForLadder($history->ladder->id)->rating;

            foreach ($otherQMQueueEntries as $otherQMQueueEntry)
            {
                // choose the person who has the lowest elo rating to base our map pick off of
                $minEloRating = min($myEloRating, $otherQMQueueEntry->qmPlayer->player->user->getEffectiveUserRatingForLadder($history->ladder->id)->rating);

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
        $opponentPlayer = null;
        $opponentsCount = 0;

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

            // Assign colour & spawn locations for current QM player
            // Then again for other players below
            for ($i = 1; $i <= $numSpawns; $i++)
            {
                $spawnArr[] = $i;
            }

            shuffle($spawnArr); //shuffle the spawns, select 2
            $spawnOrder[0] = $spawnArr[0];
            $spawnOrder[1] = $spawnArr[1];

            Log::debug("QuickMatchService ** Random spawns selected for qmMap: '" . $qmMap->description . "', " . $spawnOrder[0] . "," . $spawnOrder[1]);
        }

        // Find the opponent player and check if both players submitted their preferred colors and make sure their is no observers.
        // Streamers might not be prepared for other colors than yellow/red. With someone observer and color info missing, we stick
        // to the old logic.
        $i = 0;
        $colorsArr = null;

        foreach ($otherQmQueueEntries as $otherQmQueueEntry)
        {
            $candidate = \App\Models\QmMatchPlayer::where("id", $otherQmQueueEntry->qmPlayer->id)->first();
            if ($candidate && !$candidate->isObserver())
            {
                $opponentPlayer = $candidate;
                $opponentsCount++;
            }
        }

        $bothHaveColorPrefs = false;
        $p1Colors = null;
        $p2Colors = null;
        if ($opponentPlayer && $opponentsCount === 1)
        {
            $p1Colors = isset($qmPlayer->colors_pref) ? json_decode($qmPlayer->colors_pref, true) : null;
            $p2Colors = isset($opponentPlayer->colors_pref) ? json_decode($opponentPlayer->colors_pref, true) : null;
            $p1OppColors = isset($qmPlayer->colors_opponent_pref) ? json_decode($qmPlayer->colors_opponent_pref, true) : null;
            $p2OppColors = isset($opponentPlayer->colors_opponent_pref) ? json_decode($opponentPlayer->colors_opponent_pref, true) : null;

            $qmPlayerIsAnonymous = $qmPlayer->player->user->userSettings->getIsAnonymous();
            $opponentPlayerIsAnonymous = $opponentPlayer->player->user->userSettings->getIsAnonymous();

            if ($qmPlayerIsAnonymous && !$opponentPlayerIsAnonymous)
            {
                // Do what opponent wants.
                $p1Colors = $p2OppColors;
                $p1OppColors = $p2Colors;
            }
            else if (!$qmPlayerIsAnonymous && $opponentPlayerIsAnonymous)
            {
                // Do what player 1 wants.
                $p2Colors = $p1OppColors;
                $p2OppColors = $p1Colors;
            }

            $bothHaveColorPrefs = is_array($p1Colors) && is_array($p2Colors) && is_array($p1OppColors) && is_array($p2OppColors);
        }

        if (!$matchHasObserver && $bothHaveColorPrefs)
        {
            // Determine best matching colors.
            [$p1Color, $p2Color] = $this->setColors($p1Colors, $p1OppColors, $p2Colors, $p2OppColors);
            $colorsArr = [$p1Color, $p2Color];
            if ($qmPlayer->isObserver() == false)
            {
                $qmPlayer->color = $colorsArr[$i];
                $qmPlayer->location = $spawnOrder[$i] - 1;
                $qmPlayer->save();
                $i++;
                Log::debug("QuickMatchService ** Assigning Spot (prefs) for " . $qmPlayer->player->username . " Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
            }
        }
        else
        {
            // No preferred colors. Used old logic.
            $colorsArr = $this->getColorsArr(8, false);
            if ($qmPlayer->isObserver() == false)
            {
                $qmPlayer->color = $colorsArr[$i];
                $qmPlayer->location = $spawnOrder[$i] - 1;
                $qmPlayer->save();
                $i++;
                Log::debug("QuickMatchService ** Assigning Spot for " . $qmPlayer->player->username . " Color: " . $qmPlayer->color .  " Location: " . $qmPlayer->location);
            }
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

            if (!$otherQmPlayer->isObserver() && $opponentPlayer === null)
            {
                $opponentPlayer = $otherQmPlayer;
                $opponentsCount++;
            }
        }

        if ($qmPlayer->actual_side == -1)
        {
            $qmPlayer->actual_side = $perMS[mt_rand(0, count($perMS) - 1)];
        }
        $qmPlayer->save();

        if ($opponentPlayer && $opponentsCount == 1)
        {
            $ladder = $qmMatch->ladder;
            $history = $ladder->currentHistory();
            $pool = $ladder->mapPool;

            // Apply FactionPolicyService for 1v1 matches. Might change actual_side for one player.
            // If there's not forced faction or forced faction ratio for the map pool, nothing will change.
            $fps = app(\App\Http\Services\FactionPolicyService::class);
            $fps->applyPolicy1v1($pool, $ladder, $history, $qmMatch->map, $qmPlayer, $opponentPlayer);
        }
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
        $qmPlayerFresh = \App\Models\QmMatchPlayer::where('id', $qmPlayer->id)->first(); // TODO why are we doing this? Whoever remembers let's put a comment

        if ($qmPlayerFresh == null)
        {
            Log::error("NULL QM_PLAYER for qmPlayer=" . $qmPlayer);
        }

        $qmPlayerFresh->qm_match_id = $qmMatch->id;  // TODO NULL POINTERS HERE SOMETIMES !!!
        $qmPlayerFresh->tunnel_id = $qmMatch->seed + $qmPlayerFresh->color;
        $qmMap = $qmMatch->map;

        $psides = explode(',', $qmPlayerFresh->mapSides->value);
        if (count($psides) > $qmMap->bit_idx)
        {
            $qmPlayerFresh->actual_side = $psides[$qmMap->bit_idx];
        }

        if ($qmPlayerFresh->actual_side < -1)
        {
            $qmPlayerFresh->actual_side = $qmPlayerFresh->chosen_side;
        }
        $qmPlayerFresh->save();

        $mapSides = array_values(array_filter($qmMap->sides_array(), function ($s)
        {
            return $s >= 0;
        }));
        $ladderSides = $ladder->qmLadderRules->all_sides();
        $perMS = array_values(array_intersect($mapSides, $ladderSides));

        if (empty($perMS))
        {
            // Fallback: if intersection of map sides and allowed ladder sides is empty, use map sides.
            Log::warning('No intersection between map sides and ladder sides');
            $perMS = $mapSides;
        }

        if ($qmPlayerFresh->isObserver() == true)
        {
            $this->setQmPlayerObserverColorLocation($qmPlayerFresh);
        }

        # These both really really really need refactoring 
        if ($ladder->clans_allowed)
        {
            $this->setClanSpawns(
                $otherQmQueueEntries,
                $ladder,
                $qmMap,
                $qmMatch,
                $qmPlayerFresh,
                $perMS,
                $qEntry
            );
        }
        else
        {
            $this->set1v1QmSpawns(
                $otherQmQueueEntries,
                $qmMatch,
                $qmPlayerFresh,
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

    public function createTeamQmMatch(LadderHistory $history, Collection $maps, Collection $teamAPlayers, Collection $teamBPlayers, Collection $observers, $gameType, array $stats = null): QmMatch
    {
        $ladder = $history->ladder;
        $currentQmQueueEntry = $teamAPlayers->first();

        $qmMapId = $this->chooseQmMapId($teamAPlayers->merge($teamBPlayers), $ladder->qmLadderRules->use_ranked_map_picker, $history, $maps);

        $matchHasObserver = $observers->count() > 0;

        $currentQueuePlayerCount = $teamAPlayers->count() + $teamBPlayers->count();
        $expectedPlayerQueueCount = $currentQueuePlayerCount + $observers->count();

        Log::debug("ApiQuickMatchController ** createQmMatch: Observer Present: " . $matchHasObserver ? 'Yes' : 'No');
        Log::debug("ApiQuickMatchController ** createQmMatch: Player counts " . $currentQueuePlayerCount . "/" . $expectedPlayerQueueCount);

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
        if (isset($stats))
        {
            $qmMatch->fill($stats);
        }
        $qmMatch->save();
        $game->qm_match_id = $qmMatch->id;
        $game->save();

        $qmMap = $qmMatch->map;

        // team1_spawn_order is a string with format of "0,0" or "1,2", etc - represents starting spawns of that team

        $spawns = new Collection;
        if ($qmMap->random_spawns) // random spawns could be LvR, TvB, or corners - random spots given for every player
        {
            Log::debug("Creating random spawns for map: " . $qmMap->description);
            // populate array with values 1 to n, n = number of players in the match
            $spawnArr = array_map(fn($num) => (string) $num, range(1, $ladder->qmLadderRules->player_count));

            // shuffle the spawns
            shuffle($spawnArr);

            // divide the spawns among both teams
            $half = count($spawnArr) / 2;
            $spawnsTeam1 = implode(",", array_slice($spawnArr, 0, $half));
            $spawnsTeam2 = implode(",", array_slice($spawnArr, $half));
            $spawns = collect([$spawnsTeam1, $spawnsTeam2]); // collection should be two strings, e.g. ["1,3", "2,4"]
            Log::debug("Random spawns for map: " . $qmMap->description . ", " . $spawns);
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

        // Set observers' team to 'observer'
        foreach ($observers->values() as $observer) {
            $qmObserver = $observer->qmPlayer;
            $qmObserver->team = 'observer';
            $qmObserver->save();
        }
        $this->setObserversSpawns($observers, $qmMatch, $colors);

        return $qmMatch;
    }

    private function setTeamSpawns(string $team, string $spawnOrders, Collection $teamPlayers, QmMatch $qmMatch, int &$colors)
    {

        Log::debug('[QuickMatchService::setTeamSpawns]');
        $spawnOrder = array_map(fn($i) => intval($i), explode(',', $spawnOrders));
        $qmMap = $qmMatch->map;


        Log::debug('[QuickMatchService::setTeamSpawns] $spawnOrder ' . json_encode($spawnOrder));

        $mapSides = array_values(array_filter($qmMap->sides_array(), fn($s) => $s >= 0));
        $ladderSides = $qmMatch->ladder->qmLadderRules->all_sides();
        $allowedForRandom = array_values(array_intersect($mapSides, $ladderSides));
        if (empty($allowedForRandom))
        {
            // Fallback: if intersection of map sides and allowed ladder sides is empty, use map sides.
            Log::warning('No intersection between map sides and ladder sides');
            $allowedForRandom = $mapSides;
        }

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
                $qmPlayer->actual_side = $allowedForRandom[mt_rand(0, count($allowedForRandom) - 1)];
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
        $maps = $maps->filter(fn($map) => $map->map_tier && $map->map_tier > 0)->values();

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
        $maps = $maps->filter(fn($map) => $map->map_tier && $map->map_tier > 0)->values();

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
     * Selects final colors for player 1 and 2 based on their preferences.
     * @param int[] $prefColorsP1 The preferred colors of player 1
     * @param int[] $prefOpponentColorsP1 What player 1 prefers for their opponent.
     * @param int[] $prefColorsP2 The preferred colors of player 2.
     * @param int[] $prefOpponentColorsP2 What player 2 prefers for their opponent.
     * @return array{int,int} [colorPlayer1, colorPlayer2]
     */
    private function setColors(array $prefColorsP1, array $prefOpponentColorsP1, array $prefColorsP2, array $prefOpponentColorsP2): array
    {
        // Penalty mapping: position in preference array -> penalty points.
        $penalties = [0, 2, 5, 10];

        // Generate all possible color combinations (0-3).
        $bestCombinations = [];
        $lowestPenalty = PHP_INT_MAX;

        for ($colorP1 = 0; $colorP1 < 4; $colorP1++)
        {
            for ($colorP2 = 0; $colorP2 < 4; $colorP2++)
            {
                // Cannot assign same color to both players.
                if ($colorP1 === $colorP2)
                {
                    continue;
                }

                // Calculate penalty for player 1's color choice.
                $posP1 = array_search($colorP1, $prefColorsP1);
                $penaltyP1Color = $penalties[$posP1];

                // Calculate penalty for player 1's opponent color preference.
                $posP1Opponent = array_search($colorP2, $prefOpponentColorsP1);
                $penaltyP1Opponent = $penalties[$posP1Opponent];

                // Calculate penalty for player 2's color choice.
                $posP2 = array_search($colorP2, $prefColorsP2);
                $penaltyP2Color = $penalties[$posP2];

                // Calculate penalty for player 2's opponent color preference.
                $posP2Opponent = array_search($colorP1, $prefOpponentColorsP2);
                $penaltyP2Opponent = $penalties[$posP2Opponent];

                // Total penalty for this combination.
                $totalPenalty = $penaltyP1Color + $penaltyP1Opponent + $penaltyP2Color + $penaltyP2Opponent;

                // Track best combinations.
                if ($totalPenalty < $lowestPenalty)
                {
                    // Replace.
                    $lowestPenalty = $totalPenalty;
                    $bestCombinations = [[$colorP1, $colorP2]];
                }
                elseif ($totalPenalty === $lowestPenalty)
                {
                    // Add.
                    $bestCombinations[] = [$colorP1, $colorP2];
                }
            }
        }

        return $bestCombinations[array_rand($bestCombinations)];
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

    /**
     * Made an array of all possible combination of match and compute the match_ranking value.
     * The lower the match_ranking is the more the match is even.
     * Team elo is the value representing how strong a team is.
     * Elo gap is a value representing the skill difference between players in the team
     * @param $currentPlayerId
     * @param $currentPlayerRank
     * @param $opponentsRating
     * @return array
     */
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

    /**
     * Find a match with the lowest match_ranking value / the most balanced match
     * @param $possibleMatches
     * @return array|null
     */
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
     * The goal of this method is to improved matching of teams.
     * If players are of a similar level, it will randomize the matchup.
     * Else if there is a big skill gap, the best possible match up will be made
     * @param $possibleMatches
     * @return array
     */
    public function findBestMatchRandomized($possibleMatches): array
    {
        $possibleMatches = collect($possibleMatches);

        // TODO : move this hard-coded value to the qmLadderRules
        $similarEloMatches = $possibleMatches->filter(fn($match) => $match['teams_elo_diff'] < 4000); // TODO hardcoding 4000 right now to ensure random teams, will re-visit this

        if ($similarEloMatches->count() > 0)
        {
            return $similarEloMatches->random();
        }
        else
        {
            return $this->findBestMatch($possibleMatches->all());
        }
    }

    /**
     * Simply get a random match
     * @param $possibleMatches
     * @return array
     */
    public function getRandomTeams($possibleMatches): array
    {
        shuffle($possibleMatches);

        return $possibleMatches[0];
    }

    /**
     * @param QmQueueEntry $currentPlayer
     * @param Collection $matchableOpponents
     * @param LadderHistory $history
     * @return QmQueueEntry[][]|Collection[]
     */
    public function getBestMatch2v2ForPlayer(QmQueueEntry $currentPlayer, Collection $matchableOpponents, LadderHistory $history): array
    {

        // Ensure matchableOpponents does not contain the current player and is unique by id
        $matchableOpponents = $matchableOpponents->filter(fn($opponent) => $opponent->id !== $currentPlayer->id)->unique('id')->values();
        $players = $matchableOpponents->concat([$currentPlayer])->unique('id')->values();

        $opponentsRating = [];
        $currentPlayerRank = $currentPlayer->qmPlayer->player->points($history);
        foreach ($matchableOpponents as $opponent)
        {
            $opponentsRating[] = [
                'id' => $opponent->id,
                'rank' => $opponent->qmPlayer->player->points($history)
            ];
        }

        // find all possible matches
        $possibleMatches = $this->findPossibleMatches(
            $currentPlayer->id,
            $currentPlayerRank,
            $opponentsRating
        );

        $matchup = $this->findBestMatchRandomized($possibleMatches);

        $g = function ($players, $match, $team)
        {
            // Only include unique players by id
            return $players
                ->filter(fn(QmQueueEntry $qmQueueEntry) => in_array($qmQueueEntry->id, [
                    $match[$team]['player1'],
                    $match[$team]['player2']
                ]))->unique('id')->values();
        };

        $teamAPlayers = $g($players, $matchup, 'teamA');
        $teamBPlayers = $g($players, $matchup, 'teamB');
        $stats = [
            'stats_teams_elo_diff' => $matchup['teams_elo_diff'],
            'stats_elo_gap_sum' => $matchup['elo_gap_sum'],
            'stats_match_ranking' => $matchup['match_ranking'],
        ];

        return [$teamAPlayers, $teamBPlayers, $stats];
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
