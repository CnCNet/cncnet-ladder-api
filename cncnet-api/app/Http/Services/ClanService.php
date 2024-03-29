<?php

namespace App\Http\Services;

use App\Models\Clan;
use App\Models\ClanRating;

class ClanService
{
    public function __construct()
    {
    }

    public function awardPointsByClanRating(
        $clanGameReport,
        $enemyAverage,
        $enemyPoints,
        $enemyGames,
        $allyAverage,
        $allyPoints,
        bool $useEloPoints,
        bool $isBestReport,
        int $eloK,
        int $wolK
    )
    {
        $points = null;
        $baseRating = $enemyAverage > $allyAverage ? $enemyAverage : $allyAverage;
        $gvc = 8;

        if ($useEloPoints)
        {
            $gvc = ceil(($baseRating * $enemyAverage) / 230000);
        }

        # Someone will know what this means. Because I don't.
        $diff = $enemyPoints - $allyPoints;
        $we = 1 / (pow(10, abs($diff) / 600) + 1);
        $we = $diff > 0 && $clanGameReport->wonOrDisco() ? 1 - $we : ($diff < 0 && !$clanGameReport->wonOrDisco() ? 1 - $we : $we);
        $wol = (int)($wolK * $we);

        $eloAdjust = 0;

        if ($clanGameReport->draw)
        {
            $clanGameReport->points = 0;
        }
        else if ($clanGameReport->wonOrDisco())
        {
            $points = (new EloService(16, $allyAverage, $enemyAverage, 1, 0))->getNewRatings()["a"];
            $diff = (int)($points - $allyAverage);
            if (!$useEloPoints)
            {
                $diff = 0;
            }

            $clanGameReport->points = $gvc + $diff + $wol;

            $eloAdjust = new EloService($eloK, $allyAverage, $enemyAverage, 1, 0);

            if ($isBestReport)
            {
                $this->updateClanRating($clanGameReport->clan_id, $eloAdjust->getNewRatings()["a"]);
            }
        }
        else
        {
            if ($enemyGames < 10)
            {
                $wol = (int)($wol * ($enemyGames / 10));
            }
            if ($allyPoints  < ($wol + $gvc) * 10)
            {
                $clanGameReport->points = -1 * (int)($allyPoints / 10);
            }
            else
            {
                $clanGameReport->points = -1 * ($wol + $gvc);
            }

            $eloAdjust = new EloService($eloK, $allyAverage, $enemyAverage, 0, 1);
            if ($isBestReport)
            {
                $this->updateClanRating(
                    $clanGameReport->clan_id,
                    $eloAdjust->getNewRatings()["a"]
                );
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
