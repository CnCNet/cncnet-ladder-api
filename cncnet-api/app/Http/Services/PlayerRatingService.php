<?php

namespace App\Http\Services;

use App\Player;
use App\PlayerHistory;
use App\PlayerRating;

class PlayerRatingService
{
    /**
     * Calculate player tiers (once a month per player username)
     * 
     * @param mixed $player 
     * @param mixed $history 
     * @return mixed \App\PlayerHistory
     */
    public function calculatePlayerTier($player, $history)
    {
        # Check we've not already assigned a tier first (PlayerHistory would be null for a new month), 
        # We only want to assign a tier once per month so players don't jump between leagues

        $playerHistory = PlayerHistory::where("player_id", $player->id)
            ->where("ladder_history_id", $history->id)
            ->first();

        if ($playerHistory == null)
        {
            $playerHistory = new PlayerHistory();
            $playerHistory->ladder_history_id = $history->id;
            $playerHistory->player_id = $player->id;
            $playerHistory->tier = 2;
            $playerHistory->save();

            # Assign a tier from player_ratings table
            $playerRating = PlayerRating::where("player_id", $player->id)->first();
            if ($playerRating)
            {
                $playerTier = $this->getTierByLadderRules($playerRating->rating, $history);
            }
            else
            {
                # We've no record of any player_ratings for this username
                # Check other users player accounts for this game type and use that highest instead
                $user = $player->user;
                $anyOtherPlayerRating = Player::where("user_id", $user->id)
                    ->where("ladder_id", $history->ladder->id)
                    ->join("player_ratings as pr", "pr.player_id", "=", "players.id")
                    ->orderBy("pr.rating", "DESC")
                    ->first();

                # Otherwise default to tier 2
                if ($anyOtherPlayerRating == null)
                {
                    $playerTier = 2;
                }

                $playerTier = $this->getTierByLadderRules($anyOtherPlayerRating->rating, $history);
            }

            $playerHistory->tier = $playerTier;
            $playerHistory->save();
        }

        return $playerHistory;
    }

    private function getTierByLadderRules($rating, $history)
    {
        # For now we only want Blitz to have tier leagues
        if ($history->abbreviation == "blitz")
        {
            # Default to tier 2 for new players signing up
            if ($rating == PlayerRating::$DEFAULT_RATING)
            {
                return 2;
            }

            # If rating is beyond tier2 rating min requirements, assign to tier 1 league
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
