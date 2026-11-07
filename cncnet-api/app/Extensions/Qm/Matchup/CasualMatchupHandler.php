<?php

namespace App\Extensions\Qm\Matchup;

use App\Helpers\MatchmakingFactionHelper;
use App\Http\Services\CasualMatchmakingService;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\QmMap;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Matches players on casual ladders. Casual matches ignore ratings and tiers: the players who
 * waited longest are matched, split into two random teams on team ladders, and get random
 * countries so that both factions of the game, such as Allied and Soviet, are played equally.
 */
class CasualMatchupHandler extends BaseMatchupHandler
{
    public function matchup(): void
    {
        $ladder = $this->history->ladder;
        $playerCount = $ladder->qmLadderRules->player_count;
        $playerName = $this->qmPlayer->player->username;

        $opponents = $this->quickMatchService->fetchQmQueueEntry($this->history, $this->qmQueueEntry)
            ->filter(fn(QmQueueEntry $entry) => $entry->updated_at >= Carbon::now()->subSeconds(CasualMatchmakingService::STALE_ENTRY_SECONDS))
            ->filter(fn(QmQueueEntry $entry) => $entry->qmPlayer !== null && !$entry->qmPlayer->isObserver())
            // A player who was just matched may still have a queue entry from a request that was sent at the same moment
            ->filter(fn(QmQueueEntry $entry) => $entry->qmPlayer->qm_match_id === null)
            ->sortBy('created_at')
            ->values();

        if ($opponents->count() < $playerCount - 1)
        {
            Log::info("CasualMatchup ** {$playerName} waiting on {$ladder->abbreviation}: {$opponents->count()}/" . ($playerCount - 1) . " opponents");
            return;
        }

        $opponents = $opponents->take($playerCount - 1);
        $maps = $ladder->mapPool->maps;

        $qmMatch = $playerCount == 2
            ? $this->createMatch($maps, $opponents)
            : $this->createCasualTeamMatch($maps, $opponents);

        $this->assignCountries($qmMatch, $ladder);

        Log::info("CasualMatchup ** Created casual match {$qmMatch->id} on {$ladder->abbreviation}");
    }

    /**
     * Splits the players into two random teams of equal size.
     */
    private function createCasualTeamMatch(Collection $maps, Collection $opponents): QmMatch
    {
        $players = $opponents->concat([$this->qmQueueEntry])->shuffle()->values();
        $teamSize = intdiv($players->count(), 2);

        return $this->quickMatchService->createTeamQmMatch(
            $this->history,
            $maps,
            $players->take($teamSize)->values(),
            $players->skip($teamSize)->values(),
            collect(),
            Game::GAME_TYPE_2VS2
        );
    }

    /**
     * Assigns random countries so that both factions of the ladder's game are played equally,
     * see MatchmakingFactionHelper. Matches on ladders without factions keep the sides they were created with.
     */
    private function assignCountries(QmMatch $qmMatch, Ladder $ladder): void
    {
        $factions = $this->getAllowedFactions($qmMatch->map, $ladder);
        if ($factions === null)
            return;

        // 1v1 players have no team, so each of them is a team of their own
        $teams = $qmMatch->players()->get()
            ->reject(fn(QmMatchPlayer $player) => $player->isObserver())
            ->groupBy(fn(QmMatchPlayer $player) => $player->team ?: "player {$player->id}")
            ->values();

        $countries = MatchmakingFactionHelper::pickCountries($teams->map(fn(Collection $team) => $team->count())->all(), $factions);

        foreach ($teams as $teamIndex => $team)
        {
            foreach ($team->values() as $playerIndex => $player)
            {
                $player->actual_side = $countries[$teamIndex][$playerIndex];
                $player->save();
            }
        }

        Log::info("CasualMatchup ** Countries of match {$qmMatch->id} by team: " . json_encode($countries));
    }

    /**
     * The countries of each faction of the ladder's game that are allowed on the map. Like random sides in ranked
     * quick match, these are the map's sides that are also sides of the ladder, or all of the map's sides.
     * Returns null if the game has no factions or a faction has no allowed country.
     */
    private function getAllowedFactions(QmMap $qmMap, Ladder $ladder): ?array
    {
        $factions = config('qm.casual_factions')[$ladder->game] ?? null;
        if ($factions === null)
            return null;

        $mapSides = array_map('intval', array_filter($qmMap->sides_array(), fn($side) => $side >= 0));
        $allowedSides = array_intersect($mapSides, $ladder->qmLadderRules->all_sides());
        if (empty($allowedSides))
        {
            $allowedSides = $mapSides;
        }

        $allowedFactions = array_map(fn(array $countries) => array_values(array_intersect($countries, $allowedSides)), $factions);

        if (in_array([], $allowedFactions, true))
        {
            Log::warning("CasualMatchup ** A faction has no country that is allowed on {$qmMap->description}, countries are not balanced");
            return null;
        }

        return $allowedFactions;
    }
}
