<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GameReport extends Model {

    protected $table = 'game_reports';
    protected $fillable = [ 'game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'fps', 'oos', 'created_at'];

	//
    public function playerGameReports()
    {
        return $this->hasMany('App\PlayerGameReport');
    }
}
