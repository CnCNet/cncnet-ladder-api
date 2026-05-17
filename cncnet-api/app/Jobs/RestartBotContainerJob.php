<?php

namespace App\Jobs;

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

    public int $userId;
    public string $composePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $composePath)
    {
        $this->userId = $userId;
        $this->composePath = $composePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate compose path
            $realPath = realpath($this->composePath);

            if ($realPath === false || !file_exists($realPath)) {
                throw new Exception("Compose file does not exist: {$this->composePath}");
            }

            if (!str_ends_with($realPath, '.yml') && !str_ends_with($realPath, '.yaml')) {
                throw new Exception("Invalid compose file extension: {$realPath}");
            }

            // Execute docker compose using Symfony Process for security
            $process = new Process([
                'docker',
                'compose',
                '-f',
                $realPath,
                'up',
                '-d',
                '--force-recreate'
            ]);

            $process->setTimeout(120); // 2 minute timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());

            // Log successful restart
            activity()
                ->causedBy($this->userId)
                ->withProperties([
                    'compose_path' => $realPath,
                    'output' => $output,
                    'success' => true,
                ])
                ->log('Restarted ladder bot container');

            Log::info('Bot container restarted successfully', [
                'user_id' => $this->userId,
                'compose_path' => $realPath,
            ]);

        } catch (Exception $e) {
            // Log failure
            activity()
                ->causedBy($this->userId)
                ->withProperties([
                    'compose_path' => $this->composePath,
                    'error' => $e->getMessage(),
                    'success' => false,
                ])
                ->log('Failed to restart ladder bot container');

            Log::error('Bot container restart failed', [
                'user_id' => $this->userId,
                'compose_path' => $this->composePath,
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
    public function failed(Exception $exception): void
    {
        Log::critical('RestartBotContainerJob failed permanently', [
            'user_id' => $this->userId,
            'compose_path' => $this->composePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
