<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ban extends Model {

	//
    protected $dates = ['expires'];

    public function admin()
    {
        return $this->belongsTo("\App\Admin");
    }

    public function user()
    {
        return $this->belongsTo("\App\User");
    }

    public function timeTill()
    {
        return $this->expires->diffForHumans();
    }
}
