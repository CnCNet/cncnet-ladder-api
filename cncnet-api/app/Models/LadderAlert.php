<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LadderAlert extends Model {

    use SoftDeletes;
	//

    public function players()
    {
        return $this->hasMany(LadderAlertPlayer::class);
    }
}
