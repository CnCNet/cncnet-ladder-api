<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApiUserController extends Controller 
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getAccount(Request $request)
    {
        $user = $this->authService->getUser();

        return $user->usernames;
    }

    public function createUser(Request $request)
    {
        $token = JWTAuth::getToken();

        if ($request->email == null && $request->password == null)
        {
            return response()->json(['bad_parameters'], 400);
        }

        if($token == null)
        {
            $check = \App\User::where("email", "=", $request->email)->first();
            if($check == null)
            {
                $user = new \App\User();
                $user->name = "";
                $user->email = $request->email;
                $user->password = \Hash::make($request->password);
                $user->save();

                $token = JWTAuth::fromUser($user);
                return response()->json(compact('token'));
            }
            else
            {
                return response()->json(['account_present'], 400);
            }
        }
        else
        {
            $user = $this->authService->getUser();
            if($user)
            {
                return response()->json(['account_present'], 400);
            }
        }
    }
}