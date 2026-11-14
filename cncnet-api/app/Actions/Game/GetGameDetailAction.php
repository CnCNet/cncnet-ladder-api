<?php

namespace App\Actions\Game;

use App\Helpers\TunnelHelper;
use App\Http\Services\ConnectionStatsService;
use App\Http\Services\GameReportService;
use App\Http\Services\LadderService;
use App\Http\Services\PointsFixDetectionService;
use App\Http\Services\Replay\ReplayStatsBuilder;
use App\Http\Services\ReplayService;
use App\Models\CountableObjectHeap;
use App\Models\Game;
use App\Models\GameChatFlag;
use App\Models\GameReplayStats;
use App\Models\GameReport;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\Player;
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
            $viewData = $this->prepareClanGameData(
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
        } else {
            $viewData = $this->prepareRegularGameData(
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

        $viewData['replayStats'] = $this->getReplayStats($game, $history, $authenticatedUser);
        $viewData['replayIntegrity'] = $this->getReplayIntegrity($game, $history, $authenticatedUser);
        $viewData['replayStaff'] = $authenticatedUser !== null && ($authenticatedUser->isModerator()
            || $authenticatedUser->isLadderAdmin($history->ladder) || $authenticatedUser->isLadderMod($history->ladder));

        // Staff only, and never read for anyone else, so the chat cannot reach a page by mistake.
        $viewData['replayChat'] = $viewData['replayStaff'] ? $this->getReplayChat($game) : null;

        return $viewData;
    }

    /**
     * What was said in the game, merged from every player's replay, with each sender's colour as the
     * game page names it.
     */
    private function getReplayChat(Game $game): ?array
    {
        $chat = (new ReplayService())->chat($game->id);
        if ($chat === null) {
            return null;
        }

        // Each flag staff have not dismissed, on the line it starts at: the line itself for a word,
        // the first line of a burst or of the repeats.
        $flags = GameChatFlag::where('game_id', $game->id)->where('status', '!=', GameChatFlag::DISMISSED)
            ->get()->groupBy(fn (GameChatFlag $f) => "{$f->house}|{$f->frame}");

        foreach ($chat['lines'] as &$line) {
            $line['color'] = ReplayStatsBuilder::COLOURS[$line['color']] ?? 'none';
            $line['flags'] = ($flags["{$line['house']}|{$line['f']}"] ?? collect())
                ->map(fn (GameChatFlag $f) => (GameChatFlag::REASONS[$f->reason] ?? $f->reason) . ': ' . $f->detail)
                ->values()->all();
        }
        unset($line);

        return $chat;
    }

    /**
     * Whether the players' replays of this game agree, by their canonical seals - which are kept
     * after the files themselves are evicted. Same audience as the replays.
     */
    private function getReplayIntegrity(Game $game, LadderHistory $history, ?Authenticatable $user): ?array
    {
        if (!$history->ladder->allowedToDownloadReplays($user)) {
            return null;
        }

        $integrity = (new ReplayService())->integrity($game->id);
        if ($integrity === null) {
            return null;
        }

        $playerIds = collect($integrity['differences'])->pluck('players')->flatten()
            ->merge($integrity['edited'])->unique()->all();
        $names = Player::whereIn('id', $playerIds)->pluck('username', 'id');
        $nameOf = fn ($id) => $names[$id] ?? "Player {$id}";
        foreach ($integrity['differences'] as &$difference) {
            $difference['names'] = array_map($nameOf, $difference['players']);
        }
        unset($difference);
        $integrity['editedNames'] = array_map($nameOf, $integrity['edited']);

        return $integrity;
    }

    /**
     * Statistics read from the game's replay, shown to whoever the ladder lets download its replays.
     */
    private function getReplayStats(Game $game, LadderHistory $history, ?Authenticatable $user): ?array
    {
        if (!$history->ladder->allowedToDownloadReplays($user)) {
            return null;
        }

        return GameReplayStats::where('game_id', $game->id)
            ->where('version', ReplayStatsBuilder::VERSION)
            ->value('stats');
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
            $qmMatchStates = $game->qmMatch->states->all();
            $qmMatchPlayers = $game->qmMatch->players->all();
        }

        // Connection stats visible to all (but limited for non-mods in view)
        if ($game->qmMatch) {
            $qmConnectionStats = $userIsMod ? $game->qmMatch->qmConnectionStats->all() : [];
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

            // Pre-compute replay (uses eager-loaded gameReplays collection). Each player has their
            // own file - the replay records that player's viewport and unit selection.
            $pgr->playerReplay = $pgr->player->gameReplays
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

        $map = $this->resolveMapWithHeaders($game);

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
            'map' => $map,
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

        $map = $this->resolveMapWithHeaders($game);

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
            'map' => $map,
            'gameAbbreviation' => $history->ladder->abbreviation,  // Use property, not method
        ];
    }

    /**
     * Resolve map with mapHeaders, handling hash collisions
     *
     * Prefers qmMap->map (proper FK chain), falls back to game->map (hash-based).
     * When hash-based map has duplicate records, finds one with mapHeaders populated.
     *
     * @param Game $game
     * @return Map|null
     */
    private function resolveMapWithHeaders(Game $game): ?Map
    {
        // Select correct map: prefer qmMap->map (proper FK), fallback to game->map (hash-based)
        $map = $game->qmMap?->map ?? $game->map;

        // Fix: hash-based map relation can match multiple maps (duplicate hashes)
        // If current map has no mapHeaders, try finding another map with same hash that has mapHeaders
        if ($map && !$map->mapHeaders) {
            $alternateMap = Map::where('hash', $map->hash)
                ->whereHas('mapHeaders')
                ->with('mapHeaders.waypoints')
                ->first();

            if ($alternateMap) {
                return $alternateMap;
            }
        }

        return $map;
    }
}
