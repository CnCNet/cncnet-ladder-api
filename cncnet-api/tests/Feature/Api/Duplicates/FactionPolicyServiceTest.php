<?php

namespace Tests\Unit;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\MapPool;
use App\Models\QmMap;
use App\Models\QmMatchPlayer;
use App\Http\Services\FactionPolicyService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FakeQmMatchPlayer extends QmMatchPlayer
{
    public function save(array $options = [])
    {
        // No saving required.
        return true;
    }
}

class FactionPolicyServiceTest extends TestCase
{
    private $svc;

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();

        $this->svc = Mockery::mock(\App\Http\Services\FactionPolicyService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->svc->shouldReceive('getForcedStats')->andReturn([0,0])->byDefault();
    }

    private function mockStatsFor(QmMatchPlayer $p1, array $s1, QmMatchPlayer $p2, array $s2): void
    {
        $this->svc->shouldReceive('getForcedStats')
            ->withArgs(fn($ladder,$history,$player,$forcedId) => $player === $p1)
            ->andReturn($s1);

        $this->svc->shouldReceive('getForcedStats')
            ->withArgs(fn($ladder,$history,$player,$forcedId) => $player === $p2)
            ->andReturn($s2);
    }

    private function makeLadder(int $id = 1): Ladder
    {
        $ladder = new Ladder();
        $ladder->id = $id;
        return $ladder;
    }

    private function makeHistory(string $start = '2000-01-01 00:00:00', string $end = '2099-12-31 23:59:59'): LadderHistory
    {
        $h = new LadderHistory();
        $h->start_date = $start;
        $h->end_date   = $end;
        return $h;
    }

    private function makePlayerWithActualSide(int $pid, int $side): FakeQmMatchPlayer
    {
        $p = new FakeQmMatchPlayer();
        $p->player_id   = $pid;
        $p->actual_side = $side;
        return $p;
    }

    private function mockQmMap(array $allowedSides, string $desc = 'TestMap'): QmMap
    {
        $qmMap = Mockery::mock(QmMap::class)->makePartial();
        $qmMap->shouldReceive('sides_array')->andReturn($allowedSides);
        $qmMap->description = $desc;
        return $qmMap;
    }

    #[Test]
    public function applyPolicy1v1_no_map_candidates_keeps_sides(): void
    {
        $pool = new MapPool();
        $pool->forced_faction_id     = 5;
        $pool->forced_faction_ratio  = 0.5;
        $pool->invalid_faction_pairs = [];

        // Map allows factions 1, 2, 3, 4, but not 5.
        $qmMap = $this->mockQmMap([1,2,3,4]);

        $ladder  = $this->makeLadder(1);
        $history = $this->makeHistory();

        $p1 = $this->makePlayerWithActualSide(101, 2);
        $p2 = $this->makePlayerWithActualSide(202, 3);

        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);

