<?php

namespace Tests\Feature\LadderEvents;

use App\Jobs\ComputeLadderEventJob;
use App\Models\LadderAdmin;
use App\Models\LadderEventAwardResult;
use App\Models\User;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventAdminTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    private string $base;
    private User $organiser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEvent();
        $this->withoutVite();
        $this->base = '/admin/events/' . self::SLUG;
        $this->travelTo(Carbon::parse('2027-01-10 12:00:00', 'UTC'));

        $this->organiser = $this->makeUser('Organiser');
        $la = LadderAdmin::findOrCreate($this->organiser->id, $this->ladder->id);
        $la->admin = true;
        $la->moderator = true;
        $la->tester = false;
        $la->save();
    }

    private function asOrganiser(): static
    {
        return $this->actingAs($this->organiser)->withoutMiddleware(VerifyCsrfToken::class);
    }

    // FR-031, NFR-006 (T-19)
    public function test_access_requires_ladder_admin(): void
    {
        $this->get($this->base)->assertRedirect();   // guest → login

        $nobody = $this->makeUser('Nobody');
        $this->actingAs($nobody)->get($this->base)->assertStatus(401);

        // Admin of a different ladder only
        $otherLadderAdmin = $this->makeUser('OtherAdmin');
        $la = LadderAdmin::findOrCreate($otherLadderAdmin->id, $this->ladder->id + 100);
        $la->admin = true; $la->moderator = true; $la->tester = false; $la->save();
        $this->actingAs($otherLadderAdmin)->get($this->base)->assertStatus(401);

        $this->actingAs($this->organiser)->get($this->base)->assertOk()->assertSee('s_class');

        $this->actingAs($this->organiser)->get('/admin/events/nope')->assertNotFound();
    }

    // NFR-006: admin mutations run in the web group (CSRF verified; Laravel skips the check itself under tests)
    public function test_mutations_are_in_csrf_protected_web_group(): void
    {
        foreach (['POST' => ['/groups', '/results', '/recompute', '/finalise', '/unfinalise'], 'DELETE' => ['/results/most_wins/override']] as $method => $paths)
        {
            foreach ($paths as $path)
            {
                $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($this->base . $path, $method));
                $middleware = $route->gatherMiddleware();
                $this->assertContains('web', $middleware, $path);
                $this->assertContains('auth', $middleware, $path);
                $this->assertContains('restrict:canAdminLadder', $middleware, $path);
            }
        }
    }

    // FR-031, FR-032 (T-20)
    public function test_add_and_remove_group_members(): void
    {
        Queue::fake();
        [$u, $p, $ossama, $x] = [$this->makeUser('U'), $this->makeUser('P'), $this->makeUser('Ossama'), $this->makeUser('X')];
        $this->addGame([$u, $p], [$ossama, $x]);

        $this->asOrganiser()->post($this->base . '/groups', ['action' => 'add', 'group_key' => 's_class', 'user_id' => $ossama->id])
            ->assertRedirect();
        $this->assertDatabaseHas('ladder_event_group_members', ['group_key' => 's_class', 'user_id' => $ossama->id]);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $this->organiser->id, 'subject_type' => \App\Models\LadderEventGroupMember::class, 'event' => 'created']);
        Queue::assertPushed(ComputeLadderEventJob::class);

        // Next computation counts wins against the new member
        $payload = (new LadderEventService())->compute($this->event->fresh());
        $chase = collect($payload['awards'])->firstWhere('key', 'chase_the_stars');
        $this->assertContains($u->id, array_column($chase['leaders'], 'user_key'));
        $this->assertSame(1, $chase['leaders'][0]['value']);

        // Shown with display name and user key; search by alias
        $this->actingAs($this->organiser)->get($this->base . '?tab=groups&search=Ossa')->assertOk()->assertSee('Ossama');

        $this->asOrganiser()->post($this->base . '/groups', ['action' => 'remove', 'group_key' => 's_class', 'user_id' => $ossama->id]);
        $this->assertDatabaseMissing('ladder_event_group_members', ['group_key' => 's_class', 'user_id' => $ossama->id]);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $this->organiser->id, 'event' => 'deleted']);

        // Unknown group / user rejected
        $this->asOrganiser()->post($this->base . '/groups', ['action' => 'add', 'group_key' => 'nope', 'user_id' => $ossama->id])
            ->assertSessionHasErrors('group_key');
        $this->asOrganiser()->post($this->base . '/groups', ['action' => 'add', 'group_key' => 's_class', 'user_id' => 999999])
            ->assertSessionHasErrors('user_id');
    }

    // FR-020, FR-022, FR-023, FR-032 (T-21)
    public function test_manual_results_are_validated(): void
    {
        Queue::fake();
        $edd = $this->makeUser('Edd', ['group' => 'selector_edd']);
        [$u, $v, $w, $x] = [$this->makeUser('U'), $this->makeUser('V'), $this->makeUser('W'), $this->makeUser('X')];

        $november = $this->addGame([$u, $v], [$w, $x], ['at' => '2026-11-20 12:00:00']);
        $withEdd = $this->addGame([$u, $edd], [$w, $x], ['at' => '2026-12-15 12:00:00']);
        $withoutEdd = $this->addGame([$u, $v], [$w, $x], ['at' => '2026-12-16 12:00:00']);

        $post = fn (array $data) => $this->asOrganiser()->post($this->base . '/results', $data);

        $post(['award_key' => 'giant_killer', 'user_id' => $u->id, 'game_id' => $november])
            ->assertSessionHasErrors(['game_id' => 'Game is not part of this event']);

        $post(['award_key' => 'giant_killer', 'user_id' => $edd->id, 'game_id' => $withoutEdd])
            ->assertSessionHasErrors(['game_id' => 'The winner did not play in this game']);

        $post(['award_key' => 'gatekeeper_edd', 'user_id' => $u->id, 'game_id' => $withoutEdd])
            ->assertSessionHasErrors(['game_id' => 'Game is not one of the candidate wins for this award']);

        $post(['award_key' => 'gatekeeper_edd', 'user_id' => $w->id, 'game_id' => $withEdd])
            ->assertSessionHasErrors('game_id');  // W lost that game

        $post(['award_key' => 'standings', 'user_id' => $u->id])->assertSessionHasErrors('award_key');

        $post(['award_key' => 'gatekeeper_edd', 'user_id' => $u->id, 'game_id' => $withEdd, 'note' => 'great game'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ladder_event_award_results', [
            'award_key' => 'gatekeeper_edd', 'kind' => 'manual', 'user_id' => $u->id, 'game_id' => $withEdd, 'set_by' => $this->organiser->id,
        ]);

        // Computed awards take an override
        $post(['award_key' => 'most_wins', 'user_id' => $v->id, 'note' => 'U disqualified'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ladder_event_award_results', ['award_key' => 'most_wins', 'kind' => 'override', 'user_id' => $v->id]);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $this->organiser->id, 'subject_type' => LadderEventAwardResult::class]);

        // Clearing
        $this->asOrganiser()->delete($this->base . '/results/most_wins/override')->assertRedirect();
        $this->assertDatabaseMissing('ladder_event_award_results', ['award_key' => 'most_wins']);
        $this->assertDatabaseHas('activity_log', ['subject_type' => LadderEventAwardResult::class, 'event' => 'deleted']);
    }

    // FR-021 (T-22)
    public function test_candidates_tab_lists_newest_first(): void
    {
        $edd = $this->makeUser('Edd', ['group' => 'selector_edd']);
        [$u, $v, $w, $x] = [$this->makeUser('U'), $this->makeUser('V'), $this->makeUser('W'), $this->makeUser('X')];
        $g1 = $this->addGame([$u, $edd], [$w, $x], ['at' => '2026-12-10 12:00:00']);
        $g2 = $this->addGame([$u, $edd], [$w, $x], ['at' => '2026-12-12 12:00:00']);
        $g3 = $this->addGame([$v, $edd], [$w, $x], ['at' => '2026-12-11 12:00:00']);

        $candidates = (new LadderEventService())->candidateWins($this->event)['gatekeeper_edd'];
        $this->assertSame([$g2, $g3, $g1], array_column($candidates, 'game_id'));
        $this->assertSame('Edd', $candidates[0]['partner']);
        $this->assertSame("/ladder/12-2026/ra2-2v2/games/$g2", $candidates[0]['url']);

        $this->actingAs($this->organiser)->get($this->base . '?tab=candidates')
            ->assertOk()
            ->assertSee('3 candidate wins')
            ->assertSee("/ladder/12-2026/ra2-2v2/games/$g2", false);
    }

    // FR-030, FR-031, FR-032 (T-23)
    public function test_lifecycle_actions(): void
    {
        Queue::fake();

        $this->asOrganiser()->post($this->base . '/recompute')->assertRedirect();
        Queue::assertPushed(ComputeLadderEventJob::class, fn ($job) => $job->ladderEventId === $this->event->id);

        // Not finalisable while live
        $this->asOrganiser()->post($this->base . '/finalise')->assertSessionHasErrors('finalise');
        $this->assertNull($this->event->fresh()->status_override);

        $this->travelTo(Carbon::parse('2027-02-03 12:00:00', 'UTC'));
        $this->asOrganiser()->post($this->base . '/finalise')->assertSessionHasNoErrors();
        $event = $this->event->fresh();
        $this->assertSame('final', $event->status_override);
        $this->assertNotNull($event->frozen_payload);
        $this->assertSame($this->organiser->id, $event->finalised_by);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $this->organiser->id, 'event' => 'finalised']);

        $this->asOrganiser()->post($this->base . '/unfinalise')->assertSessionHasNoErrors();
        $this->assertNull($this->event->fresh()->status_override);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $this->organiser->id, 'event' => 'unfinalised']);
    }

    // Recompute job does the same work as the command
    public function test_recompute_job_computes_payload(): void
    {
        (new ComputeLadderEventJob($this->event->id))->handle(new LadderEventService());
        $this->assertNotNull($this->event->fresh()->computed_payload);
    }

    // T-24
    public function test_admin_index_links_to_event_admin(): void
    {
        $god = $this->makeUser('God');
        DB::table('users')->where('id', $god->id)->update(['group' => 'God']);

        $this->actingAs($god->fresh())->get('/admin')
            ->assertOk()
            ->assertSee('/admin/events/' . self::SLUG, false);
    }
}
