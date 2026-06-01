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
    }

    public function findClanRatingById($clanId)
    {
        $clan = Clan::find($clanId);
        $clanRating = $clan->getOrCreateLiveClanRating();
        return $clanRating;
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
