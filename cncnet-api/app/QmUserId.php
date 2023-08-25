<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QmUserId extends Model
{
    protected $fillable = ['qm_user_id', 'user_id'];

    public static function createNew($userId, $qmClientId)
    {
        $qmUserId = QmUserId::where("user_id", $userId)->where("qm_user_id", $qmClientId)->first();
        if ($qmUserId == null)
        {
            $qmUserId = new QmUserId();
            $qmUserId->qm_user_id = $qmClientId;
            $qmUserId->user_id = $userId;
            $qmUserId->save();
        }
        return $qmUserId;
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
