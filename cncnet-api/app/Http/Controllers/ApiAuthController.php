<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;

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

    public function refresh(Request $request)
    {
        try
        {
            $user = JWTAuth::parseToken()->authenticate();
            if (! $user )
            {
                throw new Exception('user_not_found');
            }
            else
            {
                $email = $user->email;
                $token = JWTAuth::parseToken()->refresh();
                return response()->json(compact('token', 'email'));
            }
        }
        catch (\Exception $e)
        {
            $response = response([ 'error' => "{$e}" ], 401);
            $response->headers->set('WWW-Authenticate', 'post email password');
            return $response;
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        return response()->json(compact('token'));

    }
}
