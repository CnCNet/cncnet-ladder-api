<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\TestCase;

class ClanMatchRequestTest extends TestCase
{
    use RefreshDatabase;

    use QmPlayerHelper;
    use JwtAuthHelper;

    private $ladder;
    private $history;

    private $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->date = Carbon::create(2024, 4, 20, 10, 10, 0);
        Carbon::setTestNow($this->date);

        // make a ladder
        $this->ladder = $this->makeLadder(4, clan: true);
        $this->history = $this->makeLadderHistory($this->ladder);

    }

    private function matchMeUpRequestV1(Player $player, ?array $body = null) {

        $matchMeUpUrl = '/api/v1/qm/'.$this->ladder->abbreviation.'/';
        $matchMeUpPayload = array_merge([
            'version' => '1.83',
            'type' => 'match me up',
            'map_bitfield' => 234325324,
            'side' => 1,
            'map_sides' => [1,1,1,1]
        ], $body ?? []);

        return $this
            ->jwtAuth($player->user)
            ->post($matchMeUpUrl.$player->username, $matchMeUpPayload);
    }

    public function test_match_up(): void
    {


        $p1 = $this->makePlayerForLadder('test1', $this->ladder, $this->makeUser('test1'));
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $this->makeUser('test2'));
        $p3 = $this->makePlayerForLadder('test3', $this->ladder, $this->makeUser('test3'));
        $p4 = $this->makePlayerForLadder('test4', $this->ladder, $this->makeUser('test4'));

        $this->makePlayerHistory($p1, $this->history);
        $this->makePlayerHistory($p2, $this->history);
        $this->makePlayerHistory($p3, $this->history);
        $this->makePlayerHistory($p4, $this->history);

        $r1 = $this->matchMeUpRequestV1($p1);
        $r2 = $this->matchMeUpRequestV1($p2);
        $r3 = $this->matchMeUpRequestV1($p3);
        $r4 = $this->matchMeUpRequestV1($p4);

        Carbon::setTestNow($this->date->clone()->addSeconds(8));

        $r1 = $this->matchMeUpRequestV1($p1)->json();
        $r2 = $this->matchMeUpRequestV1($p2)->json();
        $r3 = $this->matchMeUpRequestV1($p3)->json();
        $r4 = $this->matchMeUpRequestV1($p4)->json();

        /*dump([
            $r1,
            $r2,
            $r3,
            $r4,
        ]);*/

        $this->assertEquals('spawn', $r1['type']);
        $this->assertEquals('spawn', $r2['type']);
        $this->assertEquals('spawn', $r3['type']);
        $this->assertEquals('spawn', $r4['type']);
    }
}
