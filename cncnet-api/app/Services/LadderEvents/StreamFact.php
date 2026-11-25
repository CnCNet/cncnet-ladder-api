<?php

namespace App\Services\LadderEvents;

/**
 * One QM game a user streamed (twitch_live_at_start = 1), FR-016.
 */
final class StreamFact
{
    public function __construct(
        public readonly int $userKey,
        public readonly bool $isObserver,
        public readonly int $duration,   // seconds, best report
        public readonly int $playedAt,   // unix timestamp of games.created_at (UTC)
    ) {
    }
}
