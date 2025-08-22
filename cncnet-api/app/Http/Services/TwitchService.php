<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TwitchService
{
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->clientId = config('services.twitch.client_id');
        $this->clientSecret = config('services.twitch.client_secret');
    }

    protected function getAccessToken(): ?string
    {
        return Cache::remember('twitch_access_token', 3600, function () {
            $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->ok()) {
                return $response->json('access_token');
            }

            return null;
        });
    }

    public function isUserLive(string $username): bool
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return false;
        }

        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $token,
        ])->get('https://api.twitch.tv/helix/streams', [
            'user_login' => $username,
        ]);

        if (!$response->ok()) {
            return false;
        }

        $data = $response->json('data');

        return !empty($data) && $data[0]['type'] === 'live';
    }
}
