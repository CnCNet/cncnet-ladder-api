<?php

namespace App\Http\Services;

use App\Models\GameReport;
use App\Models\LadderHistory;
use Illuminate\Support\Collection;

class PointsFixDetectionService
{
    /**
     * Detect if points need fixing for a game
     *
     * Returns an array with flags indicating what type of fix is needed
     *
     * @param Collection $playerGameReports
     * @return array ['showBothPositiveFix' => bool, 'showBothZeroFix' => bool]
     */
    public function detectFixNeeded(Collection $playerGameReports): array
    {
        $showBothPositiveFix = false;
        $showBothZeroFix = false;

        // Only check for 1v1 games (exactly 2 player reports)
        if ($playerGameReports->count() === 2) {
            $p1 = $playerGameReports[0];
            $p2 = $playerGameReports[1];

            $hasOneWinner = $p1->won != $p2->won;
            $bothPositive = $p1->points > 0 && $p2->points > 0;
            $bothZero = $p1->points == 0 && $p2->points == 0;

            $showBothPositiveFix = $hasOneWinner && $bothPositive;
            $showBothZeroFix = $hasOneWinner && $bothZero;
        }

        return [
            'showBothPositiveFix' => $showBothPositiveFix,
            'showBothZeroFix' => $showBothZeroFix,
        ];
    }

    /**
     * Get points fix preview for moderators
     *
     * This calculates what the correct points should be for both players
     * based on the ladder's point calculation rules.
     *
     * @param GameReport $gameReport
     * @param LadderHistory $history
     * @return array Array of player point previews
     */
    public function getFixPreview(GameReport $gameReport, LadderHistory $history): array
    {
        $playerGameReports = $gameReport->playerGameReports;

        if ($playerGameReports->count() !== 2) {
            return [];
        }

        $winner = $playerGameReports->firstWhere(fn($pgr) => $pgr->wonOrDisco());
        $loser = $playerGameReports->firstWhere(fn($pgr) => !$pgr->wonOrDisco());

        if (!$winner || !$loser) {
            return [];
        }

        $rules = $history->ladder->qmLadderRules;
        $pointService = new PointService();
        $playerService = new PlayerService();

        $winnerCache = $winner->player->playerCache($history->id);
        $winnerCurrentPts = $winnerCache ? $winnerCache->points : 0;
        $loserCache = $loser->player->playerCache($history->id);
        $loserCurrentPts = $loserCache ? $loserCache->points : 0;

        $winnerRating = $playerService->findUserRatingByPlayerId($winner->player_id);
        $loserRating = $playerService->findUserRatingByPlayerId($loser->player_id);

        $winnerPoints = $pointService->calculatePoints([
            'draw'               => false,
            'hasWinner'          => true,
            'myTeamWon'          => true,
            'allyPts'            => $winner->player->pointsBefore($history, $winner->game_id),
            'enemyPts'           => $loser->player->pointsBefore($history, $loser->game_id),
            'allyElo'            => $winnerRating->rating,
            'enemyElo'           => $loserRating->rating,
            'allyCount'          => 1,
            'enemyCount'         => 1,
            'allyDeviationSum'   => $winnerRating->deviation,
            'enemyDeviationSum'  => $loserRating->deviation,
            'enemyGamesSum'      => $loser->player->totalGames($history),
            'currentPoints'      => $winnerCurrentPts,
            'wol_k'              => $rules->wol_k,
            'upset_k'            => $rules->upset_k,
            'upset_k_loser_multiplier' => $rules->upset_k_loser_multiplier,
            'fixed_points'       => $rules->fixed_points,
            'no_negative_points' => $rules->no_negative_points,
        ]);

        $loserPoints = $pointService->calculatePoints([
            'draw'               => false,
            'hasWinner'          => true,
            'myTeamWon'          => false,
            'allyPts'            => $loser->player->pointsBefore($history, $loser->game_id),
            'enemyPts'           => $winner->player->pointsBefore($history, $winner->game_id),
            'allyElo'            => $loserRating->rating,
            'enemyElo'           => $winnerRating->rating,
            'allyCount'          => 1,
            'enemyCount'         => 1,
            'allyDeviationSum'   => $loserRating->deviation,
            'enemyDeviationSum'  => $winnerRating->deviation,
            'enemyGamesSum'      => $winner->player->totalGames($history),
            'currentPoints'      => $loserCurrentPts,
            'wol_k'              => $rules->wol_k,
            'upset_k'            => $rules->upset_k,
            'upset_k_loser_multiplier' => $rules->upset_k_loser_multiplier,
            'fixed_points'       => $rules->fixed_points,
            'no_negative_points' => $rules->no_negative_points,
        ]);

        if ($loserPoints > 0)
        {
            $loserPoints = 0;
        }

        return [
            [
                'player_id' => $winner->player->id,
                'player' => $winner->player->username,
                'calculated_points' => $winnerPoints,
                'won' => true,
            ],
            [
                'player_id' => $loser->player->id,
                'player' => $loser->player->username,
                'calculated_points' => $loserPoints,
                'won' => false,
            ]
        ];
    }
}
