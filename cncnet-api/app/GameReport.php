<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameReport extends Model
{
    protected $table = 'game_reports';
    protected $fillable = ['game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'fps', 'oos', 'created_at'];

    public function getPointReportByClan($clanId)
    {
        $clanWon = $this->checkIsWinningClan($clanId);
        if ($clanWon)
        {
            return $this->getWinningClanReport();
        }
        return $this->getLosingClanReport();
    }

    public function checkIsWinningClan($clanId)
    {
        $winningReport = $this->getWinningClanReport();
        if ($winningReport)
        {
            return $winningReport->clan_id == $clanId;
        }
        return false;
    }

    public function getWinningClanReport()
    {
        $winningTeam = $this->playerGameReports()->groupBy("clan_id")
            ->where("won", true)
            ->first();

        return $winningTeam;
    }

    public function getLosingClanReport()
    {
        $winningTeam = $this->playerGameReports()->groupBy("clan_id")
            ->where("won", true)
            ->first();

        if ($winningTeam)
        {
            $losingTeam = $this->playerGameReports()->groupBy("clan_id")
                ->where("clan_id", "!=", $winningTeam->clan_id)
                ->first();

            return $losingTeam;
        }
        else
        {
            $losingTeam = $this->playerGameReports()->groupBy("clan_id")
                ->where("won", false)
                ->first();

            return $losingTeam;
        }
    }

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
