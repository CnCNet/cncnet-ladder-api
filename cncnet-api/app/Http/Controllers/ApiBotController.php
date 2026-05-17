<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiBotController extends Controller
{
    public function __construct()
    {
    }

    public function restart(Request $request)
    {
        try
        {
            $cacheKey = 'bot_last_restart';
            $cooldownMinutes = 30;
            $lastRestart = Cache::get($cacheKey);

            // Check rate limit
            if ($lastRestart !== null)
            {
                $now = time();
                $elapsed = $now - $lastRestart;
                $cooldownSeconds = $cooldownMinutes * 60;

                if ($elapsed < $cooldownSeconds)
                {
                    $remainingSeconds = $cooldownSeconds - $elapsed;
                    $remainingMinutes = ceil($remainingSeconds / 60);

                    return redirect('/admin')
                        ->with('error', 'Rate limit exceeded. Bot was recently restarted.')
                        ->with('remaining_cooldown', $remainingMinutes);
                }
            }

            // Get bot compose path from environment
            $composePath = env('BOT_COMPOSE_PATH');

            if (!$composePath)
            {
                throw new Exception('BOT_COMPOSE_PATH is not configured.');
            }

            // Execute docker compose up with force recreate to handle stopped/missing containers
            $command = "docker compose -f {$composePath} up -d --force-recreate 2>&1";
            $output = shell_exec($command);

            // Store current timestamp in cache (expires after cooldown period)
            Cache::put($cacheKey, time(), $cooldownMinutes * 60);

            return redirect('/admin')
                ->with('success', 'Bot container recreated and started successfully')
                ->with('output', trim($output));
        }
        catch (Exception $ex)
        {
            return redirect('/admin')
                ->with('error', 'Failed to restart bot container: ' . $ex->getMessage());
        }
    }
}
