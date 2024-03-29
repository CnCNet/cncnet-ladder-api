<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTier extends Model
{

    public static function createNew($userId, $ladderId, $tier = 2)
    {
        $userTier = new UserTier();
        $userTier->user_id = $userId;
        $userTier->ladder_id = $ladderId;
        $userTier->tier = $tier;
        $userTier->save();
        return $userTier;
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function ladder()
    {
        return $this->hasOne(Ladder::class);
    }
}
