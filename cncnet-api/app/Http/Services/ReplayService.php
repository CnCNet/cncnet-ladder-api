<?php

namespace App\Http\Services;

use App\Http\Services\Replay\ReplayCanonical;
use App\Http\Services\Replay\ReplayChat;
use App\Http\Services\Replay\ReplayFileReader;
use App\Http\Services\Replay\ReplayStatsBuilder;
use App\Jobs\Qm\BuildReplayStatsJob;
use App\Models\GameReplay;
use App\Models\GameReplayChat;
use App\Models\GameReplaySeals;
use App\Models\GameReplayStats;
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

        // The seals and, for the first copy of a game, the statistics: off the upload request.
        BuildReplayStatsJob::dispatch($replay->id);

        return $replay;
    }

    /**
     * Whether a game still needs its statistics read from a replay. Every player's recording holds
     * the same simulation, so one current build per game is enough.
     */
    public function needsStats(int $gameId): bool
    {
        $version = GameReplayStats::where('game_id', $gameId)->value('version');
        return $version === null || (int) $version < ReplayStatsBuilder::VERSION;
    }

    /**
     * Reads a replay into its game's statistics. Returns false when the replay had none to give -
     * an older recording, or one cut off before the spawner closed it.
     */
    public function buildStats(GameReplay $replay): bool
    {
        $path = $this->replayPath($replay);
        if ($path === null)
        {
            return false;
        }

        $builder = new ReplayStatsBuilder();
        try
        {
            $stats = $builder->build($path);
        }
        catch (\Throwable $ex)
        {
            Log::warning("ReplayService: could not read stats from replay {$replay->id}: " . $ex->getMessage());
            return false;
        }

        if ($stats === null)
        {
            return false;
        }

        GameReplayStats::updateOrCreate(
            ['game_id' => $replay->game_id],
            [
                'game_replay_id' => $replay->id,
                'version'        => ReplayStatsBuilder::VERSION,
                'stats'          => $stats,
            ]
        );

        return true;
    }

    /**
     * Keeps a replay's canonical seals, recomputed from its contents rather than taken from the seals
     * it carries, and whether those matched: a copy edited after the game fails that check unless its
     * chain was redone, and then it no longer agrees with the other players' copies. Returns false
     * when the replay carries no seals.
     */
    public function storeSeals(GameReplay $replay): bool
    {
        $path = $this->replayPath($replay);
        if ($path === null)
        {
            return false;
        }

        try
        {
            $reader = new ReplayFileReader($path);
            $complete = $reader->header()['cleanShutdown'];
            $verified = ReplayCanonical::verify($reader);
        }
        catch (\Throwable $ex)
        {
            Log::warning("ReplayService: could not read seals from replay {$replay->id}: " . $ex->getMessage());
            return false;
        }

        if ($verified === null)
        {
            return false;
        }

        $list = [];
        foreach ($verified['seals'] as $through => $hash)
        {
            $list[] = [(int) $through, $hash];
        }

        GameReplaySeals::updateOrCreate(
            ['game_id' => $replay->game_id, 'player_id' => $replay->player_id],
            ['seals' => $list, 'complete' => $complete, 'consistent' => $verified['consistent']]
        );

        return true;
    }

    /**
     * Keeps the chat a replay recorded, for the game page to merge with the other copies'. Returns
     * false when the file cannot be read; a game with no chat stores an empty list.
     */
    public function storeChat(GameReplay $replay): bool
    {
        $path = $this->replayPath($replay);
        if ($path === null)
        {
            return false;
        }

        try
        {
            $messages = ReplayChat::read(new ReplayFileReader($path));
        }
        catch (\Throwable $ex)
        {
            Log::warning("ReplayService: could not read chat from replay {$replay->id}: " . $ex->getMessage());
            return false;
        }

        GameReplayChat::updateOrCreate(
            ['game_id' => $replay->game_id, 'player_id' => $replay->player_id],
            ['version' => ReplayChat::VERSION, 'messages' => $messages]
        );

        return true;
    }

    /** Whether a replay's chat has yet to be read, or was read by an older ReplayChat. */
    public function needsChat(GameReplay $replay): bool
    {
        $version = GameReplayChat::where('game_id', $replay->game_id)
            ->where('player_id', $replay->player_id)->value('version');
        return $version === null || (int) $version < ReplayChat::VERSION;
    }

    /**
     * Everything said in a game, from every copy's chat, each line once and in order. A copy whose
     * seals show it was edited after the game verifies none of its lines. Returns how many copies it
     * came from and the 'lines'; null when no copy's chat has been read.
     */
    public function chat(int $gameId): ?array
    {
        $rows = GameReplayChat::where('game_id', $gameId)->orderBy('player_id')->get();
        if ($rows->isEmpty())
        {
            return null;
        }

        $edited = GameReplaySeals::where('game_id', $gameId)->where('consistent', false)
            ->pluck('player_id')->map(fn ($id) => (int) $id)->all();

        return [
            'copies' => $rows->count(),
            'lines'  => ReplayChat::merge($rows->pluck('messages', 'player_id')->all(), $edited),
        ];
    }

    /**
     * Whether the players' copies of a game agree. Every copy of one game has the same seal for every
     * full interval both reached, so two copies that differ were either edited or recorded on machines
     * that went out of sync - the interval they first differ in says when. A copy that does not match
     * its own seals was edited after the game, and is listed apart rather than compared.
     *
     * Null with fewer than two copies to compare. Otherwise how many copies there are, the players
     * whose copy was edited, and for each pair of the rest that disagree, the first interval they
     * differ in.
     */
    public function integrity(int $gameId): ?array
    {
        $rows = GameReplaySeals::where('game_id', $gameId)->orderBy('player_id')->get();
        if ($rows->count() < 2)
        {
            return null;
        }

        $edited = $rows->where('consistent', false)->pluck('player_id')->values()->all();

        // Only full intervals: the last seal of a copy covers wherever that recording stopped.
        $full = $rows->where('consistent', true)->mapWithKeys(function ($row)
        {
            $seals = [];
            foreach ($row->seals as [$through, $hash])
            {
                if ($through % ReplayFileReader::SEAL_INTERVAL_FRAMES === ReplayFileReader::SEAL_INTERVAL_FRAMES - 1)
                {
                    $seals[$through] = $hash;
                }
            }
            return [$row->player_id => $seals];
        });

        $players = $full->keys()->all();
        $differences = [];
        $lastShared = -1;

        foreach ($players as $i => $a)
        {
            foreach (array_slice($players, $i + 1) as $b)
            {
                $shared = array_intersect_key($full[$a], $full[$b]);
                ksort($shared);
                foreach ($shared as $through => $hash)
                {
                    if ($full[$b][$through] !== $hash)
                    {
                        $differences[] = ['players' => [$a, $b], 'frame' => $through - ReplayFileReader::SEAL_INTERVAL_FRAMES + 1];
                        continue 2;
                    }
                    $lastShared = max($lastShared, $through);
                }
            }
        }

        return [
            'copies'      => $rows->count(),
            'agree'       => count($differences) === 0 && count($edited) === 0,
            'checkedTo'   => $lastShared,
            'edited'      => $edited,
            'differences' => $differences,
        ];
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
                    GameReplayStats::where('game_replay_id', $replay->id)->update(['game_replay_id' => null]);
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
