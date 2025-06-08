<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use JwtAuthHelper;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Testman',
            'email' => 'test@test.com',
            'password' => Hash::make('testpass'),
        ]);
    }


    public function test_login(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'testpass',
        ]);

        $response->assertStatus(200);
    }

    public function test_user_account() {

        $response = $this
            ->jwtAuth($this->user)
            ->get('/api/v1/user/account');

        $response->assertStatus(200);
    }
}
