<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\PointService;
use \App\Http\Services\AuthService;
use \Carbon\Carbon;
use Log;

class ApiLadderController extends Controller
{
    private $ladderService;
    private $gameService;
    private $playerService;
    private $pointService;
    private $authService;

    private $elo;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->gameService = new GameService();
        $this->playerService = new PlayerService();
        $this->authService = new AuthService();
    }

    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function postLadder(Request $request, $cncnetGame = null, $username = null, $pingSent = 0, $pingReceived = 0)
    {
        // Ladder exists
        $ladder = $this->ladderService->getLadderByGame($cncnetGame);
        if ($ladder == null)
        {
            return response()->json(['Ladder does not exist'], 400);
        }

        // Game stats result
        $result = $this->gameService->processStatsDmp($request->file('file'), $cncnetGame, $ladder->id);
        if (count($result) == 0 || $result == null)
        {
            return response()->json(['No data'], 400);
        }

        // Get Active Ladder
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $history = \App\LadderHistory::where("starts", "=", $start)
            ->where("ends", "=", $end)
            ->where("ladder_id", "=", $ladder->id)
            ->first();

        // Player checks
        $player = $this->checkPlayer($request, $username, $ladder);
        if($player == null)
        {
            return response()->json(['Player error'], 400);
        }

        // Game creation
        $game = $this->gameService->findOrCreateGame($result, $history);
        if ($game == null)
        {
            return response()->json(['Error creating game'], 400);
        }

        // Keep a record of the raw stats sent in
        $this->gameService->saveRawStats($result, $game->id, $history->id);

        // Now save the processed stats
        $result = $this->gameService->saveGameStats($result, $game->id, $player->id, $history->ladder->id, $cncnetGame);
        $gameReport = $result['gameReport'];
        if ($gameReport === null)
        {
            return response()->json(['Error' => $result['error']], 400);
        }
        $gameReport->pings_sent = $pingSent;
        $gameReport->pings_received = $pingReceived;
        $gameReport->save();

        // Award points
        $status = $this->awardPoints($gameReport, $history);

        // Dispute handling
        $this->handleGameDispute($gameReport);

        return response()->json(['success' => $status], 200);
    }

    // TODO - should be middleware
    private function checkPlayer($request, $username, $ladder)
    {
        $player = $this->playerService->findPlayerByUsername($username, $ladder);
        $authUser = $this->authService->getUser($request);
        /*
        // TODO
        // Add back when no longer testing
        if ($player == null || $authUser == null)
            return null;

        if ($authUser->id != $player->user_id)
            return null;
        */
        return $player;
    }

    public function handleGameDispute($gameReport)
    {
        $game = $gameReport->game()->first();

        if ($game->game_report_id == $gameReport->id)
            return;

        $allReports = $game->allReports()->get();

        $bestReport = $game->report()->first();

        // If we're not the best report and the best report is disconnected
        // I'm disconnected then we wash the game
        if (($bestReport->disconnected() && $gameReport->disconnected())
            ||
            ($bestReport->oos && $gameReport->oos))
        {
            $wash = new \App\GameReport();
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
                $playerGR = new \App\PlayerGameReport;
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
            return;
        }
        else if ($gameReport->pings_sent - $gameReport->pings_received
                 <
                 $bestReport->pings_sent - $bestReport->pings_received)
        {
            $bestReport->best_report = false;
            $gameReport->best_report = true;
            $game->game_report_id = $gameReport->id;
            $game->save();
            $gameReport->save();
            $bestReport->save();
            return;
        }
    }

    public function awardPoints($gameReport, $history)
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
            $ally_average = 0;
            $ally_points = 0;
            $ally_count = 0;
            $enemy_average = 0;
            $enemy_points = 0;
            $enemy_count = 0;
            $enemy_games = 0;

            foreach ($playerGameReports as $pgr)
            {
                $other = $this->playerService->findPlayerRatingByPid($pgr->player_id);
                $players[] = $other;
                if ($pgr->local_team_id == $playerGR->local_team_id)
                {
                    $ally_average += $other->rating;
                    $ally_points += $pgr->player->points($history);
                    $ally_count++;
                }
                else {
                    $enemy_average += $other->rating;
                    $enemy_points += $pgr->player->points($history);
                    $enemy_count++;
                    $enemy_games += $pgr->player->totalGames($history);
                }
            }
            $ally_average /= $ally_count;
            $enemy_average /= $enemy_count;

            $elo_k = $this->playerService->getEloKvalue($players);

            $points = null;

            $base_rating = $enemy_average > $ally_average ? $enemy_average : $ally_average;
            $gvc = ceil(($base_rating * $enemy_average) / 230000);

            $diff = $enemy_points - $ally_points;
            $we = 1/(pow(10, abs($diff)/600)+1);
            $we = $diff > 0 && $playerGR->won ? 1 - $we : ($diff < 0 && !$playerGR->won ? 1 - $we : $we);
            $wol = (int)(64 * $we);

            $eloAdjust = 0;
            if ($playerGR->won || ($playerGR->disconnected && $playerGR->player_id == $gameReport->player_id))
            {
                $points = (new PointService(16, $ally_average, $enemy_average, 1, 0))->getNewRatings()["a"];
                $diff = (int)($points - $ally_average);
                $playerGR->points = $gvc + $diff + $wol;

                $eloAdjust = new PointService($elo_k, $ally_average, $enemy_average, 1, 0);
                if ($gameReport->best_report)
                    $this->playerService->updatePlayerRating($playerGR->player_id,$eloAdjust->getNewRatings()["a"]);
            }
            else
            {
                if ($enemy_games < 10)
                {
                    $wol = (int)($wol * ($enemy_games/10));
                }
                if ($ally_points  < $wol * 10)
                {
                    $wol = (int)($ally_points/10);
                }

                $playerGR->points = -1 * ($wol + $gvc);

                $eloAdjust = new PointService($elo_k, $ally_average, $enemy_average, 0, 1);
                if ($gameReport->best_report)
                    $this->playerService->updatePlayerRating($playerGR->player_id,$eloAdjust->getNewRatings()["a"]);

            }

            $playerGR->player->doTierStuff($history);
            $playerGR->save();
            if ($playerGR->player->points($history) < 1)
            {
                $playerGR->points = 0;
                $playerGR->save();
            }
        }

        return 200;
    }

    public function getCurrentLadders(Request $request)
    {
        return $this->ladderService->getLadders();
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return $this->ladderService->getLadderGameById($game, $gameId);
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        return $this->ladderService->getLadderPlayer($game, $player);
    }

    public function viewRawGame(Request $request, $gameId)
    {
        $rawGame = \App\GameRaw::where("game_id", "=", $gameId)->get();

        return response($rawGame, 200)
                  ->header('Content-Type', 'application/json');
    }
}