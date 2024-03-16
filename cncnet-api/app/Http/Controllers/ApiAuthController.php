<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class ApiAuthController extends Controller
{
    public function __construct()
    {
    }

    public function getAuth(Request $request)
    {
        $user = Auth::user();
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
            if (!$user)
            {
                throw new Exception('user_not_found');
            }
            else
            {
                $email = $user->email;
                $token = JWTAuth::parseToken()->refresh();
                return response()->json(['token' => $token, 'email' => $user->email, 'name' => $user->name]);
            }
        }
        catch (\Exception $e)
        {
            $response = response(['error' => "{$e}"], 401);
            $response->headers->set('WWW-Authenticate', 'post email password');
            return $response;
        }
    }

    public function login(Request $request)
    {
        $email = $request->email;
        if (str_contains($email, " "))
        {
            // Assume qm client has stripped + from email request
            $email = str_replace(" ", "+", $email);
        }

        $validator = Validator::make(["email" => $email, "password" => $request->password], [
            'email' => 'required|string|email|max:255',
            'password' => 'required'
        ]);

        if ($validator->fails())
        {
            return response()->json($validator->errors(), 400);
        }

        $credentials = ["email" => $email, "password" => $request->password];

        try
        {
            if (!$token = JWTAuth::attempt($credentials))
            {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        }
        catch (JWTException $e)
        {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        $request->headers->set('Authorization', "Bearer $token");
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(['token' => $token, 'email' => $user->email, 'name' => $user->name]);
    }
}
