<?php

namespace App\Http\Services;

use App\LeaguePlayer;

class LeaguePlayerService
{

    /**
     * Updates league player if it exists
     * Creates if it does not exist
     */
    public function updateLeaguePlayer($ladderId, $userId, $canPlayBothTiers)
    {
        $leaguePlayer = LeaguePlayer::where("ladder_id", $ladderId)
            ->where("user_id", $userId)
            ->first();

        if ($leaguePlayer == null)
        {
            $leaguePlayer = new LeaguePlayer();
            $leaguePlayer->ladder_id = $ladderId;
            $leaguePlayer->user_id = $userId;
            $leaguePlayer->save();
        }

        $leaguePlayer->can_play_both_tiers = $canPlayBothTiers;
        $leaguePlayer->save();

        return $leaguePlayer;
    }

    public function deleteLeaguePlayer($ladderId, $userId)
    {
        $leaguePlayer = LeaguePlayer::where("ladder_id", $ladderId)
            ->where("user_id", $userId)
            ->first();

        if ($leaguePlayer)
        {
            return $leaguePlayer->delete();
        }
    }
}
