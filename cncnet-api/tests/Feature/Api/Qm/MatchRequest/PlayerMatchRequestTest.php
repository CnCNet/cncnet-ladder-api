<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\TestCase;

class PlayerMatchRequestTest extends TestCase
{
    use RefreshDatabase;

    use QmPlayerHelper;
    use JwtAuthHelper;

    private $user1;
    private $player1;
    private $ladder;

    protected function setUp(): void
    {
        parent::setUp();

        // make a user
        $this->user1 = $this->makeUser('test1');

        // make a ladder
        $this->ladder = $this->makeLadder(2);

        $this->player1 = $this->makePlayerForLadder('test1', $this->ladder, $this->user1);

    }

    public function test_match_me_up_on_empty_q(): void
    {
        $ladderName = $this->ladder->abbreviation;
        $playerName = $this->player1->username;

        $lh = $this->makeLadderHistory($this->ladder);
        $this->makePlayerHistory($this->player1, $lh);

        $response = $this
            ->jwtAuth($this->user1)
            ->post('/api/v1/qm/'.$ladderName.'/'.$playerName, [
                'version' => '1.83',
                'type' => 'match me up',
                'map_bitfield' => 234325324,
                'side' => 1
            ]);

        $json = $response->json();

        $this->assertEquals('please wait', $json['type']);
    }

    public function test_check_back()
    {

        $d = Carbon::create(2024, 4, 20, 10, 10, 0);

        Carbon::setTestNow($d);

        $ladderName = $this->ladder->abbreviation;
        $playerName = $this->player1->username;

        $lh = $this->makeLadderHistory($this->ladder);
        $this->makePlayerHistory($this->player1, $lh);

        $this
            ->jwtAuth($this->user1)
            ->post('/api/v1/qm/'.$ladderName.'/'.$playerName, [
                'version' => '1.83',
                'type' => 'match me up',
                'map_bitfield' => 234325324,
                'side' => 1
            ]);

        Carbon::setTestNow($d->clone()->addSeconds(8));

        $response = $this
                ->jwtAuth($this->user1)
                ->post('/api/v1/qm/'.$ladderName.'/'.$playerName, [
                    'version' => '1.83',
                    'type' => 'match me up',
                    'map_bitfield' => 234325324,
                    'side' => 1
                ]);

        $json = $response->json();

        $this->assertEquals('please wait', $json['type']);

    }

    public function test_match_me_up_and_find_opponent(): void
    {
        $d = Carbon::create(2024, 4, 20, 10, 10, 0);
        Carbon::setTestNow($d);

        $ladderName = $this->ladder->abbreviation;

        $lh = $this->makeLadderHistory($this->ladder);
        $this->makePlayerHistory($this->player1, $lh);

        $u2 = $this->makeUser('test2');
        $p2 = $this->makePlayerForLadder('test2', $this->ladder, $u2);
        $this->makePlayerHistory($p2, $lh);

        // queue a 1st player
        $this
            ->jwtAuth($this->user1)
            ->post('/api/v1/qm/'.$ladderName.'/'.$this->player1->username, [
                'version' => '1.83',
                'type' => 'match me up',
                'map_bitfield' => 0xffffffff,
                'side' => 1,
                'map_sides' => [1,1,1,1]
            ]);


        Carbon::setTestNow($d->clone()->addSeconds(8));

        // queue a 2nd player
        $this
            ->jwtAuth($u2)
            ->post('/api/v1/qm/'.$ladderName.'/'.$p2->username, [
                'version' => '1.83',
                'type' => 'match me up',
                'map_bitfield' => 0xffffffff,
                'side' => 1,
                'map_sides' => [1,1,1,1]
            ]);

        Carbon::setTestNow($d->clone()->addSeconds(8));

        // queue a 2nd player
        $response = $this
            ->jwtAuth($u2)
            ->post('/api/v1/qm/'.$ladderName.'/'.$p2->username, [
                'version' => '1.83',
                'type' => 'match me up',
                'map_bitfield' => 0xffffffff,
                'side' => 1,
                'map_sides' => [1,1,1,1]
            ]);

        $json = $response->json();

        $this->assertEquals('spawn', $json['type']);
    }
}
