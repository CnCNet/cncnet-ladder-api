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

        $winnerPointsBefore = $winner->player->pointsBefore($history, $winner->game_id);
        $loserPointsBefore = $loser->player->pointsBefore($history, $loser->game_id);
        $diff = $loserPointsBefore - $winnerPointsBefore;

        // ELO calculation
        $we = 1 / (pow(10, abs($diff) / 600) + 1);
        if ($diff > 0) {
            $we = 1 - $we;
        }

        $wol_k = $history->ladder->qmLadderRules->wol_k;
        $wol = (int)($wol_k * $we);
        $gvc = 8;

        $winnerPoints = $gvc + $wol;

        // Calculate loser points
        if ($winnerPointsBefore < 10 * ($gvc + $wol)) {
            $loserPoints = -1 * (int)($loserPointsBefore / 10);
        } else {
            $loserPoints = -1 * ($gvc + $wol);
        }

        // Prevent negative total points
        $loserCache = $loser->player->playerCache($history->id);
        if ($loserPoints < 0 && (!$loserCache || $loserCache->points < 0)) {
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
