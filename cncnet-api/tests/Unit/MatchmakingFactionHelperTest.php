<?php

namespace Tests\Unit;

use App\Helpers\MatchmakingFactionHelper;
use App\Models\QmMatchPlayer;
use Tests\TestCase;

class MatchmakingFactionHelperTest extends TestCase
{
    private function createMockPlayer(string $username): QmMatchPlayer
    {
        $player = new QmMatchPlayer();
        $player->setRelation('player', (object)['username' => $username]);
        return $player;
    }

    public function test_assign_1v1_factions(): void
    {
        $p1 = $this->createMockPlayer('Player1');
        $p2 = $this->createMockPlayer('Player2');

        // We mock save on players or test logic
        for ($i = 0; $i < 20; $i++) {
            $p1IsAllied = (bool) mt_rand(0, 1);
            if ($p1IsAllied) {
                $p1->actual_side = MatchmakingFactionHelper::pickRandomCountry(MatchmakingFactionHelper::ALLIED_COUNTRIES);
                $p2->actual_side = MatchmakingFactionHelper::pickRandomCountry(MatchmakingFactionHelper::SOVIET_COUNTRIES);
            } else {
                $p1->actual_side = MatchmakingFactionHelper::pickRandomCountry(MatchmakingFactionHelper::SOVIET_COUNTRIES);
                $p2->actual_side = MatchmakingFactionHelper::pickRandomCountry(MatchmakingFactionHelper::ALLIED_COUNTRIES);
            }

            $alliedSides = MatchmakingFactionHelper::ALLIED_COUNTRIES;
            $sovietSides = MatchmakingFactionHelper::SOVIET_COUNTRIES;

            $hasOneAllied = (in_array($p1->actual_side, $alliedSides) && in_array($p2->actual_side, $sovietSides))
                         || (in_array($p1->actual_side, $sovietSides) && in_array($p2->actual_side, $alliedSides));

            $this->assertTrue($hasOneAllied, "1v1 must have exactly 1 Allied and 1 Soviet");
        }
    }

    public function test_assign_2v2_factions(): void
    {
        $allied = MatchmakingFactionHelper::ALLIED_COUNTRIES;
        $soviet = MatchmakingFactionHelper::SOVIET_COUNTRIES;

        for ($i = 0; $i < 20; $i++) {
            $p1 = $this->createMockPlayer('P1');
            $p2 = $this->createMockPlayer('P2');
            $p3 = $this->createMockPlayer('P3');
            $p4 = $this->createMockPlayer('P4');

            // Team A
            $tA1 = MatchmakingFactionHelper::pickRandomCountry($allied);
            $tA2 = MatchmakingFactionHelper::pickRandomCountry($soviet);

            // Team B
            $tB1 = MatchmakingFactionHelper::pickRandomCountry($allied);
            $tB2 = MatchmakingFactionHelper::pickRandomCountry($soviet);

            $this->assertTrue(in_array($tA1, $allied) && in_array($tA2, $soviet));
            $this->assertTrue(in_array($tB1, $allied) && in_array($tB2, $soviet));
        }
    }

    public function test_assign_3v3_factions(): void
    {
        $allied = MatchmakingFactionHelper::ALLIED_COUNTRIES;
        $soviet = MatchmakingFactionHelper::SOVIET_COUNTRIES;

        for ($i = 0; $i < 10; $i++) {
            $teamAAllied = (bool) mt_rand(0, 1);
            $assignedAllied = [];
            for ($k = 0; $k < 3; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($allied, $assignedAllied);
                $assignedAllied[] = $c;
            }

            $assignedSoviet = [];
            for ($k = 0; $k < 3; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($soviet, $assignedSoviet);
                $assignedSoviet[] = $c;
            }

            $this->assertCount(3, $assignedAllied);
            $this->assertCount(3, $assignedSoviet);
            foreach ($assignedAllied as $c) {
                $this->assertContains($c, $allied);
            }
            foreach ($assignedSoviet as $c) {
                $this->assertContains($c, $soviet);
            }
        }
    }

    public function test_assign_4v4_factions(): void
    {
        $allied = MatchmakingFactionHelper::ALLIED_COUNTRIES;
        $soviet = MatchmakingFactionHelper::SOVIET_COUNTRIES;

        for ($i = 0; $i < 20; $i++) {
            // Team A: 2 Allied, 2 Soviet
            $assignedAlliedA = [];
            for ($k = 0; $k < 2; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($allied, $assignedAlliedA);
                $assignedAlliedA[] = $c;
            }
            $assignedSovietA = [];
            for ($k = 0; $k < 2; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($soviet, $assignedSovietA);
                $assignedSovietA[] = $c;
            }

            // Team B: 2 Allied, 2 Soviet
            $assignedAlliedB = [];
            for ($k = 0; $k < 2; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($allied, $assignedAlliedB);
                $assignedAlliedB[] = $c;
            }
            $assignedSovietB = [];
            for ($k = 0; $k < 2; $k++) {
                $c = MatchmakingFactionHelper::pickRandomCountry($soviet, $assignedSovietB);
                $assignedSovietB[] = $c;
            }

            $this->assertCount(2, $assignedAlliedA);
            $this->assertCount(2, $assignedSovietA);
            $this->assertCount(2, $assignedAlliedB);
            $this->assertCount(2, $assignedSovietB);

            // Distinct countries in each pool per team
            $this->assertEquals(2, count(array_unique($assignedAlliedA)));
            $this->assertEquals(2, count(array_unique($assignedSovietA)));
            $this->assertEquals(2, count(array_unique($assignedAlliedB)));
            $this->assertEquals(2, count(array_unique($assignedSovietB)));
        }
    }
}
