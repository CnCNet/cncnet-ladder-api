<?php

namespace Tests\Unit\Spawn;

use App\Http\Services\QuickMatchSpawnService;
use App\Models\Game;
use App\Models\QmMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\Feature\Api\Qm\MatchRequest\QmPlayerHelper;

class SpawnIniTest extends TestCase
{
    use RefreshDatabase;

    use QmPlayerHelper;

    private $ladder;
    private $history;

    protected function setUp(): void
    {
        parent::setUp();
        // make a ladder
        $this->ladder = $this->makeLadder(2);
        $this->history = $this->makeLadderHistory($this->ladder);
    }

    public function test_create_spawn_struct(): void
    {
        $p1 = $this->makePlayerForLadder('test1', $this->ladder, $this->makeUser('test1'));
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $this->makeUser('test2'));

        $qmMatch = $this->makeQmMatch($this->ladder, $this->ladder->mapPool->maps->first());

        $qmMatchPlayer = $this->makeQmMatchPlayer($p1, $this->ladder, $qmMatch, ['color' => 0, 'actual_side' => 1]);
        $this->makeQmMatchPlayer($p2, $this->ladder, $qmMatch, ['color' => 1, 'actual_side' => 1]);

        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmMatchPlayer, $this->ladder, $this->ladder->qmLadderRules);

        //dump($spawnStruct);

        $this->assertEquals(1, $spawnStruct['gameID']);
        $this->assertIsArray($spawnStruct['spawn']);
        $this->assertIsArray($spawnStruct['spawn']['Settings']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['UIMapName']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['MapHash']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Scenario']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Seed']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['GameID']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['WOLGameID']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Host']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Name']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Side']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['Color']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['MyIndex']);
        $this->assertNotNull($spawnStruct['spawn']['Settings']['IsSpectator']);

        $this->assertEquals($this->ladder->qmLadderRules->show_map_preview, $spawnStruct['client']['show_map_preview']);
    }

    public function test_append_others_2(): void {
        $p1 = $this->makePlayerForLadder('test1', $this->ladder, $this->makeUser('test1'));
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $this->makeUser('test2'));

        $qmMatch = $this->makeQmMatch($this->ladder, $this->ladder->mapPool->maps->first());

        $qmMatchPlayer = $this->makeQmMatchPlayer($p1, $this->ladder, $qmMatch, ['color' => 0, 'actual_side' => 1]);
        $otherQmMatchPlayer = $this->makeQmMatchPlayer($p2, $this->ladder, $qmMatch, ['color' => 1, 'actual_side' => 1]);

        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmMatchPlayer, $this->ladder, $this->ladder->qmLadderRules);

        $spawnStruct = QuickMatchSpawnService::appendOthersToSpawnIni($spawnStruct, $qmMatchPlayer, [$otherQmMatchPlayer]);

        $this->assertEquals($qmMatch->game_id, $spawnStruct['gameID']);

        $this->assertEquals('test1', $spawnStruct['spawn']['Settings']['Name']);
        $this->assertEquals('test2', $spawnStruct['spawn']['Other1']['Name']);
    }

    public function test_append_others_4(): void {
        $p1 = $this->makePlayerForLadder('test1', $this->ladder, $this->makeUser('test1'));
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $this->makeUser('test2'));
        $p3 = $this->makePlayerForLadder('test3', $this->ladder, $this->makeUser('test3'));
        $p4 = $this->makePlayerForLadder('test4', $this->ladder, $this->makeUser('test4'));

        $qmMatch = $this->makeQmMatch($this->ladder, $this->ladder->mapPool->maps->first());

        $qmMatchPlayer = $this->makeQmMatchPlayer($p1, $this->ladder, $qmMatch, ['color' => 0, 'actual_side' => 1]);
        $otherQmMatchPlayer1 = $this->makeQmMatchPlayer($p2, $this->ladder, $qmMatch, ['color' => 1, 'actual_side' => 1]);
        $otherQmMatchPlayer2 = $this->makeQmMatchPlayer($p3, $this->ladder, $qmMatch, ['color' => 2, 'actual_side' => 1]);
        $otherQmMatchPlayer3 = $this->makeQmMatchPlayer($p4, $this->ladder, $qmMatch, ['color' => 3, 'actual_side' => 1]);
        $others = [
            $otherQmMatchPlayer1,
            $otherQmMatchPlayer2,
            $otherQmMatchPlayer3
        ];

        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmMatchPlayer, $this->ladder, $this->ladder->qmLadderRules);
        $spawnStruct = QuickMatchSpawnService::appendOthersToSpawnIni($spawnStruct, $qmMatchPlayer, $others);

        $this->assertEquals($qmMatch->game_id, $spawnStruct['gameID']);

        $this->assertEquals('test1', $spawnStruct['spawn']['Settings']['Name']);
        $this->assertEquals('test2', $spawnStruct['spawn']['Other1']['Name']);
        $this->assertEquals('test3', $spawnStruct['spawn']['Other2']['Name']);
        $this->assertEquals('test4', $spawnStruct['spawn']['Other3']['Name']);
    }

    public function test_append_alliance_team(): void {
        $p1 = $this->makePlayerForLadder('test1', $this->ladder, $this->makeUser('test1'));
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $this->makeUser('test2'));
        $p3 = $this->makePlayerForLadder('test3', $this->ladder, $this->makeUser('test3'));
        $p4 = $this->makePlayerForLadder('test4', $this->ladder, $this->makeUser('test4'));

        $qmMatch = $this->makeQmMatch($this->ladder, $this->ladder->mapPool->maps->first());

        $qmMatchPlayer = $this->makeQmMatchPlayer($p1, $this->ladder, $qmMatch, ['color' => 0, 'actual_side' => 1, 'team' => 'A', 'location' => 0]);
        $otherQmMatchPlayer1 = $this->makeQmMatchPlayer($p2, $this->ladder, $qmMatch, ['color' => 1, 'actual_side' => 1, 'team' => 'A', 'location' => 1]);
        $otherQmMatchPlayer2 = $this->makeQmMatchPlayer($p3, $this->ladder, $qmMatch, ['color' => 2, 'actual_side' => 1, 'team' => 'B', 'location' => 2]);
        $otherQmMatchPlayer3 = $this->makeQmMatchPlayer($p4, $this->ladder, $qmMatch, ['color' => 3, 'actual_side' => 1, 'team' => 'B', 'location' => 3]);
        $others = collect([
            $otherQmMatchPlayer1,
            $otherQmMatchPlayer2,
            $otherQmMatchPlayer3
        ]);

        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmMatchPlayer, $this->ladder, $this->ladder->qmLadderRules);
        $spawnStruct = QuickMatchSpawnService::appendOthersToSpawnIni($spawnStruct, $qmMatchPlayer, $others);
        $spawnStruct = QuickMatchSpawnService::appendAlliancesToSpawnIni($spawnStruct, $qmMatchPlayer, $others);

        $this->assertEquals($qmMatch->game_id, $spawnStruct['gameID']);

        $this->assertEquals('test1', $spawnStruct['spawn']['Settings']['Name']);
        $this->assertEquals('test2', $spawnStruct['spawn']['Other1']['Name']);
        $this->assertEquals('test3', $spawnStruct['spawn']['Other2']['Name']);
        $this->assertEquals('test4', $spawnStruct['spawn']['Other3']['Name']);

        $this->assertEquals(1, $spawnStruct['spawn']['Multi1_Alliances']['HouseAllyOne']);
        $this->assertEquals(0, $spawnStruct['spawn']['Multi2_Alliances']['HouseAllyOne']);
        $this->assertEquals(2, $spawnStruct['spawn']['Multi4_Alliances']['HouseAllyOne']);
        $this->assertEquals(3, $spawnStruct['spawn']['Multi3_Alliances']['HouseAllyOne']);
    }

    public function test_append_alliance_clan(): void {
        
    }
}
