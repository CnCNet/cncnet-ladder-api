<?php namespace App\Http\Services;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\Exceptions;

class AuthService
{
    public function __construct()
    {

    }

    public function getUser($request)
    {
        try
        {
            if (! $user = JWTAuth::parseToken()->authenticate())
            {
                return ['response' => ['error' => 'user_not_found'], 'status' => 404];
            }
        }
        catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e)
        {
            // Using fatal as a work-around for the qm client
            return [ "response" => ['type' => 'fatal', 'error' => 'token_expired',
                                     'message' => 'Authentication has expired, Please restart Quick Match'],
                     "status" => $e->getStatusCode(),
                     "user" => null];
        }
        catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e)
        {
            return [ "response" => ['type' => 'fatal', 'error' => 'token_invalid',
                                    'message' => "Authentication failed token_invalid"],
                     "status" => $e->getStatusCode(),
                     "user" => null];
        }
        catch (JWTException $e)
        {
            return [ "response" => ['type' => 'fatal', 'error' => 'token_absent',
                                    'message' => 'Authentication failed token_absent'],
                     "status" => $e->getStatusCode(),
                     "user" => null];
        }

        // the token is valid and we have found the user via the sub claim
        return [ "user" => $user, "response" => null];
    }
}
