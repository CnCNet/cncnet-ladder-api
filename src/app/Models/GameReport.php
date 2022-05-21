<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReport extends Model
{

    protected $table = 'game_reports';
    protected $fillable = ['game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'fps', 'oos', 'created_at'];

    //
    public function playerGameReports()
    {
        return $this->hasMany('App\Models\PlayerGameReport');
    }
    public function game()
    {
        return $this->belongsTo('App\Models\Game');
    }
    public function reporter()
    {
        return $this->belongsTo('App\Models\Player', 'player_id');
    }

    public function player()
    {
        return $this->belongsTo('App\Models\Player');
    }


    public function disconnected()
    {
        $pgrCount = $this->playerGameReports()->count();
        $disCount = $this->playerGameReports()->where('disconnected', true)->count();
        return $pgrCount == $disCount;
    }
}
