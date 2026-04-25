<?php

namespace App\Actions\Game;

use App\Helpers\TunnelHelper;
use App\Http\Services\ConnectionStatsService;
use App\Http\Services\GameReportService;
use App\Http\Services\LadderService;
use App\Http\Services\PointsFixDetectionService;
use App\Models\CountableObjectHeap;
use App\Models\Game;
use App\Models\GameReport;
use App\Models\Ladder;
use App\Models\LadderHistory;
use Illuminate\Contracts\Auth\Authenticatable;

class GetGameDetailAction
{
    public function __construct(
        private readonly LadderService $ladderService,
        private readonly GameReportService $gameReportService,
        private readonly ConnectionStatsService $connectionStatsService,
        private readonly PointsFixDetectionService $pointsFixService,
    ) {}

    /**
     * Execute the action and return view data for game detail page
     *
     * @param string $date Ladder history date
     * @param string $cncnetGame Game abbreviation
     * @param int $gameId Game ID
     * @param int|null $reportId Specific report ID (optional)
     * @param Authenticatable|null $authenticatedUser Currently authenticated user
     * @return array View data
     */
    public function execute(
        string $date,
        string $cncnetGame,
        int $gameId,
        ?int $reportId,
        ?Authenticatable $authenticatedUser
    ): array {
        // Get ladder history
        $history = $this->getHistoryOrFail($date, $cncnetGame);

        // Check if user is a moderator
        $userIsMod = $authenticatedUser?->isLadderMod($history->ladder) ?? false;

        // Get game with comprehensive eager loading
        $game = $this->getGameOrFail($history, $gameId, $userIsMod);

        // Get appropriate reports based on mod status
        $allGameReports = $this->gameReportService->getReportsForDisplay($game, $userIsMod);

        // Get specific or default game report
        $gameReport = $this->getGameReport($game, $reportId, $userIsMod);

        // Get player game reports
        $playerGameReports = $gameReport->playerGameReports ?? collect();

        // Calculate pings for all players
        $this->connectionStatsService->attachPingsToPlayerReports(
            $playerGameReports,
            $game->qmMatch
        );

        // Pre-compute player data to avoid queries in view
        $this->attachPlayerCacheData($playerGameReports, $history);

        // Pre-compute point reports for clan games
        $this->attachPointReports($playerGameReports, $gameReport, $history);

        // Detect if points fix is needed
        $fixDetection = $this->pointsFixService->detectFixNeeded($playerGameReports);
        $showBothPositiveFix = $fixDetection['showBothPositiveFix'];
        $showBothZeroFix = $fixDetection['showBothZeroFix'];

        // Get fix preview if needed and user is mod
        $fixedPointsPreview = [];
        if ($showBothZeroFix && $userIsMod) {
            $fixedPointsPreview = $this->pointsFixService->getFixPreview($gameReport, $history);
        }

        // Get QM match data for mods
        [$qmMatchStates, $qmConnectionStats, $qmMatchPlayers] = $this->getQmMatchData($game, $userIsMod);

        // Get all countable object heaps (for stats display)
        $heaps = CountableObjectHeap::all();

        // Handle clan vs regular ladder display
        if ($history->ladder->clans_allowed) {
            return $this->prepareClanGameData(
                $game,
                $gameReport,
                $allGameReports,
                $playerGameReports,
                $history,
                $heaps,
                $authenticatedUser,
                $userIsMod,
                $cncnetGame,
                $qmMatchStates,
                $qmConnectionStats,
                $qmMatchPlayers,
                $date,
                $showBothPositiveFix,
                $showBothZeroFix,
                $fixedPointsPreview
            );
        }

        return $this->prepareRegularGameData(
            $game,
            $gameReport,
            $allGameReports,
            $playerGameReports,
            $history,
            $heaps,
            $authenticatedUser,
            $userIsMod,
            $cncnetGame,
            $qmMatchStates,
            $qmConnectionStats,
            $qmMatchPlayers,
            $date,
            $showBothPositiveFix,
            $showBothZeroFix,
            $fixedPointsPreview
        );
    }

