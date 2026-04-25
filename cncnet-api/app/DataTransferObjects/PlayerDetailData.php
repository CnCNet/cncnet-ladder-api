<?php

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for Player Detail information
 *
 * Note: This DTO is included as an example of the DTO pattern and best practices,
 * but is not currently used in production code. The Action class uses a simpler
 * object cast approach for pragmatic backward compatibility with the view layer.
 *
 * This file demonstrates:
 * - Immutable data containers (readonly properties)
 * - Type-safe data transfer
 * - Clean object construction
 * - Conversion methods
 *
 * Consider using this pattern for new features or API endpoints where you have
 * full control over the data structure.
 */
class PlayerDetailData
{
    public function __construct(
        public readonly string $username,
        public readonly int $id,
        public readonly ?object $player = null,
        public readonly ?object $elo = null,
    ) {}

    /**
     * Create from ladder service response
     */
    public static function fromLadderService(array $ladderPlayerData): self
    {
        return new self(
            username: $ladderPlayerData['username'] ?? '',
            id: $ladderPlayerData['id'] ?? 0,
            player: (object) ($ladderPlayerData['player'] ?? []),
            elo: isset($ladderPlayerData['elo']) ? (object) $ladderPlayerData['elo'] : null,
        );
    }

    /**
     * Convert to array for view
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'id' => $this->id,
            'player' => $this->player,
            'elo' => $this->elo,
        ];
    }

    /**
     * Convert to object for backward compatibility with views
     */
    public function toObject(): object
    {
        return (object) $this->toArray();
    }
}
