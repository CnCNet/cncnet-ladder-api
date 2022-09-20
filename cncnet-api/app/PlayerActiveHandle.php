<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PlayerActiveHandle extends Model
{
    protected $table = 'player_active_handles';

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public static function setPlayerActiveHandle($ladderId, $playerId, $userId)
    {
        $activeHandle = new PlayerActiveHandle();
        $activeHandle->ladder_id = $ladderId;
        $activeHandle->player_id = $playerId;
        $activeHandle->user_id = $userId;
        $activeHandle->save();

        return $activeHandle;
    }

    public static function getPlayerActiveHandles($playerId, $ladderId)
    {
        $activeHandles = PlayerActiveHandle::where("player_id", $playerId)
            ->where("ladder_id", $ladderId);

        return $activeHandles;
    }

    public static function getAnyPreviousPlayerHandle($userId, $ladderId)
    {
        return PlayerActiveHandle::where("user_id", $userId)
            ->where("ladder_id", $ladderId)
            ->first();
    }

    public static function getActiveMonthPlayerHandle($userId, $ladderId)
    {
        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        return PlayerActiveHandle::where("user_id", $userId)
            ->where("ladder_id", $ladderId)
            ->where("created_at", ">=", $startOfMonth)
            ->where("created_at", "<=", $endOfMonth)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public static function getPlayerActiveHandle($playerId, $ladderId, $dateStart, $dateEnd)
    {
        $activeHandle = PlayerActiveHandle::where("player_id", $playerId)
            ->where("ladder_id", $ladderId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->orderBy('created_at', 'DESC')
            ->first();

        return $activeHandle;
    }

    public static function getUserActiveHandles($userId, $dateStart, $dateEnd)
    {
        $hasActiveHandles = PlayerActiveHandle::where("user_id", $userId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->orderBy('created_at', 'DESC');

        return $hasActiveHandles;
    }

    public static function getUserActiveHandleCount(
        $userId,
        $ladderId,
        $dateStart,
        $dateEnd
    )
    {
        $hasActiveHandles = PlayerActiveHandle::where("ladder_id", $ladderId)
            ->where("user_id", $userId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->count();

        return $hasActiveHandles;
    }

    /**
     * Return how many games played the user has played this month from their active handles.
     */
    public static function getUserActiveHandleGamesPlayedCount(
        $userId,
        $ladderId,
        $dateStart,
        $dateEnd
    )
    {
        $activeHandles = PlayerActiveHandle::where("ladder_id", $ladderId)
            ->where("user_id", $userId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->get();

        $count = 0;
        foreach ($activeHandles as $activeHandle)
        {
            $count += $activeHandle->player->gameReports()
                ->where('g.created_at', '<=', $dateEnd)
                ->where('g.created_at', '>', $dateStart)
                ->get()->count();
        }

        return $count;
    }

    public function user()
    {
        $this->belongsTo('App\User');
    }
}
