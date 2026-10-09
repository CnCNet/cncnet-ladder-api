<?php

namespace App\Extensions\Qm\Matchup;

use App\Helpers\MatchmakingFactionHelper;
use App\Http\Services\CasualMatchmakingService;
use App\Http\Services\CasualTunnelService;
use App\Models\Game;
use App\Models\QmMatch;
use App\Models\QmQueueEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Matches players on casual ladders. Casual matches ignore ratings and tiers: the players who
 * waited longest are matched, split into two random teams on team ladders, and get random
 * Allied and Soviet countries so that both sides are balanced.
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
            ->sortBy('created_at')
            ->values();

        if ($opponents->count() < $playerCount - 1)
        {
            Log::info("CasualMatchup ** {$playerName} waiting on {$ladder->abbreviation}: {$opponents->count()}/" . ($playerCount - 1) . " opponents");
            return;
        }

        // Players are only connected through a tunnel, so that they never receive each other's addresses.
        // Without a tunnel no match is made and the players keep waiting.
        $tunnelService = app(CasualTunnelService::class);
        $tunnel = null;
        if ($tunnelService->isEnabled())
        {
            $tunnel = $tunnelService->allocate($playerCount);
            if ($tunnel === null)
            {
                Log::warning("CasualMatchup ** No tunnel available, {$playerName} keeps waiting on {$ladder->abbreviation}");
                return;
            }
        }

        $opponents = $opponents->take($playerCount - 1);
        $maps = $ladder->mapPool->maps;

        $qmMatch = $playerCount == 2
            ? $this->createMatch($maps, $opponents)
            : $this->createCasualTeamMatch($maps, $opponents);

        $this->assignCountries($qmMatch, $ladder->game);

        if ($tunnel !== null)
        {
            $tunnelService->assignTunnel($qmMatch, $tunnel);
        }

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
     * Assigns random countries so that Allied and Soviet players are balanced.
     * Allied and Soviet countries only exist in Red Alert 2 and Yuri's Revenge.
     */
    private function assignCountries(QmMatch $qmMatch, ?string $game): void
    {
        if (!in_array(strtolower($game ?? ''), ['ra2', 'yr']))
            return;

        $players = $qmMatch->players()->get()->filter(fn($player) => !$player->isObserver())->values();

        if ($players->count() === 2)
        {
            MatchmakingFactionHelper::assign1v1($players[0], $players[1]);
            return;
        }

        $teamA = $players->where('team', 'A')->values()->all();
        $teamB = $players->where('team', 'B')->values()->all();

        switch (count($teamA))
        {
            case 2:
                MatchmakingFactionHelper::assign2v2($teamA, $teamB);
                break;
            case 3:
                MatchmakingFactionHelper::assign3v3($teamA, $teamB);
                break;
            case 4:
                MatchmakingFactionHelper::assign4v4($teamA, $teamB);
                break;
        }
    }
}
