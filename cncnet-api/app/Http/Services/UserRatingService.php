<?php

namespace App\Http\Services;

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
