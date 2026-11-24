<?php

namespace Tests\Feature\LadderEvents;

use App\Models\LadderEventAwardResult;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LadderEventServiceTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    private LadderEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEvent();
        $this->service = new LadderEventService();
    }

    private function card(array $payload, string $key): array
    {
        foreach ($payload['awards'] as $c)
        {
            if ($c['key'] === $key)
            {
                return $c;
            }
        }
        $this->fail("card $key missing");
    }

    private function seedGames(): array
    {
        $gino = $this->makeUser('GinoName', ['alias' => 'Gino', 'player' => 'Gino']);
        $edd = $this->makeUser('Edd', ['group' => 'selector_edd']);
        $qien = $this->makeUser('Qien', ['group' => 's_class']);
        $lgnd = $this->makeUser('Lgnd', ['group' => 's_class']);

        $this->addGame([[$gino, 7], $edd], [$qien, $lgnd], ['at' => '2026-12-25 10:00:00']);
        $this->addGame([[$gino, 7], $edd], [$qien, $lgnd], ['at' => '2027-01-05 10:00:00']);
        $this->addGame([$qien, $lgnd], [$gino, $edd], ['at' => '2027-01-06 10:00:00']);

        $this->setPoints($gino, $this->dec, 820);
        $this->setPoints($gino, $this->jan, 640);
        $this->setPoints($qien, $this->dec, 1500);

        return [$gino, $edd, $qien, $lgnd];
    }

    // FR-002, FR-003, FR-009, FR-011, FR-026 (payload), T-15
    public function test_compute_builds_payload(): void
    {
        [$gino, $edd, $qien] = $this->seedGames();
        $this->makePlayer($gino, 'GinoX');   // renamed player in January
        $this->addGame([$gino, $edd], [$qien, $this->makeUser('Other')], ['at' => '2027-01-20 10:00:00']);

        $payload = $this->service->compute($this->event, Carbon::parse('2027-01-21', 'UTC'));

        $this->assertSame(['event', 'status', 'computed_at', 'months', 'sections', 'standings', 'awards'], array_keys($payload));
        $this->assertSame('live', $payload['status']);
        $this->assertCount(30, $payload['awards']);

        // Standings: Qien 1500 + 0 above Gino 820 + 640
        $this->assertSame(['Qien', 'Gino'], array_column($payload['standings'], 'name'));
        $this->assertSame(1460, $payload['standings'][1]['points']);
        $this->assertSame(820, $payload['standings'][1]['dec']);
        $this->assertSame(640, $payload['standings'][1]['jan']);
        $this->assertSame([300, 150], array_column($payload['standings'], 'prize'));

        // Most games: Gino's two player names aggregate into one entry shown by alias
        $mostGames = $this->card($payload, 'most_games');
        $gEntry = collect($mostGames['entries'])->firstWhere('user_key', $gino->id);
        $this->assertSame('Gino', $gEntry['name']);
        $this->assertSame(4, $gEntry['value']);
        $this->assertSame('/ladder/1-2027/ra2-2v2/player/GinoX', $gEntry['url'], 'links to most recent player');

        // Chase the Stars: the description lists the S-class group
        $chase = $this->card($payload, 'chase_the_stars');
        $this->assertSame('Most wins against S-Class players: Lgnd and Qien.', $chase['description']);
        $this->assertSame(3, $chase['leaders'][0]['value']);
        $this->assertCount(2, $chase['leaders'], 'Gino and Edd joint leaders');
        $this->assertTrue($chase['leaders'][0]['tied']);

        // Cuba wins across both months
        $this->assertSame(2, $this->card($payload, 'faction_cuba')['leaders'][0]['value']);
        $this->assertSame(1, $this->card($payload, 'santa_cubaus')['leaders'][0]['value']);

        // Selector award: candidate wins partnered with Edd (FR-021)
        $gate = $this->card($payload, 'gatekeeper_edd');
        $this->assertSame(3, $gate['candidate_count']);
        $this->assertSame('Edd', $gate['selector']);
        $this->assertNull($gate['result']);

        // Payload is stored for requests to read
        $this->assertNotNull($this->event->fresh()->computed_payload);
    }

    // FR-024
    public function test_award_without_values_has_no_leader(): void
    {
        $this->seedGames();
        $payload = $this->service->compute($this->event, Carbon::parse('2027-01-21', 'UTC'));
        $libya = $this->card($payload, 'libya_dominance');
        $this->assertSame([], $libya['leaders']);
        $this->assertSame([], $libya['entries']);
    }

    // FR-020, FR-023
    public function test_manual_result_and_override(): void
    {
        [$gino, $edd, $qien] = $this->seedGames();
        $admin = $this->makeUser('Admin');

        $gameId = DB::table('games')->orderBy('id')->value('id');
        LadderEventAwardResult::create([
            'ladder_event_id' => $this->event->id, 'award_key' => 'giant_killer', 'kind' => 'manual',
            'user_id' => $gino->id, 'game_id' => $gameId, 'note' => '2-0 vs Lgnd/Qien', 'set_by' => $admin->id,
        ]);
        LadderEventAwardResult::create([
            'ladder_event_id' => $this->event->id, 'award_key' => 'most_wins', 'kind' => 'override',
            'user_id' => $qien->id, 'note' => 'Gino disqualified', 'set_by' => $admin->id,
        ]);

        $payload = $this->service->compute($this->event, Carbon::parse('2027-01-21', 'UTC'));

        $gk = $this->card($payload, 'giant_killer');
        $this->assertSame('manual', $gk['result']['kind']);
        $this->assertSame('Gino', $gk['result']['name']);
        $this->assertSame($gameId, $gk['result']['game_id']);
        $this->assertSame("/ladder/12-2026/ra2-2v2/games/$gameId", $gk['result']['game_url']);
        $this->assertSame('2-0 vs Lgnd/Qien', $gk['result']['note']);
        $this->assertSame('community vote', $gk['decided_by']);

        $mw = $this->card($payload, 'most_wins');
        $this->assertSame('override', $mw['result']['kind']);
        $this->assertSame('Qien', $mw['result']['name']);
        $this->assertSame([$edd->id, $gino->id], array_column(array_slice($mw['entries'], 0, 2), 'user_key'), 'computed list kept (Edd/Gino tied 1st)');
    }

    // FR-028
    public function test_holiday_cards_upcoming_before_christmas(): void
    {
        $this->seedGames();
        $payload = $this->service->compute($this->event, Carbon::parse('2026-12-20', 'UTC'));

        $this->assertSame('upcoming', $this->card($payload, 'santa_cubaus')['status']);
        $this->assertSame('upcoming', $this->card($payload, 'new_year_flyby')['status']);
        $this->assertSame('live', $this->card($payload, 'faction_cuba')['status']);

        // Statuses refresh at read time without recomputing
        $served = $this->service->payloadFor($this->event->fresh(), Carbon::parse('2026-12-25 08:00', 'UTC'));
        $this->assertSame('live', $this->card($served, 'santa_cubaus')['status']);
        $this->assertSame('live', $served['status']);
    }

    // FR-030
    public function test_status_lifecycle(): void
    {
        $this->assertSame('upcoming', $this->service->status($this->event, Carbon::parse('2026-11-30 23:59:59', 'UTC')));
        $this->assertSame('live', $this->service->status($this->event, Carbon::parse('2026-12-01 00:00:00', 'UTC')));
        $this->assertSame('live', $this->service->status($this->event, Carbon::parse('2027-01-31 23:59:59', 'UTC')));
        $this->assertSame('pending_final', $this->service->status($this->event, Carbon::parse('2027-02-01 00:00:00', 'UTC')));

        $this->event->status_override = 'final';
        $this->assertSame('final', $this->service->status($this->event, Carbon::parse('2027-02-01', 'UTC')));
    }

    // NFR-004
    public function test_compute_is_deterministic(): void
    {
        $this->seedGames();
        $a = $this->service->build($this->event, Carbon::parse('2027-01-21 10:00:00', 'UTC'));
        $b = $this->service->build($this->event, Carbon::parse('2027-01-21 10:00:07', 'UTC'));
        unset($a['computed_at'], $b['computed_at']);
        $this->assertSame(json_encode($a), json_encode($b));
    }

    // FR-030, FR-032
    public function test_finalised_payload_unaffected_by_later_wash(): void
    {
        [$gino] = $this->seedGames();
        $admin = $this->makeUser('Admin');

        $this->travelTo(Carbon::parse('2027-02-03', 'UTC'));
        $this->service->finalise($this->event, $admin);
        $frozen = $this->service->payloadFor($this->event->fresh());
        $this->assertSame('final', $frozen['status']);
        $this->assertSame('final', $this->card($frozen, 'santa_cubaus')['status']);

        // Admin washes a January game on 5 Feb, and the scheduler recomputes
        $this->travelTo(Carbon::parse('2027-02-05', 'UTC'));
        DB::table('game_reports')->where('game_id', DB::table('games')->orderByDesc('id')->value('id'))->update(['valid' => false]);
        $this->service->compute($this->event->fresh());

        $this->assertSame($frozen, $this->service->payloadFor($this->event->fresh()));
        $this->assertDatabaseHas('activity_log', ['causer_id' => $admin->id, 'event' => 'finalised']);

        // Un-finalise serves the live computation again
        $this->service->unfinalise($this->event->fresh(), $admin);
        $this->assertSame('pending_final', $this->service->payloadFor($this->event->fresh())['status']);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $admin->id, 'event' => 'unfinalised']);
    }

    // Error handling: never computed
    public function test_payload_skeleton_when_never_computed(): void
    {
        $payload = $this->service->payloadFor($this->event, Carbon::parse('2026-11-25', 'UTC'));
        $this->assertSame('upcoming', $payload['status']);
        $this->assertNull($payload['computed_at']);
        $this->assertSame([], $payload['standings']);
        $this->assertSame([], $payload['awards']);
    }

    // FR-018 end to end with stream facts
    public function test_streaming_exclusivity_end_to_end(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U'), $this->makeUser('V'), $this->makeUser('W'), $this->makeUser('X')];
        $obs = $u1;
        // U observes 12h total and plays 9h; V plays 7h
        $this->addGame([$u2, $u3], [$u4, $this->makeUser('Y')], ['duration' => 12 * 3600, 'qm' => [$obs->id => ['live' => true, 'observer' => true]]]);
        $this->addGame([$u1, $u3], [$u4, $this->makeUser('Z')], ['duration' => 9 * 3600, 'qm' => [$u1->id => ['live' => true]]]);
        $this->addGame([$u2, $u3], [$u4, $this->makeUser('Q')], ['duration' => 7 * 3600, 'qm' => [$u2->id => ['live' => true]]]);

        $payload = $this->service->compute($this->event, Carbon::parse('2027-01-21', 'UTC'));

        $eye = $this->card($payload, 'eye_of_beholder');
        $see = $this->card($payload, 'let_me_see');
        $this->assertSame('U', $eye['leaders'][0]['name']);
        $this->assertSame(12.0, (float) $eye['leaders'][0]['value']);
        $this->assertSame('V', $see['leaders'][0]['name']);
        $this->assertSame(['U excluded: already leads Eye of the Beholder'], $see['notes']);
        $this->assertTrue($see['estimated']);
        $this->assertSame('hours', $see['unit']);
    }
}
