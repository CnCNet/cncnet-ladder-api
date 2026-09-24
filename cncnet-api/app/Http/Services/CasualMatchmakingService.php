<?php

namespace App\Http\Services;

use App\Models\Ladder;
use App\Models\Player;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use App\Models\User;
use App\Models\UserSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Casual matchmaking on casual ladders. Casual players do not log in: they play under their
 * CnCNet nickname with a casual account that is separate from any registered account.
 * Matches are created by CasualMatchupHandler and spawn.ini is built with the regular
 * QuickMatchSpawnService functions.
 */
class CasualMatchmakingService
{
    /**
     * Casual clients poll this often. Queue entries that are not updated for STALE_ENTRY_SECONDS
     * belong to clients that left without quitting and are not matched anymore.
     */
    public const CHECKBACK_SECONDS = 5;
    public const STALE_ENTRY_SECONDS = 10;

    /**
     * A search that is not updated for this long, e.g. because the client crashed, is removed like a quit.
     * Clients poll every CHECKBACK_SECONDS and receive their match on the next poll.
     */
    private const ABANDONED_SEARCH_SECONDS = 60;

    /**
     * Casual accounts use the reserved .invalid domain, so they can never receive mail
     * and cannot be mistaken for registered accounts.
     */
    private const CASUAL_ACCOUNT_EMAIL_SUFFIX = '@casual.invalid';

    /**
     * Same rules as the client's NameValidator: at most 16 characters and no digit or hyphen as the first character.
     */
    private const PLAYER_NAME_PATTERN = '/^[a-zA-Z_\[\]\{\}\^`|\\\\][a-zA-Z0-9_\-\[\]\{\}\^`|\\\\]{0,15}$/';

    private const QUEUE_COUNTS_CACHE_KEY = 'qm_casual_queue_counts';

    /**
     * Every new casual name creates an account, so a connection can only use a few new names per hour.
     */
    private const NEW_NAMES_PER_HOUR = 10;
    private const NEW_NAME_LIMIT_KEY = 'qm_casual_new_names:';

    /**
     * The random token that a client sends with every request of a search, see CasualMatchUpController.
     */
    private const SEARCH_TOKEN_PATTERN = '/^[A-Za-z0-9\-]{16,64}$/';
    private const SEARCH_TOKEN_KEY = 'qm_casual_search_token:';
    private const SEARCH_TOKEN_SECONDS = 3600;

    private QuickMatchService $quickMatchService;
    private PlayerService $playerService;
    private CasualTunnelService $tunnelService;

    public function __construct(QuickMatchService $quickMatchService, PlayerService $playerService, CasualTunnelService $tunnelService)
    {
        $this->quickMatchService = $quickMatchService;
        $this->playerService = $playerService;
        $this->tunnelService = $tunnelService;
    }

    public function isValidPlayerName(string $playerName): bool
    {
        return preg_match(self::PLAYER_NAME_PATTERN, $playerName) === 1;
    }

    public function isCasualAccount(User $user): bool
    {
        return str_ends_with(strtolower($user->email), self::CASUAL_ACCOUNT_EMAIL_SUFFIX);
    }

    /**
     * Creates a casual player on a casual ladder. A name has one casual account on all casual ladders,
     * separate from any registered account with the same name. Casual accounts are only created here and
     * an existing account is never used for a casual name, not even one with a casual account email,
     * because any email address can be registered on the website.
     * Returns null if too many new names were used from the connection.
     */
    public function createPlayer(Ladder $ladder, string $playerName, string $ip): ?Player
    {
        $user = $this->findCasualUser($playerName);

        if ($user === null)
        {
            $limitKey = self::NEW_NAME_LIMIT_KEY . $ip;
            if (RateLimiter::tooManyAttempts($limitKey, self::NEW_NAMES_PER_HOUR))
                return null;

            RateLimiter::hit($limitKey, 3600);

            $user = User::create([
                'name' => $playerName,
                'email' => Str::uuid() . self::CASUAL_ACCOUNT_EMAIL_SUFFIX,
                'password' => bcrypt(Str::random(32)),
            ]);
        }

        $player = new Player();
        $player->username = $playerName;
        $player->ladder_id = $ladder->id;
        $player->user_id = $user->id;
        $player->save();

        return $player;
    }

    /**
     * The casual account of a name is the account of its players on the other casual ladders.
     */
    private function findCasualUser(string $playerName): ?User
    {
        return Player::where('username', $playerName)
            ->whereHas('ladder', fn($query) => $query->where('is_casual', true))
            ->get()
            ->map(fn(Player $player) => $player->user)
            ->first(fn(?User $user) => $user !== null && $this->isCasualAccount($user));
    }

