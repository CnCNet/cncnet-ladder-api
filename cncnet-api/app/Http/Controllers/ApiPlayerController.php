<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;

class ApiPlayerController extends Controller
{
    private $authService;
    private $playerService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->playerService = new PlayerService();
    }

    public function createPlayer(Request $request, $username = null)
    {
        $user = $this->authService->getUser($request);

        if($username == null)
            return response()->json(['username_required'], 400);

        $response = $this->playerService->addPlayerToUser($username, $user);

        if($response)
        {
            return response()->json($response, 200);
        }
        else
        {
            return response()->json(['username_taken'], 403);
        }
    }
}
