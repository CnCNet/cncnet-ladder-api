<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\PointService;
use \App\Http\Services\AuthService;

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

    // TODO - Currently if there is no unique id from stats - game gets recorded more than once. 
    public function postLadder(Request $request, $cncnetGame = null, $username = null)
    {
        $result = $this->gameService->processStatsDmp($request->file('file'));

        if (count($result) == 0 || $result == null)
            return response()->json(['No data'], 400);

        // Player Check
        $player = $this->playerService->findPlayerByName($username);
        if($player == null)
            return response()->json(['No player found by that username'], 400);

        $authUser = $this->authService->getUser($request);
        if($authUser == null)
            return response()->json(['No User'], 400);

        if ($authUser->id != $player->user_id)
            return response()->json(['User mismatch'], 400);

        // Check a ladder exists for this game
        $ladder = $this->ladderService->getLadderByGame($cncnetGame);
        if ($ladder == null)
            return response()->json(['Ladder does not exist'], 400);
        
        // Check Game Exists
        $uniqueId = $this->gameService->getUniqueGameIdentifier($result);
        $game = $this->gameService->findOrCreateGame($uniqueId, $ladder);

        // Keep a record of the raw stats
        $rawStats = $this->gameService->saveRawStats($result, $game->id, $ladder->id);
        if ($rawStats == null)
            return response()->json(['Raw stats were not saved'], 400);

        // Now save the actual stats
        $gameStats = $this->gameService->saveGameStats($result, $game->id, $player->id);
        if ($gameStats != 200)
            return response()->json(['Error' => $gameStats], 400);

        // Update Game Record
        $gameStats = \App\GameStats::where("game_id", "=", $game->id)->get();
        if (count($gameStats) <= 1)
            $this->gameService->saveGameDetails($game, $gameStats[0]);

        // Award ELO points
        $this->awardPoints($game->id);

        return response()->json(['success'], 200);
    }

    public function awardPoints($gameId)
    {
        $gamePlayers = \App\PlayerGame::where("game_id", "=", $gameId)->get();
        $players = [];

        foreach($gamePlayers as $gamePlayer)
        {
            $player = $this->playerService->findPlayerById($gamePlayer->player_id);
            if ($player == null)
                return response()->json(['error' => 'Player not found'], 400);

            if($gamePlayer->result)
            {
                $players["won"] = $player;
            }
            else
            {
                $players["lost"] = $player;
            }
        }

        $points = new PointService($players["lost"]["points"], $players["won"]["points"], 0, 1);
        $results = $points->getNewRatings();

        foreach ($players as $k => $player)
        {
            $playerPoints = \App\PlayerPoint::where("player_id", "=", $player->id)
                ->where("game_id", "=", $gameId)->first();

            if ($playerPoints != null)
                return;

            if ($k == "lost")
            {
                $newPoints = ($player->points > 0 ? $results["a"] - $player->points : $results["a"]);
                $this->playerService->awardPlayerPoints($player->id, $gameId, $newPoints);
                $player->points = $results["a"];

                $player->games_count += 1;
                $player->loss_count += 1;
                $player->save();
            }
            else if ($k == "won")
            {
                $newPoints = ($player->points > 0 ? $results["b"] - $player->points : $results["b"]);
                $this->playerService->awardPlayerPoints($player->id, $gameId, $newPoints, true);
                $player->points = $results["b"];

                $player->games_count += 1;
                $player->win_count += 1;
                $player->save();
            }
        }
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