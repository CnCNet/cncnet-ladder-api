<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $this->ladder = $this->makeLadder();

        $this->player1 = $this->makePlayerForLadder('test1', $this->ladder, $this->user1);

    }



    public function test_queue(): void
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
                'side' => 6
            ]);

        dd($response->json());
    }
}
