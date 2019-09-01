<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerActiveHandle extends Model
{
    protected $table = 'player_active_handles';

    public function __construct()
    {
    }

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

    public static function getPlayerActiveHandle($playerId, $ladderId, $dateStart, $dateEnd)
    {
        $activeHandle = PlayerActiveHandle::where("player_id", $playerId)
            ->where("ladder_id", $ladderId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->first();

        return $activeHandle;
    }
    
    public static function getUserActiveHandles($userId, $dateStart, $dateEnd)
    {
        $hasActiveHandles = PlayerActiveHandle::where("user_id", $userId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->get();

        return $hasActiveHandles;
    }

    public static function getUserActiveHandleCount($userId, $ladderId,
        $dateStart, $dateEnd)
    {
        $hasActiveHandles = PlayerActiveHandle::where("ladder_id", $ladderId)
            ->where("user_id", $userId)
            ->where("created_at", ">=", $dateStart)
            ->where("created_at", "<=", $dateEnd)
            ->count();

        return $hasActiveHandles;
    }
}