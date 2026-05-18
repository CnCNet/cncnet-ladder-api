<?php

namespace App\Http\Services;

use App\Models\Clan;
use App\Models\ClanRating;

class ClanService
{
    private $pointService;

    public function __construct()
    {
        $this->pointService = new PointService();
    }

    public function awardPointsByClanRating(
        $clanGameReport,
        $enemyAverage,
        $enemyPoints,
        $enemyGames,
        $allyAverage,
        $allyPoints,
        bool $isBestReport,
        int $eloK,
        array $pointRules,
        float $allyDeviationSum = 350.0,
        float $enemyDeviationSum = 350.0,
        int $allyCount = 1,
        int $enemyCount = 1,
        int $currentPoints = 0
    )
    {
        $isDraw = $clanGameReport->draw;
        $myTeamWon = $clanGameReport->wonOrDisco();

        $clanGameReport->points = $this->pointService->calculatePoints([
            'draw'               => $isDraw,
            'hasWinner'          => true,
            'myTeamWon'          => $myTeamWon,
            'allyPts'            => $allyPoints,
            'enemyPts'           => $enemyPoints,
            'allyElo'            => $allyAverage,
            'enemyElo'           => $enemyAverage,
            'allyCount'          => $allyCount,
            'enemyCount'         => $enemyCount,
            'allyDeviationSum'   => $allyDeviationSum,
            'enemyDeviationSum'  => $enemyDeviationSum,
            'enemyGamesSum'      => $enemyGames,
            'currentPoints'      => $currentPoints,
            'wol_k'              => $pointRules['wol_k'],
            'upset_k'            => $pointRules['upset_k'],
            'upset_k_loser_multiplier' => $pointRules['upset_k_loser_multiplier'],
            'fixed_points'       => $pointRules['fixed_points'],
            'no_negative_points' => $pointRules['no_negative_points'],
        ]);

        if (!$myTeamWon && !$isDraw && $clanGameReport->points > 0)
        {
            $clanGameReport->points = 0;
        }

        if (!$isDraw)
        {
            $eloAdjust = new EloService($eloK, $allyAverage, $enemyAverage, $myTeamWon ? 1 : 0, $myTeamWon ? 0 : 1);

            if ($isBestReport)
            {
                $this->updateClanRating($clanGameReport->clan_id, $eloAdjust->getNewRatings()["a"]);
            }
        }
    }

    public function findClanRatingById($clanId)
    {
        $clan = Clan::find($clanId);
        $clanRating = $clan->getOrCreateLiveClanRating();
        return $clanRating;
    }

    public function getEloKvalue($clanRatings)
    {
        // For Clans with less than 10 games, K will be 32, otherwise 16
        foreach ($clanRatings as $clanRating)
        {
            if ($clanRating->rated_games < 10)
            {
                return 32;
            }
        }
        return 16;
    }

    public function updateClanRating($clanId, $newRating)
    {
        $clanRating = $this->findClanRatingById($clanId);
        if ($newRating > $clanRating->peak_rating)
        {
            $clanRating->peak_rating = $newRating;
        }

        $clanRating->rating = $newRating;
        $clanRating->rated_games = $clanRating->rated_games + 1;
        $clanRating->save();
    }

    public function createClanRatingIfNull($clan)
    {
        $rating = $clan->rating()->first();
        if ($rating == null)
        {
            $clanRating = new ClanRating();
            $clanRating->clan_id = $clan->id;
            $clanRating->save();
        }
        return $rating;
    }
}
