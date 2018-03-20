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
}