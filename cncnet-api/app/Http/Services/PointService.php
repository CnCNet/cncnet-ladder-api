<?php

namespace App\Http\Services;

class PointService
{
    public function computeWOL(int $allyPts, int $enemyPts, bool $myTeamWon, int $wolK): int
    {
        $diff = $enemyPts - $allyPts;
        $we = 1.0 / (pow(10.0, abs($diff) / 600.0) + 1.0);
        if (($diff > 0 && $myTeamWon) || ($diff < 0 && !$myTeamWon))
        {
            $we = 1.0 - $we;
        }
        $wol = (int) floor($wolK * $we);
        return $myTeamWon ? $wol : -$wol;
    }

    public function computeUpset(float $winEloSum, float $loseEloSum, int $upsetK): int
    {
        $pWin = 1.0 / (1.0 + pow(10.0, ($loseEloSum - $winEloSum) / 400.0));
        return (int) floor((1.0 - $pWin) * $upsetK);
    }

    public function calculatePoints(array $params): int
    {
        $draw             = $params['draw'] ?? false;
        $hasWinner        = $params['hasWinner'] ?? true;
        $myTeamWon        = $params['myTeamWon'] ?? false;
        $allyPts          = $params['allyPts'] ?? 0;
        $enemyPts         = $params['enemyPts'] ?? 0;
        $allyElo          = $params['allyElo'] ?? 0.0;
        $enemyElo         = $params['enemyElo'] ?? 0.0;
        $allyCount        = $params['allyCount'] ?? 0;
        $enemyCount       = $params['enemyCount'] ?? 0;
        $allyDeviationSum = $params['allyDeviationSum'] ?? 0.0;
        $enemyDeviationSum = $params['enemyDeviationSum'] ?? 0.0;
        $enemyGamesSum    = $params['enemyGamesSum'] ?? 0;
        $currentPoints    = $params['currentPoints'] ?? 0;

        $wolK                   = (int)   $params['wol_k'];
        $upsetK                 = (int)   $params['upset_k'];
        $upsetKLoserMultiplier  = (float) $params['upset_k_loser_multiplier'];
        $fixedPoints            = (int)   $params['fixed_points'];
        $noNegativePoints       = (bool)  $params['no_negative_points'];

        if ($draw || !$hasWinner)
        {
            return 0;
        }

        $wol = $this->computeWOL($allyPts, $enemyPts, $myTeamWon, $wolK);

        if (!$myTeamWon && $enemyGamesSum < 10)
        {
            $wol = (int) floor($wol * ($enemyGamesSum / 10));
        }

        $fixed = $myTeamWon ? $fixedPoints : -$fixedPoints;

        $upset = 0;
        if ($allyCount > 0 && $enemyCount > 0 && $allyCount == $enemyCount)
        {
            $avgDevForWeight = max(
                $allyDeviationSum / $allyCount,
                $enemyDeviationSum / $enemyCount
            );
            $devWeight = $avgDevForWeight <= 100 ? 1.0
                : ($avgDevForWeight >= 200 ? 0.0
                : 1.0 - (($avgDevForWeight - 100.0) / 100.0));

            if ($myTeamWon)
            {
                $upset = $devWeight * $this->computeUpset($allyElo, $enemyElo, $upsetK);
            }
            else
            {
                $winUpset = $this->computeUpset($enemyElo, $allyElo, $upsetK);
                $upset = -((int) floor($winUpset * $upsetKLoserMultiplier) * $devWeight);
            }
        }

        $total = $fixed + $wol + $upset;

        if ($noNegativePoints)
        {
            if ($currentPoints + $total < 0)
            {
                $total = -$currentPoints;
            }
        }

        return (int) $total;
    }
}
