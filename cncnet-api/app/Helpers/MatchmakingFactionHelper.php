<?php

namespace App\Helpers;

use App\Models\QmMatchPlayer;
use Illuminate\Support\Facades\Log;

class MatchmakingFactionHelper
{
    // Red Alert 2 / Yuri's Revenge Country IDs
    public const ALLIED_COUNTRIES = [
        0, // America
        1, // Korea
        2, // France
        3, // Germany
        4, // Great Britain
    ];

    public const SOVIET_COUNTRIES = [
        5, // Libya
        6, // Iraq
        7, // Cuba
        8, // Russia
    ];

    /**
     * Pick a random country from a given list, attempting to avoid duplicates if possible.
     */
    public static function pickRandomCountry(array $pool, array $alreadyAssigned = []): int
    {
        $available = array_values(array_diff($pool, $alreadyAssigned));
        if (empty($available)) {
            $available = $pool;
        }

        return $available[array_rand($available)];
    }

    /**
     * 1v1: One player is Allied (Random country), the other is Soviet (Random country).
     * 50/50 chance for who gets Allies vs Soviets.
     */
    public static function assign1v1(QmMatchPlayer $p1, QmMatchPlayer $p2): void
    {
        $p1IsAllied = (bool) mt_rand(0, 1);

        if ($p1IsAllied) {
            $p1->actual_side = self::pickRandomCountry(self::ALLIED_COUNTRIES);
            $p2->actual_side = self::pickRandomCountry(self::SOVIET_COUNTRIES);
        } else {
            $p1->actual_side = self::pickRandomCountry(self::SOVIET_COUNTRIES);
            $p2->actual_side = self::pickRandomCountry(self::ALLIED_COUNTRIES);
        }

        $p1->save();
        $p2->save();

        Log::info("[MatchmakingFactionHelper] 1v1 Assigned: {$p1->player->username}=" . ($p1IsAllied ? "Allied({$p1->actual_side})" : "Soviet({$p1->actual_side})") . " vs {$p2->player->username}=" . (!$p1IsAllied ? "Allied({$p2->actual_side})" : "Soviet({$p2->actual_side})"));
    }

    /**
     * 2v2: Each team has exactly 1 Allied and 1 Soviet player.
     * Both teams are synchronized: Team A (1 Allied + 1 Soviet), Team B (1 Allied + 1 Soviet).
     *
     * @param QmMatchPlayer[] $teamAPlayers
     * @param QmMatchPlayer[] $teamBPlayers
     */
    public static function assign2v2(array $teamAPlayers, array $teamBPlayers): void
    {
        self::assignTeamAlliedAndSoviet($teamAPlayers, 'Team A');
        self::assignTeamAlliedAndSoviet($teamBPlayers, 'Team B');
    }

    /**
     * 3v3: Team 1 is ALL Allied (3 Allied players), Team 2 is ALL Soviet (3 Soviet players).
     *
     * @param QmMatchPlayer[] $teamAPlayers
     * @param QmMatchPlayer[] $teamBPlayers
     */
    public static function assign3v3(array $teamAPlayers, array $teamBPlayers): void
    {
        $teamAIsAllied = (bool) mt_rand(0, 1);
        $alliedTeam = $teamAIsAllied ? $teamAPlayers : $teamBPlayers;
        $sovietTeam = $teamAIsAllied ? $teamBPlayers : $teamAPlayers;

        $assignedAllied = [];
        foreach ($alliedTeam as $player) {
            $c = self::pickRandomCountry(self::ALLIED_COUNTRIES, $assignedAllied);
            $assignedAllied[] = $c;
            $player->actual_side = $c;
            $player->save();
        }

        $assignedSoviet = [];
        foreach ($sovietTeam as $player) {
            $c = self::pickRandomCountry(self::SOVIET_COUNTRIES, $assignedSoviet);
            $assignedSoviet[] = $c;
            $player->actual_side = $c;
            $player->save();
        }

        Log::info("[MatchmakingFactionHelper] 3v3 Assigned: " . ($teamAIsAllied ? "Team A=Allies, Team B=Soviets" : "Team A=Soviets, Team B=Allies"));
    }

    /**
     * 4v4: Each team has exactly 2 Allied and 2 Soviet players.
     * Both teams are synchronized: Team A (2 Allied + 2 Soviet) vs Team B (2 Allied + 2 Soviet).
     *
     * @param QmMatchPlayer[] $teamAPlayers
     * @param QmMatchPlayer[] $teamBPlayers
     */
    public static function assign4v4(array $teamAPlayers, array $teamBPlayers): void
    {
        self::assignTeam2Allied2Soviet($teamAPlayers, 'Team A');
        self::assignTeam2Allied2Soviet($teamBPlayers, 'Team B');
    }

    /**
     * Helper to assign 2 Allied and 2 Soviet countries to a 4-player team.
     *
     * @param QmMatchPlayer[] $players
     */
    private static function assignTeam2Allied2Soviet(array $players, string $teamName): void
    {
        if (count($players) < 4) {
            return;
        }

        // Shuffle player indices so faction assignment within the team is random
        $indices = [0, 1, 2, 3];
        shuffle($indices);

        $alliedIndices = [$indices[0], $indices[1]];
        $sovietIndices = [$indices[2], $indices[3]];

        $assignedAllied = [];
        foreach ($alliedIndices as $idx) {
            $c = self::pickRandomCountry(self::ALLIED_COUNTRIES, $assignedAllied);
            $assignedAllied[] = $c;
            $players[$idx]->actual_side = $c;
            $players[$idx]->save();
        }

        $assignedSoviet = [];
        foreach ($sovietIndices as $idx) {
            $c = self::pickRandomCountry(self::SOVIET_COUNTRIES, $assignedSoviet);
            $assignedSoviet[] = $c;
            $players[$idx]->actual_side = $c;
            $players[$idx]->save();
        }

        Log::info("[MatchmakingFactionHelper] Team {$teamName} 4v4 Assigned: 2 Allies, 2 Soviets");
    }

    /**
     * Helper to assign 1 Allied and 1 Soviet country to a 2-player team.
     *
     * @param QmMatchPlayer[] $players
     */
    private static function assignTeamAlliedAndSoviet(array $players, string $teamName): void
    {
        if (count($players) < 2) {
            return;
        }

        $firstIsAllied = (bool) mt_rand(0, 1);
        $p1 = $players[0];
        $p2 = $players[1];

        if ($firstIsAllied) {
            $p1->actual_side = self::pickRandomCountry(self::ALLIED_COUNTRIES);
            $p2->actual_side = self::pickRandomCountry(self::SOVIET_COUNTRIES);
        } else {
            $p1->actual_side = self::pickRandomCountry(self::SOVIET_COUNTRIES);
            $p2->actual_side = self::pickRandomCountry(self::ALLIED_COUNTRIES);
        }

        $p1->save();
        $p2->save();

        Log::info("[MatchmakingFactionHelper] Team {$teamName} Assigned: {$p1->player->username}=" . ($firstIsAllied ? "Allied({$p1->actual_side})" : "Soviet({$p1->actual_side})") . ", {$p2->player->username}=" . (!$firstIsAllied ? "Allied({$p2->actual_side})" : "Soviet({$p2->actual_side})"));
    }
}

