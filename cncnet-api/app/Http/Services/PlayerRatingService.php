<?php

namespace App\Http\Services;

use App\Helpers\GameHelper;
use App\LadderHistory;
use App\Player;
use App\PlayerHistory;
use App\PlayerRating;
use Carbon\Carbon;

class PlayerRatingService
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

        $prevMonthPlayerHistories = PlayerHistory::where("ladder_history_id", $prevMonthHistory->id)
            ->get();

        $playerIdRatingsCompleted = [];

        # Check for last months players and apply to this months ratings first
        foreach ($prevMonthPlayerHistories as $ph)
        {
            $players = Player::where("id", $ph->player_id)
                ->where("ladder_id", $prevMonthHistory->ladder->id)
                ->get();

            foreach ($players as $player)
            {
                $playerRating = PlayerRating::where("player_id", $player->id)->first();

                $prevTier = $ph->tier;
                $ph->tier = $this->getPlayerTierFromLadderHistory($player, $prevMonthHistory);
                $ph->save();

                echo "<strong>Player:</strong> " . $player->username . " <strong>Rating:</strong> " . $playerRating->rating . " <strong>Previous Tier:</strong>" . $prevTier . " <strong>New Tier:</strong> " . $ph->tier . "<br/>";

                $playerHistoryThisMonth = PlayerHistory::where("player_id", $player->id)->where("ladder_history_id", $history->id)->first();
                if ($playerHistoryThisMonth)
                {
                    $playerHistoryThisMonth->tier = $ph->tier;
                    $playerHistoryThisMonth->save();

                    echo "Applying to this months ladder: " . "$player->username" . " Now Tier: " . $playerHistoryThisMonth->tier . "<br/>";
                }

                $playerIdRatingsCompleted[] = $player->id;
            }
        }

        $currentMonthPlayerHistories = PlayerHistory::where("ladder_history_id", $history->id)->get();

        # Now we check players this month calculate ratings excluding the ones we've done above
        foreach ($currentMonthPlayerHistories as $ph)
        {
            if (in_array($ph->player_id, $playerIdRatingsCompleted))
            {
                echo "Skipping " . $ph->player->username . " as we've already applied their Tier from previous month above <br/>";
                continue;
            }

            $players = Player::where("id", $ph->player_id)
                ->where("ladder_id", $history->ladder->id)
                ->get();

            foreach ($players as $player)
            {
                $playerRating = PlayerRating::where("player_id", $player->id)->first();

                $prevTier = $ph->tier;
                $ph->tier = $this->getPlayerTierFromLadderHistory($player, $history);
                $ph->save();

                echo "Player: " . $player->username . " Rating: " . $playerRating->rating . " Previous Tier:" . $prevTier . " New Tier: " . $ph->tier . "<br/>";
            }
        }
    }



    /**
     * 
     * @param mixed $player 
     * @param mixed $history 
     * @return int 
     */
    public function getPlayerTierFromLadderHistory($player, $history)
    {
        $playerTier = 2;

        # Get the elo rating from player_ratings table
        $playerRating = PlayerRating::where("player_id", $player->id)->first();
        if ($playerRating)
        {
            $playerTier = $this->getTierByLadderRules($playerRating->rating, $history);
        }
        else
        {
            # We've no record of any player_ratings for this username
            # Check the user's other player usernamesa for this game type and use that highest instead
            $user = $player->user;
            $usersOtherPlayerAccounts = Player::where("user_id", $user->id)
                ->where("ladder_id", $history->ladder->id)
                ->join("player_ratings as pr", "pr.player_id", "=", "players.id")
                ->orderBy("pr.rating", "DESC")
                ->first();

            if ($usersOtherPlayerAccounts)
            {
                echo "Player Rating Not Found for this ladder, but found other usernames from other ladders: $usersOtherPlayerAccounts->username" . "Rating: $usersOtherPlayerAccounts->rating";

                $playerTier = $this->getTierByLadderRules($usersOtherPlayerAccounts->rating, $history);
            }
            else
            {
                # Otherwise let the function decide based on a default elo rating
                echo "Player Rating Not Found for $player->username";

                $playerTier = $this->getTierByLadderRules(PlayerRating::$DEFAULT_RATING, $history);
            }
        }
        return $playerTier;
    }

    /**
     * @param mixed $rating 
     * @param mixed $history 
     * @return int 
     */
    public static function getTierByLadderRules($rating, $history)
    {
        // echo "<strong>Tier 2 Ladder Rule Rating:</strong> " . $history->ladder->qmLadderRules->tier2_rating . " - ";
        if ($rating > $history->ladder->qmLadderRules->tier2_rating)
        {
            return 1;
        }

        return 2;
    }
}
