<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\PointService;
use \App\Http\Services\AuthService;
use \Carbon\Carbon;

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
        $gameStats = $this->gameService->saveGameStats($result, $game->id, $player->id, $history->ladder->id, $cncnetGame);
        if ($gameStats != 200)
        {
            return response()->json(['Error' => $gameStats], 400);
        }

        // Award ELO points
        if ($game->plrs == 2)
        {
            $status = $this->awardPoints($game->id, $history);
        }
        else if ($cncnetGame == "ra")
        {
            $status = $this->awardPoints($game->id, $history);
        }

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

    public function awardPoints($gameId, $history)
    {
        $players = [];
        $gamePlayers = \App\PlayerGame::where("game_id", "=", $gameId)->get();

        // Don't award points multiple time
        if ($gamePlayers->count() != 1)
        {
            return 604;
        }

        $playerGame = $gamePlayers->first();

        $plr = $this->playerService->findPlayerRatingByPid($playerGame->player_id);
        $opn = $this->playerService->findPlayerRatingByPid($playerGame->opponent_id);

        if ($playerGame->result)
        {
            $players["won"] = $plr;
            $players["lost"] = $opn;
        }
        else
        {
            $players["won"] = $opn;
            $players["lost"] = $plr;
        }

        $elo_k = $this->playerService->getEloKvalue($players);

        $points = new PointService($elo_k, $players["lost"]->rating, $players["won"]->rating, 0, 1);
        $results = $points->getNewRatings();

        // Tweak this number until things feel right
        $gvcWon = ceil(($players["lost"]->rating * $players["won"]->rating) / 200000);
        $gvcLost = ceil($gvcWon/2);

        foreach ($players as $k => $player)
        {
            if ($k == "lost")
            {
                $diff = $results["a"] - $player->rating;
                $newPoints = $gvcLost + ($diff > 0 ? $diff : 0);
                $this->playerService->awardPlayerPoints($player->player_id, $gameId, $newPoints, false, $history);
            }
            else if ($k == "won")
            {
                $diff = $results["b"] - $player->rating;
                $newPoints = $gvcWon + ($diff > 1 ? $diff : 1);
                $this->playerService->awardPlayerPoints($player->player_id, $gameId, $newPoints, true, $history);
            }
        }

        $this->playerService->updatePlayerRating($players["lost"], $results["a"]);
        $this->playerService->updatePlayerRating($players["won"], $results["b"]);
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

    public function viewRawGame(Request $request, $rawId)
    {
        $rawGame = \App\GameRaw::where("id", "=", $rawId)->first();

        return response($rawGame->packet, 200)
                  ->header('Content-Type', 'application/json');
    }
}