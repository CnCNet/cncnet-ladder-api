<?php

namespace App\Services\LadderEvents\Calculators;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\EventContext;
use App\Services\LadderEvents\GameFact;

/**
 * Manual awards are not ranked. For "selected by X" awards this returns the candidate
 * wins per user: eligible wins partnered with a member of the selector group (FR-021).
 */
class ManualCalculator implements AwardCalculator
{
    public function __construct(private CountCalculator $counter = new CountCalculator())
    {
    }

    public function compute(AwardDefinition $award, EventContext $ctx): array
    {
        if ($award->candidates === null)
        {
            return [];
        }

        $counts = [];
        foreach ($this->candidateFacts($award, $ctx) as $fact)
        {
            $counts[$fact->userKey] = ($counts[$fact->userKey] ?? 0) + 1;
        }

        $out = [];
        foreach ($counts as $userKey => $value)
        {
            $out[] = ['user_key' => $userKey, 'value' => $value];
        }
        return $out;
    }

    /** @return GameFact[] newest first */
    public function candidateFacts(AwardDefinition $award, EventContext $ctx): array
    {
        if ($award->candidates === null)
        {
            return [];
        }

        $facts = iterator_to_array(
            $this->counter->matching(['partner_group' => $award->candidates], true, $ctx),
            false
        );

        usort($facts, fn (GameFact $a, GameFact $b) => [$b->playedAt, $b->gameId, $a->userKey] <=> [$a->playedAt, $a->gameId, $b->userKey]);
        return $facts;
    }
}
