<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PlayerActiveHandle extends Model
{
    protected $table = 'player_active_handles';

    public static function setPlayerActiveHandle($ladderId, $playerId, $userId)
    {
        $activeHandle = new PlayerActiveHandle();
        $activeHandle->ladder_id = $ladderId;
        $activeHandle->player_id = $playerId;
        $activeHandle->user_id = $userId;
        $activeHandle->save();

        return $activeHandle;
    }

    public static function getUserPlayerHandles($userId, $ladderId)
    {
        return PlayerActiveHandle::where("user_id", $userId)
            ->where("ladder_id", $ladderId)
            ->get();
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
        $activeHandle,
        $dateStart,
        $dateEnd
    )
    {
        if ($activeHandle == null)
            return 0;

        $count = 0;

        $count += $activeHandle->player->playerGameReports()
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->where('games.created_at', '<=', $dateEnd)
            ->where('games.created_at', '>', $dateStart) # ensure player has not played any games this month
            ->groupBy('player_game_reports.id')
            ->count();

        return $count;
    }

    # Relationships
    public function user()
    {
        $this->belongsTo('App\User');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }
}
