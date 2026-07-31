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
     * Dispatches a background job to restart the bot using a sudo-configured
     * script. Includes rate limiting to prevent abuse.
     */
    public function restart(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new Exception('User not authenticated.');
            }

            // Get configuration
            $scriptPath = config('bot.restart_script_path');
            $cooldownMinutes = config('bot.restart_cooldown_minutes', 30);

            // Validate configuration
            if (!$scriptPath) {
                throw new Exception('BOT_RESTART_SCRIPT is not configured. Please contact system administrator.');
            }

            // Validate script exists and is executable
            $realPath = realpath($scriptPath);

            if ($realPath === false || !file_exists($realPath)) {
                throw new Exception('Bot restart script not found at configured path.');
            }

            if (!is_executable($realPath)) {
                throw new Exception('Bot restart script is not executable.');
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
            RestartBotContainerJob::dispatch($user, $realPath);

            // Store current timestamp in cache (expires after cooldown period)
            Cache::put($cacheKey, time(), $cooldownMinutes * 60);

            // Log the restart request
            activity()
                ->causedBy($user)
                ->withProperties([
                    'script_path' => $realPath,
                    'action' => 'restart_requested',
                ])
                ->log('Requested ladder bot container restart');

            Log::info('Bot restart queued', [
                'user_id' => $user->id,
                'script_path' => $realPath,
            ]);

            return redirect('/admin')
                ->with('success', 'Bot container restart requested. The operation will complete in the background.');

        } catch (Exception $ex) {
            // Log the error with full context
            Log::error('Bot restart request failed', [
                'user_id' => $request->user()?->id,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            // Return user-friendly error message
            $errorMessage = 'Failed to restart bot container';

            // Only show detailed error for clarity
            if ($ex->getMessage()) {
                $errorMessage .= ': ' . $ex->getMessage();
            }

            return redirect('/admin')
                ->with('error', $errorMessage);
        }
    }
}
