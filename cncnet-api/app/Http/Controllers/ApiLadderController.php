<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;

class ApiLadderController extends Controller 
{
    private $ladderService;
    private $gameService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->gameService = new GameService();
    }
    
    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function postLadder(Request $request, $game = null, $playerId = null)
    {
        $file = "stats_ts.dmp"; // TODO handle incoming file
        $result = $this->gameService->processStatsDmp($file);

        if(count($result) == 0 || $result == null)
            return "Error processing stats file";

        // Check a ladder exists for this game
        $ladder = $this->ladderService->getLadderByGame($game);
        if ($ladder == null)
            return "Ladder does not exist";
        
        // Unique Identifier - TODO
        $uniqueId = 1;

        // Check we've got a record of the game already
        $ladderGame = \App\Game::where("wol_game_id", "=", $uniqueId)->first();

        if($ladderGame == null)
        {
            // Game does not exist so create it
            $ladderGame = new \App\Game();
            $ladderGame->ladder_id = $ladder->id;
            $ladderGame->wol_game_id = $uniqueId;
            $ladderGame->save();
        }

        // Keep a record of the raw stats
        $rawStats = $this->gameService->saveRawStats($result, $ladderGame->id, $ladder->id);

        // Now save the actual stats
        $gameStats = $this->gameService->saveGameStats($result,  $ladderGame->id, $playerId);

        return $gameStats;
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return $this->ladderService->getLadderGameById($game, $gameId);
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        return $this->ladderService->getLadderPlayer($game, $player);
    }
}