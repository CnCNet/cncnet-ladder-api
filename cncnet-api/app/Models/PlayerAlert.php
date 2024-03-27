<?php namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PlayerAlert extends Model {

	//
    public function acknowledge()
    {
        $this->seen_at = Carbon::now();
        $this->save();
    }
}
