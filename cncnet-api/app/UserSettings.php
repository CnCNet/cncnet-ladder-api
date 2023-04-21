<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    protected $table = 'user_settings';

    public function __construct()
    {
        $this->timestamps = false;
        $this->enableAnonymous = false;     //by default users will not be anonymous
        $this->disabledPointFilter = false;    //by default point filter will be enabled
        $this->match_ai = true;
    }

    public function user()
    {
        return $this->belongsTo("App\User");
    }

    public function getMatchAI()
    {
        return $this->match_ai;
    }
}
