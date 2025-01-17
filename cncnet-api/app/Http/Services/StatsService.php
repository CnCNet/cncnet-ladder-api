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
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
        return Cache::remember("getPlayerMatchups/$history->short/$player->id", 5 * 60, function () use ($player, $history)
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
                if ($team == null)
                {
                    $team = $pgr?->gameReport?->game?->qmMatch?->findQmPlayerByPlayerId($pgr->player_id)?->team;
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
                    if ($opponentTeam == null)
                    {
                        $opponentTeam = $opponentReport?->gameReport?->game?->qmMatch?->findQmPlayerByPlayerId($opponentReport->player_id)?->team;
                    }

                    if ($team != null && $opponentTeam != null && $opponentTeam == $team) // players on same team
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
        return Cache::remember("getTeamMatchups/$history->short/$player->id", 5 * 60, function () use ($player, $history)
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
                {
                    continue;
                }

                $team = $pgr->team;
                if ($team == null)
                {
                    $team = $pgr->gameReport->game->qmMatch->findQmPlayerByPlayerId($pgr->player_id)?->team;
                }

                // get the opponents from this game
                $otherReports = \App\Models\PlayerGameReport::join('players as p', 'player_game_reports.player_id', '=', 'p.id')
                    ->join('game_reports as gr', 'player_game_reports.game_report_id', '=', 'gr.id')->where('gr.game_id', $pgr->game_id)
                    ->where('p.id', '!=', $player->id)
                    ->where('gr.valid', true)
                    ->where('gr.best_report', true)
                    ->select('p.username', 'player_game_reports.team', 'player_game_reports.player_id', 'player_game_reports.game_id', 'player_game_reports.game_report_id')
                    ->get();

                foreach ($otherReports as $otherReport)
                {
                    if ($otherReport == null)
                    {
                        continue;
                    }

                    $opponentTeam = $otherReport->team;
                    if ($opponentTeam == null)
                    {
                        $opponentTeam = $otherReport->gameReport->game->qmMatch->findQmPlayerByPlayerId($otherReport->player_id)?->team;
                    }

                    if ($team != null && $opponentTeam != null && $opponentTeam != $team) // players on same team
                    {
                        continue;
                    }

                    $opponentName = $otherReport->username;

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
