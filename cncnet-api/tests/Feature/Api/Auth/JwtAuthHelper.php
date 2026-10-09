<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait JwtAuthHelper
{
    public function jwtAuth(User $user) {
        // The api guard and the JWT instance cache the user and token between requests in the same test.
        auth()->forgetGuards();
        app('tymon.jwt')->unsetToken();
        $token = JWTAuth::fromUser($user);

        return $this
            ->withHeaders(['Authorization' => 'Bearer '. $token]);
    }
}