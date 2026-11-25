<?php

namespace Tests\Feature\LadderEvents;

use App\Models\LadderEventAwardResult;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEvent();
        $this->withoutVite();
    }

    private function seedAndCompute(string $now = '2027-01-10 12:00:00'): void
    {
        $this->travelTo(Carbon::parse($now, 'UTC'));

        $gino = $this->makeUser('GinoName', ['alias' => 'Gino']);
        $edd = $this->makeUser('Edd', ['group' => 'selector_edd']);
        $this->makeUser('Monster', ['group' => 'selector_steve']);
        $sander = $this->makeUser('HCKiD', ['alias' => 'Sander', 'player' => 'Sanchez']);
        $qien = $this->makeUser('Qien', ['group' => 's_class']);

        $this->addGame([[$gino, 7], $edd], [$qien, $sander], ['at' => '2026-12-10 10:00:00']);
        $this->addGame([[$sander, 7], $edd], [$qien, $gino], ['at' => '2026-12-11 10:00:00']);
        $this->addGame([[$sander, 7], $edd], [$qien, $gino], ['at' => '2026-12-12 10:00:00']);
        $this->setPoints($sander, $this->dec, 500);

        (new LadderEventService())->compute($this->event->fresh());
    }

    // FR-027
    public function test_api_returns_payload(): void
    {
        $this->seedAndCompute();

        $res = $this->getJson('/api/v1/events/' . self::SLUG);

        $res->assertOk()
            ->assertJsonStructure(['event' => ['slug', 'name', 'ladder', 'starts', 'ends'], 'status', 'computed_at', 'standings', 'awards'])
            ->assertJsonPath('event.name', 'RA2 2v2 Holiday Championship')
            ->assertJsonPath('status', 'live')
            ->assertJsonPath('standings.0.name', 'Sander');
        $this->assertCount(30, $res->json('awards'));
    }

    // FR-029
    public function test_unknown_slug_is_404(): void
    {
        $this->getJson('/api/v1/events/nope')->assertNotFound();
        $this->get('/events/nope')->assertNotFound();
    }

    // NFR-001
    public function test_api_reads_payload_with_few_queries(): void
    {
        $this->seedAndCompute();

        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = $q->sql;
        });

        $this->getJson('/api/v1/events/' . self::SLUG)->assertOk();

        $this->assertLessThanOrEqual(3, count($queries), implode("\n", $queries));
        foreach ($queries as $sql)
        {
            $this->assertStringNotContainsString('player_game_reports', $sql);
        }
    }

    // NFR-006
    public function test_public_json_exposes_no_private_data(): void
    {
        $this->seedAndCompute();
        $alt = $this->makeUser('SanderAlt', ['primary' => DB::table('users')->where('name', 'HCKiD')->value('id')]);
        (new LadderEventService())->compute($this->event->fresh());

        $json = $this->getJson('/api/v1/events/' . self::SLUG)->getContent();

        $this->assertStringNotContainsString('@test.com', $json);
        $this->assertStringNotContainsString('"email"', $json);
        $this->assertStringNotContainsString('ip_address', $json);
        $this->assertStringNotContainsString('"user_key":' . $alt->id . ',', $json);
        $this->assertStringNotContainsString('HCKiD', $json, 'name hidden when alias set');
    }

    // FR-026, FR-003, FR-019, FR-020, FR-021
    public function test_web_page_renders_sections_and_cards(): void
    {
        $this->seedAndCompute();

        $res = $this->get('/events/' . self::SLUG);
        $res->assertOk();

        $html = $res->getContent();
        $positions = [];
        foreach (['Overall Ladder Standings', 'Performance', 'Christmas Day', "New Year&#039;s Day", 'Combined Holiday', 'Player-Based', 'Streaming', 'Faction'] as $title)
        {
            $pos = strpos($html, $title);
            $this->assertNotFalse($pos, "section $title");
            $positions[] = $pos;
        }
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'sections in prize-list order');

        $res->assertSee('Sander')                                       // FR-003 alias, not player name
            ->assertDontSee('>Sanchez<', false)                         // player name only in the link URL
            ->assertSee('estimated*')                                   // FR-019
            ->assertSee('streaming-footnote', false)
            ->assertSee('To be decided (community vote)')               // FR-020
            ->assertSee('3 candidate wins — selected by Edd')           // FR-021
            ->assertSee('selected by Steve')
            ->assertDontSee('selected by Monster')
            ->assertSee('/ladder/12-2026/ra2-2v2/player/Sanchez', false) // FR-026 profile link
            ->assertSee('Live')
            ->assertSee('Sponsors and admins have the final say on all prize outcomes.')
            ->assertSee('player-faction player-faction-great-britain', false)   // faction icons
            ->assertSee('player-faction player-faction-cuba', false);

        // One icon per faction award plus the four holiday faction awards; none on other cards
        $this->assertSame(12, substr_count($html, 'data-faction='));
    }

    // Prize graphic: the configured file ships with the code, and the page still renders if it can't be resolved
    public function test_prize_image(): void
    {
        $path = config('ladder_events.' . self::SLUG . '.prize_image');
        $this->assertFileExists(base_path($path));
        $this->assertStringStartsWith('resources/images/', $path, 'must be under the Vite image glob');

        $this->seedAndCompute();
        $this->get('/events/' . self::SLUG)->assertOk()->assertDontSee('id="prize-list"', false);   // withoutVite() resolves no URL

        config(['ladder_events.' . self::SLUG . '.prize_image' => null]);
        $this->get('/events/' . self::SLUG)->assertOk()->assertDontSee('id="prize-list"', false);
    }

    // FR-020 (manual result rendered)
    public function test_web_page_shows_manual_result(): void
    {
        $this->seedAndCompute();
        $gameId = DB::table('games')->orderBy('id')->value('id');
        $gino = DB::table('users')->where('alias', 'Gino')->value('id');
        LadderEventAwardResult::create([
            'ladder_event_id' => $this->event->id, 'award_key' => 'giant_killer', 'kind' => 'manual',
            'user_id' => $gino, 'game_id' => $gameId, 'note' => '2-0 vs Lgnd/Latof', 'set_by' => $gino,
        ]);
        (new LadderEventService())->compute($this->event->fresh());

        $this->get('/events/' . self::SLUG)
            ->assertOk()
            ->assertSee('2-0 vs Lgnd/Latof')
            ->assertSee("/ladder/12-2026/ra2-2v2/games/$gameId", false);
    }

    // FR-028
    public function test_holiday_cards_show_start_date_before_christmas(): void
    {
        $this->seedAndCompute('2026-12-20 12:00:00');

        $this->get('/events/' . self::SLUG)
            ->assertOk()
            ->assertSee('Starts 25 Dec')
            ->assertSee('Starts 1 Jan');
    }

    // FR-028
    public function test_before_event_start(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 12:00:00', 'UTC'));

        $this->get('/events/' . self::SLUG)->assertOk()->assertSee('Starts 1 Dec 2026');
        $this->getJson('/api/v1/events/' . self::SLUG)->assertOk()
            ->assertJsonPath('status', 'upcoming')
            ->assertJsonPath('computed_at', null)
            ->assertJsonPath('awards', []);
    }

    // NFR-002: events:compute only touches live / pending_final events and is scheduled every 5 minutes
    public function test_compute_command_and_schedule(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 12:00:00', 'UTC'));
        $this->artisan('events:compute')->assertSuccessful();
        $this->assertNull($this->event->fresh()->computed_payload, 'upcoming event not computed');

        $this->travelTo(Carbon::parse('2026-12-05 12:00:00', 'UTC'));
        $this->artisan('events:compute')->assertSuccessful();
        $this->assertNotNull($this->event->fresh()->computed_payload);

        $this->artisan('events:compute', ['slug' => 'nope'])->assertFailed();

        $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains($e->command ?? '', 'events:compute'));
        $this->assertCount(1, $events);
        $this->assertSame('*/5 * * * *', $events->first()->expression);
        $this->assertTrue($events->first()->withoutOverlapping);
        $this->assertTrue($events->first()->onOneServer);
    }

    // Error handling: a failed compute keeps the previous payload
    public function test_failed_compute_keeps_previous_payload(): void
    {
        $this->seedAndCompute();
        $before = $this->event->fresh()->computed_payload;

        DB::table('ladder_history')->where('id', $this->jan->id)->update(['short' => 'gone']);
        $this->artisan('events:compute', ['slug' => self::SLUG])->assertFailed();

        $this->assertSame($before, $this->event->fresh()->computed_payload);
    }
}
