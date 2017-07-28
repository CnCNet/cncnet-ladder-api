<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Ladder extends Model
{
    protected $table = 'ladders';

    protected $fillable = ['name', 'abbreviation'];

    public function qmLadderRules()
    {
        return $this->hasMany('App\QmLadderRules');
    }
}