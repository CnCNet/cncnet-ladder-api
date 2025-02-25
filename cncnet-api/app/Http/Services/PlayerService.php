<?php

namespace App\Http\Services;

use App\Helpers\LeagueHelper;
use App\Models\Ladder;
use App\Models\Player;
use App\Models\PlayerActiveHandle;
use App\Models\PlayerRating;
use App\Models\QmMatchPlayer;
use App\Models\QmUserId;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class PlayerService
{
    /**
     * Creates Player, PlayerActiveHandle if the username is not taken.
     * Returns null if username taken.
     * @param mixed $username
     * @param mixed $user
     * @param mixed $ladderId
     * @return Player|null
     */
    public function addPlayerToUser($username, $user, $ladderId)
    {
        $username = str_replace([",", ";", "="], "-", $username); // Dissallowed by qm client

        $player = \App\Models\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladderId)
            ->first();

        $ladder = Ladder::find($ladderId);

        if ($player == null)
        {
            $player = new \App\Models\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladderId;
            $player->save();

            $activeHandle = new \App\Models\PlayerActiveHandle();
            $activeHandle->ladder_id = $ladderId;
            $activeHandle->player_id = $player->id;
            $activeHandle->user_id = $user->id;
            $activeHandle->save();

            return $player;
        }

        return null;
    }

    public function addPlayerToUserAccount($username, $user, $ladderId)
    {
        $username = str_replace([",", ";", "="], "-", $username); // Dissallowed by qm client

        $player = \App\Models\Player::where("username", "=", $username)->first();

        if ($player == null)
        {
            $player = new \App\Models\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladderId;
            $player->save();

            return $player;
        }

        return null;
    }

    public function findUserRatingByPlayerId($pid)
    {
        $player = Player::find($pid);
        $user = $player->user;
        $userRating = $user->getOrCreateLiveUserRating();

        return $userRating;
    }

    public function findPlayerByUsername($name, $ladder): ?Player
    {
        return Player::where("username", "=", $name)
            ->where("ladder_id", "=", $ladder->id)
            ->first();
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

    public function getEloKvalue($users)
    {
        // For players with less than 10 games, K will be 32, otherwise 16
        foreach ($users as $userRating)
        {
            if ($userRating->rated_games < 10)
            {
                return 32;
            }
        }
        return 16;
    }

    public function updateUserRating($playerID, $newRating)
    {
        $userRating = $this->findUserRatingByPlayerId($playerID);
        if ($newRating > $userRating->peak_rating)
        {
            $userRating->peak_rating = $newRating;
        }

        $userRating->rating = $newRating;
        $userRating->rated_games = $userRating->rated_games + 1;
        $userRating->save();
    }

    public function setActiveUsername($player, $ladder)
    {
        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        // Player checks - ensure nick is registered as an active handle
        $hasActiveHandle = PlayerActiveHandle::getPlayerActiveHandle($player->id, $ladder->id, $startOfMonth, $endOfMonth);
        if ($hasActiveHandle == null)
        {
            PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $player->user->id);
        }
    }

    /**
     * @param $player
     * @param $ip
     * @param $qmClientId
     * @return mixed|null
     * @deprecated use checkUserForBans
     */
    public function checkPlayerForBans($player, $ip, $qmClientId)
    {
        $ban = $player->user->getBan(true);
        if ($ban !== null)
        {
            return $ban;
        }

        $ban = \App\Models\IpAddress::findByIP($ip)->getBan(true);
        if ($ban !== null)
        {
            return $ban;
        }

        try
        {
            $qmUserIds = QmUserId::where("qm_user_id", $qmClientId)->get();
            foreach ($qmUserIds as $qmUserId)
            {
                $ban = $qmUserId->user->getBan(true);
                if ($ban !== null)
                {
                    return $ban;
                }
            }
        }
        catch (Exception $ex)
        {
            Log::info("Error checking player for bans: " . $ex->getMessage());
        }

        return null;
    }

    /**
     * @param $player
     * @param $ip
     * @param $qmClientId
     * @return mixed|null
     */
    public function checkUserForBans(\App\Models\User $user, $ip, $qmClientId)
    {
        $ban = $user->getBan(true);
        if ($ban !== null)
        {
            return $ban;
        }

        $ban = \App\Models\IpAddress::findByIP($ip)->getBan(true);
        if ($ban !== null)
        {
            return $ban;
        }

        try
        {
            $qmUserIds = QmUserId::where("qm_user_id", $qmClientId)->get();
            foreach ($qmUserIds as $qmUserId)
            {
                $ban = $qmUserId->user->getBan(true);
                if ($ban !== null)
                {
                    return $ban;
                }
            }
        }
        catch (Exception $ex)
        {
            Log::info("Error checking player for bans: " . $ex->getMessage());
        }

        return null;
    }

    /**
     * @param $player
     * @return bool
     * @deprecated use checkUserHasVerifiedEmail
     */
    public function checkPlayerHasVerifiedEmail($player)
    {
        if (!$player->user->email_verified)
        {
            if (!$player->user->verificationSent())
            {
                $player->user->sendNewVerification();
            }

            return false;
        }

        return true;
    }


    public function checkUserHasVerifiedEmail($user)
    {
        if (!$user->email_verified)
        {
            if (!$user->verificationSent())
            {
                $user->sendNewVerification();
            }

            return false;
        }

        return true;
    }

    public function createPlayerRatingIfNull($player)
    {
        $rating = $player->rating()->first();
        if ($rating == null)
        {
            $playerRating = new PlayerRating();
            $playerRating->player_id = $player->id;
            $playerRating->save();
        }
        return $rating;
    }

    public function getActivePlayersByUser(User $user)
    {
        $playerList = [];

        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        $activeHandles = PlayerActiveHandle::getUserActiveHandles($user->id, $startOfMonth, $endOfMonth)->get();

        foreach ($activeHandles as $activeHandle)
        {
            // IMPORTANT: Include this $player->ladder in this check to trigger it in the response
            // No idea why.
            $ladder = $activeHandle->player->ladder;
            $player = $activeHandle->player;
            $player["user"] = $player->user;

            // @TODO - Remove QM client from preventing chat.
            $player["user"]["chat_allowed"] = 1; // Stand down QM client from preventing in game chat.

            if ($activeHandle->player->ladder)
            {
                $activeHandle->player["rank"] = null;

                if ($ladder->clans_allowed)
                {
                    // Check player has clan
                    if ($player && $player->clanPlayer == null)
                    {
                        continue;
                    }

                    $clan = $player->clanPlayer->clan;
                    $player->clanPlayer;

                    if ($ladder->currentHistory())
                    {
                        $clanCache = $player->clanPlayer->clanCache($ladder->currentHistory());
                        $activeHandle->player["rank"] = $clanCache ? $clanCache->rank() : null;
                    }
                }
                else
                {
                    if ($ladder->currentHistory())
                    {
                        $playerCache = $activeHandle->player->playerCache($ladder->currentHistory()->id);
                        $activeHandle->player["rank"] = $playerCache ? $playerCache->rank() : null;
                    }
                }

                $playerList[] = $activeHandle->player;
            }
        }

        return $playerList;
    }


    public function checkPlayerShouldMatchAI(Request $request, Player $player, Ladder $ladder, QmMatchPlayer $qmPlayer)
    {

        $user = $player->user;
        $ladderRules = $ladder->qmLadderRules;
        $userPlayerTier = $player->user->getUserLadderTier($ladder)->tier;
        $version = $request->version;

        $qmQueueEntry = $qmPlayer->qEntry;

        // Test user
        if ($user->email == "neogrant3@gmail.com")
        {
            return true;
        }

        if (
            $userPlayerTier == LeagueHelper::CONTENDERS_LEAGUE
            && $qmQueueEntry !== null
            && $version >= 1.75
            && $user->userSettings->getMatchAI() == true
        )
        {
            // We're in the queue for normal player matchups
            // If we reach a certain amount of time, switch to AI matchup
            $now = Carbon::now();
            $timeSinceQueuedSeconds = $now->diffInRealSeconds($qmQueueEntry->created_at);
            Log::debug("ApiQuickMatchController ** Time Since Queued $timeSinceQueuedSeconds QM Player: $qmPlayer , QM Client Version: $version");

            // Reached max queue time without match as set by ladder rules
            return ($timeSinceQueuedSeconds > $ladderRules->getMatchAIAfterSeconds());
        }

        return false;
    }
}
