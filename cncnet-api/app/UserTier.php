<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTier extends Model
{
    public function __construct()
    {
    }

    public static function createNew($userId, $ladderId, $tier = 2)
    {
        $userTier = new \App\UserTier();
        $userTier->user_id = $userId;
        $userTier->ladder_id = $ladderId;
        $userTier->tier = $tier;
        $userTier->save();
        return $userTier;
    }

    public function user()
    {
        return $this->hasOne("App\User");
    }

    public function ladder()
    {
        return $this->hasOne("App\Ladder");
    }
}
