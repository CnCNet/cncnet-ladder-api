<?php

namespace App\Http\Controllers;

use App\Commands\FindOpponent;
use App\Helpers\AIHelper;
use App\Helpers\GameHelper;
use App\Helpers\LeagueHelper;
use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Http\Services\QuickMatchService;
use App\Http\Services\QuickMatchSpawnService;
use App\Http\Services\StatsService;
use App\Models\ClanCache;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\MapPool;
use App\Models\PlayerCache;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use App\Models\QmUserId;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiQuickMatchController extends Controller
{
    private $ladderService;
    private $playerService;
    private $quickMatchService;
    private $quickMatchSpawnService;
    private $statsService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->playerService = new PlayerService();
        $this->quickMatchService = new QuickMatchService();
        $this->quickMatchSpawnService = new QuickMatchSpawnService();
        $this->statsService = new StatsService();
    }

    public function clientVersion(Request $request, $platform = null)
    {
        return json_encode(DB::table("client_version")->where("platform", $platform)->first());
    }

    public function statsRequest(Request $request, string $ladderAbbrev = null, int $tierId = 1)
    {
        if ($ladderAbbrev == 'all')
        {
            $allStats = [];
            $ladders = \App\Models\Ladder::query()
                ->where('private', '=', false)
                ->with([
                    'current_history' => function($q) {
                        $q->with([
                            'queued_players'
                        ]);
                    },
                ])
                ->withCount([
                    'recent_matched_players',
                    'recent_matches',
                    'active_matches',
                    'past_24_hours_matches',
                ])
                ->get();

            foreach ($ladders as $ladder)
            {
                $clans = [];
                if ($ladder->clans_allowed)
                {
                    $groupedByClans = $ladder->current_history->queued_players->groupBy('clan_id');
                    $queuedPlayersOrClans = $groupedByClans->count();
                    $clans = $groupedByClans->map->count();
                }
                else
                {
                    $queuedPlayersOrClans = $ladder->current_history->queued_players->count();
                }
                $results = [
                    'recentMatchedPlayers' => $ladder->recent_matched_players_count,
                    'past24hMatches' => $ladder->past_24_hours_matches_count,
                    'recentMatches' => $ladder->recent_matches_count,
                    'activeMatches' => $ladder->active_matches_count,
                    'queuedPlayers' => $queuedPlayersOrClans,
                    'clans' => $clans,
                    'time' => now(),
                ];
                $allStats[$ladder->abbreviation] = $results;
            }

            return $allStats;
        } 
        else 
        {
            return $this->getStats($ladderAbbrev, $tierId);
        }
    }

    private function getStats(Ladder|string $ladder, int $tierId = 1) {
        $ladder = is_string($ladder) ? $this->ladderService->getLadderByGame($ladder) : $ladder;
        $history = $ladder->current_history;
        $qmStats = $this->statsService->getQmStats($history, $tierId);

        return [
            'recentMatchedPlayers' => $qmStats['recentMatchedPlayers'],
            'queuedPlayers' => $qmStats['queuedPlayers'],
            'past24hMatches' => $qmStats['past24hMatches'],
            'recentMatches' => $qmStats['recentMatches'],
            'activeMatches'   => $qmStats['activeMatches'],
            'clans' => $qmStats['clans'],
            'time' => $qmStats['time']
        ];
    }

    /**
     * Fetch details about games thare are currently in match
     */
    public function getActiveMatches(Request $request, $ladderAbbrev = null)
    {
        $games = [];
        if ($ladderAbbrev == "all")
        {
            $ladders = \App\Models\Ladder::query()
                ->where('private', '=', false)
                ->with([
                    'current_history' => function($q) {
                    },
                ])
                ->with([
                    'sides',
                    'recent_spawned_matches',
                    'recent_spawned_matches.players',
                    'recent_spawned_matches.players.clan:id,short',
                    'recent_spawned_matches.players.player:id,username',
                    'recent_spawned_matches.map',
                ])
                ->get();

            foreach ($ladders as $ladder)
            {
                $results = $this->getActiveMatchesByLadder($ladder);

                foreach ($results as $key => $val)
                {
                    $games[$ladder->abbreviation][$key] = $val;
                }
            }
        }
        else
        {
            $results = $this->getActiveMatchesByLadder($ladderAbbrev);

            foreach ($results as $key => $val)
            {
                $games[$ladderAbbrev][$key] = $val;
            }
        }

        return $games;
    }

    private function getActiveMatchesByLadder(Ladder|string $ladder)
    {
        $ladder = is_string($ladder) ? $this->ladderService->getLadderByGame($ladder) : $ladder;
        $sides = $ladder->sides->pluck('name', 'local_id')->toArray();

        if ($ladder == null)
            abort(400, "Invalid ladder provided");

        //get all recent QMs that whose games have spawned. (state_type_id == 5)
        $qms = $ladder->recent_spawned_matches;

        $games = [];

        foreach ($qms as $qm) //iterate over every active quick match
        {
            $map = trim($qm->map->description);
            $dt = $qm->created_at;

            //get the player data pertaining to this quick match
            $players = $qm->players;

            $playersString = "";
            if ($ladder->clans_allowed)
            {
                $playersString = $this->getActiveClanMatchesData($sides, $players);
            }
            else if ($ladder->ladder_type == \App\Models\Ladder::TWO_VS_TWO) // 2v2
            {
                $playersString = $this->getTeamActivePlayerMatchesData($sides, $players, $qm->created_at);
            }
            else
            {
                $playersString = $this->getActivePlayerMatchesData($sides, $players, $qm->created_at);
            }

            $duration = Carbon::now()->diff($dt);
            $duration_formatted = $duration->format('%i mins %s sec');
            $games[] = $playersString . " on " . $map . " (" . $duration_formatted . ")";
        }

        return $games;
    }

    private function getActiveClanMatchesData($sides, $players)
    {
        $clans = [];

        //put each player data in appropriate clan array
        foreach ($players as $player)
        {
            if (count($clans) == 0)
            {
                $clans[$player->clan_id][] = $player;
                continue;
            }

            if (in_array($player->clan_id, $clans))
            {
                $clans[$player->clan_id][] = $player;
                continue;
            }
            else
            {
                $clans[$player->clan_id][] = $player;
                continue;
            }
        }

        $playersString = "";

        $j = 0;
        foreach ($clans as $clanId => $players)
        {
            $i = 0;
            foreach ($players as $player)
            {
                $playersString .= '['. $player->clan->short .']' . $player->name . " (" . ($sides[$player->actual_side] ?? '') . ")";

                if ($i < count($players) - 1)
                    $playersString .= " and ";

                $i++;
            }

            if ($j < count($clans) - 1)
                $playersString .= " vs ";

            $j++;
        }

        return $playersString;
    }

    private function getActivePlayerMatchesData($sides, $players, $created_at)
    {
        $playersString = "";
        $dt = new DateTime($created_at);
        for ($i = 0; $i < count($players); $i++)
        {
            $player = $players[$i];
            $playerName = "Player" . ($i + 1);
            if (abs(Carbon::now()->diffInSeconds($dt)) > 120) //only show real player name if 2mins has passed
                $playerName = $player->player->username;

            $playersString .= $playerName . " (" . ($sides[$player->actual_side] ?? '') . ")";

            if ($i < count($players) - 1)
                $playersString .= " vs ";
        }
        return $playersString;
    }

    /**
     * returns a 'pretty' message describing the players on each team
     * 
     * should probably return a json array with the data but we are where we are
     */
    private function getTeamActivePlayerMatchesData($sides, $players, $created_at) // TODO will this logic work for clan ladder?
    {
        $playersString = "";
        $dt = new DateTime($created_at);
        $teams = [];

        foreach ($players as $player)
        {
            $teams[$player->team][] = $player;
        }

        $teamCount = 0;
        $playerNum = 1;
        foreach ($teams as $teamId => $teammates)
        {
            $teammateNum = 0;
            foreach ($teammates as $player)
            {
                $playerName = "Player" . ($playerNum + 1);
                if (abs(Carbon::now()->diffInSeconds($dt)) > 60) //only show real player name if 1 min has passed
                { 
                    $playerName = $player->player->username;
                }
                $playersString .= $playerName . " (" . ($sides[$player->actual_side] ?? '') . ")";

                if ($teammateNum < count($teammates) - 1) // if not last player on the team append ' and '
                    $playersString .= " and ";

                $teammateNum++;
                $playerNum++;
            }

            if ($teamCount < count($teams) - 1) // if not last team append ' vs '
                $playersString .= " vs ";

            $teamCount++;
        }

        return $playersString;
    }

    public function mapListRequest(Request $request, $ladderAbbrev = null)
    {
        return \App\Models\QmMap::findMapsByLadder($this->ladderService->getLadderByGame($ladderAbbrev)->id);
    }

    public function sidesListRequest(Request $request, $ladderAbbrev = null)
    {
        $ladder = $this->ladderService->getLadderByGame($ladderAbbrev);
        $rules = $ladder->qmLadderRules()->first();
        $allowed_sides = $rules->allowed_sides();
        $sides = $ladder->sides()->get();

        return $sides->filter(function ($side) use (&$allowed_sides)
        {
            return in_array($side->local_id, $allowed_sides);
        });
    }


    /**
     * Called by cron service only
     */
    public function prunePlayersInQueue()
    {
        $queuedPlayers = QmQueueEntry::all();
        $now = Carbon::now();

        foreach ($queuedPlayers as $queuedPlayer)
        {
            $secondsSinceQMClientTouch = $queuedPlayer->updated_at->diffInSeconds($now->copy());

            # QM client calls API calls every 10-15 seconds when in queue, cron called every minute
            if ($secondsSinceQMClientTouch > 45)
            {
                try
                {
                    $player = $queuedPlayer->qmPlayer->player;
                    $timeInQueue = $queuedPlayer->created_at->diffInSeconds($queuedPlayer->updated_at);
                    $qmId = $queuedPlayer->qmPlayer->id;
                    Log::info("Removing player from queue due to inactivity: $player, qmId=$qmId, time in queue: $timeInQueue (secs)");
                }
                catch (Exception $ex)
                {
                    Log::info("Failed removing player from queue due to inactivity: $queuedPlayer");
                }

                $queuedPlayer->delete();
            }
        }
    }

    private function checkQMClientRequiresUpdate($ladder, $version)
    {
        # YR Games check
        if ($ladder->game == "yr")
        {
            if ($version < 1.79)
            {
                return true;
            }

            return false;
        }

        # RA/TS Games check
        if ($ladder->game == "ra" || $ladder->game == "ts")
        {
            if ($version < 1.69)
            {
                return true;
            }

            return false;
        }

        return false;
    }


    public function getPlayerRankingsByLadder(Request $request, $ladderAbbrev)
    {
        $ladder = Ladder::where("abbreviation", $ladderAbbrev)->first();
        if ($ladder == null)
        {
            abort(404);
        }

        $rankings = [];
        $tier = $request->tier == 2 ? 2 : 1;

        $history = $ladder->currentHistory();

        if ($history->ladder->clans_allowed)
        {
            $rankings = ClanCache::where("ladder_history_id", "=", $history->id)
                ->where("clan_name", "like", "%" . $request->search . "%")
                ->orderBy("points", "desc")
                ->limit(200)
                ->get();
        }
        else
        {
            $rankings = PlayerCache::where("ladder_history_id", "=", $history->id)
                ->where("tier", "=", $tier)
                ->where("player_name", "like", "%" . $request->search . "%")
                ->orderBy("points", "desc")
                ->limit(200)
                ->get();
        }

        return [
            "ladder" => $ladder,
            "rankings" => $rankings
        ];
    }

    public function getPlayerRankings(Request $request, $count = 50)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->format('Y');

        $rankings = [];
        $tier = $request->tier == 2 ? 2 : 1;

        foreach ($this->ladderService->getLadders() as $ladder)
        {
            $history = \App\Models\LadderHistory::where('short', '=', $month . "-" . $year)
                ->where('ladder_id', $ladder->id)
                ->first();

            if ($history == null)
                continue;

            $pc = \App\Models\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->join('players as p', 'player_caches.player_id', '=', 'p.id')
                ->join('users as u', 'p.user_id', '=', 'u.id')
                ->where("tier", "=", $tier)
                ->orderBy('player_caches.points', 'DESC')
                ->select('u.discord_profile as discord_name', 'player_caches.*')
                ->limit($count)
                ->orderBy('points', 'DESC')
                ->get();

            $rankings[strtoupper($ladder->abbreviation)] = $pc;
        }

        return $rankings;
    }

    public function getErroredGames($ladderAbbrev)
    {
        $ladder = \App\Models\Ladder::where('abbreviation', $ladderAbbrev)->first();

        if ($ladder == null)
            return "Bad ladder abbreviation " . $ladderAbbrev;

        $ladderHistory = $ladder->currentHistory();

        $numErroredGames = \App\Models\Game::join('game_reports', 'games.game_report_id', '=', 'game_reports.id')
            ->where("ladder_history_id", "=", $ladderHistory->id)
            ->where('game_reports.duration', '<=', 3)
            ->where('finished', '=', 1)
            ->get()->count();

        $url = \App\Models\URLHelper::getLadderUrl($ladderHistory) . '/games?errorGames=true';

        $data = [];
        $data["url"] = "https://ladder.cncnet.org" . $url;
        $data["count"] = $numErroredGames;

        return $data;
    }

    public function getRecentLadderWashedGamesCount($ladderAbbrev, $hours)
    {
        $ladder = \App\Models\Ladder::where("abbreviation", $ladderAbbrev)->first();

        if ($ladder == null)
            return "Bad ladder abbreviation " . $ladderAbbrev;

        $ladderHistory = $ladder->currentHistory();

        $start = Carbon::now()->subHour($hours);

        $gameAuditsCount = \App\Models\GameAudit::where("created_at", ">=", $start)
            ->where("ladder_history_id", $ladderHistory->id)
            ->where("username", "ladder-auto-wash")
            ->count();

        $url = \App\Models\URLHelper::getWashGamesUrl($ladderAbbrev);

        $data = [];
        $data["count"] = $gameAuditsCount;
        $data["url"] = "https://ladder.cncnet.org" . $url;

        return $data;
    }
}
