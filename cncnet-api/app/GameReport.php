<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameReport extends Model
{
    protected $table = 'game_reports';
    protected $fillable = ['game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'fps', 'oos', 'created_at'];

    //
    public function playerGameReports()
    {
        return $this->hasMany('App\PlayerGameReport');
    }

    public function game()
    {
        return $this->belongsTo('App\Game');
    }

    public function reporter()
    {
        return $this->belongsTo('App\Player', 'player_id');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function disconnected()
    {
        $pgrCount = $this->playerGameReports()->count();
        $disCount = $this->playerGameReports()->where('disconnected', true)->count();
        return $pgrCount == $disCount;
    }
}
