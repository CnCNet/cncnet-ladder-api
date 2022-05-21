<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlayerAlert extends Model
{

    //
    public function acknowledge()
    {
        $this->seen_at = Carbon::now();
        $this->save();
    }
}
