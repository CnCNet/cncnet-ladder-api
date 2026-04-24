<?php

namespace App\Http\Services;

use App\Models\Clan;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use App\Models\StatsCache;
use App\Models\Games;
use App\Models\GameReport;
use App\Models\PlayerGameReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StatsService
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function getQmStats(LadderHistory $history, $tierId = 1)
    {
        $ladder = $history->ladder;
        $ladderAbbrev = $ladder->abbreviation;

        return Cache::remember("getQmStats/$ladderAbbrev/$tierId", 1 * 60, function () use (&$history, &$ladder)
        {
            $carbonDateSub24Hours = Carbon::now()->subHours(24);

            $ladderId = $ladder->id;
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $queuedPlayers = QmQueueEntry::join('qm_match_players', 'qm_match_players.id', '=', 'qm_queue_entries.qm_match_player_id')
                ->where('ladder_history_id', $history->id)
                ->where('is_observer', false)
                ->whereNull('qm_match_id')
                ->count();

            $past24hMatches = QmMatch::where('qm_matches.created_at', '>', $carbonDateSub24Hours)
                ->where('qm_matches.ladder_id', '=', $ladderId)
                ->count();

            $matchesByMonth = QmMatch::where("updated_at", ">", $startOfMonth)
                ->where("updated_at", "<", $endOfMonth)
                ->where("ladder_id", "=", $ladderId)
                ->count();

            return [
                "recentMatchedPlayers" => 0, # deprecated
                "queuedPlayers" => $queuedPlayers,
                "past24hMatches" => $past24hMatches,
                "recentMatches" => 0, #deprecated
                "matchesByMonth" => $matchesByMonth,
                "activeMatches" => 0, # deprecated
                "clans" => 0, # $clans
                "time" => Carbon::now()
            ];
        });
    }

    public function getFactionsPlayedByPlayer($player, $history)
    {
        return Cache::remember("getFactionsPlayedByPlayer/$history->short/$player->id", 10 * 60, function () use ($player, $history)
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
        // Use single query with aggregation instead of N+1 loop
        $results = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->selectRaw('
                sid,
                SUM(CASE WHEN won = 1 THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN won = 0 THEN 1 ELSE 0 END) as lost,
                COUNT(*) as total
            ')
            ->groupBy("sid")
            ->get();

        $factionResults = [];
        foreach ($results as $result)
        {
            $factionResults[$result->sid] = [
                "won" => $result->won,
                "lost" => $result->lost,
                "total" => $result->total
            ];
        }
        return $factionResults;
    }

    private function getFactionResultsForYR($player, $history, $from, $to)
    {
        // Use single query with aggregation instead of N+1 loop
        $results = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->selectRaw('
                cty,
                SUM(CASE WHEN won = 1 THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN won = 0 THEN 1 ELSE 0 END) as lost,
                COUNT(*) as total
            ')
            ->groupBy("cty")
            ->get();

        $factionResults = [];
        foreach ($results as $result)
        {
            $factionResults[$result->cty] = [
                "won" => $result->won,
                "lost" => $result->lost,
                "total" => $result->total
            ];
        }
        return $factionResults;
    }


    public function getMapWinLossByPlayer($player, $history)
    {
        // 10 mins cache, this is pretty heavy?
        return Cache::remember("getMapWinLossByPlayer/$history->short/$player->id", 10 * 60, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            // Use direct query with aggregation to avoid conflicts with playerGames() select
            // Join maps table to get map name, group by scen
            $playerGamesByMaps = \DB::table('player_game_reports')
                ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
                ->join('games', 'games.id', '=', 'game_reports.game_id')
                ->join('stats2', 'player_game_reports.stats_id', '=', 'stats2.id')
                ->leftJoin('maps', 'games.hash', '=', 'maps.hash')
                ->where('player_game_reports.player_id', $player->id)
                ->where('game_reports.valid', true)
                ->where('game_reports.best_report', true)
                ->where('games.ladder_history_id', $history->id)
                ->whereBetween('player_game_reports.created_at', [$from, $to])
                ->selectRaw('
                    games.scen,
                    MAX(maps.name) as map,
                    SUM(CASE WHEN player_game_reports.won = 1 THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN player_game_reports.won = 0 THEN 1 ELSE 0 END) as lost,
                    COUNT(*) as total
                ')
                ->groupBy('games.scen')
                ->get();

            $mapResults = [];
            foreach ($playerGamesByMaps as $pg)
            {
                $mapResults[$pg->scen] = [
                    "map" => $pg->map,
                    "won" => $pg->won,
                    "lost" => $pg->lost,
                    "total" => $pg->total
                ];
            }
            return $mapResults;
        });
    }

    public function getWinnerOfTheDay(LadderHistory $history)
    {
        if ($history->ladder->clans_allowed)
        {
            $statsXOfTheDay = $this->getClanOfTheDay($history);
        }
        else
        {
            $statsXOfTheDay = $this->getPlayerOfTheDay($history);
        }

        return $statsXOfTheDay;
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
            $stats[$k]["name"] = $player->username;
            $stats[$k]["wins"] = $winCount;
        }

        if (count($stats) > 0)
        {
            $playerOfDay = collect($stats)->sortByDesc("wins")->first();
            return json_decode(json_encode($playerOfDay));
        }

        return null;
    }


    public function getClanOfTheDay($history)
    {
        // Clans won't change too often, cache them for 30 minutes under each ladder
        $clans = StatsCache::getClansTodayFromCache($history);

        // These stats will update instantly
        $now = Carbon::now();
        $from = $now->copy()->startOfDay()->toDateTimeString();
        $to = $now->copy()->endOfDay()->toDateTimeString();
        $stats = [];

        foreach ($clans as $k => $clanId)
        {
            $clan = Clan::where("id", $clanId)->first();

            if ($clan == null)
                continue;

            $winCountGames = $clan->clanGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("won", true)
                ->get();

            $winCount = count($winCountGames);

            $stats[$k]["id"] = $clan->id;
            $stats[$k]["name"] = $clan->short;
            $stats[$k]["wins"] = $winCount;
        }

        if (count($stats) > 0)
        {
            $clanOfTheDay = collect($stats)->sortByDesc("wins")->first();
            return json_decode(json_encode($clanOfTheDay));
        }

        return null;
    }


    public function checkPlayerIsPlayerOfTheDay($history, $player)
    {
        $playerOfTheDay = $this->getPlayerOfTheDay($history);
        if ($playerOfTheDay != null)
        {
            if ($player->username === $playerOfTheDay->name)
            {
                return $playerOfTheDay;
            }
        }
        return null;
    }

    public function getPlayerMatchups($player, $history)
    {
        $ladderType = $history->ladder->ladder_type;
        return Cache::remember("getPlayerMatchups/$history->short/$player->id", 5 * 60, function () use ($player, $history, $ladderType)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            // Eager load gameReport and its playerGameReports to avoid N+1
            $playerGameReports = $player->playerGames()
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where('draw', false)
                ->where('no_completion', false)
                ->with(['gameReport.playerGameReports.player'])
                ->get();

            $matchupResults = [];
            foreach ($playerGameReports as $pgr)
            {

                // Use eager-loaded relationship instead of query
                $allZeroPoints = !$pgr->gameReport->playerGameReports
                    ->where('points', '!=', 0)
                    ->count();
                if ($allZeroPoints)
                {
                    continue;
                }

                $team = $pgr->team;

                if ($team == null && $ladderType == Ladder::TWO_VS_TWO)
                {
                    continue;
                }

                // Use eager-loaded playerGameReports instead of fresh query
                $opponentReports = $pgr->gameReport->playerGameReports
                    ->where('player_id', '!=', $player->id)
                    ->map(function($report) {
                        // Map to match the original select structure
                        return (object)[
                            'username' => $report->player->username,
                            'team' => $report->team,
                            'player_id' => $report->player_id,
                            'game_id' => $report->game_id,
                            'game_report_id' => $report->game_report_id
                        ];
                    });

                foreach ($opponentReports as $opponentReport)
                {
                    $opponentTeam = $opponentReport->team;

                    if (($opponentTeam == null || $opponentTeam == $team) && $ladderType == Ladder::TWO_VS_TWO)
                    {
                        continue;
                    }

                    $opponentName = $opponentReport->username;

                    if (!array_key_exists($opponentName, $matchupResults))
                    {
                        $matchupResults[$opponentName] = [];
                        $matchupResults[$opponentName]["won"] = 0;
                        $matchupResults[$opponentName]["lost"] = 0;
                        $matchupResults[$opponentName]["total"] = 0;
                    }

                    // Use points > 0 instead of won flag to handle DCs and 2v2 cases correctly
                    if ($pgr->points > 0)
                    {
                        $matchupResults[$opponentName]["won"] = $matchupResults[$opponentName]["won"] + 1;
                    }
                    else
                    {
                        $matchupResults[$opponentName]["lost"] = $matchupResults[$opponentName]["lost"] + 1;
                    }
                    $matchupResults[$opponentName]["total"] = $matchupResults[$opponentName]["total"] + 1;
                }
            }

            uksort($matchupResults, function ($a, $b)
            {
                return strcasecmp($a, $b); // Case-insensitive alphabetical sort
            });

            return $matchupResults;
        });
    }

    public function getTeamMatchups($player, $history)
    {
        $cacheKey = "getTeamMatchups/" . Str::slug($history->short) . "/$player->id";

        return Cache::remember($cacheKey, 5 * 60, function () use ($player, $history)
        {

            // Eager load playerGameReports and their players to avoid N+1
            $gameReports = GameReport
                ::whereHas('game', function ($query) use ($history)
                {
                    $query->where('ladder_history_id', $history->id);
                })
                ->whereHas('playerGameReports', function ($query) use ($player)
                {
                    $query->where('player_id', $player->id);
                })
                ->where('valid', true)
                ->where('manual_report', false)
                ->where('best_report', true)
                ->with(['playerGameReports.player', 'game'])
                ->get();

            $matchupResults = [];

            foreach ($gameReports as $gameReport)
            {
                // Use eager-loaded collection instead of query
                $allZeroPoints = !$gameReport->playerGameReports
                    ->where('points', '!=', 0)
                    ->count();
                if ($allZeroPoints)
                {
                    continue;
                }

                // Use eager-loaded collection instead of query
                $myPlayerGameReport = $gameReport->playerGameReports
                    ->where('draw', false)
                    ->where('player_id', $player->id) // get my player report
                    ->first();

                if (!$myPlayerGameReport)
                {
                    continue;
                }

                $team = $myPlayerGameReport->team;
                if ($team == null)
                {
                    $playerGameReportId = $myPlayerGameReport->id;
                    $gameId = $myPlayerGameReport->game->id;
                    continue;
                }

                // Use eager-loaded collection instead of fresh query
                $teamMatePlayerGameReports = $gameReport->playerGameReports
                    ->where('team', $team) // my teammate(s)
                    ->where('id', '!=', $myPlayerGameReport->id); // ignore my report

                if ($teamMatePlayerGameReports->isEmpty())
                {
                    continue;
                }

                foreach ($teamMatePlayerGameReports as $teamMatePlayerGameReport)
                {
                    if ($teamMatePlayerGameReport->team == null)
                    {
                        $gameId = $teamMatePlayerGameReport->game->id;
                        continue;
                    }

                    $teammateName = $teamMatePlayerGameReport->player->username;

                    // Initialize matchup stats if not already set
                    $matchupResults[$teammateName] ??= ["won" => 0, "lost" => 0, "total" => 0];

                    // Update win/loss count - use points > 0 to handle DCs and cases where both players die but team wins
                    if ($myPlayerGameReport->points > 0 || $teamMatePlayerGameReport->points > 0)
                    {
                        $matchupResults[$teammateName]["won"]++;
                    }
                    else
                    {
                        $matchupResults[$teammateName]["lost"]++;
                    }
                    $matchupResults[$teammateName]["total"]++;
                }
            }

            uksort($matchupResults, fn($a, $b) => strcasecmp($a, $b)); // Case-insensitive alphabetical sort

            return $matchupResults;
        });
    }


    public function getClanPlayerWinLosses($clan, $history)
    {
        // Get clan games reports
        // Group by players and get their wins/losses breakdown
        return Cache::remember("getClanPlayerWinLosses/$history->short/$clan->id", 5 * 60, function () use ($clan, $history)
        {
            $clanGames = $clan->clanGames()->where('ladder_history_id', $history->id)->get();
            $results = [];
            foreach ($clanGames as $cgr)
            {
                $report = $cgr->gameReport->getPointReportByClan($cgr->clan_id);

                $playerReports = $cgr->gameReport->playerGameReports->where("clan_id", $cgr->clan_id);
                foreach ($playerReports as $pr)
                {
                    if ($report->won)
                    {
                        $results[$pr->player_id]["wins"][] = $report;
                    }
                    else
                    {
                        $results[$pr->player_id]["losses"][] = $report;
                    }
                }
            }

            $formattedResults = [];
            foreach ($results as $playerId => $report)
            {
                if (isset($report["wins"]))
                {
                    $report["wins"] = count($report["wins"]);
                }
                else
                {
                    $report["wins"] = 0;
                }
                if (isset($report["losses"]))
                {
                    $report["losses"] = count($report["losses"]);
                }
                else
                {
                    $report["losses"] = 0;
                }

                $report["total"] = $report["wins"] + $report["losses"];
                $formattedResults[$playerId] = $report;
            }
            return $formattedResults;
        });
    }
}
