<?php

namespace App\Services\LadderEvents;

/**
 * One eligible, non-spectator player game report, attributed to a user key (FR-002, FR-004).
 */
final class GameFact
{
    /**
     * @param int[] $opponentKeys user keys of the non-spectator players on the other team(s)
     */
    public function __construct(
        public readonly int $gameId,
        public readonly int $historyId,
        public readonly int $userKey,
        public readonly int $ownerUserId,      // users.id owning the player (may be an alt)
        public readonly string $username,      // player name, used only for profile links
        public readonly ?int $partnerKey,      // FR-006: null when team is null or not exactly 2 players
        public readonly array $opponentKeys,
        public readonly bool $win,             // FR-005: points > 0
        public readonly bool $draw,
        public readonly ?int $country,         // FR-007: stats2.cty, null without stats
        public readonly int $playedAt,         // FR-008: unix timestamp of games.created_at (UTC); report rows are recreated on regrade
    ) {
    }
}
