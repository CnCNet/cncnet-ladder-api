<?php

namespace App\Http\Controllers;

use App\Commands\FindOpponent;
use App\Helpers\AIHelper;
use App\Helpers\GameHelper;
use App\Helpers\LeagueHelper;
use App\Helpers\SiteHelper;
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

    public function statsRequest(Request $request, string $ladderAbbrev, int $tierId = 1)
    {
        if ($ladderAbbrev == 'all')
        {
            $allStats = [];
            $ladders = Ladder::query()
                ->where('private', '=', false)
                ->with([
                    'current_history' => function ($q)
                    {
                        $q->with([
                            'queued_players'
                        ]);
                    },
                ])
                ->withCount([
                    'past_24_hours_matches',
                ])
                ->get();

            foreach ($ladders as $ladder)
            {
                $queuedPlayers = $ladder->current_history->queued_players->count();

                $results = [
                    'recentMatchedPlayers' => 0, # DEPRECATED
                    'past24hMatches' => $ladder->past_24_hours_matches_count,
                    'recentMatches' => 0, # DEPRECATED
                    'activeMatches' => 0, # DEPRECATED
                    'queuedPlayers' => $queuedPlayers,
                    'clans' => 0,
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

    private function getStats(Ladder|string $ladder, int $tierId = 1)
    {
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
     * This V2 function will return the data as json array, where each object in the array is an object.
     * V1 returns array of strings.
     */
    public function getActiveMatches(Request $request, string $ladderAbbrev)
    {
        $games = [];

        if ($ladderAbbrev == "all")
        {
            $ladders = Ladder::query()
                ->where('private', '=', false)
                ->with([
                    'current_history' => function ()
                    {
                    },
                    'sides',
                    'recent_spawned_matches',
                    'recent_spawned_matches.players',
                    'recent_spawned_matches.players.clan:id,short',
                    'recent_spawned_matches.players.player:id,user_id,username',
                    'recent_spawned_matches.players.player.user:id,twitch_profile',
                    'recent_spawned_matches.map',
                ])
                ->get();

            $ladders->each(function (Ladder $ladder) use (&$games)
            {
                $games[$ladder->abbreviation] = $this->getActiveMatchesByLadder($ladder);
            });
        }
        else
        {
            $ladder = Ladder::query()
                ->where('abbreviation', '=', $ladderAbbrev)
                ->with([
                    'current_history' => function ($q)
                    {
                    },
                    'sides',
                    'recent_spawned_matches',
                    'recent_spawned_matches.players',
                    'recent_spawned_matches.players.clan:id,short',
                    'recent_spawned_matches.players.player:id,user_id,username',
                    'recent_spawned_matches.players.player.user:id,twitch_profile',
                    'recent_spawned_matches.map',
                ])
                ->first();

            $games[$ladder->abbreviation] = $this->getActiveMatchesByLadder($ladder);
        }

        return $games;
    }

    private function getActiveMatchesByLadder(Ladder $ladder)
    {
        $sides = $ladder->sides->pluck('name', 'local_id')->toArray();

        if ($ladder == null)
        {
            abort(400, "Invalid ladder provided");
        }

        //get all recent QMs that whose games have spawned. (state_type_id == 5)
        $qms = $ladder->recent_spawned_matches;

        $games = [];

        foreach ($qms as $qm) //iterate over every active quick match
        {
            $dt = $qm->created_at;

            //get the player data pertaining to this quick match
            $qmPlayers = $qm->players;

            $playersData = [];
            if ($ladder->clans_allowed)
            {
                $playersData = $this->getActiveClanMatchesData($sides, $qmPlayers);
            }
            else if ($ladder->ladder_type == Ladder::TWO_VS_TWO) // 2v2
            {
                $playersData = $this->getTeamActivePlayerMatchesData($sides, $qmPlayers, $qm->created_at);
            }
            else
            {
                $playersData = $this->getActivePlayerMatchesData($sides, $qmPlayers, $qm->created_at);
            }

            $duration = Carbon::now()->diff($dt);
            $duration_formatted = $duration->format('%i mins %s sec');

            $games[] = [
                "ladderName" => $ladder->name,
                "ladderType" => $ladder->ladder_type,
                "players" => $playersData,
                "gameDuration" => $duration_formatted,
                "mapName" => trim($qm->map->description),
                "mapHash" => $qm->map->map->hash,
                "mapUrl" => SiteHelper::getMapPreviewUrlV2($ladder->game, $qm->map->map)
            ];
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
                $playersString .= '[' . $player->clan->short . ']' . $player->name . " (" . ($sides[$player->actual_side] ?? '') . ")";

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

    /**
     * @return an array containing every player's name and their faction
     */
    private function getActivePlayerMatchesData(array $sides, $qmPlayers, $created_at)
    {
        $dt = new DateTime($created_at);
        $showRealNames = abs(Carbon::now()->diffInSeconds($dt)) > 120;

        return collect($qmPlayers)
            ->values()
            ->map(function ($qmPlayer, $index) use ($sides, $showRealNames)
            {
                return [
                    "playerName" => $showRealNames ? $qmPlayer->player->username : "Player" . ($index + 1),
                    "playerFaction" => $sides[$qmPlayer->actual_side] ?? '',
                    "playerColor" => $qmPlayer->color,
                    "twitchProfile" => $qmPlayer->player->user->twitch_profile
                ];
            })
            ->all();
    }

    private function getTeamActivePlayerMatchesData(array $sides, $qmPlayers, $created_at)
    {
        $dt = new DateTime($created_at);
        $showRealNames = abs(Carbon::now()->diffInSeconds($dt)) > 90;

        return collect($qmPlayers)
            ->groupBy('team')
            ->flatten()
            ->values()
            ->map(function ($qmPlayer, $index) use ($sides, $showRealNames)
            {
                $useRealName = $showRealNames || $qmPlayer->team === "observer";
                $faction = $qmPlayer->team === "observer" ? "Observer" : $sides[$qmPlayer->actual_side] ?? '';
                return [
                    "teamId" => $qmPlayer->team,
                    "playerName" => $useRealName ? $qmPlayer->player->username : "Player" . ($index + 1),
                    "playerFaction" => $faction,
                    "playerColor" => $qmPlayer->color,
                    "twitchProfile" => $qmPlayer->player->user->twitch_profile
                ];
            })
            ->all();
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
