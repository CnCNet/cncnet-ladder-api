<?php

namespace Tests\Unit\LadderEvents;

use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\InvalidEventConfigException;
use App\Services\LadderEvents\LadderEventDefinition;
use Tests\TestCase;

class LadderEventDefinitionTest extends TestCase
{
    const SLUG = 'ra2-2v2-holiday-2026';

    private function shipped(): array
    {
        return config('ladder_events.' . self::SLUG);
    }

    private function withAward(array $award): array
    {
        $config = $this->shipped();
        $config['awards'][] = array_merge([
            'key' => 'extra', 'section' => 'performance', 'title' => 'Extra', 'description' => '',
            'type' => 'count', 'metric' => 'wins', 'filters' => [], 'prizes' => [1],
        ], $award);
        return $config;
    }

    // T-04: shipped config validates; FR-001
    public function test_shipped_config_validates(): void
    {
        $def = LadderEventDefinition::fromConfig(self::SLUG, $this->shipped());

        $this->assertSame('RA2 2v2 Holiday Championship', $def->name);
        $this->assertSame('ra2-2v2', $def->ladder);
        $this->assertSame(['12-2026', '1-2027'], $def->histories);
        $this->assertSame('2026-12-01T00:00:00+00:00', $def->starts->toIso8601String());
        $this->assertSame('2027-01-31T23:59:59+00:00', $def->ends->toIso8601String());
    }

    // FR-001: awards are exactly the prize list, in display order
    public function test_award_catalogue_order(): void
    {
        $def = LadderEventDefinition::fromConfig(self::SLUG, $this->shipped());

        $this->assertSame([
            'standings',
            'most_games', 'most_wins', 'chase_the_stars', 'handicap_carrier', 'giant_killer',
            'santa_cubaus', 'snipe_the_reindeer', 'santas_eyeball',
            'new_year_flyby', 'new_year_streamer', 'libya_dominance',
            'more_than_mamma',
            'dogs_out', 'beta_to_zedd', 'dayi_of_nikoz', 'ginocologist', 'gatekeeper_edd', 'monster_bhead', 'sandles', 'andicap',
            'eye_of_beholder', 'let_me_see',
            'faction_france', 'faction_korea', 'faction_america', 'faction_great_britain',
            'faction_germany', 'faction_cuba', 'faction_russia', 'faction_libya',
        ], array_map(fn ($a) => $a->key, $def->awards));
    }

    // T-27: prize total $1,900; 30 award cards + 10 standings places
    public function test_catalogue_prize_totals(): void
    {
        $def = LadderEventDefinition::fromConfig(self::SLUG, $this->shipped());

        $this->assertSame(1900, $def->totalPrizes());
        $this->assertCount(30, $def->cardAwards());
        $this->assertCount(10, $def->standingsAward()->prizes);
        $this->assertSame(700, $def->standingsAward()->prizeTotal());
        $this->assertSame(1200, array_sum(array_map(fn ($a) => $a->prizeTotal(), $def->cardAwards())));
    }

    // FR-021: selector awards carry the prize-list name, not the alias
    public function test_selector_labels(): void
    {
        $def = LadderEventDefinition::fromConfig(self::SLUG, $this->shipped());

        $this->assertSame('Steve', $def->award('monster_bhead')->selectorLabel);
        $this->assertSame('selector_steve', $def->award('monster_bhead')->candidates);
        foreach ($def->cardAwards() as $a)
        {
            if ($a->candidates !== null)
            {
                $this->assertNotNull($a->selectorLabel, $a->key);
            }
        }
    }

    public function test_unknown_type_rejected(): void
    {
        $this->expectException(InvalidEventConfigException::class);
        $this->expectExceptionMessage("unknown type 'bogus'");
        LadderEventDefinition::fromConfig(self::SLUG, $this->withAward(['type' => 'bogus']));
    }

    public function test_unknown_filter_rejected(): void
    {
        $this->expectException(InvalidEventConfigException::class);
        $this->expectExceptionMessage("unknown filter 'map'");
        LadderEventDefinition::fromConfig(self::SLUG, $this->withAward(['filters' => ['map' => 'x']]));
    }

    public function test_undeclared_group_rejected(): void
    {
        $this->expectException(InvalidEventConfigException::class);
        $this->expectExceptionMessage("undeclared group 'nobody'");
        LadderEventDefinition::fromConfig(self::SLUG, $this->withAward(['filters' => ['partner_group' => 'nobody']]));
    }

    public function test_duplicate_key_rejected(): void
    {
        $this->expectException(InvalidEventConfigException::class);
        $this->expectExceptionMessage("duplicate award key 'most_wins'");
        LadderEventDefinition::fromConfig(self::SLUG, $this->withAward(['key' => 'most_wins']));
    }

    public function test_undeclared_day_rejected(): void
    {
        $this->expectException(InvalidEventConfigException::class);
        $this->expectExceptionMessage("undeclared day 'easter'");
        LadderEventDefinition::fromConfig(self::SLUG, $this->withAward(['filters' => ['days' => ['easter']]]));
    }

    // FR-008 / FR-028: holiday windows are whole UTC days
    public function test_award_windows(): void
    {
        $def = LadderEventDefinition::fromConfig(self::SLUG, $this->shipped());

        [$s, $e] = $def->awardWindow($def->award('santa_cubaus'));
        $this->assertSame('2026-12-25 00:00:00', $s->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-25 23:59:59', $e->format('Y-m-d H:i:s'));

        [$s, $e] = $def->awardWindow($def->award('more_than_mamma'));
        $this->assertSame('2026-12-25 00:00:00', $s->format('Y-m-d H:i:s'));
        $this->assertSame('2027-01-01 23:59:59', $e->format('Y-m-d H:i:s'));

        [$s, $e] = $def->awardWindow($def->award('faction_cuba'));
        $this->assertTrue($s->eq($def->starts));
        $this->assertTrue($e->eq($def->ends));
    }
}
