<?php

namespace App\Services\LadderEvents\Calculators;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\EventContext;

interface AwardCalculator
{
    /** @return list<array{user_key:int, value:int|float}> unsorted */
    public function compute(AwardDefinition $award, EventContext $ctx): array;
}
