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
            $carbonDateSubHour = Carbon::now()->subHour();
            $carbonDateSub24Hours = Carbon::now()->subHours(24);

            $ladderId = $ladder->id;
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $recentMatchedPlayers = QmMatchPlayer::where('qm_match_players.created_at', '>', $carbonDateSubHour)
                ->where('ladder_id', '=', $ladderId)
                ->count();

            $clans = [];

            if ($history->ladder->clans_allowed)
            {
                $queuedClans = QmQueueEntry::join('qm_match_players', 'qm_match_players.id', '=', 'qm_queue_entries.qm_match_player_id')
                    ->where('ladder_history_id', $history->id)
                    ->whereNull('qm_match_id')
                    ->get();

                //count how many players are in queue for each clan
                foreach ($queuedClans as $queuedClan)
                {
                    $count = 1;
                    if (isset($clans[$queuedClan->clan_id]))
                    {
                        $count = $clans[$queuedClan->clan_id] + 1;
                    }
                    $clans[$queuedClan->clan_id] = $count;
                }

                // Groupby doesn't work with ->count()
                $queuedPlayersOrClans = count($clans);
            }
            else
            {
                $queuedPlayersOrClans = QmQueueEntry::join('qm_match_players', 'qm_match_players.id', '=', 'qm_queue_entries.qm_match_player_id')
                    ->where('ladder_history_id', $history->id)
                    ->whereNull('qm_match_id')
                    ->count();
            }

            $recentMatches = QmMatch::where('qm_matches.created_at', '>', $carbonDateSubHour)
                ->where('qm_matches.ladder_id', '=', $ladderId)
                ->count();

            $activeMatches = QmMatch::where('qm_matches.created_at', '>', $carbonDateSubHour)
                ->where('qm_matches.ladder_id', '=', $ladderId)
                ->where('qm_matches.updated_at', '>', Carbon::now()->subMinute(2))
                ->count();

            $past24hMatches = \App\Models\QmMatch::where('qm_matches.created_at', '>', $carbonDateSub24Hours)
                ->where('qm_matches.ladder_id', '=', $ladderId)
                ->count();

            $matchesByMonth = QmMatch::where("updated_at", ">", $startOfMonth)
                ->where("updated_at", "<", $endOfMonth)
                ->where("ladder_id", "=", $ladderId)
                ->count();

            return [
                "recentMatchedPlayers" => $recentMatchedPlayers,
                "queuedPlayers" => $queuedPlayersOrClans,
                "past24hMatches" => $past24hMatches,
                "recentMatches" => $recentMatches,
                "matchesByMonth" => $matchesByMonth,
                "activeMatches" => $activeMatches,
                "clans" => $clans,
                "time" => Carbon::now()
            ];
        });
    }

    public function getFactionsPlayedByPlayer($player, $history)
    {
        return Cache::remember("getFactionsPlayedByPlayer/$history->short/$player->id", 5 * 60, function () use ($player, $history)
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
        // return Cache::remember("getMapWinLossByPlayer/$history->short/$player->id", 5, function () use ($player, $history)
        // {
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
                "map" => $pg->game->map,
                "won" => $mapWins,
                "lost" => $mapLosses,
                "total" => $mapTotal
            ];
        }
        return $mapResults;
        // });
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

            $playerGameReports = $player->playerGames()
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->get();

            $matchupResults = [];
            foreach ($playerGameReports as $pgr)
            {
                if ($pgr->disconnected || $pgr->draw || $pgr->no_completion)
                    continue;

                $team = $pgr->team;

                if ($team == null && $ladderType == Ladder::TWO_VS_TWO)
                {
                    continue;
                }

                // get the opponents from this game
                $opponentReports = \App\Models\PlayerGameReport::join('players as p', 'player_game_reports.player_id', '=', 'p.id')
                    ->join('game_reports as gr', 'player_game_reports.game_report_id', '=', 'gr.id')->where('gr.game_id', $pgr->game_id)
                    ->where('p.id', '!=', $player->id)
                    ->where('gr.valid', true)
                    ->where('gr.best_report', true)
                    ->select('p.username', 'player_game_reports.team', 'player_game_reports.player_id', 'player_game_reports.game_id', 'player_game_reports.game_report_id')
                    ->get();

                foreach ($opponentReports as $opponentReport)
                {
                    if ($opponentReport == null)
                    {
                        continue;
                    }

                    $opponentTeam = $opponentReport->team;

                    if ($opponentTeam == null && $ladderType == Ladder::TWO_VS_TWO)
                    {
                        continue;
                    }

                    if ($ladderType == Ladder::TWO_VS_TWO && $opponentTeam == $team) // players on same team
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

                    if ($pgr->won)
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
                ->get();

            $matchupResults = [];

            foreach ($gameReports as $gameReport)
            {
                $allZeroPoints = !$gameReport->playerGameReports()
                    ->where('points', '!=', 0)
                    ->exists();
                if ($allZeroPoints)
                {
                    continue;
                }

                $myPlayerGameReport = $gameReport->playerGameReports()
                    ->where('draw', false)
                    ->where('player_game_reports.player_id', '=', $player->id) // get my player report
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

                // Get teammates
                $teamMatePlayerGameReports = $gameReport->playerGameReports()
                    ->join('players as p', 'player_game_reports.player_id', '=', 'p.id')
                    ->where('player_game_reports.team', '=', $team) // my teammate(s)
                    ->where('player_game_reports.id', '!=', $myPlayerGameReport->id) // ignore my report
                    ->select('p.username', 'player_game_reports.won', 'player_game_reports.team', 'player_game_reports.id', 'player_game_reports.game_id')
                    ->get();

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

                    $teammateName = $teamMatePlayerGameReport->username;

                    // Initialize matchup stats if not already set
                    $matchupResults[$teammateName] ??= ["won" => 0, "lost" => 0, "total" => 0];

                    // Update win/loss count
                    if ($myPlayerGameReport->won || $teamMatePlayerGameReport->won)
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
