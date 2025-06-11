<?php

namespace Tests\Unit\Api\Qm;

use App\Http\Services\QuickMatchService;
use Tests\TestCase;

class TeamMatchMakingLogicTest extends TestCase
{

    public function test_find_possible_matches(): void
    {
        $service = new QuickMatchService();

        $possibleMatches = $service->findPossibleMatches(
            1,
            1230,
            [
                ['id' => 2, 'rank' => 1550],
                ['id' => 3, 'rank' => 1020],
                ['id' => 4, 'rank' => 1600],
                ['id' => 5, 'rank' => 1123],
                ['id' => 6, 'rank' => 2000],
            ]
        );

        $best = $service->findBestMatch($possibleMatches);

        // The most balanced match would be
        //   team A : 1230 + 1550
        //   team B : 1600 + 1123

        $this->assertEquals(1, $best['teamA']['player1']);
        $this->assertEquals(2, $best['teamA']['player2']);
        $this->assertEquals(4, $best['teamB']['player1']);
        $this->assertEquals(5, $best['teamB']['player2']);
    }
}
