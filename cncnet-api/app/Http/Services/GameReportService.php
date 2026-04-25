<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\GameReport;
use App\Models\LadderHistory;

class GameReportService
{
    /**
     * Get game with comprehensive eager loading to prevent N+1 queries
     *
     * @param LadderHistory $history
     * @param int $gameId
     * @param bool $includeModData Whether to include moderator-only data
     * @return Game|null
     */
    public function getGameWithReports(
        LadderHistory $history,
        int $gameId,
        bool $includeModData = false
    ): ?Game {
        $query = Game::where("id", "=", $gameId)
            ->where('ladder_history_id', $history->id);

        // Build eager loading based on what data is needed
        $with = [
            'report.playerGameReports.player.user',
            'report.playerGameReports.stats',
            'map',
        ];

        // Add moderator-specific eager loading
        if ($includeModData) {
            $with = array_merge($with, [
                'allReports.playerGameReports.player.user',
                'allReports.playerGameReports.stats',
                'qmMatch.states',
                'qmMatch.players.player.user',
                'qmMatch.qmConnectionStats',
            ]);
        } else {
            // Non-mods still need basic qmMatch for connection stats display
            $with[] = 'qmMatch.qmConnectionStats';
        }

        return $query->with($with)->first();
    }

    /**
     * Get specific game report by ID
     *
     * @param Game $game
     * @param int $reportId
     * @return GameReport|null
     */
    public function getGameReportById(Game $game, int $reportId): ?GameReport
    {
        return $game->allReports->where('id', $reportId)->first();
    }

    /**
     * Get default game report (the best/official report)
     *
     * @param Game $game
     * @return GameReport|null
     */
    public function getDefaultGameReport(Game $game): ?GameReport
    {
        return $game->report;
    }

    /**
     * Get all reports or just the default based on mod status
     *
     * @param Game $game
     * @param bool $userIsMod
     * @return mixed
     */
    public function getReportsForDisplay(Game $game, bool $userIsMod)
    {
        return $userIsMod ? $game->allReports : $game->report;
    }
}
