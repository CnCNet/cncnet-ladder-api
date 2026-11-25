<?php

namespace Tests\Feature\LadderEvents;

use Database\Seeders\LadderEventDemoCleanupSeeder;
use Database\Seeders\LadderEventDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LadderEventDemoSeederTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    public function test_demo_seed_and_cleanup_round_trip(): void
    {
        $this->setUpEvent();
        $real = $this->makeUser('RealMember', ['group' => 's_class']);
        $counts = fn () => [
            DB::table('users')->count(), DB::table('players')->count(), DB::table('games')->count(),
            DB::table('player_game_reports')->count(), DB::table('stats2')->count(), DB::table('player_caches')->count(),
            DB::table('qm_matches')->count(), DB::table('qm_match_players')->count(), DB::table('ladder_event_award_results')->count(),
        ];
        $before = $counts();

        $this->seed(LadderEventDemoSeeder::class);

        $payload = $this->event->fresh()->computed_payload;
        $this->assertCount(10, $payload['standings']);
        $this->assertCount(30, $payload['awards']);
        $this->assertNotEmpty(collect($payload['awards'])->firstWhere('key', 'let_me_see')['leaders']);
        $this->assertSame(810, DB::table('games')->where('wol_game_id', LadderEventDemoSeeder::DEMO_MARKER)->count());

        $this->seed(LadderEventDemoCleanupSeeder::class);

        $this->assertSame($before, $counts());
        $this->assertDatabaseHas('ladder_event_group_members', ['user_id' => $real->id, 'group_key' => 's_class']);
        $this->assertNull($this->event->fresh()->computed_payload);
    }
}
