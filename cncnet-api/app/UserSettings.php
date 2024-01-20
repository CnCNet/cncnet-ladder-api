<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    protected $table = 'user_settings';

    protected $casts = [
        'disabledPointFilter' => 'boolean',
        'skip_score_screen' => 'boolean',
        'match_any_map' => 'boolean',
        'enableAnonymous' => 'boolean',
        'match_ai' => 'boolean',
        'is_observer' => 'boolean',
        'allow_observers' => 'boolean',
    ];

    protected $fillable = [
        'disabledPointFilter',
        'skip_score_screen',
        'match_any_map',
        'enableAnonymous',
        'match_ai',
        'is_observer',
        'allow_observers',
    ];


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

    public function getIsAnonymous()
    {
        return $this->enableAnonymous == true;
    }
}
