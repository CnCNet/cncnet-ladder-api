<?php

namespace App\Services\LadderEvents;

use Carbon\Carbon;

/**
 * Turns raw calculator values into award card payloads.
 * FR-018, FR-020, FR-023, FR-024, FR-025, FR-028.
 */
class AwardResolver
{
    const CARD_LIMIT = 5;
    const STANDINGS_LIMIT = 10;

    /**
     * FR-024: drop zeros, sort by value DESC then display name ASC, assign competition places ("1,1,3").
     *
     * @param list<array{user_key:int, value:int|float}> $raw
     * @param array<int, string> $names
     * @return list<array<string, mixed>> entries with place, tied, user_key, name, value (+ any extra raw keys)
     */
    public function rank(array $raw, array $names): array
    {
        $rows = array_values(array_filter($raw, fn ($r) => $r['value'] > 0));

        foreach ($rows as &$r)
        {
            $r['name'] = $names[$r['user_key']] ?? ('#' . $r['user_key']);
        }
        unset($r);

        usort($rows, function ($a, $b) {
            return [$b['value'], strtolower($a['name']), $a['user_key']]
                <=> [$a['value'], strtolower($b['name']), $b['user_key']];
        });

        return $this->assignPlaces($rows);
    }

    /** @param list<array<string, mixed>> $sorted already ordered */
    private function assignPlaces(array $sorted): array
    {
        $out = [];
        $count = count($sorted);
        for ($i = 0; $i < $count; $i++)
        {
            $place = ($i > 0 && $sorted[$i]['value'] == $sorted[$i - 1]['value']) ? $out[$i - 1]['place'] : $i + 1;
            $out[$i] = ['place' => $place, 'tied' => false] + $sorted[$i];
        }

        $perPlace = array_count_values(array_column($out, 'place'));
        foreach ($out as &$e)
        {
            $e['tied'] = $perPlace[$e['place']] > 1;
        }
        unset($e);

        return $out;
    }

    /**
     * FR-018: a user who is the sole or joint leader of both awards in an exclusive pair keeps the one
     * with the higher value (the `exclusive_wins_ties` award if equal) and is removed from the other.
     * Runs once per pair; does not cascade.
     *
     * @param array<string, list<array>> $ranked award key => ranked entries
     * @param array<string, AwardDefinition> $awards
     * @return array{0: array<string, list<array>>, 1: array<string, string[]>} [ranked, notes per award]
     */
    public function applyExclusivity(array $ranked, array $awards): array
    {
        $notes = [];
        $done = [];

        foreach ($awards as $key => $award)
        {
            $otherKey = $award->exclusiveWith;
            if ($otherKey === null || isset($done[$key]) || !isset($awards[$otherKey]))
            {
                continue;
            }
            $done[$key] = $done[$otherKey] = true;
            $other = $awards[$otherKey];

            $leadersA = $this->leaderValues($ranked[$key] ?? []);
            $leadersB = $this->leaderValues($ranked[$otherKey] ?? []);
            $removeFromA = [];
            $removeFromB = [];

            foreach (array_intersect_key($leadersA, $leadersB) as $userKey => $valueA)
            {
                $valueB = $leadersB[$userKey];
                $keepA = $valueA > $valueB || ($valueA == $valueB && $award->exclusiveWinsTies);

                if ($keepA)
                {
                    $removeFromB[$userKey] = true;
                    $notes[$otherKey][] = $this->nameOf($ranked[$key], $userKey) . ' excluded: already leads ' . $award->title;
                }
                else
                {
                    $removeFromA[$userKey] = true;
                    $notes[$key][] = $this->nameOf($ranked[$otherKey], $userKey) . ' excluded: already leads ' . $other->title;
                }
            }

            if ($removeFromA)
            {
                $ranked[$key] = $this->without($ranked[$key], $removeFromA);
            }
            if ($removeFromB)
            {
                $ranked[$otherKey] = $this->without($ranked[$otherKey], $removeFromB);
            }
        }

        return [$ranked, $notes];
    }

    private function leaderValues(array $entries): array
    {
        $out = [];
        foreach ($entries as $e)
        {
            if ($e['place'] === 1)
            {
                $out[$e['user_key']] = $e['value'];
            }
        }
        return $out;
    }

    private function nameOf(array $entries, int $userKey): string
    {
        foreach ($entries as $e)
        {
            if ($e['user_key'] === $userKey)
            {
                return $e['name'];
            }
        }
        return '#' . $userKey;
    }

    private function without(array $entries, array $remove): array
    {
        $kept = array_values(array_filter($entries, fn ($e) => !isset($remove[$e['user_key']])));
        $kept = array_map(function ($e) {
            unset($e['place'], $e['tied']);
            return $e;
        }, $kept);
        return $this->assignPlaces($kept);
    }

    /** FR-028: upcoming / live / final from a window. */
    public function windowStatus(Carbon $starts, Carbon $ends, Carbon $now): string
    {
        if ($now->lt($starts))
        {
            return 'upcoming';
        }
        if ($now->gt($ends))
        {
            return 'final';
        }
        return 'live';
    }

    /**
     * Attach prizes by place (tied users share the place's prize label) and trim to $limit.
     * @return list<array<string, mixed>>
     */
    public function withPrizes(array $entries, array $prizes, int $limit): array
    {
        $out = [];
        foreach (array_slice($entries, 0, $limit) as $e)
        {
            $e['prize'] = $prizes[$e['place'] - 1] ?? null;
            $out[] = $e;
        }
        return $out;
    }

    /**
     * Build the card payload for one award.
     *
     * @param list<array> $ranked
     * @param string[] $notes
     * @param array|null $result manual result (manual awards) or override (computed awards)
     * @param array<string, mixed> $extra merged into the card (candidate_count, selector, …)
     */
    public function card(
        AwardDefinition $award,
        string $description,
        array $ranked,
        array $notes,
        ?array $result,
        Carbon $windowStart,
        Carbon $windowEnd,
        Carbon $now,
        array $extra = []
    ): array {
        $isManual = $award->type === AwardDefinition::TYPE_MANUAL;
        $entries = $isManual ? [] : $this->withPrizes($ranked, $award->prizes, self::CARD_LIMIT);
        $leaders = array_values(array_filter($isManual ? [] : $this->withPrizes($ranked, $award->prizes, PHP_INT_MAX), fn ($e) => $e['place'] === 1));

        return array_merge([
            'key' => $award->key,
            'section' => $award->section,
            'title' => $award->title,
            'description' => $description,
            'type' => $award->type,
            'unit' => $award->unit(),
            'status' => $this->windowStatus($windowStart, $windowEnd, $now),
            'starts' => self::iso($windowStart),
            'ends' => self::iso($windowEnd),
            'prizes' => $award->prizes,
            'estimated' => $award->type === AwardDefinition::TYPE_STREAMING,
            'leaders' => $leaders,
            'entries' => $entries,
            'result' => $result,
            'notes' => array_values($notes),
        ], $extra);
    }

    public static function iso(Carbon $c): string
    {
        return $c->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
