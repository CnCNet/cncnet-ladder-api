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

    public static function createNew($user, $rating, $ratedGames, $peakRating)
    {
        $userRating = new UserRating();
        $userRating->rating = $rating;
        $userRating->rated_games = $ratedGames;
        $userRating->peak_rating = $peakRating;
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
        $exists = UserRating::where("user_id", $user->id)->first();
        if ($exists)
        {
            return $exists;
        }

        # Take player usernames from user 
        $userPlayerIds = $user->usernames()->pluck("id")->toArray();

        # Find legacy Player rating for all the nicks this user owns
        # Grab highest rating with at least one rated game
        $playerRating = PlayerRating::whereIn("player_id", $userPlayerIds)
            ->where("rated_games", ">", 0)
            ->orderBy("rating", "DESC")
            ->select(["rating", "peak_rating", "rated_games"])
            ->first();

        $ratedGames = 0;
        $peakRating = 0;
        $rating = UserRating::$DEFAULT_RATING;

        if ($playerRating)
        {
            $rating = $playerRating->rating;
            $ratedGames = $playerRating->rated_games;
            $peakRating = $playerRating->peak_rating;
        }

        return UserRating::createNew($user, $rating, $ratedGames, $peakRating);
    }

    public function user()
    {
        return $this->hasOne("App\User");
    }
}
