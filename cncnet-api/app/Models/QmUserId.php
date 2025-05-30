<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;

class QmUserId extends Model
{
    protected $fillable = ['qm_user_id', 'user_id'];

    public static function createNew($userId, $qmClientId)
    {
        try {
            $qmUserId = QmUserId::where("user_id", $userId)->where("qm_user_id", $qmClientId)->first();
            if ($qmUserId == null) {
                $qmUserId = new QmUserId();
                $qmUserId->qm_user_id = $qmClientId;
                $qmUserId->user_id = $userId;
                $qmUserId->save();
            }
            return $qmUserId;
        }
        catch (Exception $ex) {
            Log::info("Error saving id: " . $ex->getMessage());
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
