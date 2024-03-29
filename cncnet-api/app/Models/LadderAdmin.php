<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LadderAdmin extends Model {

	//
    public static function findOrCreate($userId, $ladderId)
    {
        $la = LadderAdmin::where('user_id', '=', $userId)->where('ladder_id', '=', $ladderId)->first();
        if ($la === null)
        {
            $la = new LadderAdmin;
            $la->user_id = $userId;
            $la->ladder_id = $ladderId;
            $la->save();
        }
        return $la;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }
}
