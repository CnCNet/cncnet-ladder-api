<?php

namespace App\Http\Services;

use App\Models\QmMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Relays casual matches through a CnCNet V2 tunnel. Casual clients do not negotiate a
 * tunnel themselves, so the server requests one port per player when a match is created.
 */
class CasualTunnelService
{
    private const CACHE_SECONDS = 3600;
    private const REQUEST_TIMEOUT_SECONDS = 2;

    /**
     * When no tunnel could be allocated, the tunnels are not asked again for this long,
     * so that a tunnel outage does not keep every matchmaking request waiting for timeouts.
     */
    private const RETRY_AFTER_SECONDS = 30;
    private const UNAVAILABLE_CACHE_KEY = 'qm_casual_tunnels_unavailable';

    /**
     * Tunnels can be disabled for local development, then players connect to each other directly.
     */
    public function isEnabled(): bool
    {
        return !empty(config('qm.casual_tunnels', []));
    }

    /**
     * Requests one port per player from the configured tunnels.
     * @return array|null ['ip', 'port', 'name', 'ports' => int[]], or null if no tunnel is available
     */
    public function allocate(int $playerCount): ?array
    {
        if (Cache::has(self::UNAVAILABLE_CACHE_KEY))
            return null;

        $tunnel = $this->allocatePorts($playerCount, config('qm.casual_tunnels', []));
        if ($tunnel === null)
        {
            Log::warning("[CasualTunnelService] No tunnel available, trying again in " . self::RETRY_AFTER_SECONDS . " seconds.");
            Cache::put(self::UNAVAILABLE_CACHE_KEY, true, self::RETRY_AFTER_SECONDS);
        }

        return $tunnel;
    }

    /**
     * Assigns the ports of an allocated tunnel to the players of a match.
     */
    public function assignTunnel(QmMatch $qmMatch, array $tunnel): void
    {
        $players = $qmMatch->players()->orderBy('color')->get()->filter(fn($player) => !$player->isObserver())->values();

        Cache::put(self::cacheKey($qmMatch->id), ['ip' => $tunnel['ip'], 'port' => $tunnel['port']], self::CACHE_SECONDS);

        foreach ($players as $index => $player)
        {
            $player->port = $tunnel['ports'][$index];
            $player->save();
        }
    }

    /**
     * Gets the tunnel assigned to a match, or null if no tunnel is known for it.
     * @return array|null ['ip' => string, 'port' => int]
     */
    public function getTunnel(int $qmMatchId): ?array
    {
        return Cache::get(self::cacheKey($qmMatchId));
    }

    /**
     * Requests ports from the first tunnel that has enough free slots.
     * @param array $tunnels list of ['ip' => string, 'port' => int, 'name' => string]
     * @return array|null ['ip', 'port', 'name', 'ports' => int[]]
     */
    private function allocatePorts(int $playerCount, array $tunnels): ?array
    {
        foreach ($tunnels as $tunnel)
        {
            try
            {
                $response = Http::timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->get("http://{$tunnel['ip']}:{$tunnel['port']}/request", ['clients' => $playerCount]);

                if (!$response->successful())
                    continue;

                $ports = self::parsePorts($response->body());
                if (count($ports) >= $playerCount)
                {
                    Log::info("[CasualTunnelService] Allocated {$playerCount} ports from {$tunnel['name']} ({$tunnel['ip']}:{$tunnel['port']})");

                    return $tunnel + ['ports' => $ports];
                }
            }
            catch (\Throwable $e)
            {
                Log::warning("[CasualTunnelService] Failed to allocate ports from {$tunnel['ip']}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Parses a tunnel response such as "[1234,-5678]". Ports above 32767 are sent as negative numbers.
     * @return int[]
     */
    private static function parsePorts(string $body): array
    {
        $ports = [];
        foreach (explode(',', str_replace(['[', ']'], '', trim($body))) as $part)
        {
            $port = (int)trim($part);
            if ($port < 0)
            {
                $port += 65536;
            }

            if ($port > 0 && $port <= 65535)
            {
                $ports[] = $port;
            }
        }

        return $ports;
    }

    private static function cacheKey(int $qmMatchId): string
    {
        return "qm_casual_match_tunnel:{$qmMatchId}";
    }
}
