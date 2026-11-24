<?php

namespace Tests\Unit\LadderEvents;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\AwardResolver;
use App\Services\LadderEvents\Calculators\CountCalculator;
use App\Services\LadderEvents\Calculators\ManualCalculator;
use App\Services\LadderEvents\Calculators\StandingsCalculator;
use App\Services\LadderEvents\Calculators\StreamingCalculator;
use App\Services\LadderEvents\EventContext;
use App\Services\LadderEvents\GameFact;
use App\Services\LadderEvents\LadderEventDefinition;
use App\Services\LadderEvents\StreamFact;
use Carbon\Carbon;
use Tests\TestCase;

class CalculatorsTest extends TestCase
{
    const DEC = 1374;
    const JAN = 1375;
    const U = 1, V = 2, P = 3, Q = 4, X = 5, Y = 6;

    private LadderEventDefinition $def;
    private int $gameId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->def = LadderEventDefinition::fromConfig('ra2-2v2-holiday-2026', config('ladder_events.ra2-2v2-holiday-2026'));
    }

    private function fact(int $user, array $o = []): GameFact
    {
        $at = $o['at'] ?? '2026-12-10 12:00:00';
        return new GameFact(
            gameId: $o['game'] ?? ++$this->gameId,
            historyId: $o['history'] ?? (str_starts_with($at, '2027') ? self::JAN : self::DEC),
            userKey: $user,
            ownerUserId: $user,
            username: 'p' . $user,
            partnerKey: array_key_exists('partner', $o) ? $o['partner'] : self::P,
            opponentKeys: $o['opponents'] ?? [self::X, self::Y],
            win: $o['win'] ?? true,
            draw: $o['draw'] ?? false,
            country: $o['country'] ?? null,
            playedAt: Carbon::parse($at, 'UTC')->getTimestamp(),
        );
    }

    private function ctx(array $facts, array $opts = []): EventContext
    {
        return new EventContext(
            definition: $this->def,
            facts: $facts,
            streams: $opts['streams'] ?? [],
            groups: $opts['groups'] ?? [],
            standings: $opts['standings'] ?? [self::DEC => [], self::JAN => []],
            historyIds: [self::DEC, self::JAN],
            now: Carbon::parse($opts['now'] ?? '2027-01-15', 'UTC'),
        );
    }

    private function values(string $awardKey, EventContext $ctx): array
    {
        $award = $this->def->award($awardKey);
        $calc = match ($award->type) {
            'count' => new CountCalculator(),
            'streaming' => new StreamingCalculator(),
            'standings' => new StandingsCalculator(),
            'manual' => new ManualCalculator(),
        };
        $out = [];
        foreach ($calc->compute($award, $ctx) as $r)
        {
            $out[$r['user_key']] = $r['value'];
        }
        ksort($out);
        return $out;
    }

    // FR-005
    public function test_draw_counts_as_game_not_win(): void
    {
        $ctx = $this->ctx([$this->fact(self::U, ['win' => false, 'draw' => true])]);
        $this->assertSame([self::U => 1], $this->values('most_games', $ctx));
        $this->assertSame([], $this->values('most_wins', $ctx));
    }

    // FR-010
    public function test_most_games_wins_and_faction(): void
    {
        $ctx = $this->ctx([
            $this->fact(self::U, ['country' => 2]),
            $this->fact(self::U, ['country' => 2, 'at' => '2027-01-05 10:00:00']),
            $this->fact(self::U, ['country' => 2, 'win' => false]),
            $this->fact(self::V, ['country' => 1]),
        ]);
        $this->assertSame([self::U => 3, self::V => 1], $this->values('most_games', $ctx));
        $this->assertSame([self::U => 2, self::V => 1], $this->values('most_wins', $ctx));
        $this->assertSame([self::U => 2], $this->values('faction_france', $ctx));
    }

    // FR-008
    public function test_utc_day_boundary(): void
    {
        $ctx = $this->ctx([
            $this->fact(self::U, ['country' => 7, 'at' => '2026-12-24 23:59:59']),
            $this->fact(self::U, ['country' => 7, 'at' => '2026-12-25 00:00:00']),
        ]);
        $this->assertSame([self::U => 1], $this->values('santa_cubaus', $ctx));
    }

    // FR-011
    public function test_chase_the_stars_counts_each_game_once_and_ignores_partner(): void
    {
        $sClass = [self::X => true, self::Y => true, self::P => true];
        $ctx = $this->ctx([
            $this->fact(self::U, ['opponents' => [self::X, self::Y]]),                  // both S-class: +1
            $this->fact(self::U, ['partner' => self::P, 'opponents' => [10, 11]]),      // S-class partner only
        ], ['groups' => ['s_class' => $sClass]]);
        $this->assertSame([self::U => 1], $this->values('chase_the_stars', $ctx));
    }

    // FR-012
    public function test_handicap_carrier_uses_monthly_rank(): void
    {
        $standings = [
            self::DEC => [self::P => ['points' => 100, 'rank' => 21], self::Q => ['points' => 200, 'rank' => 20]],
            self::JAN => [],
        ];
        $ctx = $this->ctx([
            $this->fact(self::U, ['partner' => self::P]),
            $this->fact(self::U, ['partner' => self::Q]),
        ], ['standings' => $standings]);
        $this->assertSame([self::U => 1], $this->values('handicap_carrier', $ctx));

        // P is 25th in December and 5th in January: only the December win counts
        $standings = [
            self::DEC => [self::P => ['points' => 100, 'rank' => 25]],
            self::JAN => [self::P => ['points' => 900, 'rank' => 5]],
        ];
        $ctx = $this->ctx([
            $this->fact(self::U, ['partner' => self::P]),
            $this->fact(self::U, ['partner' => self::P, 'at' => '2027-01-10 12:00:00']),
        ], ['standings' => $standings]);
        $this->assertSame([self::U => 1], $this->values('handicap_carrier', $ctx));

        // Partner absent from the standings counts; no partner does not
        $ctx = $this->ctx([
            $this->fact(self::U, ['partner' => 99]),
            $this->fact(self::U, ['partner' => null]),
        ]);
        $this->assertSame([self::U => 1], $this->values('handicap_carrier', $ctx));
    }

    // FR-013 (Beta's alt is already resolved to Beta's user key by the repository)
    public function test_partner_group_any_member(): void
    {
        $zedd = 13; $beta = 51953;
        $ctx = $this->ctx([
            $this->fact(self::U, ['partner' => $zedd]),
            $this->fact(self::U, ['partner' => $zedd]),
            $this->fact(self::U, ['partner' => $beta]),
            $this->fact(self::U, ['partner' => $beta, 'win' => false]),
        ], ['groups' => ['partner_zedd_beta' => [$zedd => true, $beta => true]]]);
        $this->assertSame([self::U => 3], $this->values('beta_to_zedd', $ctx));
    }

    // FR-014
    public function test_holiday_faction_award(): void
    {
        $facts = [];
        for ($i = 0; $i < 3; $i++) $facts[] = $this->fact(self::U, ['country' => 1, 'at' => '2027-01-01 1' . $i . ':00:00']);
        for ($i = 0; $i < 5; $i++) $facts[] = $this->fact(self::U, ['country' => 1, 'at' => '2027-01-02 1' . $i . ':00:00']);
        $this->assertSame([self::U => 3], $this->values('new_year_flyby', $this->ctx($facts)));
    }

    // FR-015
    public function test_more_than_mamma_counts_games_on_both_days(): void
    {
        $facts = [];
        for ($i = 0; $i < 6; $i++) $facts[] = $this->fact(self::U, ['win' => $i % 2 === 0, 'at' => '2026-12-25 0' . $i . ':00:00']);
        for ($i = 0; $i < 4; $i++) $facts[] = $this->fact(self::U, ['win' => false, 'draw' => true, 'at' => '2027-01-01 0' . $i . ':00:00']);
        $facts[] = $this->fact(self::U, ['at' => '2026-12-26 01:00:00']);
        $this->assertSame([self::U => 10], $this->values('more_than_mamma', $this->ctx($facts)));
    }

    // FR-016, FR-017
    public function test_streaming_hours(): void
    {
        $s = fn ($user, $obs, $dur, $at = '2026-12-10 12:00:00') => new StreamFact($user, $obs, $dur, Carbon::parse($at, 'UTC')->getTimestamp());

        $ctx = $this->ctx([], ['streams' => [$s(self::U, false, 1200), $s(self::U, false, 600)]]);
        $this->assertSame([self::U => 0.5], $this->values('let_me_see', $ctx));

        $ctx = $this->ctx([], ['streams' => [
            $s(self::U, false, 3600, '2026-12-25 10:00:00'),
            $s(self::U, true, 1800, '2026-12-25 12:00:00'),
            $s(self::U, true, 1800, '2026-12-26 12:00:00'),
        ]]);
        $this->assertSame([self::U => 1.5], $this->values('santas_eyeball', $ctx));
        $this->assertSame([self::U => 1.0], $this->values('eye_of_beholder', $ctx));
        $this->assertSame([self::U => 1.0], $this->values('let_me_see', $ctx));
    }

    // FR-009
    public function test_standings_sum_months(): void
    {
        $ctx = $this->ctx([], ['standings' => [
            self::DEC => [self::U => ['points' => 820, 'rank' => 2], self::V => ['points' => 1500, 'rank' => 1]],
            self::JAN => [self::U => ['points' => 640, 'rank' => 1]],
        ]]);
        $rows = (new StandingsCalculator())->compute($this->def->standingsAward(), $ctx);
        $byUser = array_column($rows, null, 'user_key');

        $this->assertSame(1460, $byUser[self::U]['value']);
        $this->assertSame([self::DEC => 820, self::JAN => 640], $byUser[self::U]['months']);
        $this->assertSame(1500, $byUser[self::V]['value']);

        $ranked = (new AwardResolver())->rank($rows, [self::U => 'U', self::V => 'V']);
        $this->assertSame([self::V, self::U], array_column($ranked, 'user_key'));
    }

    // FR-021
    public function test_manual_candidates(): void
    {
        $edd = 2152;
        $ctx = $this->ctx([
            $this->fact(self::U, ['partner' => $edd, 'at' => '2026-12-10 10:00:00']),
            $this->fact(self::U, ['partner' => $edd, 'at' => '2026-12-12 10:00:00']),
            $this->fact(self::V, ['partner' => $edd, 'at' => '2026-12-11 10:00:00']),
            $this->fact(self::V, ['partner' => $edd, 'win' => false]),
        ], ['groups' => ['selector_edd' => [$edd => true]]]);

        $this->assertSame([self::U => 2, self::V => 1], $this->values('gatekeeper_edd', $ctx));

        $facts = (new ManualCalculator())->candidateFacts($this->def->award('gatekeeper_edd'), $ctx);
        $this->assertCount(3, $facts);
        $this->assertSame(['2026-12-12', '2026-12-11', '2026-12-10'], array_map(fn ($f) => gmdate('Y-m-d', $f->playedAt), $facts), 'newest first');
        $this->assertSame([], $this->values('giant_killer', $ctx));
    }

    // FR-024
    public function test_ranking_ties_and_zero_drop(): void
    {
        $r = new AwardResolver();
        $ranked = $r->rank([
            ['user_key' => 3, 'value' => 12],
            ['user_key' => 2, 'value' => 14],
            ['user_key' => 1, 'value' => 14],
            ['user_key' => 4, 'value' => 0],
        ], [1 => 'Bravo', 2 => 'Alpha', 3 => 'Charlie', 4 => 'Zero']);

        $this->assertSame([2, 1, 3], array_column($ranked, 'user_key'), 'value DESC then name ASC');
        $this->assertSame([1, 1, 3], array_column($ranked, 'place'));
        $this->assertSame([true, true, false], array_column($ranked, 'tied'));

        $withPrizes = $r->withPrizes($ranked, [25], 5);
        $this->assertSame([25, 25, null], array_column($withPrizes, 'prize'));

        $this->assertSame([], $r->rank([], []));
    }

    // FR-018
    public function test_streaming_exclusivity(): void
    {
        $r = new AwardResolver();
        $names = [self::U => 'U', self::V => 'V'];
        $ranked = [
            'eye_of_beholder' => $r->rank([['user_key' => self::U, 'value' => 12.0]], $names),
            'let_me_see' => $r->rank([['user_key' => self::U, 'value' => 9.0], ['user_key' => self::V, 'value' => 7.0]], $names),
        ];
        $awards = ['eye_of_beholder' => $this->def->award('eye_of_beholder'), 'let_me_see' => $this->def->award('let_me_see')];

        [$out, $notes] = $r->applyExclusivity($ranked, $awards);

        $this->assertSame(self::U, $out['eye_of_beholder'][0]['user_key']);
        $this->assertSame(self::V, $out['let_me_see'][0]['user_key']);
        $this->assertSame(1, $out['let_me_see'][0]['place']);
        $this->assertCount(1, $out['let_me_see']);
        $this->assertSame(['U excluded: already leads Eye of the Beholder'], $notes['let_me_see']);
        $this->assertArrayNotHasKey('eye_of_beholder', $notes);

        // Equal hours: Let Me See! wins the tie
        $ranked = [
            'eye_of_beholder' => $r->rank([['user_key' => self::U, 'value' => 5.0], ['user_key' => self::V, 'value' => 4.0]], $names),
            'let_me_see' => $r->rank([['user_key' => self::U, 'value' => 5.0]], $names),
        ];
        [$out, $notes] = $r->applyExclusivity($ranked, $awards);
        $this->assertSame(self::V, $out['eye_of_beholder'][0]['user_key']);
        $this->assertSame(self::U, $out['let_me_see'][0]['user_key']);
        $this->assertSame(['U excluded: already leads Let Me See! Let Me See!'], $notes['eye_of_beholder']);
    }

    // FR-025: no other exclusivity — the same user may lead several awards
    public function test_user_can_lead_many_awards(): void
    {
        $r = new AwardResolver();
        $ranked = [
            'most_wins' => $r->rank([['user_key' => self::U, 'value' => 3]], [self::U => 'U']),
            'most_games' => $r->rank([['user_key' => self::U, 'value' => 3]], [self::U => 'U']),
        ];
        [$out, $notes] = $r->applyExclusivity($ranked, ['most_wins' => $this->def->award('most_wins'), 'most_games' => $this->def->award('most_games')]);
        $this->assertSame($ranked, $out);
        $this->assertSame([], $notes);
    }

    // FR-028
    public function test_window_status(): void
    {
        $r = new AwardResolver();
        [$s, $e] = $this->def->awardWindow($this->def->award('santa_cubaus'));
        $this->assertSame('upcoming', $r->windowStatus($s, $e, Carbon::parse('2026-12-20', 'UTC')));
        $this->assertSame('live', $r->windowStatus($s, $e, Carbon::parse('2026-12-25 12:00', 'UTC')));
        $this->assertSame('final', $r->windowStatus($s, $e, Carbon::parse('2026-12-26 00:00', 'UTC')));

        [$s, $e] = $this->def->awardWindow($this->def->award('faction_cuba'));
        $this->assertSame('live', $r->windowStatus($s, $e, Carbon::parse('2026-12-20', 'UTC')));
    }
}