        // Factions stay the same because forced faction cannot be applied to given map.
        $this->assertSame(2, $p1->actual_side);
        $this->assertSame(3, $p2->actual_side);
    }

    #[Test]
    public function applyPolicy1v1_mueller_vs_drunk(): void
    {
        $pool = new MapPool();
        $pool->forced_faction_id     = 9;
        $pool->forced_faction_ratio  = 0.5;
        $pool->invalid_faction_pairs = [[0, 6], [0, 0], [6, 6], [9, 9]];

        // Map allows forced faction.
        $qmMap = $this->mockQmMap([0, 6, 9]);

        $ladder  = $this->makeLadder(1);
        $history = $this->makeHistory();

        $mueller = $this->makePlayerWithActualSide(101, 0);
        $drunkmaster = $this->makePlayerWithActualSide(202, 0);

        $this->mockStatsFor($mueller, [24, 1], $drunkmaster, [8, 7]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $mueller, $drunkmaster);
        $this->assertSame(0, $drunkmaster->actual_side);
        $this->assertSame(9, $mueller->actual_side);
    }

    #[Test]
    public function applyPolicy1v1_sneer_vs_funky(): void
    {
        $pool = new MapPool();
        $pool->forced_faction_id     = 9;
        $pool->forced_faction_ratio  = 0.5;
        $pool->invalid_faction_pairs = [[0, 6], [0, 0], [6, 6], [9, 9]];

        // Map allows forced faction.
        $qmMap = $this->mockQmMap([0, 6, 9]);

        $ladder  = $this->makeLadder(1);
        $history = $this->makeHistory();

        $sneer = $this->makePlayerWithActualSide(101, 6);
        $funky = $this->makePlayerWithActualSide(202, 6);

        $this->mockStatsFor($sneer, [13, 3], $funky, [20, 0]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $sneer, $funky);
        $this->assertSame(6, $sneer->actual_side);
        $this->assertSame(9, $funky->actual_side);
    }

    #[Test]
    public function applyPolicy1v1_one_candidate_applies(): void
    {
        $pool = new MapPool();
        $pool->forced_faction_id     = 9;
        $pool->forced_faction_ratio  = 0.5;
        $pool->invalid_faction_pairs = [[0, 6], [0, 0], [6, 6], [9, 9]];

        // Map allows forced faction.
        $qmMap = $this->mockQmMap([0, 6, 9]);

        $ladder  = $this->makeLadder(1);
        $history = $this->makeHistory();

        // Player 2 has been forced 100% of the times, while players 1, though forced
        // much more often, has lower percentage and will be forced to 9.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 0);

        $this->mockStatsFor($p1, [5, 3], $p2, [1, 1]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(9, $p1->actual_side);
        $this->assertSame(0, $p2->actual_side);

        // Player 2 will end up with a forced faction ratio of 0.66 while player 1
        // will have 0.75. That's why player 2 has to change faction.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 0);
        $this->mockStatsFor($p1, [3, 2], $p2, [2, 1]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(6, $p1->actual_side);
        $this->assertSame(9, $p2->actual_side);

        // Player 1 will end up with a forced faction ratio of 2/6 while player 2
        // will have 100%. So faction for player 1 is changed.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 0);
        $this->mockStatsFor($p1, [5, 1], $p2, [0, 0]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(9, $p1->actual_side);
        $this->assertSame(0, $p2->actual_side);

        // Player 2 will end up with a forced faction ratio of 2/6 while player 1
        // will have 0.5. So faction for player 1 is changed.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 0);
        $this->mockStatsFor($p1, [20, 15], $p2, [4, 2]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(6, $p1->actual_side);
        $this->assertSame(9, $p2->actual_side);
    }

    #[Test]
    public function applyPolicy1v1_test_avs_month(): void
    {
        $pool = new MapPool();
        $pool->forced_faction_id     = 0;
        $pool->forced_faction_ratio  = 0.5;
        $pool->invalid_faction_pairs = [[ 0, 0 ], [ 6, 6 ]];

        $qmMap = $this->mockQmMap([0, 6]);

        $ladder  = $this->makeLadder(1);
        $history = $this->makeHistory();

        // Player 1's faction is changed (though it would be ok to change player 2).
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 6);
        $this->mockStatsFor($p1, [7, 2], $p2, [0, 0]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(0, $p1->actual_side);
        $this->assertSame(6, $p2->actual_side);

        // Next game players 2's faction is forced.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 6);
        $this->mockStatsFor($p1, [8, 3], $p2, [1, 0]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(6, $p1->actual_side);
        $this->assertSame(0, $p2->actual_side);

        // Next game players 1's faction is forced again.
        $p1 = $this->makePlayerWithActualSide(101, 6);
        $p2 = $this->makePlayerWithActualSide(202, 6);
        $this->mockStatsFor($p1, [9, 3], $p2, [2, 1]);
        $this->svc->applyPolicy1v1($pool, $ladder, $history, $qmMap, $p1, $p2);
        $this->assertSame(0, $p1->actual_side);
        $this->assertSame(6, $p2->actual_side);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
