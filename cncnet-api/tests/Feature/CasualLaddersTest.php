<?php

namespace Tests\Feature;

use App\Http\Services\LadderService;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\Feature\Api\Qm\MatchRequest\QmPlayerHelper;
use Tests\TestCase;

/**
 * Casual ladders are played without accounts and ratings, so they are kept out of the
 * ladder lists and the username registration of ranked play.
 */
class CasualLaddersTest extends TestCase
{
    use RefreshDatabase;
    use QmPlayerHelper;
    use JwtAuthHelper;

    private $casualLadder;
    private $rankedLadder;

    protected function setUp(): void
    {
        parent::setUp();

        // Ladder histories are created for the month of the current (test) time
        Carbon::setTestNow(Carbon::create(2026, 4, 20, 10, 10, 0));

        // makeLadder() always uses the abbreviation "tl"
        $this->casualLadder = $this->makeLadder(2);
        $this->casualLadder->is_casual = true;
        $this->casualLadder->save();
        $this->makeLadderHistory($this->casualLadder);

        $this->rankedLadder = $this->makeLadder(2);
        $this->rankedLadder->abbreviation = 'tl-ranked';
        $this->rankedLadder->save();
        $this->makeLadderHistory($this->rankedLadder);
    }

    public function test_casual_ladders_are_not_listed_with_ranked_ladders(): void
    {
        $ladderService = new LadderService();

        $lists = [
            'website ladders' => $ladderService->getLatestLadders()->pluck('ladder.abbreviation')->all(),
            'current ladders' => $ladderService->getLadders()->pluck('abbreviation')->all(),
            'ladder api' => $this->getJson('/api/v1/ladder')->json('*.abbreviation'),
        ];

        foreach ($lists as $list => $abbreviations)
        {
            $this->assertContains('tl-ranked', $abbreviations, $list);
            $this->assertNotContains('tl', $abbreviations, $list);
        }
    }

    public function test_registered_users_cannot_create_usernames_on_casual_ladders(): void
    {
        $user = $this->makeUser('RegUser');

        $this->jwtAuth($user)->postJson('/api/v1/player/create', ['username' => 'RegName', 'ladderAbbrev' => 'tl'])
            ->assertStatus(400);
        $this->actingAs($user)->post('/account/tl/username', ['username' => 'RegName']);

        $this->assertEquals(0, Player::where('ladder_id', $this->casualLadder->id)->count());

        // Ranked ladders are not affected
        $this->jwtAuth($user)->postJson('/api/v1/player/create', ['username' => 'RegName', 'ladderAbbrev' => 'tl-ranked'])
            ->assertStatus(200);
    }
}
