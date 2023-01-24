<?php

namespace App\Commands;

use App\Commands\Command;
use App\Http\Services\QuickMatchService;
use App\LeaguePlayer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\QmQueueEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class FindOpponent extends Command implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public $qEntryId = null;
    public $gameType = null;
    private $quickMatchService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($id, $gameType)
    {
        $this->qEntryId = $id;
        $this->gameType = $gameType;
        $this->quickMatchService = new QuickMatchService();
    }

    public function queue($queue, $arguments)
    {
        $queue->pushOn('findmatch', $arguments);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->delete();
        $qEntry = QmQueueEntry::find($this->qEntryId);

        if ($qEntry === null)
        {
            Log::info("FindOpponent ** qEntry is null.");
            return;
        }

        $qEntry->touch();

        $qmPlayer = $qEntry->qmPlayer;

        # A player could cancel out of queue before this function runs
        if ($qmPlayer === null)
        {
            Log::info("FindOpponent ** qmPlayer is null.");
            $qEntry->delete();
            return;
        }

        # Skip if the player has already been matched up
        if ($qmPlayer->qm_match_id !== null)
        {
            Log::info("FindOpponent ** qmPlayer->qm_match_id is not null.");
            $qEntry->delete();
            return;
        }

        $history = $qEntry->ladderHistory;

        if ($history === null)
        {
            Log::info("FindOpponent ** history is null.");
            $qEntry->delete();
            return;
        }

        $ladder = $history->ladder;

        if ($ladder === null)
        {
            Log::info("FindOpponent ** ladder is null.");
            $qEntry->delete();
            return;
        }

        $player = $qmPlayer->player;

        if ($player === null)
        {
            Log::info("FindOpponent ** player is null.");
            $qEntry->delete();
            return;
        }

        # map_bitfield is an old and unused bit of code
        $qmPlayer->map_bitfield = 0xffffffff;
        $qmPlayer->save();

        $ladder = $qmPlayer->ladder;
        $ladder_rules = $ladder->qmLadderRules;

        /* 
        Try to find a matchup
         * Matchups are based on the player's rating,
         * The absolute value of the difference of me and every other player is calculated.
         * Any players whose difference is greater 100 is thrown out with some exceptions
         * If a player has been waiting a long time for a matchup he should get some special
         * treatment.  To allow for this, the player rating difference gets wait time, in
         * seconds, subtracted from it.
         * If 2 players rated 1200, and 1400 are the only players a match won't be made
         * until one player has been waiting for 100 seconds 1400-1200-100seconds = 100
         * The ratio of seconds is tunable per ladder
         */

        $user = $qmPlayer->player->user;
        $userPlayerTier = $player->getCachedPlayerTierByLadderHistory($history);
        $userSettings = $user->userSettings;

        # Fetch all opponents who are currently in queue for this ladder
        $opponentEntries = QmQueueEntry::where('qm_match_player_id', '<>', $qEntry->qmPlayer->id)
            ->where('ladder_history_id', '=', $history->id)
            ->get();

        # Collection of qm opponents who are within point filter but also includes opponents who have mutual point filter disabled
        $opponentEntriesFiltered = (new QmQueueEntry())->newCollection();

        foreach ($opponentEntries as $opponentEntry)
        {
            $oppPlayer = $opponentEntry->qmPlayer->player;
            $oppUserPlayerTier = $oppPlayer->getCachedPlayerTierByLadderHistory($history);
            $oppUserSettings = $oppPlayer->user->userSettings;
            $oppUser = $oppPlayer->user;

            # Checks players are in same league tier otherwise skip
            if ($oppUserPlayerTier !== $userPlayerTier)
            {
                # At this point we've now deemed they can't match based on current tiers/elo
                # But now check if players we've specifically chosen in the admin panel can still match in this tier
                $canMatch = false;

                # Check both as either player could be tier 1
                if ($oppUserPlayerTier == 1)
                {
                    $canMatch = LeaguePlayer::playerCanPlayBothTiers($oppUser, $ladder);
                }

                if ($canMatch == false && $userPlayerTier == 1)
                {
                    $canMatch = LeaguePlayer::playerCanPlayBothTiers($user, $ladder);
                }

                if ($canMatch == false)
                {
                    Log::info("FindOpponent ** Players in different tiers for ladder " . $history->ladder->abbreviation . "- P1:" . $oppPlayer->username . " (Tier: " . $oppUserPlayerTier . ") VS  P2:" . $player->username . " (Tier: " . $userPlayerTier . ")");
                    continue;
                }
                else
                {
                    Log::info("FindOpponent ** Players in different tiers for ladder BUT LeaguePlayer Settings have ruled them to play  " . $history->ladder->abbreviation . "- P1:" . $oppPlayer->username . " (Tier: " . $oppUserPlayerTier . ") VS  P2:" . $player->username . " (Tier: " . $userPlayerTier . ")");
                }
            }

            $ptFilterOff = false;

            # Checks players point filter settings
            if ($userSettings->disabledPointFilter && $oppUserSettings->disabledPointFilter)
            {
                $playerRank = $player->rank($history);

                $oppPlayerRank = $oppPlayer->rank($history);

                # Do both players' rank meet the minimum rank required for no pt filter to apply
                $rankDiff = abs($playerRank - $oppPlayerRank);
                if ($rankDiff <= $ladder_rules->point_filter_rank_threshold)
                {
                    Log::info("FindOpponent ** Players meet the min pt filter rank p1: " . $playerRank . ", p2: " . $oppPlayerRank);
                    $ptFilterOff = true;
                }
                else
                {
                    Log::info("FindOpponent ** Players do not meet the min pt filter rank. p1: " . $playerRank . ", p2: " . $oppPlayerRank);
                }
            }

            if ($ptFilterOff)
            {
                # Both players have the point filter disabled, we will ignore the point filter
                $opponentEntriesFiltered->add($opponentEntry);
            }
            else
            {
                # (updated_at - created_at) / 60 = seconds duration player has been waiting in queue
                $points_time = ((strtotime($qEntry->updated_at) - strtotime($qEntry->created_at))) * $ladder_rules->points_per_second;

                # is the opponent within the point filter
                if ($points_time + $ladder_rules->max_points_difference > ABS($qEntry->points - $opponentEntry->points))
                {
                    $opponentEntriesFiltered->add($opponentEntry);
                }
            }
        }

        $qmOpns = $opponentEntriesFiltered->shuffle();

        if ($qmOpns->count() >= $ladder_rules->player_count - 1)
        {
            // Randomly choose the opponents from the best matches. To prevent
            // long runs of identical matchups.
            $qmOpns = $qmOpns->shuffle()->take($ladder_rules->player_count - 1);

            // Randomly select a map
            $commonQMMaps = array();
            $qmMaps = $ladder->mapPool->maps;

            foreach ($qmMaps as $qmMap)
            {
                $match = true;
                if (
                    array_key_exists(
                        $qmMap->bit_idx,
                        $qmPlayer->map_side_array()
                    )
                    && $qmPlayer->map_side_array()[$qmMap->bit_idx] > -2
                    &&
                    in_array(
                        $qmPlayer->map_side_array()[$qmMap->bit_idx],
                        $qmMap->sides_array()
                    )
                )
                {
                    foreach ($qmOpns as $qOpn)
                    {
                        $opn = $qOpn->qmPlayer;

                        if ($opn === null)
                        {
                            $qOpn->delete();
                            $qEntry->delete();
                            return;
                        }

                        if (
                            array_key_exists($qmMap->bit_idx, $opn->map_side_array())
                            &&
                            ($opn->map_side_array()[$qmMap->bit_idx] < -1 || !in_array(
                                $opn->map_side_array()[$qmMap->bit_idx],
                                $qmMap->sides_array()
                            ))
                        )
                        {
                            $match = false;
                        }
                    }
                }
                else
                {
                    $match = false;
                }

                if ($match)
                {
                    $commonQMMaps[] = $qmMap;
                }
            }

            $qEntry->delete();

            $reduceMapRepeats = $ladder->qmLadderRules->reduce_map_repeats;

            if ($reduceMapRepeats > 0) //remove the recent maps from common_qm_maps
            {
                $playerGameReports = $player->playerGames()
                    ->where("ladder_history_id", "=", $history->id)
                    ->where("disconnected", "=", 0)
                    ->where("no_completion", "=", 0)
                    ->where("draw", "=", 0)
                    ->orderBy('created_at', 'DESC')
                    ->limit($reduceMapRepeats)
                    ->get();

                $recentMaps = $playerGameReports->map(function ($item)
                {
                    return $item->game->map;
                });

                $recentMaps = $recentMaps->filter(function ($value)
                {
                    return !is_null($value);
                });

                foreach ($recentMaps as $recentMap)
                {
                    $commonQMMaps = $this->removeMap($recentMap, $commonQMMaps);
                }

                foreach ($qmOpns as $qOpn)
                {
                    $oppPlayer = $qOpn->qmPlayer->player;
                    $oppPlayerGames = $oppPlayer->playerGames()
                        ->where("ladder_history_id", "=", $history->id)
                        ->where("disconnected", "=", 0)
                        ->where("no_completion", "=", 0)
                        ->where("draw", "=", 0)
                        ->orderBy('created_at', 'DESC')
                        ->limit($reduceMapRepeats)
                        ->get();

                    $recentMaps = $oppPlayerGames->map(function ($item)
                    {
                        return $item->game->map;
                    });

                    $recentMaps = $recentMaps->filter(function ($value)
                    {
                        return !is_null($value);
                    });

                    foreach ($recentMaps as $recentMap) //remove the recent maps from common_qm_maps
                    {
                        $commonQMMaps = $this->removeMap($recentMap, $commonQMMaps);
                    }
                }
            }

            if (count($commonQMMaps) < 1)
            {
                Log::info("FindOpponent ** No common maps available");

                $qmPlayer->touch();
                return;
            }

            $this->quickMatchService->createQmMatch(
                $qmPlayer,
                $userPlayerTier,
                $commonQMMaps,
                $qmOpns,
                $qEntry,
                $this->gameType
            );
        }
    }

    /**
     * Remove this 'Map' from this array of 'QmMaps'.
     * The function will loop through the array of common_qm_maps and check if equal to the $recentmMap
     */
    private function removeMap($recentMap, $commonQmMaps)
    {
        $newCommonQmMaps = [];

        foreach ($commonQmMaps as $common_qm_map)
        {
            if ($common_qm_map->map->id != $recentMap->id)
            {
                $newCommonQmMaps[] = $common_qm_map;
            }
        }

        return $newCommonQmMaps;
    }
}
