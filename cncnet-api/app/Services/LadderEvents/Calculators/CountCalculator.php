<?php

namespace App\Services\LadderEvents\Calculators;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\EventContext;
use App\Services\LadderEvents\GameFact;

/**
 * Counts eligible games or wins per user, filtered by composable predicates.
 * FR-005, FR-008, FR-010 – FR-015.
 */
class CountCalculator implements AwardCalculator
{
    public function compute(AwardDefinition $award, EventContext $ctx): array
    {
        $counts = [];
        foreach ($this->matching($award->filters, $award->metric === 'wins', $ctx) as $fact)
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

    /**
     * Facts that satisfy the filters (and are wins, if $winsOnly). Each fact is one game for one user,
     * so a game is counted at most once per user (FR-011).
     *
     * @return iterable<GameFact>
     */
    public function matching(array $filters, bool $winsOnly, EventContext $ctx): iterable
    {
        $predicates = $this->predicates($filters, $ctx);

        foreach ($ctx->facts as $fact)
        {
            if ($winsOnly && !$fact->win)
            {
                continue;
            }
            foreach ($predicates as $p)
            {
                if (!$p($fact))
                {
                    continue 2;
                }
            }
            yield $fact;
        }
    }

    /** @return list<callable(GameFact):bool> */
    private function predicates(array $filters, EventContext $ctx): array
    {
        $out = [];

        if (!empty($filters['days']))
        {
            $days = (array) $filters['days'];
            $out[] = fn (GameFact $f) => $ctx->inDays($f->playedAt, $days);
        }

        if (array_key_exists('faction', $filters))
        {
            $localId = (int) $filters['faction'];
            $out[] = fn (GameFact $f) => $f->country === $localId;
        }

        if (isset($filters['opponent_group']))
        {
            $group = $ctx->group($filters['opponent_group']);
            $out[] = function (GameFact $f) use ($group) {
                foreach ($f->opponentKeys as $k)
                {
                    if (isset($group[$k]))
                    {
                        return true;
                    }
                }
                return false;
            };
        }

        if (isset($filters['partner_group']))
        {
            $group = $ctx->group($filters['partner_group']);
            $out[] = fn (GameFact $f) => $f->partnerKey !== null && isset($group[$f->partnerKey]);
        }

        if (isset($filters['partner_outside_top']))
        {
            $n = (int) $filters['partner_outside_top'];
            $standings = $ctx->standings;
            $out[] = fn (GameFact $f) => $f->partnerKey !== null
                && ($standings[$f->historyId][$f->partnerKey]['rank'] ?? PHP_INT_MAX) > $n;
        }

        return $out;
    }
}
