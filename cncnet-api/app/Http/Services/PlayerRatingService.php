<?php

namespace App\Http\Services;

use App\Helpers\GameHelper;
use App\Player;
use App\PlayerHistory;
use App\PlayerRating;

class PlayerRatingService
{
    public function recalculatePlayersTiers($history)
    {
        $playerHistories = PlayerHistory::where("ladder_history_id", $history->id)->get();

        foreach ($playerHistories as $ph)
        {
            $player = Player::where("id", $ph->player_id)->first();
            $ph->tier = $this->getPlayerTierFromLadderHistory($player, $history);
            $ph->save();
            echo "Updated tier " . $ph->tier . "\n";
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
            $playerTier = $this->getTierByLadderRules($playerRating->rating->rating, $history);
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
                $playerTier = $this->getTierByLadderRules($usersOtherPlayerAccounts->rating, $history);
            }
            else
            {
                # Otherwise let the function decide based on a default elo rating
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
    private function getTierByLadderRules($rating, $history)
    {
        if ($history->ladder->abbreviation == GameHelper::$GAME_BLITZ)
        {
            if ($rating > $history->ladder->qmLadderRules->tier2_rating)
            {
                return 1;
            }

            return 2;
        }

        # Default to tier 1 for other ladders
        return 1;
    }
}
