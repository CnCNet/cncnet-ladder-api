<?php

namespace App\Jobs;

use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RestartBotContainerJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Dispatchable, Queueable;

    public User $user;
    public string $scriptPath;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $scriptPath)
    {
        $this->user = $user;
        $this->scriptPath = $scriptPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate script path
            $realPath = realpath($this->scriptPath);

            if ($realPath === false || !file_exists($realPath)) {
                throw new Exception("Restart script does not exist: {$this->scriptPath}");
            }

            if (!is_executable($realPath)) {
                throw new Exception("Restart script is not executable: {$realPath}");
            }

            // Get timeout from config
            $timeout = config('bot.restart_timeout_seconds', 120);

            // Execute restart script via sudo
            $process = new Process([
                'sudo',
                $realPath
            ]);

            $process->setTimeout($timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());

            // Log successful restart
            activity()
                ->causedBy($this->user)
                ->withProperties([
                    'script_path' => $realPath,
                    'output' => $output,
                    'success' => true,
                ])
                ->log('Restarted ladder bot container');

            Log::info('Bot container restarted successfully', [
                'user_id' => $this->user->id,
                'script_path' => $realPath,
            ]);

        } catch (Exception $e) {
            // Log failure
            activity()
                ->causedBy($this->user)
                ->withProperties([
                    'script_path' => $this->scriptPath,
                    'error' => $e->getMessage(),
                    'success' => false,
                ])
                ->log('Failed to restart ladder bot container');

            Log::error('Bot container restart failed', [
                'user_id' => $this->user->id,
                'script_path' => $this->scriptPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('RestartBotContainerJob failed permanently', [
            'user_id' => $this->user->id,
            'script_path' => $this->scriptPath,
            'error' => $exception->getMessage(),
        ]);
    }
}
