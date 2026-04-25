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

        // Build comprehensive eager loading to eliminate N+1 queries
        // These relationships are accessed in the views
        $with = [
            // Game's own relationships
            'map.mapHeaders.waypoints', // For player spawn positions on map preview

            // Default report and all its nested relationships
            'report.playerGameReports.player.user.userSettings',
            'report.playerGameReports.player.clanPlayer.clan',
            'report.playerGameReports.player.gameClips', // For game clips display
            'report.playerGameReports.stats.gameObjectCounts.countableGameObject', // CRITICAL: Eliminates N+1 for cameo stats
            'report.playerGameReports.clan',
            'report.playerGameReports.gameReport', // Needed for clan logic in views
            'report.game.qmMatch.map', // Needed for map preview

            // QM Match data (available to all users for connection stats)
            'qmMatch.qmConnectionStats',
            'qmMatch.map',
        ];

        // Add moderator-specific eager loading
        if ($includeModData) {
            $with = array_merge($with, [
                // All reports with nested relationships
                'allReports.playerGameReports.player.user.userSettings',
                'allReports.playerGameReports.player.clanPlayer.clan',
                'allReports.playerGameReports.player.gameClips',
                'allReports.playerGameReports.stats.gameObjectCounts.countableGameObject',
                'allReports.playerGameReports.clan',
                'allReports.playerGameReports.gameReport',

                // Mod-only QM match data
                'qmMatch.states',
                'qmMatch.players.player.user',
            ]);
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
