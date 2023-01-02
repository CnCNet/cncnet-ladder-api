<?php

namespace App\Http\Services;

use App\LadderHistory;
use App\PlayerCache;
use App\PlayerHistory;
use App\UserRating;
use Carbon\Carbon;

class UserRatingService
{
    public function recalculatePlayersTiersByLadderHistory($history)
    {
        $now = Carbon::now();
        $startOfPreviousMonth = $now->copy()->subMonth(1)->startOfMonth();
        $endOfPreviousMonth = $now->copy()->subMonth(1)->endOfMonth();

        $prevMonthHistory = LadderHistory::where("starts", $startOfPreviousMonth)
            ->where("ends", $endOfPreviousMonth)
            ->where("ladder_id", $history->ladder->id)
            ->first();

        $prevMonthPlayerHistories = PlayerHistory::where("ladder_history_id", $prevMonthHistory->id)->get();

        foreach ($prevMonthPlayerHistories as $playerHistory)
        {
            $player = $playerHistory->player;
            $user = $player->user;
            $userRating = $user->userRating;

            if ($userRating == null)
            {
                $userRating = UserRating::createNewFromLegacyPlayerRating($user);
                echo "User rating was null, creating new based on playerRating" . $userRating . "\n";
            }

            $playerHistoryThisMonth = PlayerHistory::where("player_id", $player->id)
                ->where("ladder_history_id", $history->id)
                ->first();

            if ($playerHistoryThisMonth)
            {
                $tier = UserRatingService::getTierByLadderRules($userRating->rating, $history);

                $playerHistoryThisMonth->tier = $tier;
                $playerHistoryThisMonth->save();

                # Update player cache for this month, only if it exists
                $pc = PlayerCache::where("player_id", $player->id)
                    ->where("ladder_history_id", $history->id)
                    ->first();

                if ($pc)
                {
                    $pc->tier = $tier;
                    $pc->save();
                }

                echo "<b>User:</b>: " . $user->name . " <b>Player:</b>: " . $player->username .  " <b>Rating:</b>: " . $userRating->rating . " <b>Tier:</b>" . $tier . "<br/>";
            }
        }
    }

    public function resetUserRating($user, $history)
    {
        $userRating = UserRating::where("user_id", $user->id)->first();

        if ($userRating == null)
        {
            die("No user rating found");
        }

        $userRating->delete();
        $userRating = UserRating::createNew($user);

        # Update tier for this months player cache only
        $players = $user->usernames;
        foreach ($players as $player)
        {
            $playerCache = PlayerCache::where("player_id", $player->id)->where("ladder_history_id", $history->id)->first();
            if ($playerCache)
            {
                $playerCache->tier = UserRatingService::getTierByLadderRules(UserRating::$DEFAULT_RATING, $history);
                $playerCache->save();
            }
        }

        return $userRating;
    }

    /**
     * 
     * @param mixed $user 
     * @param mixed $history 
     * @return int 
     */
    public function getUserTier($user, $history)
    {
        # Get the elo rating from user_ratings table
        $userRating = UserRating::where("user_id", $user->id)->first();
        if ($userRating == null)
        {
            $userRating = UserRating::createNewFromLegacyPlayerRating($user);
        }

        return $this->getTierByLadderRules($userRating->rating, $history);
    }


    /**
     * Get user tier based on ladder rules
     * @param mixed $rating 
     * @param mixed $history 
     * @return int 
     */
    public static function getTierByLadderRules($rating, $history)
    {
        if ($rating > $history->ladder->qmLadderRules->tier2_rating)
        {
            return 1;
        }

        return 2;
    }
}
