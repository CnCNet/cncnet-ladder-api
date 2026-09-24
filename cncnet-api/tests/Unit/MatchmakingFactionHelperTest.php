<?php

namespace Tests\Unit;

use App\Helpers\MatchmakingFactionHelper;
use PHPUnit\Framework\TestCase;

class MatchmakingFactionHelperTest extends TestCase
{
    private const ALLIED = [0, 1, 2, 3, 4];
    private const SOVIET = [5, 6, 7, 8];

    /**
     * Countries are random, so every test picks them this many times.
     */
    private const RUNS = 50;

    public function test_1v1_players_play_different_factions(): void
    {
        $this->assertFactionsOfTeams([1, 1], [[[1, 0], [0, 1]], [[0, 1], [1, 0]]]);
    }

    public function test_2v2_teams_have_one_player_of_each_faction(): void
    {
        $this->assertFactionsOfTeams([2, 2], [[[1, 1], [1, 1]]]);
    }

    public function test_3v3_teams_play_different_factions(): void
    {
        $this->assertFactionsOfTeams([3, 3], [[[3, 0], [0, 3]], [[0, 3], [3, 0]]]);
    }

    public function test_4v4_teams_have_two_players_of_each_faction(): void
    {
        $this->assertFactionsOfTeams([4, 4], [[[2, 2], [2, 2]]]);
    }

    public function test_odd_teams_play_both_factions_over_time(): void
    {
        $firstTeamFactions = [];
        for ($run = 0; $run < self::RUNS; $run++)
        {
            $countries = MatchmakingFactionHelper::pickCountries([1, 1], [self::ALLIED, self::SOVIET]);
            $firstTeamFactions[] = in_array($countries[0][0], self::ALLIED) ? 'allied' : 'soviet';
        }

        $this->assertEqualsCanonicalizing(['allied', 'soviet'], array_values(array_unique($firstTeamFactions)));
    }

    public function test_countries_are_not_repeated_within_a_team(): void
    {
        for ($run = 0; $run < self::RUNS; $run++)
        {
            foreach (MatchmakingFactionHelper::pickCountries([4, 4], [self::ALLIED, self::SOVIET]) as $teamCountries)
            {
                $this->assertCount(4, array_unique($teamCountries));
            }
        }
    }

    public function test_countries_are_repeated_when_a_faction_has_too_few(): void
    {
        $countries = MatchmakingFactionHelper::pickCountries([3, 3], [[0], [5]]);

        $this->assertEqualsCanonicalizing([[0, 0, 0], [5, 5, 5]], $countries);
    }

    /**
     * Checks that every team gets countries of the expected factions.
     *
     * @param int[] $teamSizes
     * @param array $expectedOptions possible [allied count, soviet count] of every team
     */
    private function assertFactionsOfTeams(array $teamSizes, array $expectedOptions): void
    {
        for ($run = 0; $run < self::RUNS; $run++)
        {
            $countries = MatchmakingFactionHelper::pickCountries($teamSizes, [self::ALLIED, self::SOVIET]);

            $factionCounts = array_map(fn(array $teamCountries) => [
                count(array_intersect($teamCountries, self::ALLIED)),
                count(array_intersect($teamCountries, self::SOVIET)),
            ], $countries);

            $this->assertContains($factionCounts, $expectedOptions, json_encode($countries));
        }
    }
}
