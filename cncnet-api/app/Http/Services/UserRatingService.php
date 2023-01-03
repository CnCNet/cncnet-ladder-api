<?php

namespace App\Http\Services;

use App\LadderHistory;
use App\Player;
use App\PlayerCache;
use App\PlayerHistory;
use App\User;
use App\UserRating;
use Carbon\Carbon;

class UserRatingService
{
    public function recalculatePlayersTiersByLadderHistory($history)
    {
        # Update based on last months
        $lastMonth = Carbon::now();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();

        $historyLastMonth = LadderHistory::where("ladder_id", $history->ladder->id)
            ->where("starts", $lastMonthStart)
            ->where("ends", $lastMonthEnd)
            ->first();

        $playersLastMonth = PlayerHistory::where("ladder_history_id", $historyLastMonth->id)
            ->join("players as p", "p.id", "=", "player_histories.player_id")
            ->select("p.*")
            ->get();

        $this->updateUserRatingsByPlayer($playersLastMonth, $history);

        $excludeLastMonthPlayers = $playersLastMonth->pluck("id");

        # Update players this months excluding last month
        $playersThisMonth = PlayerHistory::where("ladder_history_id", $history->id)
            ->join("players as p", "p.id", "=", "player_histories.player_id")
            ->whereNotIn("p.id", $excludeLastMonthPlayers)
            ->select("p.*")
            ->get();

        $this->updateUserRatingsByPlayer($playersThisMonth, $history);
    }

    private function updateUserRatingsByPlayer($players, $history)
    {
        foreach ($players as $player)
        {
            $user = User::find($player->user_id);
            $userRating = $user->getUserRating($history->ladder);
            $userTier = $user->getUserTier($history);

            $playerHistoryThisMonth = PlayerHistory::where("ladder_history_id", $history->id)
                ->where("player_id", $player->id)
                ->first();

            if ($playerHistoryThisMonth)
            {
                $playerHistoryThisMonth->tier = $userTier;
                $playerHistoryThisMonth->save();
            }

            # Update player cache for this month, only if it exists
            $pc = PlayerCache::where("player_id", $player->id)
                ->where("ladder_history_id", $history->id)
                ->first();

            if ($pc)
            {
                $pc->tier = $userTier;
                $pc->save();
            }

            echo "Player :" . $player->username . " Tier: " . $userTier . " -- Rating: " . $userRating->rating . "<br/>";
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
