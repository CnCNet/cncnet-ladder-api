<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApiAuthController extends Controller
{
    public function __construct()
    {

    }

    public function getAuth(Request $request)
    {
        $user = \Auth::user();
        $user_name = $user->name;
        $token = JWTAuth::fromUser($user);
        return response()->json(compact('token', 'user_name'));
    }

    public function putAuth(Request $request, $player = null)
    {

    }
}
