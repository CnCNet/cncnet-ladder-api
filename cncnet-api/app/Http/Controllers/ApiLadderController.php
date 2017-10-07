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

    public function postLadder(Request $request, $cncnetGame = null, $username = null)
    {
        // Game stats result
        $result = $this->gameService->processStatsDmp($request->file('file'), $cncnetGame);
        if (count($result) == 0 || $result == null)
        {
            return response()->json(['No data'], 400);
        }

        // Ladder exists
        $ladder = $this->ladderService->getLadderByGame($cncnetGame);
        if ($ladder == null)
        {
            return response()->json(['Ladder does not exist'], 400);
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
        $gameReport = $this->gameService->saveGameStats($result, $game->id, $player->id, $history->ladder->id, $cncnetGame);
        if ($gameReport === null)
        {
            return response()->json(['Error' => $gameStats], 400);
        }

        // Award points
        $status = $this->awardPoints($gameReport, $history);

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

    public function awardPoints($gameReport, $history)
    {
        $players = [];
        $playerGameReports = $gameReport->playerGameReports()->get();

        // Oops we don't have any players
        if ($playerGameReports->count() < 1)
        {
            return 604;
        }

        foreach ($playerGameReports as $playerGR)
        {
            $ally_average = 0;
            $ally_count = 0;
            $enemy_average = 0;
            $enemy_count = 0;

            foreach ($playerGameReports as $pgr)
            {
                $other = $this->playerService->findPlayerRatingByPid($pgr->player_id);
                $players[] = $other;
                if ($pgr->local_team_id == $playerGR->local_team_id)
                {
                    $ally_average += $other->rating;
                    $ally_count++;
                }
                else {
                    $enemy_average += $other->rating;
                    $enemy_count++;
                }
            }
            $ally_average /= $ally_count;
            $enemy_average /= $enemy_count;

            $elo_k = $this->playerService->getEloKvalue($players);

            $points = null;

            $gvc = ceil(($ally_average * $enemy_average) / 200000);

            if ($playerGR->won && !$playerGR->defeated && !$playerGR->draw)
            {
                $points = new PointService(16, $ally_average, $enemy_average, 1, 0);
                $eloAdjust = new PointService($elo_k, $ally_average, $enemy_average, 1, 0);
            }
            else if ($playerGR->defeated)
            {
                $points = new PointService(16, $ally_average, $enemy_average, 0, 1);
                $eloAdjust = new PointService($elo_k, $ally_average, $enemy_average, 1, 0);
                $gvc /= 2;
            }

            if ($points !== null)
            {
                $eloResults = $points->getNewRatings();
                $diff = $eloResults["a"] - $ally_average;
                $playerGR->points = $gvc + ($diff > 0 ? $diff : 0);

                // Only do a rating adjustment once per game
                if ($gameReport->best_report)
                    $this->playerService->updatePlayerRating($playerGR->player_id,$eloAdjust->getNewRatings()["a"]);
            }
            else
            {
                $playerGR->points = $gvc;
            }
            $playerGR->save();
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
        $rawGame = \App\GameRaw::where("game_id", "=", $gameId)->first();

        return response($rawGame->packet, 200)
                  ->header('Content-Type', 'application/json');
    }
}