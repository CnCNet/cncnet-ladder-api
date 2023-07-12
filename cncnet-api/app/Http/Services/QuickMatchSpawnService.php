<?php

namespace App\Http\Services;

use App\Helpers\AIHelper;
use App\Helpers\GameHelper;
use App\SpawnOptionType;
use Illuminate\Support\Facades\Log;

class QuickMatchSpawnService
{
    /**
     * Creates base spawn.ini for matchups
     * @param mixed $qmMatch 
     * @param mixed $qmPlayer 
     * @param mixed $ladder 
     * @param mixed $ladderRules 
     * @return array 
     */
    public static function createSpawnStruct($qmMatch, $qmPlayer, $ladder, $ladderRules)
    {
        $qmMap = $qmMatch->map;
        $map = $qmMap->map;

        $spawnStruct = [
            "type" => "spawn",
            "gameID" => $qmMatch->game_id,
            "spawn" => [
                "SpawnLocations" => [],
                "Settings" => [
                    "SkipScoreScreen" => $qmPlayer->player->user->userSettings->skip_score_screen ? "Yes" : "No"
                ]
            ],
            "client" => ["show_map_preview" => $ladderRules->show_map_preview]
        ];

        srand($qmMatch->seed); // Seed the RNG for possibly random boolean options

        $spawnStruct["spawn"]["Settings"] = array_filter(
            [
                "UIGameMode" =>     $qmMap->game_mode,
                "UIMapName" =>      $qmMap->description,
                "MapHash" =>        $map->hash,
                "Seed" =>           $qmMatch->seed,
                "GameID" =>         $qmMatch->seed,
                "WOLGameID" =>      $qmMatch->seed,
                "Host" =>           "No",
                "Name" =>           $qmPlayer->player()->first()->username,
                "Port" =>           $qmPlayer->port,
                "Side" =>           $qmPlayer->actual_side,
                "Color" =>          $qmPlayer->color,
                "IsSpectator" =>    "False"
                // Filter null values
            ],
            function ($var)
            {
                return !is_null($var);
            }
        );

        foreach ($ladder->spawnOptionValues as $sov)
        {
            switch ($sov->spawnOption->type->id)
            {
                case SpawnOptionType::SPAWN_INI:
                    $spawnStruct["spawn"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                    break;
                case SpawnOptionType::SPAWNMAP_INI:
                    $spawnStruct["spawnmap"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                    break;
                case SpawnOptionType::PREPEND_FILE:
                    $spawnStruct["prepends"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                    break;
                case SpawnOptionType::COPY_FILE:
                    $spawnStruct["copies"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                    break;
                default:
                    break;
            }
        }

        foreach ($qmMap->spawnOptionValues as $sov)
        {
            switch ($sov->spawnOption->type->id)
            {
                case SpawnOptionType::SPAWN_INI:
                    $spawnStruct["spawn"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                    break;
                case SpawnOptionType::SPAWNMAP_INI:
                    $spawnStruct["spawnmap"][$sov->spawnOption->string1->string][$sov->spawnOption->string2->string] = $sov->value->string;
                    break;
                case SpawnOptionType::PREPEND_FILE:
                    $spawnStruct["prepends"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                    break;
                case SpawnOptionType::COPY_FILE:
                    $spawnStruct["copies"][] = ["to" => $sov->spawnOption->string1->string, "from" => $sov->value->string];
                    break;
                default:
                    break;
            }
        }

        return $spawnStruct;
    }


    public static function appendSpawnLocations($spawnStruct, $qmPlayer, $qmMatchPlayers)
    {
        $otherIndex = 1;
        $multiIndex = $qmPlayer->color + 1;

        $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $qmPlayer->location;

        # Other, SpawnLocations, 
        foreach ($qmMatchPlayers as $opn)
        {
            # Other{1,2,3} etc
            $spawnStruct["spawn"]["Other{$otherIndex}"] = [
                "Name"          => $opn->player()->first()->username,
                "Side"          => $opn->actual_side,
                "Color"         => $opn->color,
                "Ip"            => $opn->ipAddress ? $opn->ipAddress->address : "",
                "Port"          => $opn->port,
                "IPv6"          => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                "PortV6"        => $opn->ipv6_port,
                "LanIP"         => $opn->lan_address ? $opn->lan_address->address : "",
                "LanPort"       => $opn->lan_port,
                "IsSpectator"   => $opn->isObserver() ? "True" : "False"
            ];

            # SpawnLocations
            $multiIndex = $opn->color + 1;
            $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $opn->location;

            # Superweapon/faction logic
            if (
                array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) &&
                $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes"
            )
            {
                # If p1 is allied and p2 is yuri, or if p1 is yuri and p2 is allied then disable SW for this match
                if (
                    ($qmPlayer->actual_side < 5 && $opn->actual_side == 9) ||
                    ($opn->actual_side < 5 && $qmPlayer->actual_side == 9)
                )
                {
                    $spawnStruct["spawn"]["Settings"]["Superweapons"] = "False";
                }
            }

            $otherIndex++;
        }

        return $spawnStruct;
    }

    public static function appendTeamAlliances($spawnStruct, $qmPlayer, $qmMatchPlayers)
    {
        $multiIndex = $qmPlayer->color + 1;
        $currentPlayerIndex = $multiIndex;
        $currentPlayerTeamIndexes = [];
        $currentPlayerTeamIndexes[] = $currentPlayerIndex;

        # Multi Alliances - for opponent's team
        $teamAllianceComplete = false;
        foreach ($qmMatchPlayers as $opn)
        {
            $multiIndex = $opn->color + 1;

            # Check if other player is in my clan, if so add alliance
            if ($qmPlayer->clan_id && $qmPlayer->clan_id == $opn->clan_id)
            {
                $p1Name = $qmPlayer->player->username;
                $p2Name = $opn->player->username;

                Log::info("PlayerIndex ** assigning $p1Name with $p2Name");

                $spawnStruct["spawn"]["Multi{$currentPlayerIndex}_Alliances"]["HouseAllyOne"] = $multiIndex - 1;
                $spawnStruct["spawn"]["Multi{$multiIndex}_Alliances"]["HouseAllyOne"] = $currentPlayerIndex - 1;

                $currentPlayerTeamIndexes[] = $multiIndex;
            }

            # This index is opponent's team
            if (!in_array($multiIndex, $currentPlayerTeamIndexes)) // This index is opponent's team
            {
                foreach ($qmMatchPlayers as $opn2)
                {
                    $otherIdx = $opn2->color + 1;

                    if ($otherIdx == $multiIndex) // self
                        continue;

                    if (!in_array($otherIdx, $currentPlayerTeamIndexes)) // This index is opponent's teammate
                    {
                        $p1Name = $opn->player->username;
                        $p2Name = $opn2->player->username;

                        Log::info("PlayerIndex ** assigning opponents $p1Name with $p2Name");
                        $spawnStruct["spawn"]["Multi{$otherIdx}_Alliances"]["HouseAllyOne"] = $multiIndex - 1;
                        $spawnStruct["spawn"]["Multi{$multiIndex}_Alliances"]["HouseAllyOne"] = $otherIdx - 1;
                        $teamAllianceComplete = true;
                    }
                }
            }

            if ($teamAllianceComplete)
                break;
        }

        return $spawnStruct;
    }



    /**
     * Checks if any players are observers and writes to spawnstruct
     * @param mixed $spawnStruct 
     * @param mixed $qmPlayer 
     * @param mixed $qmMatchPlayers 
     * @return mixed 
     */
    public static function appendObservers($spawnStruct, $qmPlayer, $qmMatchPlayers)
    {
        # Checks if player is observer
        if ($qmPlayer->isObserver())
        {
            $spawnStruct["spawn"]["Settings"]["IsSpectator"] = "True";
        }

        # Make sure we mark other players too
        foreach ($qmMatchPlayers as $playerIndex => $opn)
        {
            if ($opn->isObserver())
            {
                # Because it references "Other", which is 1-8
                $playerIndex = $playerIndex + 1;
                $spawnStruct["isspectator"]["Multi$playerIndex"] = "True";
            }
        }
        return $spawnStruct;
    }


    /**
     * Matchup against AI only, prepends AI house data
     * @param mixed $spawnStruct 
     * @return void 
     */
    public static function addQuickMatchAISpawnIni($spawnStruct, $ladder, $difficulty)
    {
        $spawnStruct["spawn"]["Settings"]["AIPlayers"] = 1;
        $spawnStruct["spawn"]["Settings"]["GameSpeed"] = 1;
        $spawnStruct["prepends"][] = ["to" => "spawn.ini", "from" => "INI/Quick Match/QuickMatchAI.ini"];

        if ($ladder->abbreviation == GameHelper::$GAME_BLITZ)
        {
            $spawnStruct["prepends"][] = ["to" => "spawnmap.ini", "from" => "INI/Quick Match/AIBlitz.ini"];
        }

        return $spawnStruct;
    }
}
