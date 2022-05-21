<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ladder extends Model
{
    protected $table = 'ladders';

    protected $fillable = ['name', 'abbreviation', 'game', 'clans_allowed', 'game_object_schema_id', 'private'];

    public function qmLadderRules()
    {
        return $this->hasOne('App\Models\QmLadderRules');
    }

    public function sides()
    {
        return $this->hasMany('App\Models\Side');
    }

    public function qmMaps()
    {
        return $this->hasMany('App\QmMap');
    }

    public function maps()
    {
        return $this->hasMany('App\Models\Map');
    }

    public function mapPools()
    {
        return $this->hasMany('App\Models\MapPool');
    }

    public function mapPool()
    {
        return $this->belongsTo('App\Models\MapPool');
    }

    public function players()
    {
        return $this->hasMany('App\Models\Player');
    }

    public function allAdmins()
    {
        return $this->hasMany('App\Models\LadderAdmin');
    }

    public function admins()
    {
        return $this->allAdmins()->where('admin', '=', true);
    }

    public function moderators()
    {
        return $this->allAdmins()->where('moderator', '=', true);
    }

    public function testers()
    {
        return $this->allAdmins()->where('tester', '=', true);
    }

    public function allowedToView($user)
    {
        if ($this->private == false)
            return true;

        if ($user === null)
            return false;

        if ($user->isGod())
            return true;

        return $user->isLadderAdmin($this) || $user->isLadderMod($this) || $user->isLadderTester($this);
    }

    public function currentHistory()
    {
        $date = Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        return \App\Models\LadderHistory::where('ladder_id', '=', $this->id)
            ->where('ladder_history.starts', '=', $start)
            ->where('ladder_history.ends', '=', $end)->first();
    }

    public function latestLeaderboardUrl()
    {
        $history = $this->currentHistory();

        if ($history === null)
        {
            return "/";
        }

        if ($this->clans_allowed)
        {
            return "/clans/{$this->abbreviation}/leaderboards/{$history->short}/";
        }

        $ladder = $history->ladder;

        return "/ladder/{$history->short}/$ladder->abbreviation";
    }

    public function alerts()
    {
        return $this->hasMany('\App\Models\LadderAlert')->where('expires_at', '>', Carbon::now());
    }

    public function gameObjectSchema()
    {
        return $this->belongsTo('\App\Models\GameObjectSchema');
    }

    public function countableGameObjects()
    {
        return $this->hasMany('\App\Models\CountableGameObject', 'game_object_schema_id', 'game_object_schema_id');
    }

    public function spawnOptionValues()
    {
        return $this->hasMany('App\SpawnOptionValue');
    }
}
