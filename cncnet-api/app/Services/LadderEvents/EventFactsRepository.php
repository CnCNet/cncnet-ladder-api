<?php

namespace App\Services\LadderEvents;

use App\Models\LadderEvent;
use App\Models\PlayerGameReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Loads everything the award calculators need with a fixed number of queries (NFR-003).
 */
class EventFactsRepository
{
    /**
     * Query 1: eligible non-spectator reports, grouped per game into GameFacts.
     * FR-002, FR-004, FR-006, FR-007.
     *
     * @param int[] $historyIds
     * @param array{0:\Carbon\Carbon,1:\Carbon\Carbon}|null $window event starts/ends; games outside it are excluded
     * @return Collection<int, GameFact>
     */
    public function gameFacts(array $historyIds, ?int $onlyGameId = null, ?array $window = null): Collection
    {
        $query = PlayerGameReport::query()
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('players', 'players.id', '=', 'player_game_reports.player_id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->leftJoin('stats2', 'stats2.id', '=', 'player_game_reports.stats_id')
            ->whereIn('games.ladder_history_id', $historyIds)
            ->where('game_reports.best_report', '=', true)
            ->where('game_reports.valid', '=', true)
            ->where('player_game_reports.spectator', '=', false)
            ->where('players.is_bot', '=', false)
            ->selectRaw('
                games.id AS game_id,
                games.ladder_history_id AS history_id,
                player_game_reports.id AS pgr_id,
                player_game_reports.team AS team,
                player_game_reports.points AS points,
                player_game_reports.draw AS draw,
                games.created_at AS created_at,
                stats2.cty AS country,
                players.username AS username,
                users.id AS owner_id,
                COALESCE(users.primary_user_id, users.id) AS user_key')
            ->orderBy('games.id')
            ->orderBy('player_game_reports.id');

        if ($onlyGameId !== null)
        {
            $query->where('games.id', '=', $onlyGameId);
        }

        self::applyWindow($query, $window);

        $facts = [];
        $currentGame = null;
        $rows = [];

        foreach ($query->toBase()->cursor() as $row)
        {
            if ($currentGame !== null && $row->game_id !== $currentGame)
            {
                array_push($facts, ...$this->factsForGame($rows));
                $rows = [];
            }
            $currentGame = $row->game_id;
            $rows[] = $row;
        }
        if ($rows)
        {
            array_push($facts, ...$this->factsForGame($rows));
        }

        return collect($facts);
    }

    /**
     * Derive partner and opponents for each player of one game (FR-006).
     * @return GameFact[]
     */
    private function factsForGame(array $rows): array
    {
        $teams = [];
        foreach ($rows as $r)
        {
            $team = ($r->team === null || $r->team === '') ? null : (string) $r->team;
            $r->team_key = $team;
            if ($team !== null)
            {
                $teams[$team][] = (int) $r->user_key;
            }
        }

        $out = [];
        foreach ($rows as $r)
        {
            $partner = null;
            $opponents = [];
            $myKey = (int) $r->user_key;

            if ($r->team_key !== null)
            {
                $mates = $teams[$r->team_key];
                if (count($mates) === 2)
                {
                    $other = $mates[0] === $myKey ? $mates[1] : $mates[0];
                    // Two of a user's own accounts on one team is not a partnership.
                    $partner = $other === $myKey ? null : $other;
                }

                foreach ($teams as $team => $keys)
                {
                    if ($team !== $r->team_key)
                    {
                        array_push($opponents, ...$keys);
                    }
                }
                $opponents = array_values(array_unique($opponents));
                sort($opponents);
            }

            $out[] = new GameFact(
                gameId: (int) $r->game_id,
                historyId: (int) $r->history_id,
                userKey: $myKey,
                ownerUserId: (int) $r->owner_id,
                username: (string) $r->username,
                partnerKey: $partner,
                opponentKeys: $opponents,
                win: (int) $r->points > 0,
                draw: (bool) $r->draw,
                country: $r->country === null ? null : (int) $r->country,
                playedAt: self::timestamp($r->created_at),
            );
        }

        return $out;
    }

    /**
     * Query 2: monthly ladder standings from player_caches (FR-009, FR-012).
     * Rank is the 1-based row position per history (points DESC, player_id ASC).
     * A user with several player rows in one month gets the points summed and the best rank.
     *
     * @param int[] $historyIds
     * @return array<int, array<int, array{points:int, rank:int}>>
     */
    public function monthlyStandings(array $historyIds): array
    {
        $rows = DB::table('player_caches')
            ->join('players', 'players.id', '=', 'player_caches.player_id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->whereIn('player_caches.ladder_history_id', $historyIds)
            ->orderBy('player_caches.ladder_history_id')
            ->orderByDesc('player_caches.points')
            ->orderBy('player_caches.player_id')
            ->selectRaw('player_caches.ladder_history_id AS history_id, player_caches.points AS points,
                player_caches.player_id AS player_id, COALESCE(users.primary_user_id, users.id) AS user_key')
            ->get();

        $out = [];
        $position = [];
        foreach ($historyIds as $h)
        {
            $out[$h] = [];
        }

        foreach ($rows as $r)
        {
            $h = (int) $r->history_id;
            $key = (int) $r->user_key;
            $position[$h] = ($position[$h] ?? 0) + 1;

            if (!isset($out[$h][$key]))
            {
                $out[$h][$key] = ['points' => 0, 'rank' => $position[$h]];
            }
            $out[$h][$key]['points'] += (int) $r->points;
            $out[$h][$key]['rank'] = min($out[$h][$key]['rank'], $position[$h]);
        }

        return $out;
    }

    /**
     * Query 3: streamed QM games (FR-016). Requires best_report but not valid.
     *
     * @param int[] $historyIds
     * @return Collection<int, StreamFact>
     */
    public function streamFacts(int $ladderId, array $historyIds, ?array $window = null): Collection
    {
        $rows = DB::table('qm_match_players')
            ->join('games', 'games.qm_match_id', '=', 'qm_match_players.qm_match_id')
            ->join('game_reports', function ($join) {
                $join->on('game_reports.game_id', '=', 'games.id')
                    ->where('game_reports.best_report', '=', true);
            })
            ->join('players', 'players.id', '=', 'qm_match_players.player_id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->where('qm_match_players.ladder_id', '=', $ladderId)
            ->where('qm_match_players.twitch_live_at_start', '=', true)
            ->whereIn('games.ladder_history_id', $historyIds)
            ->orderBy('games.id')
            ->orderBy('qm_match_players.id')
            ->selectRaw('qm_match_players.is_observer AS is_observer, game_reports.duration AS duration,
                games.created_at AS created_at, COALESCE(users.primary_user_id, users.id) AS user_key');

        self::applyWindow($rows, $window);
        $rows = $rows->get();

        return $rows->map(fn ($r) => new StreamFact(
            userKey: (int) $r->user_key,
            isObserver: (bool) $r->is_observer,
            duration: max(0, (int) $r->duration),
            playedAt: self::timestamp($r->created_at),
        ))->values();
    }

    /**
     * Query 4: display names (FR-003): the primary user's alias, falling back to name.
     *
     * @param int[] $userKeys
     * @return array<int, string>
     */
    public function userDisplay(array $userKeys): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($userKeys)), 1000) as $chunk)
        {
            $rows = DB::table('users')->whereIn('id', $chunk)->get(['id', 'alias', 'name']);
            foreach ($rows as $r)
            {
                $alias = trim((string) $r->alias);
                $out[(int) $r->id] = $alias !== '' ? $alias : (string) $r->name;
            }
        }
        return $out;
    }

    /**
     * Named groups resolved to user keys. Members whose user no longer exists are skipped.
     *
     * @return array<string, array<int, true>>
     */
    public function groups(LadderEvent $event, array $groupKeys): array
    {
        $out = array_fill_keys($groupKeys, []);

        $rows = DB::table('ladder_event_group_members')
            ->join('users', 'users.id', '=', 'ladder_event_group_members.user_id')
            ->where('ladder_event_group_members.ladder_event_id', '=', $event->id)
            ->selectRaw('ladder_event_group_members.group_key AS group_key,
                COALESCE(users.primary_user_id, users.id) AS user_key')
            ->get();

        foreach ($rows as $r)
        {
            if (array_key_exists($r->group_key, $out))
            {
                $out[$r->group_key][(int) $r->user_key] = true;
            }
        }

        foreach ($out as $g => $members)
        {
            ksort($members);
            $out[$g] = $members;
        }

        return $out;
    }

    /** User key for a users.id (alts resolve to their primary). */
    public function userKeyFor(int $userId): ?int
    {
        $row = DB::table('users')->where('id', '=', $userId)->first(['id', 'primary_user_id']);
        if ($row === null)
        {
            return null;
        }
        return (int) ($row->primary_user_id ?? $row->id);
    }

    /** Restrict a query to games played inside the event's starts/ends (inclusive, UTC). */
    private static function applyWindow($query, ?array $window): void
    {
        if ($window === null)
        {
            return;
        }
        [$from, $to] = $window;
        $query->whereBetween('games.created_at', [
            $from->copy()->utc()->format('Y-m-d H:i:s'),
            $to->copy()->utc()->format('Y-m-d H:i:s'),
        ]);
    }

    private static function timestamp($value): int
    {
        if ($value instanceof \DateTimeInterface)
        {
            return $value->getTimestamp();
        }
        return (int) strtotime($value . ' UTC');
    }
}
