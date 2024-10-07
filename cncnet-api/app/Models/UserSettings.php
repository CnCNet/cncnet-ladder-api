<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'disabledPointFilter',
        'skip_score_screen',
        'match_any_map',
        'enableAnonymous',
        'match_ai',
        'is_observer',
        'allow_observers',
        'pro_only_matchups'
    ];


    public function __construct()
    {
        $this->timestamps = false;
        $this->enableAnonymous = false;     //by default users will not be anonymous
        $this->disabledPointFilter = false;    //by default point filter will be enabled
        $this->match_ai = true;
        $this->pro_only_matchups = false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMatchAI()
    {
        return $this->match_ai;
    }

    public function getIsAnonymous()
    {
        return $this->enableAnonymous == true;
    }
}
