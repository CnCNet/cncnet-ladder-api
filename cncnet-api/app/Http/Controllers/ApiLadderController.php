<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\PointService;

class ApiLadderController extends Controller 
{
    private $ladderService;
    private $gameService;
    private $playerService;
    private $pointService;

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
    // TODO - Currently if there is no unique id from stats - game gets recorded more than once. 
    public function postLadder(Request $request, $cncnetGame = null)
    {
        $result = $this->gameService->processStatsDmp( $request->file('file'));

        if (count($result) == 0 || $result == null)
            return response()->json(['No data'], 400);

        // Player Check
        $player = $this->playerService->findPlayerById($request->playerId);
        if ($player == null)
            return response()->json(['Player does not exist'], 400);

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
        $gameStats = $this->gameService->saveGameStats($result, $game->id, $player);
        if ($gameStats != 200)
            return response()->json(['Error' => $gameStats], 400);

        // Update Game Record
        $gameStats = \App\GameStats::where("game_id", "=", $game->id)->get();
        if (count($gameStats) <= 1)
            $this->gameService->saveGameDetails($game, $gameStats[0]);

        // Create Player Game Record
        $this->playerService->createPlayerGame($player, $game);

        // Award ELO points
        $this->awardPoints($game->id);

        return response()->json(['success'], 200);
    }

    public function awardPoints($gameId)
    {
        $games = \App\GameStats::where("game_id", "=", $gameId)->get();
        $players = [];

        // 1vs1 For now
        if (count($games) == 2)
        {
            // We have both games in, so now we can do elo and award points to winners/losers
            foreach ($games as $g)
            {
                // Safety?
                if ($g->plrs == 2)
                {
                    $player = $this->playerService->findPlayerById($g->player_id)->first();
                    
                    if ($player == null)
                        return response()->json(['error' => 'Player not found'], 400);

                    // TODO - Need some proper logic to know who has actually won
                    if ($g->cmp == 256)
                    {
                        $players["won"] = $player;
                    }
                    else 
                    {
                        $players["lost"] = $player;
                    }
                }
            }

            // TODO - refine

            $points = new PointService($players["lost"]["points"], $players["won"]["points"], 0, 1);
            $results = $points->getNewRatings();

            $playerA = $this->playerService->findPlayerById($players["lost"]["id"])->first();
            $playerB = $this->playerService->findPlayerById($players["won"]["id"])->first();

            $playerPoints = new \App\PlayerPoint();
            $playerPoints->player_id = $playerA->id;
            $playerPoints->game_id = $gameId;
            $playerPoints->points_awarded = $results["a"];
            $playerPoints->game_won = 0;
            $playerPoints->save();

            $playerPoints = new \App\PlayerPoint();
            $playerPoints->player_id = $playerB->id;
            $playerPoints->game_id = $gameId;
            $playerPoints->points_awarded = $results["b"];
            $playerPoints->game_won = 1;
            $playerPoints->save();

            $playerA->points = $results["a"];
            if ($playerA->loss_count != 0)
                $playerA->loss_count -= 1;
            $playerA->save();

            $playerB->points = $results["b"];
            $playerB->win_count += 1;
            $playerB->save();

            return $results;
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