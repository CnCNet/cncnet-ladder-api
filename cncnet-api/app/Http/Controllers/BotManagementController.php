<?php

namespace App\Http\Controllers;

use App\Jobs\RestartBotContainerJob;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BotManagementController extends Controller
{
    private const CACHE_KEY_PREFIX = 'bot_last_restart_user_';

    /**
     * Restart the ladder bot container.
     *
     * Dispatches a background job to recreate and start the bot container using
     * docker compose. Includes rate limiting to prevent abuse.
     */
    public function restart(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new Exception('User not authenticated.');
            }

            // Get configuration
            $composePath = config('bot.compose_path');
            $cooldownMinutes = config('bot.restart_cooldown_minutes', 30);

            // Validate configuration
            if (!$composePath) {
                throw new Exception('BOT_COMPOSE_PATH is not configured. Please contact system administrator.');
            }

            // Validate compose file exists
            $realPath = realpath($composePath);

            if ($realPath === false || !file_exists($realPath)) {
                throw new Exception('Bot compose file not found at configured path.');
            }

            // Validate file extension
            if (!str_ends_with($realPath, '.yml') && !str_ends_with($realPath, '.yaml')) {
                throw new Exception('Invalid compose file: must be .yml or .yaml');
            }

            // Check rate limit (per-user to prevent one user from blocking others)
            $cacheKey = self::CACHE_KEY_PREFIX . $user->id;
            $lastRestart = Cache::get($cacheKey);

            if ($lastRestart !== null) {
                $now = time();
                $elapsed = $now - $lastRestart;
                $cooldownSeconds = $cooldownMinutes * 60;

                if ($elapsed < $cooldownSeconds) {
                    $remainingSeconds = $cooldownSeconds - $elapsed;
                    $remainingMinutes = ceil($remainingSeconds / 60);

                    return redirect('/admin')
                        ->with('error', 'Rate limit exceeded. You recently restarted the bot.')
                        ->with('remaining_cooldown', $remainingMinutes);
                }
            }

            // Dispatch job for async execution
            RestartBotContainerJob::dispatch($user->id, $realPath);

            // Store current timestamp in cache (expires after cooldown period)
            Cache::put($cacheKey, time(), $cooldownSeconds ?? $cooldownMinutes * 60);

            // Log the restart request
            activity()
                ->causedBy($user)
                ->withProperties([
                    'compose_path' => $realPath,
                    'action' => 'restart_requested',
                ])
                ->log('Requested ladder bot container restart');

            Log::info('Bot restart queued', [
                'user_id' => $user->id,
                'compose_path' => $realPath,
            ]);

            return redirect('/admin')
                ->with('success', 'Bot container restart has been queued. The operation will complete in the background.');

        } catch (Exception $ex) {
            // Log the error with full context
            Log::error('Bot restart request failed', [
                'user_id' => $request->user()?->id,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            // Return user-friendly error message
            $errorMessage = 'Failed to restart bot container';

            // Only show detailed error to super admins
            if ($request->user()?->isAdmin()) {
                $errorMessage .= ': ' . $ex->getMessage();
            } else {
                $errorMessage .= '. Please contact an administrator.';
            }

            return redirect('/admin')
                ->with('error', $errorMessage);
        }
    }
}
