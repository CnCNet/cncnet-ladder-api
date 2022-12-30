<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use \App\Http\Services\LadderService;
use App\Player;
use App\PlayerGameReport;
use \App\QmMatchPlayer;
use \App\QmMatch;
use App\StatsCache;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StatsService
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function getQmStats($ladderAbbrev)
    {
        return Cache::remember("getQmStats/$ladderAbbrev", 5, function () use ($ladderAbbrev)
        {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $timediff = Carbon::now()->subHour()->toDateTimeString();
            $ladder_id = $this->ladderService->getLadderByGame($ladderAbbrev)->id;

            $recentMatchedPlayers = QmMatchPlayer::where("created_at", ">", $timediff)
                ->where("ladder_id", "=", $ladder_id)
                ->count();

            $queuedPlayers = QmMatchPlayer::where("ladder_id", "=", $ladder_id)->whereNull("qm_match_id")->count();

            $recentMatches = QmMatch::where("created_at", ">", $timediff)
                ->where("ladder_id", "=", $ladder_id)
                ->count();

            $activeGames = QmMatch::where("updated_at", ">", Carbon::now()->subMinute(2))
                ->where("ladder_id", "=", $ladder_id)
                ->count();

            $past24hMatches = QmMatch::where("updated_at", ">", Carbon::now()->subDay(1))
                ->where("ladder_id", "=", $ladder_id)
                ->count();

            $matchesByMonth = QmMatch::where("updated_at", ">", $startOfMonth)
                ->where("updated_at", "<", $endOfMonth)
                ->where("ladder_id", "=", $ladder_id)
                ->count();

            return [
                "recentMatchedPlayers" => $recentMatchedPlayers,
                "queuedPlayers" => $queuedPlayers,
                "matchesByMonth" => $matchesByMonth,
                "past24hMatches" => $past24hMatches,
                "recentMatches" => $recentMatches,
                "activeMatches"   => $activeGames,
                "time"          => Carbon::now()
            ];
        });
    }

    public function getFactionsPlayedByPlayer($player, $history)
    {
        return Cache::remember("getFactionsPlayedByPlayer/$history->short/$player->id", 5, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            $factionResults = [];

            if ($history->ladder->game == "yr")
            {
                $factionResults = $this->getFactionResultsForYR($player, $history, $from, $to);
            }
            else
            {
                $factionResults = $this->getFactionResults($player, $history, $from, $to);
            }

            return $factionResults;
        });
    }

    private function getFactionResults($player, $history, $from, $to)
    {
        $factionResults = [];

        $playerGames = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->groupBy("sid")
            ->get();

        foreach ($playerGames as $pg)
        {
            $sideCountWon = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("sid", $pg->sid)
                ->where("won", true)
                ->count();

            $sideCountLost = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("sid", $pg->sid)
                ->where("won", false)
                ->count();

            $total = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("sid", $pg->sid)
                ->count();

            $factionResults[$pg->sid] =
                [
                    "won" => $sideCountWon,
                    "lost" => $sideCountLost,
                    "total" => $total
                ];
        }
        return $factionResults;
    }

    private function getFactionResultsForYR($player, $history, $from, $to)
    {
        $factionResults = [];

        $playerGames = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->groupBy("cty")
            ->get();

        foreach ($playerGames as $pg)
        {
            $sideCountWon = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->where("won", true)
                ->count();

            $sideCountLost = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->where("won", false)
                ->count();

            $total = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->count();

            $factionResults[$pg->cty] =
                [
                    "won" => $sideCountWon,
                    "lost" => $sideCountLost,
                    "total" => $total
                ];
        }
        return $factionResults;
    }

    public function getMapWinLossByPlayer($player, $history)
    {
        return Cache::remember("getMapWinLossByPlayer/$history->short/$player->id", 5, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            $playerGamesByMaps = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->groupBy("scen")
                ->get();

            $mapResults = [];
            foreach ($playerGamesByMaps as $pg)
            {
                $mapWins = $player->playerGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$from, $to])
                    ->where("scen", $pg->scen)
                    ->where("won", true)
                    ->count();

                $mapLosses = $player->playerGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$from, $to])
                    ->where("scen", $pg->scen)
                    ->where("won", false)
                    ->count();

                $mapTotal = $player->playerGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$from, $to])
                    ->where("scen", $pg->scen)
                    ->count();

                $mapResults[$pg->scen] = [
                    "preview" => $pg->hash,
                    "won" => $mapWins,
                    "lost" => $mapLosses,
                    "total" => $mapTotal
                ];
            }
            return $mapResults;
        });
    }

    public function getPlayerOfTheDay($history)
    {
        // Players won't change too often, cache them for 30 minutes under each ladder
        $players = StatsCache::getPlayersTodayFromCache($history);

        // These stats will update instantly
        $now = Carbon::now();
        $from = $now->copy()->startOfDay()->toDateTimeString();
        $to = $now->copy()->endOfDay()->toDateTimeString();
        $stats = [];

        foreach ($players as $k => $playerId)
        {
            $player = Player::where("id", $playerId)->first();
            $winCount = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("won", true)
                ->count();

            $stats[$k]["id"] = $player->id;
            $stats[$k]["username"] = $player->username;
            $stats[$k]["wins"] = $winCount;
        }

        if (count($stats) > 0)
        {
            $playerOfDay = collect($stats)->sortByDesc("wins")->first();
            return json_decode(json_encode($playerOfDay));
        }

        return null;
    }

    public function checkPlayerIsPlayerOfTheDay($history, $player)
    {
        $playerOfTheDay = $this->getPlayerOfTheDay($history);
        if ($playerOfTheDay != null)
        {
            if ($player->username === $playerOfTheDay->username)
            {
                return $playerOfTheDay;
            }
        }
        return null;
    }

    public function getPlayerMatchups($player, $history)
    {
        return Cache::remember("getPlayerMatchups/$history->short/$player->id", 5, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            $playerGameReports = $player->playerGames()
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->get();

            $matchupResults = [];
            foreach ($playerGameReports as $pgr)
            {
                if ($pgr->disconnected || $pgr->draw || $pgr->no_completion)
                    continue;

                $opponent = \App\PlayerGameReport::join('players as p', 'player_game_reports.player_id', '=', 'p.id')
                    ->join('game_reports as gr', 'player_game_reports.game_report_id', '=', 'gr.id')->where('gr.game_id', $pgr->game_id)
                    ->where('p.id', '!=', $player->id)
                    ->where('gr.valid', true)
                    ->where('gr.best_report', true)
                    ->select('p.username')
                    ->first();

                if ($opponent == null)
                    continue;

                $opponentName = $opponent->username;

                if (!array_key_exists($opponentName, $matchupResults))
                {
                    $matchupResults[$opponentName] = [];
                    $matchupResults[$opponentName]["won"] = 0;
                    $matchupResults[$opponentName]["lost"] = 0;
                    $matchupResults[$opponentName]["total"] = 0;
                }

                if ($pgr->won)
                    $matchupResults[$opponentName]["won"] = $matchupResults[$opponentName]["won"] + 1;
                else
                    $matchupResults[$opponentName]["lost"] = $matchupResults[$opponentName]["lost"] + 1;
                $matchupResults[$opponentName]["total"] = $matchupResults[$opponentName]["total"] + 1;
            }

            return $matchupResults;
        });
    }
}
