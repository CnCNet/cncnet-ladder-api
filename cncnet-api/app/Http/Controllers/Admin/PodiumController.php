<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Podium\ComputePodiumRequest;
use App\Models\Ladder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * This class provider a form to
 */
class PodiumController extends Controller
{
    public function getPodiumForm()
    {
        $ladders = Ladder::query()
            ->where('private', 0)
            ->pluck('name', 'id')
            ->toArray();
        $fromDate = Carbon::now()->subWeek()->endOfWeek()->subDays(2)->setTime(20, 0, 0);
        $toDate = Carbon::now()->subWeek()->endOfWeek()->setTime(22, 0, 0);
        return view('admin.podium', compact('ladders', 'fromDate', 'toDate'));
    }

    public function computePodium(ComputePodiumRequest $request)
    {

        if (RateLimiter::tooManyAttempts($this->getRateLimiterKey(), 1))
        {
            $seconds = RateLimiter::availableIn($this->getRateLimiterKey());
            $message = 'Don\'t submit this form this too often... You may try again in ' . $seconds . ' seconds.';
            return view('admin.podium.result-too-many-attempts', compact('message'));
        }

        $inputs = $request->validated();

        $ladderId = $inputs['ladder_id'];
        $ladder = Ladder::find($ladderId);
        $from = Carbon::createFromFormat('Y-m-d H:i', $inputs['date_from'] . ' ' . $inputs['time_from']);
        $to = Carbon::createFromFormat('Y-m-d H:i', $inputs['date_to'] . ' ' . $inputs['time_to']);

        $players = DB::table('player_game_reports')
            ->select('players.username', 'player_game_reports.player_id', DB::raw('count(*) AS win_count'))
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('players', 'players.id', '=', 'player_game_reports.player_id')
            ->where('players.ladder_id', $ladderId)
            ->whereBetween('player_game_reports.created_at', [$from, $to])
            ->where('player_game_reports.points', '>', 0)
            ->where('game_reports.valid', true)
            ->where('game_reports.best_report', true)
            ->where('player_game_reports.won', 1)
            ->groupBy('players.username', 'player_game_reports.player_id')
            ->orderByDesc('win_count')
            ->limit(3)
            ->get();


        RateLimiter::increment($this->getRateLimiterKey(), 120);

        return view('admin.podium.result', compact('players', 'from', 'to', 'ladder'));
    }

    private function getRateLimiterKey()
    {
        return 'compute-podium-win-count:' . auth()->id();
    }
}
