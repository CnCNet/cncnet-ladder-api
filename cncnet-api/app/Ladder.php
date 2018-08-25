<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Ladder extends Model
{
    protected $table = 'ladders';

    protected $fillable = ['name', 'abbreviation'];

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
}