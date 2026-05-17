<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GameReport extends Model
{
    use LogsActivity;

    protected $table = 'game_reports';
    protected $fillable = ['game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'fps', 'oos', 'created_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['game_id', 'player_id', 'best_report', 'manual_report', 'duration', 'valid', 'finished', 'fps', 'oos'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            // Only log admin manual actions, not routine game processing
            ->dontLogIfAttributesChangedOnly(['created_at', 'updated_at'])
            ->useLogName('admin');
    }

    // Only log when manually created by admin (reprocess, wash, etc)
    public function shouldLogActivity(): bool
    {
        return $this->manual_report === true;
    }

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
        return $this->hasMany(PlayerGameReport::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function reporter()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function disconnected()
    {
        $pgrCount = $this->playerGameReports()->count();
        $disCount = $this->playerGameReports()->where('disconnected', true)->count();
        return $pgrCount == $disCount;
    }
}
