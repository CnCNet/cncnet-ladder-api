<?php namespace App\Http\Services;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class PlayerService
{
    public function __construct()
    {

    }

    public function addPlayerToUser($username, $user, $ladderId)
    {
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladderId)->first();

        if ($player == null)
        {
            $player = new \App\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladderId;
            $player->save();

            $prating = new \App\PlayerRating();
            $prating->player_id = $player['id'];
            $prating->save();
            return $player;
        }

        return null;
    }

    public function updatePlayerCard($user, $card, $playerId)
    {
        if ($card == null)
        {
            $request->session()->flash('error', 'There was a problem saving your profile card');
            return redirect("/account");
        }

        // Check the playerId belongs to us
        foreach($user->usernames as $user)
        {
            if ($user->id == $playerId)
            {
                $player = \App\Player::find($user->id);
                $player->card_id = $card->id;
                $player->save();
            }
        }

        return redirect("/account");
    }

    public function findPlayerById($id)
    {
        return \App\Player::find($id);
    }

    public function findPlayerRatingByPid($pid)
    {
        return \App\PlayerRating::where('player_id', '=', $pid)->first();
    }

    public function findPlayerByUsername($name, $ladder)
    {
        return \App\Player::where("username", "=", $name)
            ->where("ladder_id", "=", $ladder->id)->first();
    }

    public function updatePlayerStats($player, $points, $won = false)
    {
        $player->points = $points;
        $player->games_count += 1;

        if ($won)
        {
            $player->win_count += 1;
            $player->points += $points;
        }
        else
        {
            $player->loss_count = $player->loss_count > 0 ? $player->loss_count -= 1 : 0;
            $player->points = $player->points > 0 ? $player->points -= $points : 0;
        }

        $player->save();
    }

    public function getEloKvalue($players)
    {
        // For players with less than 10 games, K will be 32, otherwise 16
        foreach ($players as $playerRating)
        {
            if ($playerRating->rated_games < 10)
            {
                return 32;
            }
        }
        return 16;
    }

    public function updatePlayerRating($playerID, $newRating)
    {
        $playerRating = $this->findPlayerRatingByPid($playerID);
        if ($newRating > $playerRating->peak_rating)
        {
            $playerRating->peak_rating = $newRating;
        }

        $playerRating->rating = $newRating;
        $playerRating->rated_games = $playerRating->rated_games + 1;
        $playerRating->save();
    }
}
