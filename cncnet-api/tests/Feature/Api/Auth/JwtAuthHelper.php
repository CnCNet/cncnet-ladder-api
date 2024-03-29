<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait JwtAuthHelper
{
    public function jwtAuth(User $user) {

        $token = JWTAuth::fromUser($user);

        return $this
            ->withHeaders(['Authorization' => 'Bearer '. $token]);
    }
}