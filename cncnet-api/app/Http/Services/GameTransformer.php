<?php

namespace App\Http\Services;

use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\Ladder;
use Illuminate\Pagination\LengthAwarePaginator;

class GameTransformer
{
    /**
     * Transform a paginated games collection to include pre-computed display data
     *
     * @param LengthAwarePaginator $games
     * @param LadderHistory $history
     * @param Player $player
     * @return LengthAwarePaginator
     */
    public function transformGamesForDisplay(
        LengthAwarePaginator $games,
        LadderHistory $history,
        Player $player
    ): LengthAwarePaginator {
        $games->getCollection()->transform(function ($game) use ($history, $player) {
            // Pre-compute game URLs
            $game->gameUrl = \App\Models\URLHelper::getGameUrl($history, $game->game_id);

            // Pre-compute map preview URL safely
            $this->addMapPreviewUrl($game, $history);

            // Pre-compute player data with URLs and avatars
            if ($game->gameReport && $game->gameReport->playerGameReports) {
                $this->transformPlayerGameReports($game, $history);

                // Pre-group players by team for 2v2 games
                if ($history->ladder->ladder_type === Ladder::TWO_VS_TWO) {
                    $this->groupPlayersForTeamGame($game);
                } else {
                    $this->identifyPlayerAndOpponent($game, $player);
                }
            }

            return $game;
        });

        return $games;
    }

    /**
     * Add map preview URL to game
     */
    private function addMapPreviewUrl($game, LadderHistory $history): void
    {
        $map = $game->gameReport?->game?->map;
        $hash = $game->gameReport?->game?->hash;

        $game->mapPreviewUrl = $map && $hash
            ? \App\Helpers\SiteHelper::getMapPreviewUrl($history, $map, $hash)
            : '';
    }

    /**
     * Transform player game reports to include URLs and avatars
     */
    private function transformPlayerGameReports($game, LadderHistory $history): void
    {
        $game->gameReport->playerGameReports->transform(function ($pgr) use ($history) {
            $pgr->profileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $pgr->player->username);
            $pgr->avatarUrl = $pgr->player->user->getUserAvatar();
            return $pgr;
        });
    }

    /**
     * Group players by team for 2v2 games
     */
    private function groupPlayersForTeamGame($game): void
    {
        $game->groupedPlayerGameReports = $game->gameReport->playerGameReports
            ->filter(fn($pgr) => $pgr->team !== null)
            ->groupBy('team');
    }

    /**
     * Identify player and opponent for 1v1 games
     */
    private function identifyPlayerAndOpponent($game, Player $player): void
    {
        $game->playerGameReport = $game->gameReport->playerGameReports
            ->firstWhere('player_id', $player->id);

        $game->opponentPlayerReport = $game->gameReport->playerGameReports
            ->where('player_id', '!=', $player->id)
            ->first();
    }
}
