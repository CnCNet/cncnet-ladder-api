<?php

namespace Tests\Feature\LadderEvents;

use App\Services\LadderEvents\EventFactsRepository;
use App\Services\LadderEvents\GameFact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFactsRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    private EventFactsRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEvent();
        $this->repo = new EventFactsRepository();
    }

    private function historyIds(): array
    {
        return [$this->dec->id, $this->jan->id];
    }

    /** @return GameFact[] */
    private function factsFor(int $userKey): array
    {
        return $this->repo->gameFacts($this->historyIds())->filter(fn ($f) => $f->userKey === $userKey)->values()->all();
    }

    // FR-004
    public function test_ineligible_reports_are_excluded(): void
    {
        [$a, $b, $c, $d] = [$this->makeUser('A'), $this->makeUser('B'), $this->makeUser('C'), $this->makeUser('D')];
        $bot = $this->makeUser('Bot', ['is_bot' => true]);
        $spec = $this->makeUser('Spec');

        $eligible = $this->addGame([$a, $b], [$c, $d], ['spectators' => [$spec]]);
        $this->addGame([$a, $b], [$c, $d], ['valid' => false]);
        $this->addGame([$a, $b], [$c, $d], ['best_report' => false]);
        $this->addGame([$a, $b], [$c, $d], ['at' => '2026-11-20 12:00:00']);   // November history
        $this->addGame([$a, $bot], [$c, $d]);

        $facts = $this->repo->gameFacts($this->historyIds());

        $aFacts = $this->factsFor($a->id);
        $this->assertCount(2, $aFacts, 'eligible game + bot game; invalid/non-best/November excluded');
        $this->assertSame($eligible, $aFacts[0]->gameId);
        $this->assertCount(0, $facts->filter(fn ($f) => $f->userKey === $spec->id));
        $this->assertCount(0, $facts->filter(fn ($f) => $f->userKey === $bot->id));
    }

    // FR-002
    public function test_alts_and_renamed_players_credit_the_primary_user(): void
    {
        $gino = $this->makeUser('Gino');
        $this->addGame([$gino, $this->makeUser('P1')], [$this->makeUser('P2'), $this->makeUser('P3')]);
        $this->makePlayer($gino, 'GinoX');      // January player name
        $this->addGame([$gino, $this->makeUser('P4')], [$this->makeUser('P5'), $this->makeUser('P6')], ['at' => '2027-01-10 12:00:00']);

        $beta = $this->makeUser('BetaMain', ['alias' => 'Beta']);
        $alt = $this->makeUser('BetaAlt', ['primary' => $beta->id]);
        $this->addGame([$alt, $this->makeUser('P7')], [$this->makeUser('P8'), $this->makeUser('P9')]);

        $this->assertCount(2, $this->factsFor($gino->id));
        $betaFacts = $this->factsFor($beta->id);
        $this->assertCount(1, $betaFacts);
        $this->assertSame($alt->id, $betaFacts[0]->ownerUserId);
        $this->assertCount(0, $this->factsFor($alt->id));
    }

    // FR-006
    public function test_partner_and_opponents_are_derived(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U1'), $this->makeUser('U2'), $this->makeUser('U3'), $this->makeUser('U4')];
        $this->addGame([$u1, $u2], [$u3, $u4]);

        $f = $this->factsFor($u1->id)[0];
        $this->assertSame($u2->id, $f->partnerKey);
        $this->assertSame([$u3->id, $u4->id], $f->opponentKeys);
        $this->assertTrue($f->win);
        $this->assertFalse($this->factsFor($u3->id)[0]->win);
    }

    // FR-006
    public function test_null_team_or_single_player_team_has_no_partner(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U1'), $this->makeUser('U2'), $this->makeUser('U3'), $this->makeUser('U4')];
        $this->addGame([$u1, $u2], [$u3, $u4], ['team_a_label' => null]);
        $this->addGame([$u1], [$u3, $u4], ['at' => '2026-12-11 12:00:00']);

        $facts = $this->factsFor($u1->id);
        $this->assertCount(2, $facts, 'games still count for non-partner awards');
        $this->assertNull($facts[0]->partnerKey);
        $this->assertNull($facts[1]->partnerKey);
        $this->assertSame([$u3->id, $u4->id], $facts[1]->opponentKeys);
    }

    // FR-007
    public function test_country_from_stats2_or_null(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U1'), $this->makeUser('U2'), $this->makeUser('U3'), $this->makeUser('U4')];
        $this->addGame([[$u1, 7], $u2], [$u3, $u4]);

        $this->assertSame(7, $this->factsFor($u1->id)[0]->country);
        $this->assertNull($this->factsFor($u2->id)[0]->country);
    }

    // FR-005
    public function test_draw_is_a_game_not_a_win(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U1'), $this->makeUser('U2'), $this->makeUser('U3'), $this->makeUser('U4')];
        $this->addGame([$u1, $u2], [$u3, $u4], ['draw' => true]);

        $f = $this->factsFor($u1->id)[0];
        $this->assertTrue($f->draw);
        $this->assertFalse($f->win);
    }

    // FR-009, FR-012 (T-07)
    public function test_monthly_standings_rank_and_alt_sum(): void
    {
        $u = $this->makeUser('U');
        $alt = $this->makeUser('UAlt', ['primary' => $u->id]);
        $v = $this->makeUser('V');

        $this->setPoints($v, $this->dec, 1500);
        $this->setPoints($u, $this->dec, 800);
        $this->setPoints($alt, $this->dec, 20);
        $this->setPoints($u, $this->jan, 640);

        $s = $this->repo->monthlyStandings($this->historyIds());

        $this->assertSame(['points' => 1500, 'rank' => 1], $s[$this->dec->id][$v->id]);
        $this->assertSame(['points' => 820, 'rank' => 2], $s[$this->dec->id][$u->id]);
        $this->assertSame(['points' => 640, 'rank' => 1], $s[$this->jan->id][$u->id]);
        $this->assertArrayNotHasKey($alt->id, $s[$this->dec->id]);
    }

    // FR-016 (T-08)
    public function test_stream_facts(): void
    {
        [$u1, $u2, $u3, $u4] = [$this->makeUser('U1'), $this->makeUser('U2'), $this->makeUser('U3'), $this->makeUser('U4')];
        $obs = $this->makeUser('Obs');

        $this->addGame([$u1, $u2], [$u3, $u4], ['duration' => 1200, 'qm' => [
            $u1->id => ['live' => true], $u2->id => ['live' => false], $obs->id => ['live' => true, 'observer' => true],
        ]]);
        // Invalid but best report still counts for streaming
        $this->addGame([$u1, $u2], [$u3, $u4], ['duration' => 600, 'valid' => false, 'qm' => [$u1->id => ['live' => true]]]);
        // Non-best report only: excluded
        $this->addGame([$u1, $u2], [$u3, $u4], ['duration' => 900, 'best_report' => false, 'qm' => [$u1->id => ['live' => true]]]);

        $facts = $this->repo->streamFacts($this->ladder->id, $this->historyIds());

        $u1Facts = $facts->filter(fn ($f) => $f->userKey === $u1->id);
        $this->assertSame([1200, 600], $u1Facts->pluck('duration')->values()->all());
        $this->assertCount(0, $facts->filter(fn ($f) => $f->userKey === $u2->id));
        $this->assertTrue($facts->firstWhere('userKey', $obs->id)->isObserver);
    }

    // FR-003 (T-09)
    public function test_user_display_prefers_alias_then_name(): void
    {
        $sander = $this->makeUser('HCKiD', ['alias' => 'Sander', 'player' => 'Sanchez']);
        $foo = $this->makeUser('Foo123', ['alias' => '']);

        $names = $this->repo->userDisplay([$sander->id, $foo->id]);

        $this->assertSame('Sander', $names[$sander->id]);
        $this->assertSame('Foo123', $names[$foo->id]);
    }

    // FR-031: group members resolve to user keys
    public function test_groups_resolve_to_user_keys(): void
    {
        $beta = $this->makeUser('Beta');
        $alt = $this->makeUser('BetaAlt', ['primary' => $beta->id]);
        $this->addToGroup('partner_zedd_beta', $alt);

        $groups = $this->repo->groups($this->event, ['partner_zedd_beta', 's_class']);

        $this->assertSame([$beta->id => true], $groups['partner_zedd_beta']);
        $this->assertSame([], $groups['s_class']);
    }

    // FR-008: an admin regrade recreates player_game_reports, so the game's own time is used
    public function test_played_at_uses_game_time_not_report_time(): void
    {
        [$a, $b, $c, $d] = [$this->makeUser('A'), $this->makeUser('B'), $this->makeUser('C'), $this->makeUser('D')];
        $gameId = $this->addGame([$a, $b], [$c, $d], ['at' => '2026-12-25 18:00:00']);
        \DB::table('player_game_reports')->where('game_id', $gameId)->update(['created_at' => '2026-12-27 09:00:00']);

        $this->assertSame(strtotime('2026-12-25 18:00:00 UTC'), $this->factsFor($a->id)[0]->playedAt);
    }

    // Event starts/ends scope games, not just the histories
    public function test_window_excludes_games_outside_event_dates(): void
    {
        [$a, $b, $c, $d] = [$this->makeUser('A'), $this->makeUser('B'), $this->makeUser('C'), $this->makeUser('D')];
        $before = $this->addGame([$a, $b], [$c, $d], [
            'at' => '2026-11-30 23:30:00', 'history' => $this->dec, 'qm' => [$a->id => ['live' => true]],
        ]);
        $inside = $this->addGame([$a, $b], [$c, $d], ['at' => '2026-12-01 00:00:00', 'qm' => [$a->id => ['live' => true]]]);

        $window = [\Carbon\Carbon::parse('2026-12-01 00:00:00', 'UTC'), \Carbon\Carbon::parse('2027-01-31 23:59:59', 'UTC')];

        $ids = $this->repo->gameFacts($this->historyIds(), null, $window)->pluck('gameId')->unique()->values()->all();
        $this->assertSame([$inside], $ids);
        $this->assertContains($before, $this->repo->gameFacts($this->historyIds())->pluck('gameId')->all());

        $this->assertCount(1, $this->repo->streamFacts($this->ladder->id, $this->historyIds(), $window));
        $this->assertCount(2, $this->repo->streamFacts($this->ladder->id, $this->historyIds()));
    }
}
