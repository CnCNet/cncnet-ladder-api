<?php

namespace App\Services\LadderEvents;

use Carbon\Carbon;

/**
 * Everything the calculators read. Built once per computation; calculators are pure over it.
 */
final class EventContext
{
    /** @var array<string, array{0:int,1:int}> day key => [start ts, end ts] (UTC) */
    public readonly array $dayWindows;

    /**
     * @param GameFact[] $facts
     * @param StreamFact[] $streams
     * @param array<string, array<int, true>> $groups group key => set of user keys
     * @param array<int, array<int, array{points:int, rank:int}>> $standings history id => user key => standing
     * @param int[] $historyIds in event order
     */
    public function __construct(
        public readonly LadderEventDefinition $definition,
        public readonly array $facts,
        public readonly array $streams,
        public readonly array $groups,
        public readonly array $standings,
        public readonly array $historyIds,
        public readonly Carbon $now,
    ) {
        $windows = [];
        foreach ($definition->dayWindows() as $day => [$start, $end])
        {
            $windows[$day] = [$start->getTimestamp(), $end->getTimestamp()];
        }
        $this->dayWindows = $windows;
    }

    public function group(string $key): array
    {
        return $this->groups[$key] ?? [];
    }

    /** FR-008: true when $ts falls inside any of the given UTC days. */
    public function inDays(int $ts, array $days): bool
    {
        foreach ($days as $day)
        {
            [$start, $end] = $this->dayWindows[$day];
            if ($ts >= $start && $ts <= $end)
            {
                return true;
            }
        }
        return false;
    }
}
