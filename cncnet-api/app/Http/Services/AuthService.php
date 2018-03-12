<?php namespace App\Http\Services;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    public function __construct()
    {

    }

    public function getUser()
    {
        try
        {
            if (! $user = JWTAuth::parseToken()->authenticate())
            {
                return response()->json(['error' => 'user_not_found'], 404);
            }
        }
        catch (TokenExpiredException $e)
        {
            // Using fatal as a work-around for the qm client
            return [ "response" => response()->json(['type' => 'fatal', 'error' => 'token_expired',
                                                     'message' => 'Authentication has expired, Please restart Quick Match'],
                                                    $e->getStatusCode()),
                     "user" => null];
        }
        catch (TokenInvalidException $e)
        {
            return [ "response" => response()->json(['type' => 'fatal', 'error' => 'token_invalid',
                                                     'message' => "Authentication failed token_invalid"], $e->getStatusCode()),
                     "user" => null];
        }
        catch (JWTException $e)
        {
            return [ "response" => response()->json(['type' => 'fatal', 'error' => 'token_absent',
                                                     'message' => 'Authentication failed token_absent'], $e->getStatusCode()),
                     "user" => null];
        }

        // the token is valid and we have found the user via the sub claim
        return [ "user" => $user, "response" => null];
    }
}