<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admi\Podium\ComputePodiumRequest;
use App\Models\Ladder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function computePodium(ComputePodiumRequest $request) {
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

        return view('admin.podium.result', compact('players', 'from', 'to', 'ladder'));
    }
}
