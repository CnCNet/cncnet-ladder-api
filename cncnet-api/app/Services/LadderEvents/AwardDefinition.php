<?php

namespace App\Services\LadderEvents;

/**
 * One award (or the standings) from config/ladder_events.php.
 */
final class AwardDefinition
{
    const TYPE_STANDINGS = 'standings';
    const TYPE_COUNT = 'count';
    const TYPE_STREAMING = 'streaming';
    const TYPE_MANUAL = 'manual';

    const TYPES = [self::TYPE_STANDINGS, self::TYPE_COUNT, self::TYPE_STREAMING, self::TYPE_MANUAL];
    const METRICS = ['games', 'wins'];
    const ROLES = ['player', 'observer'];
    const COUNT_FILTERS = ['days', 'faction', 'opponent_group', 'partner_group', 'partner_outside_top'];
    const STREAMING_FILTERS = ['days'];

    /**
     * @param int[] $prizes
     * @param array<string, mixed> $filters
     * @param string[] $roles
     */
    public function __construct(
        public readonly string $key,
        public readonly string $section,
        public readonly string $title,
        public readonly string $description,
        public readonly string $type,
        public readonly array $prizes,
        public readonly ?string $metric = null,
        public readonly array $filters = [],
        public readonly array $roles = [],
        public readonly ?string $candidates = null,
        public readonly ?string $selectorLabel = null,
        public readonly ?string $decidedBy = null,
        public readonly ?string $exclusiveWith = null,
        public readonly bool $exclusiveWinsTies = false,
    ) {
    }

    public static function fromArray(array $a): self
    {
        return new self(
            key: (string) ($a['key'] ?? ''),
            section: (string) ($a['section'] ?? ''),
            title: (string) ($a['title'] ?? ''),
            description: (string) ($a['description'] ?? ''),
            type: (string) ($a['type'] ?? ''),
            prizes: array_values($a['prizes'] ?? []),
            metric: $a['metric'] ?? null,
            filters: $a['filters'] ?? [],
            roles: array_values($a['roles'] ?? []),
            candidates: $a['candidates'] ?? null,
            selectorLabel: $a['selector_label'] ?? null,
            decidedBy: $a['decided_by'] ?? null,
            exclusiveWith: $a['exclusive_with'] ?? null,
            exclusiveWinsTies: (bool) ($a['exclusive_wins_ties'] ?? false),
        );
    }

    /** Day keys this award is restricted to, or [] for the whole event. */
    public function days(): array
    {
        return array_values((array) ($this->filters['days'] ?? []));
    }

    /** Named groups this award references. */
    public function referencedGroups(): array
    {
        $groups = [];
        foreach (['opponent_group', 'partner_group'] as $f)
        {
            if (isset($this->filters[$f]))
            {
                $groups[] = $this->filters[$f];
            }
        }
        if ($this->candidates !== null)
        {
            $groups[] = $this->candidates;
        }
        return $groups;
    }

    /** The group whose members fill the ":members" description placeholder. */
    public function descriptionGroup(): ?string
    {
        return $this->filters['opponent_group'] ?? $this->filters['partner_group'] ?? $this->candidates;
    }

    public function unit(): ?string
    {
        return match ($this->type) {
            self::TYPE_COUNT => $this->metric,
            self::TYPE_STREAMING => 'hours',
            self::TYPE_STANDINGS => 'points',
            default => null,
        };
    }

    /** RA2/YR sides.local_id => faction slug used by the `player-faction-{slug}` icon classes. */
    const FACTION_SLUGS = [
        0 => 'america', 1 => 'korea', 2 => 'france', 3 => 'germany', 4 => 'great-britain',
        5 => 'libya', 6 => 'iraq', 7 => 'cuba', 8 => 'russia',
    ];

    /** Faction slug when the award is restricted to one faction, else null. */
    public function factionSlug(): ?string
    {
        return array_key_exists('faction', $this->filters) ? (self::FACTION_SLUGS[$this->filters['faction']] ?? null) : null;
    }

    public function prizeTotal(): int
    {
        return (int) array_sum($this->prizes);
    }
}
