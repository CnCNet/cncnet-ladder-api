<?php

namespace App\Actions\Player;

use App\Http\Services\AchievementService;
use App\Http\Services\ChartService;
use App\Http\Services\GameTransformer;
use App\Http\Services\LadderService;
use App\Http\Services\StatsService;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\PlayerActiveHandle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPlayerDetailAction
{
    // Constants for magic numbers
    private const GAMES_PER_PAGE = 24;
    private const RECENT_ACHIEVEMENTS_LIMIT = 3;

    public function __construct(
        private readonly LadderService $ladderService,
        private readonly StatsService $statsService,
        private readonly ChartService $chartService,
        private readonly AchievementService $achievementService,
        private readonly GameTransformer $gameTransformer,
    ) {}

    /**
     * Execute the action and return view data
     */
    public function execute(
        string $date,
        string $cncnetGame,
        string $username,
        ?Authenticatable $authenticatedUser
    ): array {
        $history = $this->getHistoryOrFail($date, $cncnetGame);
        $player = $this->getPlayerOrFail($history, $username);
        $playerUser = $player->user;

        // Get games with eager loading
        $games = $this->getPlayerGames($player, $history);

        // Check permissions
        $userIsMod = $authenticatedUser?->isLadderMod($player->ladder) ?? false;

        // Get bans and alerts if authorized
        [$bans, $alerts] = $this->getBansAndAlerts($player, $playerUser, $authenticatedUser, $userIsMod);

        // Get player statistics
        $stats = $this->getPlayerStats($player, $history, $playerUser);

        // Get ladder player data
        $ladderPlayerData = $this->ladderService->getLadderPlayer($history, $player->username);

        // Convert to object for view compatibility (replaces json_encode/decode anti-pattern)
        $ladderPlayer = (object) $ladderPlayerData;

        // Get anonymous player nicknames
        $ladderNicks = $this->getLadderNicknames($playerUser, $player, $history, $stats['isAnonymous']);

        return [
            'ladderNicks' => $ladderNicks,
            'mod' => $authenticatedUser,
            'isAnonymous' => $stats['isAnonymous'],
            'history' => $history,
            'ladderPlayer' => $ladderPlayer,
            'player' => $ladderPlayerData['player'],
            'games' => $games,
            'userIsMod' => $userIsMod,
            'playerUser' => $playerUser,
            'ladderId' => $player->ladder->id,
            'alerts' => $alerts,
            'bans' => $bans,
            'userTier' => $stats['userTier'],
            'graphGamesPlayedByMonth' => $stats['graphGamesPlayedByMonth'],
            'playerFactionsByMonth' => $stats['playerFactionsByMonth'],
            'playerGamesLast24Hours' => $stats['playerGamesLast24Hours'],
            'playerWinLossByMaps' => $stats['playerWinLossByMaps'],
            'playerOfTheDayAward' => $stats['playerOfTheDayAward'],
            'userPlayer' => $playerUser,
            'teamMatchups' => $stats['teamMatchups'],
            'playerMatchups' => $stats['playerMatchups'],
            'achievements' => $stats['achievements'],
            'achievementsCount' => $stats['achievementsCount'],
        ];
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
     * Get player or fail with 404
     */
    private function getPlayerOrFail(LadderHistory $history, string $username): Player
    {
        $player = Player::where('ladder_id', $history->ladder->id)
            ->where('username', $username)
            ->with([
                'user.userSettings',
                'user.usernames',
                'ladder',
                'alerts'
            ])
            ->first();

        if ($player === null) {
            abort(404, "No player found");
        }

        return $player;
    }

    /**
     * Get player games with eager loading and transformation
     */
    private function getPlayerGames(Player $player, LadderHistory $history): LengthAwarePaginator
    {
        $games = $player->playerGames()
            ->where('ladder_history_id', $history->id)
            ->with([
                'gameReport.game.map',
                'gameReport.playerGameReports.player.user',
                'gameReport.playerGameReports.stats',
                'player.user',
                'stats'
            ])
            ->orderBy('created_at', 'DESC')
            ->paginate(self::GAMES_PER_PAGE);

        return $this->gameTransformer->transformGamesForDisplay($games, $history, $player);
    }

    /**
     * Get bans and alerts if user is authorized
     */
    private function getBansAndAlerts(
        Player $player,
        User $playerUser,
        ?Authenticatable $authenticatedUser,
        bool $userIsMod
    ): array {
        $bans = [];
        $alerts = [];

        if ($authenticatedUser && ($playerUser->id === $authenticatedUser->id || $userIsMod)) {
            $alerts = $player->alerts;
            $ban = $playerUser->getBan();

            if ($ban) {
                $bans[] = $ban;
            }
        }

        return [$bans, $alerts];
    }

    /**
     * Get all player statistics
     */
    private function getPlayerStats(Player $player, LadderHistory $history, User $playerUser): array
    {
        $userTier = $playerUser->getUserLadderTier($history->ladder)->tier;

        return [
            'userTier' => $userTier,
            'graphGamesPlayedByMonth' => $this->chartService->getPlayerGamesPlayedByMonth($player, $history),
            'playerFactionsByMonth' => $this->statsService->getFactionsPlayedByPlayer($player, $history),
            'playerWinLossByMaps' => $this->statsService->getMapWinLossByPlayer($player, $history),
            'playerGamesLast24Hours' => $player->totalGames24Hours($history),
            'playerMatchups' => $this->statsService->getPlayerMatchups($player, $history),
            'teamMatchups' => $this->getTeamMatchups($player, $history),
            'playerOfTheDayAward' => $this->statsService->checkPlayerIsPlayerOfTheDay($history, $player),
            'achievements' => $this->achievementService->getRecentlyUnlockedAchievements(
                $history,
                $playerUser,
                self::RECENT_ACHIEVEMENTS_LIMIT
            ),
            'achievementsCount' => $this->achievementService->getProgressCountsByUser($history, $playerUser),
            'isAnonymous' => $player->user->userSettings->getIsAnonymousForLadderHistory($history),
        ];
    }

    /**
     * Get team matchups for 2v2 ladders only
     */
    private function getTeamMatchups(Player $player, LadderHistory $history): array
    {
        if ($history->ladder->ladder_type !== Ladder::TWO_VS_TWO) {
            return [];
        }

        return $this->statsService->getTeamMatchups($player, $history);
    }

    /**
     * Get ladder nicknames excluding current player and active handle
     */
    private function getLadderNicknames(
        User $playerUser,
        Player $player,
        LadderHistory $history,
        bool $isAnonymous
    ): array {
        $now = Carbon::now();
        $dateStart = $now->copy()->startOfMonth()->toDateTimeString();
        $dateEnd = $now->copy()->endOfMonth()->toDateTimeString();

        $activeHandle = PlayerActiveHandle::getPlayerActiveHandle(
            $player->id,
            $history->ladder->id,
            $dateStart,
            $dateEnd
        );

        $ladderNicks = $playerUser->usernames
            ->where('id', '!=', $player->id)
            ->where('ladder_id', $history->ladder->id);

        // If anonymous and there's an active handle, also exclude it
        if ($isAnonymous && $activeHandle) {
            $ladderNicks = $ladderNicks->where('id', '!=', $activeHandle->player_id);
        }

        return $ladderNicks->pluck('username')->toArray();
    }
}