    public function isValidSearchToken(string $searchToken): bool
    {
        return preg_match(self::SEARCH_TOKEN_PATTERN, $searchToken) === 1;
    }

    public function startSearch(QmMatchPlayer $qmPlayer, string $searchToken): void
    {
        Cache::put(self::SEARCH_TOKEN_KEY . $qmPlayer->id, $searchToken, self::SEARCH_TOKEN_SECONDS);
    }

    /**
     * Whether the token belongs to the client that started the search. When the token of a search was lost
     * from the cache, e.g. after a restart, the search goes to the next client that continues it.
     */
    public function checkSearchToken(QmMatchPlayer $qmPlayer, string $searchToken): bool
    {
        $ownToken = Cache::get(self::SEARCH_TOKEN_KEY . $qmPlayer->id);
        if ($ownToken === null)
        {
            $this->startSearch($qmPlayer, $searchToken);
            return true;
        }

        return hash_equals($ownToken, $searchToken);
    }

    /**
     * Finds the player's current search. Abandoned searches are removed first, so that a new search
     * gets a new record with the current game file hash and never a match that was made long ago.
     */
    public function findWaitingQmPlayer(Player $player): ?QmMatchPlayer
    {
        $this->removeWaitingQmPlayers(
            QmMatchPlayer::where('player_id', $player->id)
                ->where('waiting', true)
                ->where('updated_at', '<', Carbon::now()->subSeconds(self::ABANDONED_SEARCH_SECONDS))
        );

        return QmMatchPlayer::where('player_id', $player->id)->where('waiting', true)->first();
    }

    /**
     * Creates the quick match player for a casual queue request.
     * Returns null if the requested side is not allowed on the ladder.
     */
    public function createQmPlayer(Request $request, Player $player, Ladder $ladder): ?QmMatchPlayer
    {
        $side = (int)$request->input('side', 0);
        $port = (int)$request->input('ip_port', 0);
        $clientVersion = $request->input('client_version');

        // Casual requests need no account, so only the fields that casual matchmaking uses are passed on.
        // Casual clients have no map preferences and cannot determine their public address.
        // map_sides has one entry per map slot (bit_idx) of the map pool.
        $mapSlotCount = max(1, (int)$ladder->mapPool->maps->max('bit_idx') + 1);
        $fields = [
            'side' => $side,
            'ip_address' => isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $request->getClientIp(),
            'ip_port' => $port > 0 && $port <= 65535 ? $port : null,
            'client_version' => is_string($clientVersion) && strlen($clientVersion) <= 32 ? $clientVersion : null,
            'map_bitfield' => 0xffffffff,
            'map_sides' => array_fill(0, $mapSlotCount, $side),
        ];
        $qmRequest = $request->duplicate([], $fields);
        $qmRequest->setJson(new InputBag($fields));

        // Quick match and spawn.ini generation read the user settings, which casual accounts do not have yet
        UserSettings::firstOrCreate(['user_id' => $player->user_id]);

        $this->playerService->setActiveUsername($player, $ladder);
        $this->playerService->createPlayerRatingIfNull($player);

        $qmPlayer = $this->quickMatchService->createQMPlayer($qmRequest, $player, $ladder->current_history);

        if (!$this->quickMatchService->checkPlayerSidesAreValid($qmPlayer, $side, $ladder->qmLadderRules))
        {
            $qmPlayer->delete();
            return null;
        }

        $qmPlayer->save();

        return $qmPlayer;
    }

    /**
     * Removes the player's search from the queue like a ranked quick match quit: a match that the player has
     * not received yet is left too, and its other players go back to the queue.
     * Only the client that started the search can remove it.
     */
    public function leaveQueue(Player $player, string $searchToken): void
    {
        $ownQmPlayerIds = QmMatchPlayer::where('player_id', $player->id)
            ->where('waiting', true)
            ->get()
            ->filter(fn(QmMatchPlayer $qmPlayer) => $this->checkSearchToken($qmPlayer, $searchToken))
            ->pluck('id');

        $this->removeWaitingQmPlayers(QmMatchPlayer::whereIn('id', $ownQmPlayerIds));
    }

    private function removeWaitingQmPlayers(Builder $qmPlayers): void
    {
        $qmPlayerIds = $qmPlayers->pluck('id');
        if ($qmPlayerIds->isEmpty())
            return;

        QmQueueEntry::whereIn('qm_match_player_id', $qmPlayerIds)->delete();
        QmMatchPlayer::whereIn('id', $qmPlayerIds)->delete();

        Cache::forget(self::QUEUE_COUNTS_CACHE_KEY);
    }

