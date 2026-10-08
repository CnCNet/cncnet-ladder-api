<?php

namespace App\Http\Services;

use App\Models\GameReplay;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReplayService
{
    // First bytes of a .yrrp file - Magic 0x50525259 followed by the format version.
    private const MAGIC = "YRRP";
    private const SUPPORTED_VERSION = 1;

    private function disk()
    {
        return Storage::disk(config('replays.disk'));
    }

    public function maxUploadBytes(): int
    {
        return (int) config('replays.max_upload_mb') * 1024 * 1024;
    }

    public function maxTotalBytes(): int
    {
        return (int) config('replays.max_total_mb') * 1024 * 1024;
    }

    /**
     * Total bytes currently accounted for by stored replays.
     */
    public function totalStoredBytes(): int
    {
        return (int) GameReplay::sum('file_size');
    }

    /**
     * Checks the replay magic and format version rather than the reported MIME type or extension,
     * neither of which the uploader can be trusted on.
     */
    public function looksLikeReplay(?UploadedFile $file): bool
    {
        if ($file === null || !$file->isValid())
        {
            return false;
        }

        $handle = @fopen($file->getRealPath(), 'rb');
        if ($handle === false)
        {
            return false;
        }

        $head = fread($handle, 8);
        fclose($handle);

        if ($head === false || strlen($head) < 8 || substr($head, 0, 4) !== self::MAGIC)
        {
            return false;
        }

        return unpack('V', substr($head, 4, 4))[1] === self::SUPPORTED_VERSION;
    }

    /**
     * Stores an uploaded replay, replacing any existing replay for the same game and player.
     */
    public function storeReplay(int $gameId, int $playerId, int $userId, UploadedFile $file): GameReplay
    {
        // Randomised name rather than one derived from game/player ids, so replay files cannot be
        // enumerated if the storage root is ever served directly by mistake.
        $filename = Str::random(40) . '.yrrp';
        $directory = config('replays.directory');

        $this->disk()->putFileAs($directory, $file, $filename);

        // A re-upload supersedes the previous file. Remove the old one from disk first, otherwise
        // it would be orphaned and never counted against budget.
        $existing = GameReplay::where('game_id', $gameId)->where('player_id', $playerId)->first();
        if ($existing !== null)
        {
            $this->deleteFile($existing);
        }

        $replay = GameReplay::updateOrCreate(
            ['game_id' => $gameId, 'player_id' => $playerId],
            [
                'user_id'   => $userId,
                'filename'  => $filename,
                'file_size' => $file->getSize(),
            ]
        );

        $this->enforceStorageBudget();

        return $replay;
    }

    /**
     * Deletes oldest-first until stored replays fit within the configured budget. Runs after each
     * upload rather than on a schedule, so the budget cannot be exceeded for long.
     */
    public function enforceStorageBudget(): void
    {
        $budget = $this->maxTotalBytes();
        if ($budget <= 0)
        {
            return;
        }

        $total = $this->totalStoredBytes();
        if ($total <= $budget)
        {
            return;
        }

        Log::info("ReplayService: replay storage at {$total} bytes exceeds budget of {$budget} bytes, evicting oldest.");

        // Chunked oldest-first so a large backlog cannot load every row into memory at once.
        GameReplay::orderBy('created_at', 'asc')->orderBy('id', 'asc')
            ->chunkById(100, function ($replays) use (&$total, $budget)
            {
                foreach ($replays as $replay)
                {
                    if ($total <= $budget)
                    {
                        return false;
                    }

                    $total -= (int) $replay->file_size;
                    $this->deleteFile($replay);
                    $replay->delete();
                }

                return true;
            });
    }

    /**
     * Removes a replay's file from disk. The database row is handled separately so a missing file
     * never blocks the row being replaced or deleted.
     */
    private function deleteFile(GameReplay $replay): void
    {
        $path = config('replays.directory') . '/' . $replay->filename;

        try
        {
            if ($this->disk()->exists($path))
            {
                $this->disk()->delete($path);
            }
        }
        catch (\Exception $ex)
        {
            Log::warning("ReplayService: could not delete replay file {$path}: " . $ex->getMessage());
        }
    }

    /**
     * Absolute path of a stored replay, or null when the file is missing.
     */
    public function replayPath(GameReplay $replay): ?string
    {
        $path = config('replays.directory') . '/' . $replay->filename;

        if (!$this->disk()->exists($path))
        {
            return null;
        }

        return $this->disk()->path($path);
    }

    /**
     * Filename offered to the browser. Includes game and player so several downloads stay
     * distinguishable, and uses the .yrrp extension the client lists replays by.
     */
    public function downloadName(GameReplay $replay): string
    {
        $playerName = optional($replay->player)->username ?? "player{$replay->player_id}";
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $playerName);

        return "replay_game{$replay->game_id}_{$safeName}.yrrp";
    }
}
