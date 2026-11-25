<?php

namespace App\Services\LadderEvents\Calculators;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\EventContext;

/**
 * FR-009: ladder points summed per user key across the event histories.
 * Each row also carries 'months' => [history_id => points].
 */
class StandingsCalculator implements AwardCalculator
{
    public function compute(AwardDefinition $award, EventContext $ctx): array
    {
        $totals = [];
        $months = [];

        foreach ($ctx->historyIds as $historyId)
        {
            foreach ($ctx->standings[$historyId] ?? [] as $userKey => $row)
            {
                $totals[$userKey] = ($totals[$userKey] ?? 0) + $row['points'];
                $months[$userKey][$historyId] = $row['points'];
            }
        }

        $out = [];
        foreach ($totals as $userKey => $total)
        {
            $breakdown = [];
            foreach ($ctx->historyIds as $historyId)
            {
                $breakdown[$historyId] = $months[$userKey][$historyId] ?? 0;
            }
            $out[] = ['user_key' => $userKey, 'value' => $total, 'months' => $breakdown];
        }
        return $out;
    }
}