    /**
     * Number of players waiting on each casual ladder, keyed by ladder abbreviation.
     */
    public function getQueueCounts(): array
    {
        return Cache::remember(self::QUEUE_COUNTS_CACHE_KEY, 1, function ()
        {
            $counts = QmQueueEntry::where('qm_queue_entries.updated_at', '>=', Carbon::now()->subSeconds(self::STALE_ENTRY_SECONDS))
                ->join('ladder_history', 'qm_queue_entries.ladder_history_id', '=', 'ladder_history.id')
                ->join('ladders', 'ladder_history.ladder_id', '=', 'ladders.id')
                ->where('ladders.is_casual', true)
                ->selectRaw('ladders.abbreviation as ladder, count(*) as count')
                ->groupBy('ladders.abbreviation')
                ->pluck('count', 'ladder');

            return Ladder::where('is_casual', true)
                ->pluck('abbreviation')
                ->mapWithKeys(fn($abbreviation) => [$abbreviation => (int)($counts[$abbreviation] ?? 0)])
                ->all();
        });
    }

    /**
     * Whether a match can be sent to one of its players.
     */
    public function canStartMatch(QmMatch $qmMatch, QmMatchPlayer $qmPlayer, Ladder $ladder): bool
    {
        // Like in ranked quick match, a match that lost a player before this player received it is not started
        if ($qmMatch->players()->where('id', '<>', $qmPlayer->id)->count() < $ladder->qmLadderRules->player_count - 1)
            return false;

        // Neither is a match whose tunnel is unknown, which would send the players each other's addresses
        return !$this->tunnelService->isEnabled() || $this->tunnelService->getTunnel($qmMatch->id) !== null;
    }

    /**
     * Builds the spawn.ini for a player of a casual match.
     */
    public function createSpawnStruct(QmMatch $qmMatch, QmMatchPlayer $qmPlayer, Ladder $ladder): array
    {
        $otherQmMatchPlayers = $qmMatch->players()
            ->where('id', '<>', $qmPlayer->id)
            ->orderBy('color', 'ASC')
            ->get();

        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmPlayer, $ladder, $ladder->qmLadderRules);
        $spawnStruct = QuickMatchSpawnService::appendOthersToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers);
        $spawnStruct["spawn"]["Settings"]["PlayerCount"] = $ladder->qmLadderRules->player_count;

        $allPlayers = $otherQmMatchPlayers->concat([$qmPlayer]);

        // Countries and colors are assigned by the server
        foreach ($allPlayers as $player)
        {
            $multiIndex = $player->color + 1;
            $spawnStruct["spawn"]["HouseCountries"]["Multi{$multiIndex}"] = $player->actual_side;
            $spawnStruct["spawn"]["HouseColors"]["Multi{$multiIndex}"] = $player->color;
        }

        $spawnStruct = self::appendTeamAlliances($spawnStruct, $allPlayers);

        $tunnel = $this->tunnelService->getTunnel($qmMatch->id);
        if ($tunnel !== null)
        {
            $spawnStruct["spawn"]["Tunnel"] = ["Ip" => $tunnel['ip'], "Port" => (int)$tunnel['port']];

            // 0.0.0.0 makes the spawner reach the other players through the tunnel
            foreach (array_keys($spawnStruct["spawn"]) as $section)
            {
                if (str_starts_with($section, "Other"))
                {
                    $spawnStruct["spawn"][$section]["Ip"] = "0.0.0.0";
                }
            }
        }

        return $spawnStruct;
    }

    /**
     * Allies every player with all of their teammates. Each ally gets its own HouseAlly key,
     * so teams of any size are supported.
     */
    private static function appendTeamAlliances(array $spawnStruct, $players): array
    {
        $allyKeys = ["HouseAllyOne", "HouseAllyTwo", "HouseAllyThree", "HouseAllyFour", "HouseAllyFive", "HouseAllySix", "HouseAllySeven"];

        foreach ($players->groupBy('team') as $team => $teamPlayers)
        {
            // 1v1 players have no team
            if (empty($team))
                continue;

            foreach ($teamPlayers as $player)
            {
                $multiIndex = $player->color + 1;
                $allies = $teamPlayers->filter(fn($ally) => $ally->id !== $player->id)->values();

                foreach ($allies as $allyIndex => $ally)
                {
                    $spawnStruct["spawn"]["Multi{$multiIndex}_Alliances"][$allyKeys[$allyIndex]] = $ally->color;
                }
            }
        }

        return $spawnStruct;
    }
}
