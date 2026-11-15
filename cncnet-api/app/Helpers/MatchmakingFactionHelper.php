<?php

namespace App\Helpers;

/**
 * Picks the countries of matchmaking players so that the two factions of a game,
 * such as Allied and Soviet in Red Alert 2 and Yuri's Revenge, are played equally.
 */
class MatchmakingFactionHelper
{
    /**
     * Picks a country for every player of a match. The players of a team with an even number of players
     * are split evenly between the two factions. Teams with an odd number of players play one faction each,
     * alternating between the factions, so in 1v1 and 3v3 one team plays each faction.
     * Countries are not repeated within a team while its faction has enough countries.
     *
     * @param int[] $teamSizes number of players in each team
     * @param int[][] $factions the countries of each of the two factions
     * @return int[][] a country for each player of each team, in the order of $teamSizes
     */
    public static function pickCountries(array $teamSizes, array $factions): array
    {
        $factions = array_values($factions);
        $oddTeamFaction = mt_rand(0, 1);
        $countries = [];

        foreach ($teamSizes as $teamSize)
        {
            if ($teamSize % 2 === 0)
            {
                $playerFactions = array_merge(array_fill(0, $teamSize / 2, 0), array_fill(0, $teamSize / 2, 1));
                shuffle($playerFactions);
            }
            else
            {
                $playerFactions = array_fill(0, $teamSize, $oddTeamFaction);
                $oddTeamFaction = 1 - $oddTeamFaction;
            }

            $teamCountries = [];
            foreach ($playerFactions as $faction)
            {
                $teamCountries[] = self::pickCountry($factions[$faction], $teamCountries);
            }

            $countries[] = $teamCountries;
        }

        return $countries;
    }

    /**
     * Picks a random country, preferring countries that are not used yet.
     */
    private static function pickCountry(array $countries, array $usedCountries): int
    {
        $unusedCountries = array_values(array_diff($countries, $usedCountries));
        $candidates = empty($unusedCountries) ? array_values($countries) : $unusedCountries;

        return $candidates[array_rand($candidates)];
    }
}
