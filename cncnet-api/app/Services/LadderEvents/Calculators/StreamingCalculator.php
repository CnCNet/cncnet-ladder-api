<?php

namespace App\Services\LadderEvents\Calculators;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\EventContext;

/**
 * FR-016, FR-017: estimated streaming hours = Σ best-report duration of streamed games / 3600.
 */
class StreamingCalculator implements AwardCalculator
{
    public function compute(AwardDefinition $award, EventContext $ctx): array
    {
        $wantPlayer = in_array('player', $award->roles, true);
        $wantObserver = in_array('observer', $award->roles, true);
        $days = $award->days();

        $seconds = [];
        foreach ($ctx->streams as $s)
        {
            if ($s->isObserver ? !$wantObserver : !$wantPlayer)
            {
                continue;
            }
            if ($days && !$ctx->inDays($s->playedAt, $days))
            {
                continue;
            }
            $seconds[$s->userKey] = ($seconds[$s->userKey] ?? 0) + $s->duration;
        }

        $out = [];
        foreach ($seconds as $userKey => $total)
        {
            $out[] = ['user_key' => $userKey, 'value' => round($total / 3600, 2)];
        }
        return $out;
    }
}
