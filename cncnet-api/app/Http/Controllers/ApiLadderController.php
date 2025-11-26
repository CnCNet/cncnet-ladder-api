<?php

namespace App\Http\Controllers;

use App\Http\Services\AdminService;
use App\Http\Services\ClanService;
use App\Http\Services\DuneGameService;
use App\Http\Services\EloService;
use App\Http\Services\GameService;
use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Http\Services\PointService;
use App\Jobs\Qm\SaveLadderResultJob;
use App\Models\Achievement;
use App\Models\AchievementProgress;
use App\Models\Game;
use App\Models\GameClip;
use App\Models\GameObjectCounts;
use App\Models\GameRaw;
use App\Models\GameReport;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\PlayerCache;
use App\Models\PlayerGameReport;
use App\Models\QmMap;
use App\Models\QmMatchPlayer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiLadderController extends Controller
{
    private $ladderService;
    private $gameService;
    private $playerService;
    private $clanService;
    private $adminService;
    private $pointService;
    private $duneGameService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->gameService = new GameService();
        $this->duneGameService = new DuneGameService();
        $this->playerService = new PlayerService();
        $this->clanService = new ClanService();
        $this->adminService = new AdminService();
        $this->pointService = new PointService();
    }

    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function newPostLadder(Request $request, $ladderId, $gameId, $playerId, $pingSent, $pingReceived)
    {
        $ladder = Ladder::find($ladderId);
        $player = Player::find($playerId);

        // Player checks
        $check = $this->ladderService->checkPlayer($request, $player->username, $ladder);
        if ($check !== null)
        {
            return $check;
        }

        $filePath = config('filesystems')['dmp'];
        $fileName = $gameId . '.' . $ladderId . '.' . $playerId . '.dmp';
        $file = $request->file('file')->move($filePath, $fileName);

        $this->dispatch(new SaveLadderResultJob($filePath . '/' . $fileName, $ladderId, $gameId, $playerId, $pingSent, $pingReceived));

        return response()->json(['success' => 'Queued for processing.'], 200);
    }

    public function reprocessTeamPointsByGameId(Request $request)
    {
        $game = Game::find($request->gameId);
        $history = $game->ladderHistory;
        // dd($game->report);
        // dd($game->allReports);
        return $this->awardTeamPoints($game->report, $history);
    }

    public function testStatsDump(Request $request)
    {
        $ladder = Ladder::find(15);

        $result = $this->gameService->processStatsDmp($request->file, $ladder->game, $ladder);
        dd($result);
    }

    public function saveVideoClip(Request $request)
    {
        $user = $request->user();

        try
        {
            $request->validate([
                'file' => 'required|mimes:mp4,webm|max:20480', // max 20MB
                'wol_game_id' => 'required|string',
            ]);

            // Get the uploaded file
            if ($request->hasFile('file'))
            {
                $clipFilename = $this->gameService->uploadGameClip($request);

                $wolGameId = $request->input('wol_game_id');
                Log::info("WolGameId:$wolGameId");
                $userPlayerIds = $user->usernames->pluck("id");
                $game = Game::where("wol_game_id", $wolGameId)->first();
                $playerFromGame = PlayerGameReport::where("game_id", $game->id)->whereIn("player_id", $userPlayerIds)->first();
                $gameClip = $this->gameService->saveGameClip(
                    $game->id,
                    $playerFromGame->player_id,
                    $user->id,
                    $clipFilename
                );

                return response()->json([
                    'message' => 'File uploaded successfully',
                    'game_id' => $game->id,
                ], 200);
            }
        }
        catch (Exception $ex)
        {
            Log::info("Error uploading game clip: " . $ex->getMessage() . " : " . $ex->getTraceAsString());
        }

        return response()->json(['message' => 'File not uploaded'], 400);
    }

    public function saveLadderTestOnly(Request $request)
    {
        Log::info($request->game_id);
        Log::info($request->access_token);
        Log::info(json_encode($request));
        die();

        $ladder = Ladder::find(15);
        $result = $this->gameService->processStatsDmp($request->file, $ladder->game, $ladder);
        dd($result["IDNO"]["value"]);
        // $ladderId = 14; // d2k
        $ladderId = 15; // yr
        $gameId = $request->gameId;
        $playerId = $request->playerId; // kipp
        $pingSent = $request->pingSent;
        $pingReceived = $request->pingReceived;

        return $this->saveLadderResult($request->file, $ladderId, $gameId, $playerId, $pingSent, $pingReceived);
    }

    public function saveLadderResult($file, $ladderId, $gameId, $playerId, $pingSent, $pingReceived)
    {
        $ladder = Ladder::find($ladderId);
        $player = Player::find($playerId);
        $game = Game::find($gameId);

        # Game stats result
        $result = $this->gameService->processStatsDmp($file, $ladder->game, $ladder);
        if (count($result) == 0 || $result == null)
        {
            return response()->json(['No data'], 400);
        }

        $history = $game->ladderHistory;

        # Keep a record of the raw stats sent in
        $this->gameService->saveRawStats($result, $game->id, $history->id);
        if ($ladder->abbreviation == "d2k")
        {
            $this->duneGameService->fillGameCols($game, $result);
            $result = $this->duneGameService->saveGameStats($result, $game->id, $player->id, $ladder, $ladder->game);
        }
        else
        {
            $this->gameService->fillGameCols($game, $result);
            $result = $this->gameService->saveGameStats($result, $game->id, $player->id, $ladder, $ladder->game);
        }

        $gameReport = $result['gameReport'];

        if ($gameReport === null)
        {
            return response()->json(['Error' => $result['error']], 400);
        }

        $gameReport->pings_sent = $pingSent;
        $gameReport->pings_received = $pingReceived;
        $gameReport->save();

        # Award points
        if ($history->ladder->ladder_type == \App\Models\Ladder::CLAN_MATCH) // clan match
        {
            $status = $this->awardClanPoints($gameReport, $history);
        }
        else if ($history->ladder->ladder_type == \App\Models\Ladder::TWO_VS_TWO) // 2vs2
        {
            $status = $this->awardTeamPoints($gameReport, $history);
        }
        else // 1vs1
        {
            $status = $this->awardPlayerPoints($gameReport, $history);
        }

        # Dispute handling
        $this->handleGameDispute($gameReport);

        # Achievements
        $stats = $gameReport->playerGameReports()->where('player_id', $playerId)->first()->stats;

        if ($ladderId == 8 || $ladderId == 1) //toggle achievements on for Blitz and YR
            $this->updateAchievements($playerId, $history->ladder, $stats);

        // Toggling off auto-wash, TODO only wash games where both players submitted a game report.
        // If only one player submitted a game report, don't wash

        // if ($gameReport->best_report == true && $gameReport->duration == 3 && $gameReport->fps == 33)
        // $this->adminService->doWashGame($gameReport->game_id, "ladder-auto-wash");

        return response()->json(['success' => $status], 200);
    }

    public function handleGameDispute($gameReport)
    {
        $game = $gameReport->game()->first();

        if ($game->game_report_id == $gameReport->id)
        {
            $this->ladderService->updateCache($gameReport);
            return;
        }

        $allReports = $game->allReports()->get();

        $bestReport = $game->report()->first();

        // If we're not the best report and the best report is disconnected
        // I'm disconnected then we wash the game
        if (($bestReport->disconnected() && $gameReport->disconnected())
            ||
            ($bestReport->oos && $gameReport->oos)
        )
        {
            $wash = new GameReport();
            $wash->game_id = $gameReport->game_id;
            $wash->player_id = $gameReport->player_id;
            $wash->best_report = false;
            $wash->manual_report = true;
            $wash->duration = $gameReport->duration;
            $wash->valid = true;
            $wash->finished = false;
            $wash->fps = $gameReport->fps;
            $wash->oos = false;
            $wash->save();

            foreach ($gameReport->playerGameReports()->get() as $pgr)
            {
                $playerGR = new PlayerGameReport();
                $playerGR->game_report_id = $wash->id;
                $playerGR->game_id = $pgr->game_id;
                $playerGR->player_id = $pgr->player_id;
                $playerGR->local_id = $pgr->local_team_id;
                $playerGR->local_team_id = $pgr->local_team_id;
                $playerGR->points = 0;
                $playerGR->disconnected = true;
                $playerGR->no_completion = false;
                $playerGR->quit = false;
                $playerGR->won = false;
                $playerGR->defeated = false;
                $playerGR->draw = true;
                $playerGR->spectator = $pgr->spectator;
                $playerGR->save();
            }

            if (($gameReport->pings_sent - $gameReport->pings_received + 5)
                <
                $bestReport->pings_sent - $bestReport->pings_received
            )
            {
                $bestReport->best_report = false;
                $gameReport->best_report = true;
                $game->game_report_id = $gameReport->id;
                $game->save();
                $gameReport->save();
                $bestReport->save();
                $this->ladderService->undoCache($bestReport);
                $this->ladderService->updateCache($gameReport);
                return;
            }
            else if ($gameReport->pings_sent - $gameReport->pings_received < 7)
            {
                $bestReport->best_report = false;
                $wash->best_report = true;
                $game->game_report_id = $wash->id;
                $game->save();
                $wash->save();
                $bestReport->save();
                $this->ladderService->undoCache($bestReport);
                $this->ladderService->updateCache($wash);
                return;
            }

            return;
        }

        // Prefer the report who saw the end of the game
        if ($gameReport->finished && (!$bestReport->finished || $bestReport->quit))
        {
            $bestReport->best_report = false;
            $gameReport->best_report = true;
            $game->game_report_id = $gameReport->id;
            $game->save();
            $gameReport->save();
            $bestReport->save();
            $this->ladderService->undoCache($bestReport);
            $this->ladderService->updateCache($gameReport);
            return;
        }

        // Prefer the longer game
        if ($bestReport->duration + 5 < $gameReport->duration)
        {
            $bestReport->best_report = false;
            $gameReport->best_report = true;
            $game->game_report_id = $gameReport->id;
            $game->save();
            $gameReport->save();
            $bestReport->save();
            $this->ladderService->undoCache($bestReport);
            $this->ladderService->updateCache($gameReport);
            return;
        }
    }

    public function awardClanPoints($gameReport, $history)
    {
        $clanRatings = [];
        $clanGameReports = collect();

        // Find the winning clan report
        $winningClanReport = $gameReport->playerGameReports()->where('won', 1)->where('spectator', 0)->groupBy("clan_id")->first();

        if ($winningClanReport != null)
        {
            $clanGameReports->push($winningClanReport);
        }

        // Find the losing clan report
        if ($winningClanReport)
        {
            $losingClanReport = $gameReport->playerGameReports()->where('clan_id', '!=', $winningClanReport->clan_id)
                ->where(function ($query)
                {
                    $query->where('won', 0);
                    $query->orWhere('disconnected', 1);
                })
                ->groupBy("clan_id")
                ->first();

            if ($losingClanReport != null)
            {
                $clanGameReports->push($losingClanReport);
            }
        }

        // Oops we don't have any players
        if ($clanGameReports->count() < 1)
        {
            return 604;
        }

        if ($gameReport->fps < $history->ladder->qmLadderRules->bail_fps)
        {
            // FPS too low, no points awarded
            return 630;
        }

        if ($gameReport->duration < $history->ladder->qmLadderRules->bail_time)
        {
            // Duration too low, no points awarded
            return 660;
        }

        foreach ($clanGameReports as $clanGameReport)
        {
            $allyRatingAverage = 0;
            $allyPoints = 0;
            $allyCount = 0;
            $enemyRatingAverage = 0;
            $enemyPoints = 0;
            $enemyCount = 0;
            $enemyGames = 0;

            foreach ($clanGameReports as $cgr)
            {
                $otherClanId = $cgr->clan_id;
                $otherClanRatingModel = $this->clanService->findClanRatingById($otherClanId);
                $gameId = $cgr->game_id;

                $clanRatings[] = $otherClanRatingModel;

                if ($otherClanId == $clanGameReport->clan_id)
                {
                    $allyRatingAverage += $otherClanRatingModel->rating;
                    $allyPoints += $cgr->clan->pointsBefore(
                        $history,
                        $gameId,
                        $otherClanId
                    );
                    $allyCount++;
                }
                else
                {
                    $enemyRatingAverage += $otherClanRatingModel->rating;
                    $enemyPoints += $cgr->clan->pointsBefore(
                        $history,
                        $gameId,
                        $otherClanId
                    );
                    $enemyCount++;
                    $enemyGames += $cgr->clan->totalGames($history);
                }
            }

            $allyRatingAverage /= $allyCount;
            $enemyRatingAverage /= $enemyCount;

            $eloK = $this->clanService->getEloKvalue($clanRatings);
            $wolK = $history->ladder->qmLadderRules->wol_k;
            $useEloPoints = $history->ladder->qmLadderRules->use_elo_points;
            $isBestReport = $gameReport->best_report;

            $this->clanService->awardPointsByClanRating(
                $clanGameReport,
                $enemyRatingAverage,
                $enemyPoints,
                $enemyGames,
                $allyRatingAverage,
                $allyPoints,
                $useEloPoints,
                $isBestReport,
                $eloK,
                $wolK
            );

            // Get correct cache type
            $cache = null;
            if ($clanGameReport->player->clanPlayer)
            {
                $cache = $clanGameReport->player->clanPlayer->clanCache($history->id);
            }

            if ($clanGameReport->points < 0 && ($cache === null || $cache->points < 0))
            {
                $clanGameReport->points = 0;
            }

            $clanGameReport->save();
        }

        return 200;
    }


    /**
     * Determine the winning team from a collection of player game reports.
     * Falls back to using a non-defeated player’s team if no player is marked as 'won'.
     *
     * @param \Illuminate\Support\Collection|\App\Models\PlayerGameReport[] $playerGameReports
     * @return string|null The winning team, or null if none found.
     */
    public function getWinningTeamFromReports($playerGameReports): ?string
    {
        foreach ($playerGameReports as $pgr)
        {
            if ($pgr->won && $pgr->spectator == false)
            {
                return $pgr->team;
            }
        }

        // Step 2: Fallback — no winner marked; use a non-defeated player (disconnected game case)
        // Have observed matches where disconnected=true on players who did not disconnect, but were in a dc'd game due to another player dc'ing
        foreach ($playerGameReports as $pgr)
        {
            if (!$pgr->defeated && $pgr->spectator == false)
            {
                Log::info("Fallback to 'defeated' logic for disconnected game.", [
                    'game_id' => $pgr->game_id,
                    'game_report_id' => $pgr->gameReport->id,
                    'player_id' => $pgr->player_id,
                    'team' => $pgr->team,
                    'defeated' => $pgr->defeated,
                    'disconnected' => $pgr->disconnected,
                ]);
                return $pgr->team;
            }
        }

        // No winning team found
        return null;
    }


    /**
     *
     * @param GameReport $gameReport
     * @param LadderHistory $history
     * @return int Error code
     */
    public function awardTeamPoints(GameReport $gameReport, LadderHistory $history)
    {
        $players = [];

        $playerGameReports = $gameReport->playerGameReports()->get();

        // Oops we don't have any players
        if ($playerGameReports->count() < 1)
        {
            return 604;
        }

        if ($gameReport->fps < $history->ladder->qmLadderRules->bail_fps)
        {
            // FPS too low, no points awarded
            return 630;
        }

        if ($gameReport->duration < $history->ladder->qmLadderRules->bail_time)
        {
            // Duration too low, no points awarded
            return 660;
        }

        $disconnected = 0;

        // determine which team won
        $winningTeam = $this->getWinningTeamFromReports($playerGameReports);

        foreach ($playerGameReports as $playerGR)
        {
            if ($playerGR->spectator == true)
            {
                continue;
            }

            $ally_average = 0;
            $ally_points = 0;
            $ally_count = 0;
            $enemy_average = 0;
            $enemy_points = 0;
            $enemy_count = 0;
            $enemy_games = 0;

            // grab the qm players that belong to this qm match, return the team of the current player
            $myTeam = $playerGR->team;

            $playerGRTeamWonTheGame = $myTeam == $winningTeam;

            // gather points from teammates and enemies, strength of team vs enemy will factor in points awarded/lost
            foreach ($playerGameReports as $otherPlayerGameReport)
            {
                if ($otherPlayerGameReport->spectator == true)
                {
                    continue;
                }

                $other = $this->playerService->findUserRatingByPlayerId($otherPlayerGameReport->player_id);
                $players[] = $other;

                $otherTeam = $otherPlayerGameReport->team;
                if ($otherTeam == $myTeam)
                {
                    $ally_average += $other->rating;
                    $ally_points += $otherPlayerGameReport->player->pointsBefore($history, $otherPlayerGameReport->game_id);
                    $ally_count++;
                }
                else
                {
                    $enemy_average += $other->rating;
                    $enemy_points += $otherPlayerGameReport->player->pointsBefore($history, $otherPlayerGameReport->game_id);
                    $enemy_count++;
                    $enemy_games += $otherPlayerGameReport->player->totalGames($history);
                }
            }

            $ally_average /= $ally_count;
            $enemy_average /= $enemy_count;
            $elo_k = $this->playerService->getEloKvalue($players);
            $points = null;
            $base_rating = $enemy_average > $ally_average ? $enemy_average : $ally_average;

            $gvc = 8;
            if ($history->ladder->qmLadderRules->use_elo_points)
            {
                $gvc = ceil(($base_rating * $enemy_average) / 230000);
            }

            $diff = $enemy_points - $ally_points;
            $we = 1 / (pow(10, abs($diff) / 600) + 1);
            if (($diff > 0 && $playerGRTeamWonTheGame) || ($diff < 0 && !$playerGRTeamWonTheGame))
            {
                $we = 1 - $we;
            }

            $wol_k = $history->ladder->qmLadderRules->wol_k;
            $wol = (int)($wol_k * $we);

            $eloAdjust = 0;

            if ($playerGR->draw || $winningTeam === null) // draw or couldn't find a winner
            {
                $playerGR->points = 0;

                Log::info("No points awarded due to draw or missing winning team.", [
                    'game_id'    => $playerGR->game_id,
                    'player_id'  => $playerGR->player_id,
                    'username'   => optional($playerGR->player)->username,
                    'draw'       => $playerGR->draw,
                    'team'       => $playerGR->team,
                    'winning_team' => $winningTeam,
                ]);
            }
            else if ($playerGRTeamWonTheGame)
            {
                $points = (new EloService(16, $ally_average, $enemy_average, 1, 0))->getNewRatings()["a"];
                $diff = (int)($points - $ally_average);
                if (!$history->ladder->qmLadderRules->use_elo_points)
                {
                    $diff = 0;
                }

                $playerGR->points = $gvc + $diff + $wol;

                $eloAdjust = new EloService($elo_k, $ally_average, $enemy_average, 1, 0);

                if ($gameReport->best_report)
                {
                    $this->playerService->updateUserRating($playerGR->player_id, $eloAdjust->getNewRatings()["a"]);
                }
            }
            else
            {
                if ($enemy_games < 10)
                {
                    $wol = (int)($wol * ($enemy_games / 10));
                }
                if ($ally_points  < ($wol + $gvc) * 10)
                {
                    $playerGR->points = -1 * (int)($ally_points / 10);
                }
                else
                {
                    $playerGR->points = -1 * ($wol + $gvc);
                }

                $eloAdjust = new EloService($elo_k, $ally_average, $enemy_average, 0, 1);
                if ($gameReport->best_report)
                {
                    $this->playerService->updateUserRating(
                        $playerGR->player_id,
                        $eloAdjust->getNewRatings()["a"]
                    );
                }
            }

            // Get correct cache type
            $cache = $playerGR->player->playerCache($history->id);

            // Prevent players from going below zero total points
            if ($cache !== null && ($cache->points + $playerGR->points) < 0)
            {
                // Cap the loss: set game points to negative of current total
                // Example: if cache = 10, set game points to -10, so new total = 10 + (-10) = 0
                $playerGR->points = -1 * $cache->points;
            }
            elseif ($cache === null && $playerGR->points < 0)
            {
                // No cache exists, don't allow negative points
                $playerGR->points = 0;
            }

            // Safety check: losers should NEVER get positive points. An edge case has been observed where players with negative or very low pts, earned ~+1 pts when winning, lets prevent that.
            if (!$playerGRTeamWonTheGame && !$playerGR->draw && $playerGR->points > 0)
            {
                $playerGR->points = 0;
            }

            $playerGR->save();
        }

        return 200;
    }

    public function awardPlayerPoints(GameReport $gameReport, LadderHistory $history)
    {
        $players = [];

        $playerGameReports = $gameReport->playerGameReports()->get();

        // Oops we don't have any players
        if ($playerGameReports->count() < 1)
        {
            return 604;
        }

        if ($gameReport->fps < $history->ladder->qmLadderRules->bail_fps)
        {
            // FPS too low, no points awarded
            return 630;
        }

        if ($gameReport->duration < $history->ladder->qmLadderRules->bail_time)
        {
            // Duration too low, no points awarded
            return 660;
        }

        $disconnected = 0;

        foreach ($playerGameReports as $playerGR)
        {
            if ($playerGR->spectator == true)
            {
                continue;
            }

            $ally_average = 0;
            $ally_points = 0;
            $ally_count = 0;
            $enemy_average = 0;
            $enemy_points = 0;
            $enemy_count = 0;
            $enemy_games = 0;

            foreach ($playerGameReports as $pgr)
            {
                if ($pgr->spectator == true)
                {
                    continue;
                }
                
                $other = $this->playerService->findUserRatingByPlayerId($pgr->player_id);
                $players[] = $other;

                if ($pgr->local_team_id == $playerGR->local_team_id)
                {
                    $ally_average += $other->rating;
                    $ally_points += $pgr->player->pointsBefore($history, $pgr->game_id);
                    $ally_count++;
                }
                else
                {
                    $enemy_average += $other->rating;
                    $enemy_points += $pgr->player->pointsBefore($history, $pgr->game_id);
                    $enemy_count++;
                    $enemy_games += $pgr->player->totalGames($history);
                }
            }

            $ally_average /= $ally_count;
            $enemy_average /= $enemy_count;
            $elo_k = $this->playerService->getEloKvalue($players);
            $points = null;
            $base_rating = $enemy_average > $ally_average ? $enemy_average : $ally_average;

            $gvc = 8;
            if ($history->ladder->qmLadderRules->use_elo_points)
            {
                $gvc = ceil(($base_rating * $enemy_average) / 230000);
            }

            $wol_k = $history->ladder->qmLadderRules->wol_k;
            $diff = $enemy_points - $ally_points;
            $we = 1 / (pow(10, abs($diff) / 600) + 1);
            $we = $diff > 0 && $playerGR->wonOrDisco() ? 1 - $we : ($diff < 0 && !$playerGR->wonOrDisco() ? 1 - $we : $we);
            $wol = (int)($wol_k * $we);

            $eloAdjust = 0;

            if ($playerGR->draw)
            {
                $playerGR->points = 0;
            }
            else if ($playerGR->wonOrDisco())
            {
                $points = (new EloService(16, $ally_average, $enemy_average, 1, 0))->getNewRatings()["a"];
                $diff = (int)($points - $ally_average);
                if (!$history->ladder->qmLadderRules->use_elo_points)
                {
                    $diff = 0;
                }

                $playerGR->points = $gvc + $diff + $wol;

                $eloAdjust = new EloService($elo_k, $ally_average, $enemy_average, 1, 0);

                if ($gameReport->best_report)
                {
                    $this->playerService->updateUserRating($playerGR->player_id, $eloAdjust->getNewRatings()["a"]);
                }
            }
            else
            {
                if ($enemy_games < 10)
                {
                    $wol = (int)($wol * ($enemy_games / 10));
                }
                if ($ally_points  < ($wol + $gvc) * 10)
                {
                    $playerGR->points = -1 * (int)($ally_points / 10);
                }
                else
                {
                    $playerGR->points = -1 * ($wol + $gvc);
                }

                $eloAdjust = new EloService($elo_k, $ally_average, $enemy_average, 0, 1);
                if ($gameReport->best_report)
                {
                    $this->playerService->updateUserRating(
                        $playerGR->player_id,
                        $eloAdjust->getNewRatings()["a"]
                    );
                }
            }

            // Get correct cache type
            $cache = $playerGR->player->playerCache($history->id);

            // Prevent players from going below zero total points
            if ($cache !== null && ($cache->points + $playerGR->points) < 0)
            {
                // Cap the loss: set game points to negative of current total
                // Example: if cache = 10, set game points to -10, so new total = 10 + (-10) = 0
                $playerGR->points = -1 * $cache->points;
            }
            elseif ($cache === null && $playerGR->points < 0)
            {
                // No cache exists, don't allow negative points
                $playerGR->points = 0;
            }

            // Safety check: losers should NEVER get positive points
            if (!$playerGR->wonOrDisco() && !$playerGR->draw && $playerGR->points > 0)
            {
                $playerGR->points = 0;
            }

            $playerGR->save();
        }

        return 200;
    }

    public function getAllLadders(Request $request)
    {
        return $this->ladderService->getAllLadders();
    }

    public function getCurrentLadders(Request $request)
    {
        return $this->ladderService->getLadders(false);
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return $this->ladderService->getLadderGameById($game, $gameId);
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        $date = $request->query('date') ?? Carbon::now()->format('m-Y');
        $ladderService = $this->ladderService;
        return Cache::remember("getLadderPlayer/$date/$game/$player", 5 * 60, function () use ($ladderService, $date, $game, $player)
        {
            $history = $ladderService->getActiveLadderByDate($date, $game);
            return $ladderService->getLadderPlayer($history, $player);
        });
    }

    public function getLadderPlayerFromPublicApi(Request $request, $game = null, $player = null)
    {
        $date = $request->query('date') ?? Carbon::now()->format('m-Y');
        $ladderService = $this->ladderService;
        return Cache::remember("getLadderPlayerFromPublicApi/$date/$game/$player", 5 * 60, function () use ($ladderService, $date, $game, $player)
        {
            $history = $ladderService->getActiveLadderByDate($date, $game);
            $response = $ladderService->getLadderPlayer($history, $player);

            // Emit user info
            $response["player"] = null;

            return $response;
        });
    }

    public function getPlayerDailyStats(Request $request, $game = null, $player = null)
    {
        return Cache::remember("getPlayerDailyStats/$game/$player/" . Carbon::now()->format('Y-m-d'), 5 * 60, function () use ($game, $player)
        {
            // Find the ladder by abbreviation
            $ladder = Ladder::where('abbreviation', '=', $game)->first();

            if (!$ladder)
            {
                return response()->json(['error' => 'Ladder not found'], 404);
            }

            // Find the player by username and ladder
            $playerModel = Player::where('username', '=', $player)
                ->where('ladder_id', '=', $ladder->id)
                ->first();

            if (!$playerModel)
            {
                return response()->json(['error' => 'Player not found'], 404);
            }

            // Get current ladder history
            $history = $ladder->currentHistory();

            if (!$history)
            {
                return response()->json(['error' => 'No active ladder history'], 404);
            }

            // Get today's date range
            $startOfDay = Carbon::now()->startOfDay();
            $endOfDay = Carbon::now()->endOfDay();

            // Count wins for today
            $wins = PlayerGameReport::where('player_game_reports.player_id', '=', $playerModel->id)
                ->where('player_game_reports.won', '=', true)
                ->where('player_game_reports.spectator', '=', false)
                ->whereBetween('player_game_reports.created_at', [$startOfDay, $endOfDay])
                ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
                ->join('games', 'games.id', '=', 'game_reports.game_id')
                ->where('games.ladder_history_id', '=', $history->id)
                ->where('game_reports.valid', '=', true)
                ->where('game_reports.best_report', '=', true)
                ->count();

            // Count losses for today (defeated and not won, excluding draws)
            $losses = PlayerGameReport::where('player_game_reports.player_id', '=', $playerModel->id)
                ->where('player_game_reports.defeated', '=', true)
                ->where('player_game_reports.won', '=', false)
                ->where('player_game_reports.draw', '=', false)
                ->where('player_game_reports.spectator', '=', false)
                ->whereBetween('player_game_reports.created_at', [$startOfDay, $endOfDay])
                ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
                ->join('games', 'games.id', '=', 'game_reports.game_id')
                ->where('games.ladder_history_id', '=', $history->id)
                ->where('game_reports.valid', '=', true)
                ->where('game_reports.best_report', '=', true)
                ->count();

            return response()->json([
                'player' => $player,
                'ladder' => $game,
                'date' => Carbon::now()->format('Y-m-d'),
                'wins' => $wins,
                'losses' => $losses
            ], 200);
        });
    }

    public function viewRawGame(Request $request, $gameId)
    {
        $rawGame = GameRaw::where("game_id", "=", $gameId)->get();

        return response($rawGame, 200)->header('Content-Type', 'application/json');
    }

    public function getLadderTopList(Request $request, $cncnetGame = null, $count = 10)
    {
        if ($count > 100) $count = 100;

        return Cache::remember(
            "$cncnetGame/top/$count",
            5 * 60,
            function () use (&$cncnetGame, &$count)
            {
                $date = Carbon::now()->format('m-Y');
                $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);
                $players = PlayerCache::where('ladder_history_id', '=', $history->id)->orderBy('points', 'DESC')->limit($count)->get();
                $top = [];
                foreach ($players as $player)
                {
                    $top[] = ["name" => $player->player_name, "points" => $player->points];
                }
                return $top;
            }
        );
    }

    public function getLadderRecentGames(Request $request, string $cncnetGame = null)
    {
        try
        {
            if ($cncnetGame == null)
            {
                return "supply game";
            }

            $date = $request->date ?? Carbon::now()->format("m-Y");
            $response = $this->ladderService->getGamesFormattedForEloService($date, $cncnetGame, $request->query(), 200);

            return $response;
        }
        catch (Exception $ex)
        {
            return "Error in request: " . $ex->getMessage();
        }
    }

    public function getLadderRecentGamesList(Request $request, $cncnetGame = null, $count = 10)
    {
        if ($count > 100) $count = 100;

        return Cache::remember(
            "$cncnetGame/games/recent/$count",
            5 * 60,
            function () use (&$request, &$cncnetGame, &$count)
            {
                $date = Carbon::now()->format('m-Y');
                $recentGames = $this->ladderService->getRecentValidLadderGames($date, $request->game, $count);

                foreach ($recentGames as $rg)
                {
                    $rg["url"] = "/ladder/" . $date . "/" . $request->game . "/games/" . $rg->id;
                    $rg["map_url"] = "/images/maps/" . $request->game . "/" . $rg->hash . ".png";
                    $rg["players"] = $rg->playerGameReports()
                        ->select("won", "player_id", "points", "no_completion", "quit", "defeated", "draw")
                        ->get();

                    foreach ($rg["players"] as $p)
                    {
                        $p["username"] = $p->player()->first()->username;
                        $p["url"] = "/ladder/" . $date . "/" . $request->game . "/player/" . $p->username;
                    }
                }
                return $recentGames;
            }
        );
    }

    public function getLadderWinners(Request $request, $cncnetGame)
    {
        $prevWinners = [];
        $prevLadders = [];

        $ladder = Ladder::where("abbreviation", "=", $cncnetGame)->first();
        $prevLadders[] = $this->ladderService->getPreviousLaddersByGame($ladder, 5)->splice(0, 1);

        foreach ($prevLadders as $h)
        {
            foreach ($h as $history)
            {
                $prevWinners[] = [
                    "game" => $history->ladder->game,
                    "short" => $history->short,
                    "full" => $history->ladder->name,
                    "abbreviation" => $history->ladder->abbreviation,
                    "ends" => $history->ends,
                    "players" => PlayerCache::where('ladder_history_id', '=', $history->id)->orderBy('points', 'desc')->get()->splice(0, 2)
                ];
            }
        }

        return $prevWinners;
    }

    public function reRunDisconnectionPoints()
    {
        $grs = GameReport::where('game_reports.created_at', '>', '2018-03-01 00:00:00')
            ->where('disconnected', '=', true)->where('points', '>', 0)
            ->join('player_game_reports', 'player_game_reports.game_report_id', '=', 'game_reports.id')
            ->orderBy('game_reports.id', 'ASC')->select('game_reports.*')->get();

        foreach ($grs as $gr)
        {
            error_log("{$gr->game_id}, {$gr->player_id}");
            $this->awardPlayerPoints($gr, $gr->game->ladderHistory);
        }
    }

    public function countMapVetos($ladderId)
    {
        $ladder = Ladder::find($ladderId);
        $qmMapSides = QmMatchPlayer::select('map_sides')
            ->where('ladder_id', '=', $ladderId)
            ->whereNotNull('qm_match_id')->where('qm_match_id', '>', 90932)
            ->get();

        $map_vetos_raw = [];
        foreach ($qmMapSides as $ms)
        {
            $map_sides = explode(',', $ms->map_sides);
            $index = 0;
            foreach ($map_sides as $side)
            {
                if ($side == -2)
                {
                    if (!array_key_exists($index, $map_vetos_raw))
                        $map_vetos_raw[$index] = 1;
                    else
                        $map_vetos_raw[$index]++;
                }
                $index++;
            }
        }
        $map_vetos = [];
        foreach ($map_vetos_raw as $index => $count)
        {
            $map = QmMap::where('ladder_id', '=', $ladderId)->where('bit_idx', '=', $index)->where('valid', '=', true)->first();
            if ($map !== null)
                $map_vetos[$map->admin_description] = $count;
            else
                $map_vetos[$index] = $count;
        }
        return $map_vetos;
    }

    public function countUniqueMapVetos($ladderId)
    {
        $ladder = Ladder::find($ladderId);
        $qmMapSides = QmMatchPlayer::select('map_sides')
            ->where('ladder_id', '=', $ladderId)
            ->whereNotNull('qm_match_id')->where('qm_match_id', '>', 90932)
            ->groupBy('player_id')
            ->orderBy('id', 'desc')
            ->get();

        $map_vetos_raw = [];
        foreach ($qmMapSides as $ms)
        {
            $map_sides = explode(',', $ms->map_sides);
            $index = 0;
            foreach ($map_sides as $side)
            {
                if ($side == -2)
                {
                    if (!array_key_exists($index, $map_vetos_raw))
                        $map_vetos_raw[$index] = 1;
                    else
                        $map_vetos_raw[$index]++;
                }
                $index++;
            }
        }
        $map_vetos = [];
        foreach ($map_vetos_raw as $index => $count)
        {
            $map = QmMap::where('ladder_id', '=', $ladderId)->where('bit_idx', '=', $index)->where('valid', '=', true)->first();
            if ($map !== null)
                $map_vetos[$map->admin_description] = $count;
            else
                $map_vetos[$index] = $count;
        }
        return $map_vetos;
    }

    private function updateAchievements($playerId, $ladder, $stats)
    {
        if ($stats === null)
            return;

        $user = Player::where('id', $playerId)->first()->user;

        //fetch achievements that have not been unlocked for this ladder
        $achievements = Achievement::where('ladder_id', $ladder->id)->get();

        //fetch the game object counts from the game stats for this game
        $gocs = GameObjectCounts::where('stats_id', $stats->id)->get();

        foreach ($gocs as $goc)
        {
            $count = $goc->count;
            $objectName = $goc->countableGameObject->name;
            $heapName = $goc->countableGameObject->heap_name;

            //fetch the achievement that uses this heap_name and object_name
            $unitCareerAchievements = $achievements->filter(function ($value) use (&$objectName, &$heapName)
            {
                return $value->object_name === $objectName && $value->heap_name === $heapName && $value->achievement_type === "CAREER";
            })->sortBy("unlock_count");

            $this->achievementCheck($user, $unitCareerAchievements, $count, "CAREER");

            $unitImmediateAchievements = $achievements->filter(function ($value) use (&$objectName, &$heapName)
            {
                return $value->object_name === $objectName && $value->heap_name === $heapName && $value->achievement_type === "IMMEDIATE";
            })->sortBy("unlock_count");
            $this->achievementCheck($user, $unitImmediateAchievements, $count, "IMMEDIATE");
        }

        $pgr = $stats->playerGameReport;
        $won = $pgr->wonOrDisco();

        //update player won achievement
        if ($won)
        {
            $winAchievements = $achievements->filter(function ($value) use (&$ladder)
            {
                return $value->tag == "Win " . $ladder->name . " QM Games";
            })->sortBy("unlock_count");

            $this->achievementCheck($user, $winAchievements, 1);

            $country = $stats->cty;

            if ($country < 5) //allied
            {
                $winAllied = $achievements->filter(function ($value)
                {
                    return $value->tag === 'Allied: Win QM Games';
                })->sortBy('unlock_count');

                $this->achievementCheck($user, $winAllied, 1);
            }
            else if ($country > 4 && $country < 9) //soviet
            {
                $winSoviet = $achievements->filter(function ($value)
                {
                    return $value->tag === 'Soviet: Win QM Games';
                })->sortBy('unlock_count');

                $this->achievementCheck($user, $winSoviet, 1);
            }
            else if ($country == 9) //yuri
            {
                $winYuri = $achievements->filter(function ($value)
                {
                    return $value->tag === 'Yuri: Win QM Games';
                })->sortBy('unlock_count');

                $this->achievementCheck($user, $winYuri, 1);
            }

            //win 'x' amount of QMs in one month
            $winGames = $achievements->filter(function ($value)
            {
                return $value->tag === 'Win QM Games in One Month';
            })->sortBy('unlock_count');

            // $this->monthlyAchievement($user, $lockedAchievement, 1);
        }

        //play 'x' amount of QMs in one month
        $lockedAchievement = $achievements->filter(function ($value)
        {
            return $value->tag === 'Play Games in one Month';
        })->sortBy('unlock_count');

        //$this->monthlyAchievement($user, $lockedAchievement, 1);
    }

    private function monthlyAchievement($user, $lockedAchievement, $count)
    {
        if ($lockedAchievement == null || $lockedAchievement->achievement_id == null)
            return;

        $lockedAchievementProgress = AchievementProgress::where('achievement_id', $lockedAchievement->achievement_id)
            ->where('user_id', $user->id)
            ->first();

        if ($lockedAchievementProgress == null)
        {
            $lockedAchievementProgress = new AchievementProgress();
            $lockedAchievementProgress->achievement_id = $lockedAchievement->achievement_id;
            $lockedAchievementProgress->user_id = $user->id;
            $lockedAchievementProgress->count = 0;
            $lockedAchievementProgress->save();
        }

        $currentMonth = Carbon::now()->month; //get the month this game was played in

        $lastUpdateMonth = $lockedAchievementProgress->updated_at->month; //get the month that the most recent game was played in

        if ($lastUpdateMonth != $currentMonth) //current game was played in a different month, set count to 0
            $lockedAchievementProgress->count = 0;


        $lockedAchievementProgress->count += $count;

        if ($lockedAchievementProgress->count >= $lockedAchievement->unlock_count)
        {
            $lockedAchievementProgress->count = $lockedAchievement->unlock_count; //since user has hit or exceeded the required unlock count, set their count to the unlock count
            $lockedAchievementProgress->achievement_unlocked_date = Carbon::now();
        }
        $lockedAchievementProgress->save();
    }

    /**
     * Tick achievement tracker then check if achievement is unlocked.
     */
    private function achievementCheck($user, $achievements, $count, $type = "CAREER")
    {
        foreach ($achievements as $achievement)
        {
            $achievementProgress = AchievementProgress::where('achievement_id', $achievement->id)
                ->where('user_id', $user->id)
                ->first();

            if ($achievementProgress == null)  //first time user is making progress towards this achievement
            {
                $achievementProgress = new AchievementProgress();
                $achievementProgress->achievement_id = $achievement->id;
                $achievementProgress->count = 0;
                $achievementProgress->user_id = $user->id;
                $achievementProgress->save();
            }
            else if ($achievementProgress->achievement_unlocked_date != null) //achievement already unlocked
            {
                continue; //go to next achievement
            }

            if ($type == "CAREER") //career achievement logic
            {
                $achievementProgress->count += $count;

                if ($achievementProgress->count >= $achievement->unlock_count)
                {
                    $achievementProgress->count = $achievement->unlock_count; //since user has hit or exceeded the required unlock count, set their count to the unlock count
                    $achievementProgress->achievement_unlocked_date = Carbon::now();
                }
                $achievementProgress->save();
            }
            else if ($type == "IMMEDIATE") //immediate achievement logic
            {
                if ($count >= $achievement->unlock_count)
                {
                    $achievementProgress->count = $achievement->unlock_count;
                    $achievementProgress->achievement_unlocked_date = Carbon::now();
                    $achievementProgress->save();
                }
            }

            break; //only increment one achievement at a time until it's unlocked
        }
    }
}
