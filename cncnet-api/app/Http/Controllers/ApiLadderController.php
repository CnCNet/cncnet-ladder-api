<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;

class ApiLadderController extends Controller 
{
    private $ladderService;
    private $gameService;
    private $playerService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->gameService = new GameService();
        $this->playerService = new PlayerService();
    }
    
    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    // TODO - Middleware to check auth token and that playerId is valid to their account
    public function postLadder(Request $request, $game = null)
    {
        $file = $request->file('file');
        $result = $this->gameService->processStatsDmp($file);

        if (count($result) == 0 || $result == null)
            return response()->json(['No data'], 400);

        $sha1 = sha1_file($file);

        // Player Check
        $player = $this->playerService->findPlayerById($request->playerId);

        if ($player == null)
            return response()->json(['Player does not exist'], 400);

        // Check a ladder exists for this game
        $ladder = $this->ladderService->getLadderByGame($game);
        if ($ladder == null)
            return response()->json(['Ladder does not exist'], 400);
        
        // Unique Identifier - IDNO
        $uniqueId = $this->gameService->getUniqueGameIdentifier($result);

        // Check we've got a record of the game already
        $ladderGame = \App\Game::where("wol_game_id", "=", $uniqueId)->first();

        if ($ladderGame == null)
        {
            $ladderGame = new \App\Game();
            $ladderGame->ladder_id = $ladder->id;
            $ladderGame->wol_game_id = $uniqueId;
            $ladderGame->save();
        }

        // Keep a record of the raw stats
        $rawStats = $this->gameService->saveRawStats($result, $ladderGame->id, $ladder->id, $sha1);
        if ($rawStats == null)
            return response()->json(['Raw stats were not saved'], 400);

        // Now save the actual stats
        $gameStats = $this->gameService->saveGameStats($result, $ladderGame->id, $player);
        if($gameStats != 200)
            return response()->json(['Error' => $gameStats], 400);

        // Update Game
        $gameStats = \App\GameStats::where("game_id", "=", $ladderGame->id)->first();
        $this->gameService->saveGameDetails($ladderGame, $gameStats);

        // Create Player Game Record
        $playerGame = \App\PlayerGame::where("player_id", "=", $player->id)
            ->where("game_id", "=", $ladderGame->id)->first();

        if ($playerGame == null)
        {
            $playerGame = new \App\PlayerGame();
            $playerGame->game_id = $ladderGame->id;
            $playerGame->player_id = $player->id;
            $playerGame->save();
        }

        return response()->json(['success'], 200);
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