    /**
     * Get ladder history or fail with 404
     */
    private function getHistoryOrFail(string $date, string $cncnetGame): LadderHistory
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        if ($history === null) {
            abort(404, "Ladder not found");
        }

        return $history;
    }

    /**
     * Get game or fail with 404
     */
    private function getGameOrFail(LadderHistory $history, int $gameId, bool $userIsMod): Game
    {
        $game = $this->gameReportService->getGameWithReports($history, $gameId, $userIsMod);

        if ($game === null) {
            abort(404, "Game not found");
        }

        return $game;
    }

    /**
     * Get specific game report or default report
     */
    private function getGameReport(Game $game, ?int $reportId, bool $userIsMod): GameReport
    {
        if ($reportId !== null) {
            $gameReport = $this->gameReportService->getGameReportById($game, $reportId);
        } else {
            $gameReport = $this->gameReportService->getDefaultGameReport($game);
        }

        if ($gameReport === null) {
            abort(404, "Game Report not found");
        }

        return $gameReport;
    }

    /**
     * Get QM match data for display
     *
     * @return array [qmMatchStates, qmConnectionStats, qmMatchPlayers]
     */
    private function getQmMatchData(Game $game, bool $userIsMod): array
    {
        $qmMatchStates = [];
        $qmConnectionStats = [];
        $qmMatchPlayers = [];

        if ($userIsMod && $game->qmMatch) {
            $qmMatchStates = $game->qmMatch->states;
            $qmMatchPlayers = $game->qmMatch->players;
        }

        // Connection stats visible to all (but limited for non-mods in view)
        if ($game->qmMatch) {
            $qmConnectionStats = $userIsMod ? $game->qmMatch->qmConnectionStats : [];
        }

        return [$qmMatchStates, $qmConnectionStats, $qmMatchPlayers];
    }

    /**
     * Attach player cache data to player game reports
     * Pre-computes rank, points, tier, and game clips to avoid queries in views
     */
    private function attachPlayerCacheData($playerGameReports, LadderHistory $history): void
    {
        foreach ($playerGameReports as $pgr) {
            // Get player cache once per player
            $playerCache = $pgr->player->playerCache($history->id);

            // Attach pre-computed data to avoid repeated queries in views
            $pgr->playerCache = $playerCache;
            $pgr->playerRank = $playerCache ? $playerCache->rank() : 0;
            $pgr->playerPoints = $playerCache ? $playerCache->points : 0;
            $pgr->playerTier = $playerCache ? $pgr->player->getCachedPlayerTierByLadderHistory($history) : 1;

            // Pre-compute game clip (uses eager-loaded gameClips collection)
            $pgr->playerGameClip = $pgr->player->gameClips
                ->where('game_id', $pgr->game_id)
                ->first();

            // Pre-compute faction for display (fixes redundant Stats2 query in views)
            if ($pgr->stats) {
                $pgr->playerFaction = $pgr->stats->faction($history->ladder, $pgr->stats->cty);
            }
        }
    }

    /**
     * Attach point reports for clan games
     * Pre-computes the winning/losing clan report to avoid method calls in views
     */
    private function attachPointReports($playerGameReports, GameReport $gameReport, LadderHistory $history): void
    {
        if (!$history->ladder->clans_allowed) {
            return;
        }

        foreach ($playerGameReports as $pgr) {
            $pgr->pointReport = $gameReport->getPointReportByClan($pgr->clan_id);
        }
    }

    /**
     * Prepare data for regular (non-clan) game view
     */
    private function prepareRegularGameData(
        Game $game,
        GameReport $gameReport,
        $allGameReports,
        $playerGameReports,
        LadderHistory $history,
        $heaps,
        ?Authenticatable $user,
        bool $userIsMod,
        string $cncnetGame,
        array $qmMatchStates,
        array $qmConnectionStats,
        array $qmMatchPlayers,
        string $date,
        bool $showBothPositiveFix,
        bool $showBothZeroFix,
        array $fixedPointsPreview
    ): array {
        // Group players by team for 2v2 games
        $groupedPlayerGameReports = [];
        if ($history->ladder->ladder_type === Ladder::TWO_VS_TWO) {
            foreach ($playerGameReports as $playerGameReport) {
                $team = $playerGameReport->team;

                if ($team !== null) {
                    $groupedPlayerGameReports[$team][] = $playerGameReport;
                }
            }
        }

        return [
            'game' => $game,
            'gameReport' => $gameReport,
            'allGameReports' => $allGameReports,
            'playerGameReports' => $playerGameReports,
            'groupedByTeamPlayerGameReports' => $groupedPlayerGameReports,
            'history' => $history,
            'heaps' => $heaps,
            'user' => $user,
            'userIsMod' => $userIsMod,
            'cncnetGame' => $cncnetGame,
            'qmMatchStates' => $qmMatchStates,
            'qmConnectionStats' => $qmConnectionStats,
            'qmMatchPlayers' => $qmMatchPlayers,
            'date' => $date,
            'showBothPositiveFix' => $showBothPositiveFix,
            'showBothZeroFix' => $showBothZeroFix,
            'fixedPointsPreview' => $fixedPointsPreview,
            // Pre-computed data to avoid queries in views
            'map' => $game->map,  // Already eager loaded
            'gameAbbreviation' => $history->ladder->abbreviation,  // Use property, not method
        ];
    }

    /**
     * Prepare data for clan game view
     */
    private function prepareClanGameData(
        Game $game,
        GameReport $gameReport,
        $allGameReports,
        $playerGameReports,
        LadderHistory $history,
        $heaps,
        ?Authenticatable $user,
        bool $userIsMod,
        string $cncnetGame,
        array $qmMatchStates,
        array $qmConnectionStats,
        array $qmMatchPlayers,
        string $date,
        bool $showBothPositiveFix,
        bool $showBothZeroFix,
        array $fixedPointsPreview
    ): array {
        // Group players by clan and maintain order
        $clans = [];
        foreach ($playerGameReports as $pgr) {
            $clans[$pgr->clan_id][] = $pgr;
        }

        $orderedClanReports = [];
        foreach ($clans as $clanId => $pgrArr) {
            foreach ($pgrArr as $pgr) {
                $orderedClanReports[] = $pgr;
            }
        }

        // Get clan-specific game reports (grouped by clan_id)
        $clanGameReports = $gameReport->playerGameReports()->groupBy("clan_id")->get();

        // Get tunnels from connection stats
        $tunnels = TunnelHelper::getTunnelsFromStats($qmConnectionStats);

        return [
            'game' => $game,
            'gameReport' => $gameReport,
            'allGameReports' => $allGameReports,
            'clanGameReports' => $clanGameReports,
            'orderedClanReports' => $orderedClanReports,
            'playerGameReports' => $playerGameReports,
            'history' => $history,
            'heaps' => $heaps,
            'user' => $user,
            'userIsMod' => $userIsMod,
            'cncnetGame' => $cncnetGame,
            'qmMatchStates' => $qmMatchStates,
            'qmConnectionStats' => $qmConnectionStats,
            'qmMatchPlayers' => $qmMatchPlayers,
            'tunnels' => $tunnels,
            'date' => $date,
            'showBothPositiveFix' => $showBothPositiveFix,
            'showBothZeroFix' => $showBothZeroFix,
            'fixedPointsPreview' => $fixedPointsPreview,
            // Pre-computed data to avoid queries in views
            'map' => $game->map,  // Already eager loaded
            'gameAbbreviation' => $history->ladder->abbreviation,  // Use property, not method
        ];
    }
}
