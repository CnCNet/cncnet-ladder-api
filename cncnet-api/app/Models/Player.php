<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Player extends Model
{
    protected $table = 'players';

    protected $fillable = [
        'user_id', 'username', 'win_count', 'loss_count', 'games_count',
        'dc_count', 'oos_count', 'points', 'countries', 'ladder_id'
    ];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];


    public function wins($history = null)
    {
        $result = 0;

        if ($history == null)
        {
            $result = $this->playerGames()->where('won', true)->count();
        }
        else
        {
            $result = $this->playerGames()->where('won', true)->where('ladder_history_id', $history->id)->count();
        }
        return $result;
    }

    public function gameReports()
    {
        return $this->hasMany(GameReport::class)
            ->join('games as g', 'g.id', '=', 'game_reports.game_id')
            ->where('g.game_report_id', '=', 'game_reports.id');
    }

    public function playerGames()
    {
        return $this->playerGameReports()
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('stats2', 'player_game_reports.stats_id', '=', 'stats2.id')
            ->where('player_game_reports.player_id', $this->id)
            ->where('game_reports.valid', true)
            ->where('game_reports.best_report', true)
            ->select(
                'player_game_reports.id as id',
                'player_game_reports.player_id as player_id',
                'game_reports.id as game_report_id',
                'games.ladder_history_id as ladder_history_id',
                'game_reports.game_id as game_id',
                'duration',
                'fps',
                'oos',
                'sid',
                'cty',
                'local_id',
                'local_team_id',
                'points',
                'stats_id',
                'disconnected',
                'no_completion',
                'quit',
                'won',
                'defeated',
                'draw',
                'spectator',
                'game_reports.created_at',
                'wol_game_id',
                'bamr',
                'crat',
                'cred',
                'shrt',
                'supr',
                'supr',
                'unit',
                'plrs',
                'scen',
                'hash'
            );
    }

    public function totalGames($history = null)
    {
        $result = 0;

        if ($history == null)
        {
            $result = $this->playerGames()->count();
        }
        else
        {
            $result = $this->playerGames()->where('ladder_history_id', $history->id)->count();
        }
        return $result;
    }

    public function totalGames24Hours($history)
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        return $this->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("game_reports.created_at", [$start, $end])
            ->count();
    }

    public function lastActive($history)
    {
        $lastGamePlayed = $this->playerGames()
            ->where("ladder_history_id", $history->id)
            ->orderBy("created_at", "DESC")
            ->first();

        if ($lastGamePlayed)
        {
            return $lastGamePlayed->created_at->diffForHumans();
        }
        return null;
    }

    public function lastFiveGames($history)
    {
        $queryResults = $this->playerGames()
            ->where("ladder_history_id", $history->id)
            ->orderBy("created_at", "DESC")
            ->limit(5)
            ->get();

        $last5GamesPlayed = [];
        foreach ($queryResults as $game)
        {
            $last5GamesPlayed[] = [
                "won" => $game->won,
                "game_id" => $game->game_id
            ];
        }

        return $last5GamesPlayed;
    }

    public function averageFPS($history)
    {
        $count = $this->playerGames()->where('ladder_history_id', '=', $history->id)->where('fps', '>', 25)->count();
        $total = $this->playerGames()->where('ladder_history_id', '=', $history->id)->where('fps', '>', 25)->sum('fps');
        if ($count != 0)
            return $total / $count;
        return 0;
    }

    public function sideUsage($history)
    {
        $pq = $this->playerGames()->where('ladder_history_id', '=', $history->id);
        $total = $pq->count();
        return $pq->select('sid', DB::raw('count(*) / ' . $total . ' * 100 as count'))->groupBy('sid')->orderBy('count', 'desc')->get();
    }

    public function countryUsage($history)
    {
        $pq = $this->playerGames()->where('ladder_history_id', '=', $history->id);
        $total = $pq->count();
        return $pq->select('cty', DB::raw('count(*) / ' . $total . ' * 100 as count'))->groupBy('cty')->orderBy('count', 'desc')->get();
    }

    public function rank($history)
    {
        $ppQuery = PlayerHistory::join('player_game_reports as pgr', 'pgr.player_id', '=', 'player_histories.player_id')
            ->join('games', 'pgr.game_report_id', '=', 'games.game_report_id')
            ->where('games.ladder_history_id', '=', $history->id)
            ->where("player_histories.ladder_history_id", '=', $history->id)
            ->where('player_histories.tier', '=', $this->playerHistory($history)->tier)
            ->groupBy('pgr.player_id')
            ->orderBy('points', 'DESC')
            ->selectRaw('pgr.player_id, SUM(points) as points');

        $playerPoints = $ppQuery->get();

        for ($i = 0; $i < $playerPoints->count(); ++$i)
        {
            if ($playerPoints[$i]->player_id == $this->id)
                return $i + 1;
        }
        return 9999;
    }

    public function playerPoints($history, $username)
    {
        $player = Player::where("username", "=", $username)
            ->where("ladder_id", "=", $history->ladder->id)->first();

        if ($player == null) return "No player";

        return $this->playerGames()->sum("points");
    }

    public function points($history)
    {
        $points = PlayerGameReport::where('player_game_reports.player_id', $this->id)
            ->join('games as g', 'g.game_report_id', '=', 'player_game_reports.game_report_id')
            ->where("g.ladder_history_id", "=", $history->id)
            ->sum('player_game_reports.points');
        return $points !== null ? $points : 0;
    }

    public function pointsBefore($history, $game_id)
    {
        $points = PlayerGameReport::where('player_game_reports.player_id', $this->id)
            ->join('games as g', 'g.game_report_id', '=', 'player_game_reports.game_report_id')
            ->where("g.ladder_history_id", "=", $history->id)
            ->where('g.id', '<', $game_id)
            ->sum('player_game_reports.points');
        return $points !== null ? $points : 0;
    }

    public function playerHistory($history)
    {
        # Dont' trust these relationships functions
        $playerHistory = PlayerHistory::where("ladder_history_id", $history->id)
            ->where("player_id", $this->id)
            ->first();

        if ($playerHistory == null)
        {
            # Player history does not exist and thus no tier has been assigned/cached yet
            $userTier = $this->user->getLiveUserTier($history);

            # If we have no history, we will create one and insert users currently known tier
            $playerHistory = new PlayerHistory();
            $playerHistory->ladder_history_id = $history->id;
            $playerHistory->player_id = $this->id;
            $playerHistory->tier = $userTier;
            $playerHistory->save();

            Log::info(
                "Player ** playerHistory null. UserEmail: " . $this->user->email . " LadderHistoryId:" . $history->id . " New PlayerHistory: " . $playerHistory
            );
        }

        return $playerHistory;
    }

    /**
     * Fetches the players user tier in the ladder at this moment in time
     * @param mixed $history 
     * @return mixed 
     */
    public function getCachedPlayerTierByLadderHistory($history)
    {
        return $this->playerHistory($history)->tier;
    }

    public function playerCache($historyId)
    {
        return PlayerCache::where('player_id', '=', $this->id)
            ->where("ladder_history_id", '=', $historyId)
            ->first();
    }


    /**
     * Return true if player has been laundered
     * A player has been laundered if their player game reports have 'backupPts' which is populated when a launder occurs, and erased when launder is undone.
     */
    public function laundered($ladderHistory)
    {
        $sum = PlayerGameReport::where('player_id', '=', $this->id)
            ->where('created_at', '<', $ladderHistory->ends)
            ->where('created_at', '>', $ladderHistory->starts)
            ->sum('backupPts');

        return $sum > 0;
    }

    public function gameClip($gameId)
    {
        return GameClip::where("player_id", $this->id)->where("game_id", $gameId)->first();
    }

    public function unSeenAlerts()
    {
        return $this->hasMany(PlayerAlert::class)->whereNull('seen_at')->where('expires_at', '>', Carbon::now());
    }

    public function alerts()
    {
        return $this->hasMany(PlayerAlert::class)->where('expires_at', '>', Carbon::now());
    }


    # Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qmPlayer()
    {
        return $this->hasOne(QmMatchPlayer::class, 'player_id');
    }

    public function playerGameReports()
    {
        return $this->hasMany(PlayerGameReport::class);
    }

    public function qmCanceledMatches()
    {
        return $this->hasMany(QmCanceledMatch::class, 'player_id');
    }

    public function clanPlayer()
    {
        return $this->hasOne(ClanPlayer::class);
    }

    public function clanInvitations()
    {
        return $this->hasMany(ClanInvitation::class);
    }

    public function ircAssociation()
    {
        return $this->hasOne(IrcAssociation::class);
    }

    public function playerRating()
    {
        return $this->hasOne(PlayerRating::class);
    }

    public function playerHistories()
    {
        return $this->hasMany(PlayerHistory::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function rating()
    {
        return $this->hasOne(PlayerRating::class);
    }
}
