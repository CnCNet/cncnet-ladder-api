<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ladder extends Model
{
    protected $table = 'ladders';

    protected $fillable = ['name', 'abbreviation', 'game', 'clans_allowed', 'game_object_schema_id', 'private'];

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

        return \App\LadderHistory::where('ladder_id', '=', $this->id)
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

        $ladder = $history->ladder;

        return "/ladder/{$history->short}/$ladder->abbreviation";
    }

    /**
     * Returns array of QM ladders user has access to
     * Show private ladders to ladder testers only
     * @param User $user 
     * @return array 
     */
    public static function getAllowedQMLaddersByUser(User $user, bool $onlyReturnPrivateLadders = false)
    {
        $userAllowedLadders = [];

        $ladders = Ladder::all();
        if ($onlyReturnPrivateLadders === true)
        {
            $ladders = Ladder::where("private", true)->get();
        }

        foreach ($ladders as $ladder)
        {
            // Show private ladders to ladder testers only
            if ($ladder->private == true)
            {
                if (!$user->isLadderAdmin($ladder) || !$user->isLadderTester($ladder))
                {
                    continue;
                }
            }

            $userAllowedLadders[] = $ladder;
        }

        return $userAllowedLadders;
    }

    public function qmLadderRules()
    {
        return $this->hasOne('App\QmLadderRules');
    }

    public function sides()
    {
        return $this->hasMany('App\Side');
    }

    public function qmMaps()
    {
        return $this->hasMany('App\QmMap');
    }

    public function maps()
    {
        return $this->hasMany('App\Map');
    }

    public function mapPools()
    {
        return $this->hasMany('App\MapPool');
    }

    # Relationships

    public function mapPool()
    {
        return $this->belongsTo('App\MapPool');
    }

    public function players()
    {
        return $this->hasMany('App\Player');
    }

    public function allAdmins()
    {
        return $this->hasMany('App\LadderAdmin');
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

    public function alerts()
    {
        return $this->hasMany('\App\LadderAlert')->where('expires_at', '>', Carbon::now());
    }

    public function gameObjectSchema()
    {
        return $this->belongsTo('\App\GameObjectSchema');
    }

    public function countableGameObjects()
    {
        return $this->hasMany('\App\CountableGameObject', 'game_object_schema_id', 'game_object_schema_id');
    }

    public function spawnOptionValues()
    {
        return $this->hasMany('App\SpawnOptionValue');
    }

    public function qmCanceledMatches()
    {
        return $this->hasMany('App\QmCanceledMatch', 'ladder_id');
    }

    public function achievements()
    {
        return $this->hasMany('App\Achievement', 'ladder_id');
    }

    public function clanLadderRules()
    {
        return $this->hasOne('App\ClanLadderRules');
    }
}
