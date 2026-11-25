<?php

namespace App\Services\LadderEvents;

use Carbon\Carbon;

/**
 * Validated, immutable view of one event in config/ladder_events.php (FR-001).
 */
final class LadderEventDefinition
{
    /**
     * @param string[] $histories ladder_history.short values, in order
     * @param array<string, string> $days day key => Y-m-d (UTC)
     * @param string[] $groups declared named group keys
     * @param array<string, array{title:string, emoji:string}> $sections
     * @param AwardDefinition[] $awards in display order (includes the standings entry)
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $ladder,
        public readonly array $histories,
        public readonly Carbon $starts,
        public readonly Carbon $ends,
        public readonly array $days,
        public readonly array $groups,
        public readonly array $sections,
        public readonly array $awards,
    ) {
    }

    public static function fromConfig(string $slug, array $config): self
    {
        $errors = [];

        foreach (['name', 'ladder', 'histories', 'starts', 'ends', 'awards'] as $required)
        {
            if (empty($config[$required]))
            {
                $errors[] = "missing '$required'";
            }
        }
        if ($errors)
        {
            throw new InvalidEventConfigException("Ladder event '$slug' config invalid: " . implode('; ', $errors));
        }

        $days = $config['days'] ?? [];
        $groups = array_values($config['groups'] ?? []);
        $sections = $config['sections'] ?? [];

        $awards = [];
        $keys = [];
        foreach ($config['awards'] as $i => $raw)
        {
            $award = AwardDefinition::fromArray($raw);
            $label = $award->key !== '' ? $award->key : "#$i";

            if ($award->key === '')
            {
                $errors[] = "award #$i has no key";
            }
            if (isset($keys[$award->key]))
            {
                $errors[] = "duplicate award key '{$award->key}'";
            }
            $keys[$award->key] = true;

            if (!in_array($award->type, AwardDefinition::TYPES, true))
            {
                $errors[] = "award '$label' has unknown type '{$award->type}'";
                continue;
            }
            if ($sections && !isset($sections[$award->section]))
            {
                $errors[] = "award '$label' has unknown section '{$award->section}'";
            }
            if (count($award->prizes) === 0)
            {
                $errors[] = "award '$label' has no prizes";
            }
            foreach ($award->prizes as $p)
            {
                if (!is_int($p) || $p < 0)
                {
                    $errors[] = "award '$label' has an invalid prize";
                }
            }

            $allowedFilters = match ($award->type) {
                AwardDefinition::TYPE_COUNT => AwardDefinition::COUNT_FILTERS,
                AwardDefinition::TYPE_STREAMING => AwardDefinition::STREAMING_FILTERS,
                default => [],
            };
            foreach (array_keys($award->filters) as $filter)
            {
                if (!in_array($filter, $allowedFilters, true))
                {
                    $errors[] = "award '$label' has unknown filter '$filter'";
                }
            }

            if ($award->type === AwardDefinition::TYPE_COUNT && !in_array($award->metric, AwardDefinition::METRICS, true))
            {
                $errors[] = "award '$label' has unknown metric '{$award->metric}'";
            }

            if ($award->type === AwardDefinition::TYPE_STREAMING
                && (count($award->roles) === 0 || array_diff($award->roles, AwardDefinition::ROLES)))
            {
                $errors[] = "award '$label' has invalid roles";
            }

            foreach ($award->days() as $day)
            {
                if (!isset($days[$day]))
                {
                    $errors[] = "award '$label' references undeclared day '$day'";
                }
            }

            foreach ($award->referencedGroups() as $group)
            {
                if (!in_array($group, $groups, true))
                {
                    $errors[] = "award '$label' references undeclared group '$group'";
                }
            }

            if (isset($award->filters['faction']) && !is_int($award->filters['faction']))
            {
                $errors[] = "award '$label' faction must be a side local_id";
            }
            if (isset($award->filters['partner_outside_top'])
                && (!is_int($award->filters['partner_outside_top']) || $award->filters['partner_outside_top'] < 1))
            {
                $errors[] = "award '$label' partner_outside_top must be a positive integer";
            }

            $awards[] = $award;
        }

        // Exclusivity must be mutual.
        $byKey = [];
        foreach ($awards as $a)
        {
            $byKey[$a->key] = $a;
        }
        foreach ($awards as $a)
        {
            if ($a->exclusiveWith === null)
            {
                continue;
            }
            $other = $byKey[$a->exclusiveWith] ?? null;
            if ($other === null || $other->exclusiveWith !== $a->key)
            {
                $errors[] = "award '{$a->key}' exclusive_with must reference an award that points back";
            }
        }

        if (count(array_filter($awards, fn ($a) => $a->type === AwardDefinition::TYPE_STANDINGS)) > 1)
        {
            $errors[] = 'only one standings award is allowed';
        }

        try
        {
            $starts = Carbon::parse($config['starts'], 'UTC');
            $ends = Carbon::parse($config['ends'], 'UTC');
            foreach ($days as $date)
            {
                Carbon::parse($date, 'UTC');
            }
        }
        catch (\Throwable $e)
        {
            throw new InvalidEventConfigException("Ladder event '$slug' config invalid: bad date (" . $e->getMessage() . ')');
        }
        if ($ends->lte($starts))
        {
            $errors[] = 'ends must be after starts';
        }

        if ($errors)
        {
            throw new InvalidEventConfigException("Ladder event '$slug' config invalid: " . implode('; ', $errors));
        }

        return new self(
            slug: $slug,
            name: $config['name'],
            ladder: $config['ladder'],
            histories: array_values($config['histories']),
            starts: $starts,
            ends: $ends,
            days: $days,
            groups: $groups,
            sections: $sections,
            awards: $awards,
        );
    }

    public function standingsAward(): ?AwardDefinition
    {
        foreach ($this->awards as $a)
        {
            if ($a->type === AwardDefinition::TYPE_STANDINGS)
            {
                return $a;
            }
        }
        return null;
    }

    /** @return AwardDefinition[] every award shown as a card (everything but standings) */
    public function cardAwards(): array
    {
        return array_values(array_filter($this->awards, fn ($a) => $a->type !== AwardDefinition::TYPE_STANDINGS));
    }

    public function award(string $key): ?AwardDefinition
    {
        foreach ($this->awards as $a)
        {
            if ($a->key === $key)
            {
                return $a;
            }
        }
        return null;
    }

    /** @return array{0:Carbon,1:Carbon} UTC start/end of a holiday day (FR-008). */
    public function dayWindow(string $day): array
    {
        $start = Carbon::parse($this->days[$day], 'UTC')->startOfDay();
        return [$start, $start->copy()->endOfDay()];
    }

    /** @return array<string, array{0:Carbon,1:Carbon}> */
    public function dayWindows(): array
    {
        $out = [];
        foreach (array_keys($this->days) as $day)
        {
            $out[$day] = $this->dayWindow($day);
        }
        return $out;
    }

    /**
     * Window an award card's status follows: its holiday days if it has any, else the event (FR-028).
     * @return array{0:Carbon,1:Carbon}
     */
    public function awardWindow(AwardDefinition $award): array
    {
        $days = $award->days();
        if (count($days) === 0)
        {
            return [$this->starts->copy(), $this->ends->copy()];
        }

        $windows = array_map(fn ($d) => $this->dayWindow($d), $days);
        usort($windows, fn ($a, $b) => $a[0] <=> $b[0]);
        return [$windows[0][0], $windows[count($windows) - 1][1]];
    }

    public function totalPrizes(): int
    {
        return array_sum(array_map(fn ($a) => $a->prizeTotal(), $this->awards));
    }
}
