<?php

namespace App\Http\Services;

use App\LadderHistory;
use App\PlayerCache;
use App\PlayerHistory;
use App\User;
use Carbon\Carbon;

class UserRatingService
{
    /**
     * Get user tier based on ladder rules
     * @param mixed $rating 
     * @param mixed $history 
     * @return int 
     */
    public static function getTierByLadderRules($rating, $ladder)
    {
        if ($rating > $ladder->qmLadderRules->tier2_rating)
        {
            return 1;
        }

        return 2;
    }
}
