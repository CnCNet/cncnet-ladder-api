<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    public static $DEFAULT_RATING = 1200;

    public function __construct()
    {
        $this->rating = UserRating::$DEFAULT_RATING;
        $this->peak_rating = 0;
        $this->rated_games = 0;
    }

    public static function createNew($user)
    {
        // Create default user rating
        $userRating = new UserRating();
        $userRating->user_id = $user->id;
        $userRating->save();
        return $userRating;
    }

    /**
     * Creates on demand UserRatings from old player_ratings table
     * @param mixed $user 
     * @return UserRating 
     */
    public static function createNewFromLegacyPlayerRating($user)
    {
        # Take based on highest rated rating
        $playerRating = \App\User::join("players as p", "p.user_id", "=", "users.id")
            ->where("users.id", "=", $user->id)
            ->join("player_ratings as pr", "pr.player_id", "=", "p.id")
            ->orderBy("pr.rating", "DESC")
            ->first();

        if ($playerRating !== null)
        {
            $userRating = new UserRating();
            $userRating->rating = $playerRating->rating;
            $userRating->rated_games = $playerRating->rated_games;
            $userRating->peak_rating = $playerRating->peak_rating;
            $userRating->user_id = $user->id;
            $userRating->save();

            return $userRating;
        }

        return UserRating::createNew($user);
    }

    public function user()
    {
        return $this->hasOne("App\User");
    }
}
