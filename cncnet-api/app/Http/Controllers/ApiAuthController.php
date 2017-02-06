<?php 
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Services\AuthService;

class ApiAuthController extends Controller 
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function getAuth(Request $request, $player = null)
    {
        $this->middleware('auth.basic');
        $user = \Auth::user();
        return $user->usernames;
    }

    public function putAuth(Request $request, $player = null)
    {
        $user = $this->authService->addUser($request, \Auth::user());

        if($user == null)
            return "Error finding or creating user";

        $player = $this->authService->addPlayerToUser($player, $user);
        
        if($player == null)
        {
            return response()->json([], 403);
        }
        else
        {
            return response()->json($player, 200);
        }
    }
}
