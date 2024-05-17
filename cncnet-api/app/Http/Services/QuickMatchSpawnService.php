<?php

namespace App\Http\Services;

use App\Helpers\AIHelper;
use App\Helpers\GameHelper;
use App\Models\QmMatchPlayer;
use App\Models\SpawnOptionType;
use Illuminate\Support\Collection;
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
                "Settings" => []
            ],
            "client" => ["show_map_preview" => $ladderRules->show_map_preview]
        ];

        srand($qmMatch->seed); // Seed the RNG for possibly random boolean options

        $spawnStruct["spawn"]["Settings"] = array_filter(
            [
                "UIGameMode" =>     $qmMap->game_mode,
                "UIMapName" =>      $qmMap->description,
                "MapHash" =>        $map->hash,
                "Scenario" =>       $map->filename !== null ? $map->filename : "spawnmap.ini",
                "Seed" =>           $qmMatch->seed,
                "GameID" =>         $qmMatch->seed,
                "WOLGameID" =>      $qmMatch->seed,
                "Host" => ($qmPlayer->color == 0 && $ladder->abbreviation == "d2k") ? "Yes" : "No", // if Dune and player color is 0
                "Name" =>           $qmPlayer->player()->first()->username,
                "Port" =>           $qmPlayer->port,
                "Side" =>           $qmPlayer->actual_side,
                "Color" =>          $qmPlayer->color,
                "MyIndex" =>        $qmPlayer->color,
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

    /**
     * Appends the "other" section of all players to the spawn.ini 
     * @param mixed $spawnStruct 
     * @param mixed $qmPlayer 
     * @param mixed $otherQmMatchPlayers 
     * @return mixed 
     */
    public static function appendOthersAndTeamAlliancesToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers)
    {
        Log::debug("QuickMatchSpawnService ** appendOthersAndTeamAlliancesToSpawnIni: Is Observer: " . $qmPlayer->isObserver());

        $otherIndex = 1;
        $multiIndex = $qmPlayer->color + 1;
        $currentQmPlayerIndex = $multiIndex;

        if ($qmPlayer->isObserver() == false)
        {
            $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $qmPlayer->location;
        }

        if ($qmPlayer->player->user->userSettings->skip_score_screen)
        {
            $spawnStruct["spawn"]["Settings"]["SkipScoreScreen"] = "Yes";
        }

        $myTeamIndices = [];
        $myTeamIndices[] = $currentQmPlayerIndex;

        foreach ($otherQmMatchPlayers as $opn)
        {
            # Other{1,2,3} etc
            $spawnStruct["spawn"]["Other{$otherIndex}"] = [
                "Name"          => $opn->player()->first()->username,
                "Side"          => $opn->actual_side,
                "Color"         => $opn->color,
                "MyIndex"       => $opn->color,
                "Ip"            => $opn->ipAddress ? $opn->ipAddress->address : "",
                "Port"          => $opn->port,
                "IPv6"          => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                "PortV6"        => $opn->ipv6_port,
                "LanIP"         => $opn->lan_address ? $opn->lan_address->address : "",
                "LanPort"       => $opn->lan_port,
                "IsSpectator"   => $opn->isObserver() ? "True" : "False",
                "Host"          => ($opn->color == 0 && $qmPlayer->ladder->abbreviation == "d2k") ? "Yes" : "No", // if Dune and player color is 0
            ];


            $multiIndex = $opn->color + 1;

            if ($opn->isObserver() == false)
            {
                $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $opn->location;
            }


            # Check if other player is in my clan, if so add alliance
            if (
                $qmPlayer->clan_id
                && $qmPlayer->clan_id == $opn->clan_id
            )
            {
                $clanName = $qmPlayer->clan->name;
                $p1Name = $qmPlayer->player->username;
                $p1IsObserver = $qmPlayer->isObserver();

                $p2Name = $opn->player->username;
                $p2IsObserver  = $opn->isObserver();

                if ($p1IsObserver == false && $p2IsObserver == false)
                {
                    Log::debug("QuickMatchSpawnService 1 ** Alliances: Teaming for $clanName, Player: $p1Name (OBS: $p1IsObserver) with Player: $p2Name (OBS: $$p2IsObserver)");

                    $spawnStruct["spawn"]["Multi{$currentQmPlayerIndex}_Alliances"]["HouseAllyOne"] = $multiIndex - 1;
                    $spawnStruct["spawn"]["Multi{$multiIndex}_Alliances"]["HouseAllyOne"] = $currentQmPlayerIndex - 1;
                    $myTeamIndices[] = $multiIndex;
                }
            }


            # Superweapon/faction logic
            if (
                array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) &&
                $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes"
            )
            {
                # If p1 is allied and p2 is yuri
                # or if p1 is yuri and p2 is allied then disable SW for this match
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

        if ($qmPlayer->clan_id)
        {
            //create multi alliance for opponent's team
            $completed = false;
            foreach ($otherQmMatchPlayers as $opn)
            {
                $multiIndex = $opn->color + 1;

                if ($opn->isObserver() == false)
                {
                    if (!in_array($multiIndex, $myTeamIndices)) //this index is opponent's team
                    {
                        foreach ($otherQmMatchPlayers as $opn2) //find teammate(s)
                        {
                            $otherIndex = $opn2->color + 1;

                            if ($otherIndex == $multiIndex) //self
                                continue;

                            if (!in_array($otherIndex, $myTeamIndices)) //this index is opponent's teammate
                            {
                                $p1Name = $opn->player->username;
                                $p2Name = $opn2->player->username;

                                $p1IsObserver = $opn->isObserver();
                                $p2IsObserver = $opn2->isObserver();

                                if ($p1IsObserver == false && $p2IsObserver == false)
                                {
                                    if ($opn->clan_id == $opn2->clan_id)
                                    {
                                        Log::debug("QuickMatchSpawnService 2 ** Alliances: Teaming Player: $p1Name (OBS: $p1IsObserver) with Player: $p2Name (OBS: $p2IsObserver)");
                                        $spawnStruct["spawn"]["Multi{$otherIndex}_Alliances"]["HouseAllyOne"] = $multiIndex - 1;
                                        $spawnStruct["spawn"]["Multi{$multiIndex}_Alliances"]["HouseAllyOne"] = $otherIndex - 1;
                                        $completed = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($completed)
                    break;
            }
        }

        return $spawnStruct;
    }

    /**
     * Appends the "other" section of all players to the spawn.ini
     * @param mixed $spawnStruct
     * @param mixed $qmPlayer
     * @param mixed $otherQmMatchPlayers
     * @return mixed
     */
    public static function appendOthersToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers)
    {
        Log::info("QuickMatchSpawnService ** appendOthersAndTeamAlliancesToSpawnIni: Is Observer: " . $qmPlayer->isObserver());

        $otherIndex = 1;
        $multiIndex = $qmPlayer->color + 1;

        if ($qmPlayer->isObserver() == false)
        {
            $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $qmPlayer->location;
        }

        if ($qmPlayer->player->user->userSettings->skip_score_screen)
        {
            $spawnStruct["spawn"]["Settings"]["SkipScoreScreen"] = "Yes";
        }

        foreach ($otherQmMatchPlayers as $opn)
        {
            # Other{1,2,3} etc
            $spawnStruct["spawn"]["Other{$otherIndex}"] = [
                "Name"          => $opn->player()->first()->username,
                "Side"          => $opn->actual_side,
                "Color"         => $opn->color,
                "MyIndex"       => $opn->color,
                "Ip"            => $opn->ipAddress ? $opn->ipAddress->address : "",
                "Port"          => $opn->port,
                "IPv6"          => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                "PortV6"        => $opn->ipv6_port,
                "LanIP"         => $opn->lan_address ? $opn->lan_address->address : "",
                "LanPort"       => $opn->lan_port,
                "IsSpectator"   => $opn->isObserver() ? "True" : "False",
                "Host"          => ($opn->color == 0 && $qmPlayer->ladder->abbreviation == "d2k") ? "Yes" : "No", // if Dune and player color is 0
            ];


            $multiIndex = $opn->color + 1;

            if ($opn->isObserver() == false)
            {
                $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIndex}"] = $opn->location;
            }


            # Superweapon/faction logic
            if (
                array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) &&
                $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes"
            )
            {
                # If p1 is allied and p2 is yuri
                # or if p1 is yuri and p2 is allied then disable SW for this match
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


    /**
     * Appents the alliences section to the spawn.ini for TEAM only (no clan)
     * @param $spawnStruct
     * @param QmMatchPlayer $qmPlayer
     * @param Collection $otherQmMatchPlayers
     * @return mixed
     */
    public static function appendAlliancesToSpawnIni($spawnStruct, QmMatchPlayer $qmPlayer, Collection $otherQmMatchPlayers)
    {

        // group all players by team
        $playersByTeam = $otherQmMatchPlayers->concat([$qmPlayer])->groupBy(fn ($p) => $p->team);
        foreach ($playersByTeam as $team => $players)
        {
            for ($i = 0; $i < $players->count(); $i++)
            {
                for ($j = 0; $j < $players->count(); $j++)
                {
                    if ($players[$i] == $players[$j]) break;
                    $p1Index = $players[$i]->color + 1;
                    $p2Index = $players[$j]->color + 1;
                    Log::info("QuickMatchSpawnService 2 ** Alliances: Teaming for $team, Player: {$players[$i]->player->username} with Player: {$players[$j]->player->username}");
                    Log::info("QuickMatchSpawnService 2 ** Alliances: Teaming for $team, Player: {$players[$j]->player->username} with Player: {$players[$i]->player->username}");
                    $spawnStruct["spawn"]["Multi{$p1Index}_Alliances"]["HouseAllyOne"] = $p2Index - 1;
                    $spawnStruct["spawn"]["Multi{$p2Index}_Alliances"]["HouseAllyOne"] = $p1Index - 1;
                }
            }
        }

        return $spawnStruct;
    }

    public static function appendRA1AlliancesToSpawnIni($spawnStruct, QmMatchPlayer $qmPlayer, Collection $otherQmMatchPlayers)
    {
        // Constants for RA1 houses starting index
        $RA1_HOUSE_MULTI_INDEX_OFFSET = 12;

        // Group all players by team
        $playersByTeam = $otherQmMatchPlayers->concat([$qmPlayer])->groupBy(fn ($p) => $p->team);
        foreach ($playersByTeam as $team => $players)
        {
            for ($i = 0; $i < $players->count(); $i++)
            {
                for ($j = 0; $j < $players->count(); $j++)
                {
                    if ($players[$i] == $players[$j]) continue;

                    $p1Index = $players[$i]->color + 1;
                    $p2Index = $players[$j]->color + 1;

                    $p1IndexV = $players[$i]->color + $RA1_HOUSE_MULTI_INDEX_OFFSET;
                    $p2IndexV = $players[$j]->color + $RA1_HOUSE_MULTI_INDEX_OFFSET;

                    Log::info("p1Index: $p1Index -- p1IndexV: $p1IndexV");
                    Log::info("p2Index: $p2Index -- p2IndexV: $p2IndexV");

                    Log::info("QuickMatchSpawnService 2 ** Alliances: Teaming for $team, Player: {$players[$i]->player->username} with Player: {$players[$j]->player->username}");
                    Log::info("QuickMatchSpawnService 2 ** Alliances: Teaming for $team, Player: {$players[$j]->player->username} with Player: {$players[$i]->player->username}");

                    // Set the alliances directly using RA1 house indices
                    $spawnStruct["spawn"]["Multi{$p1Index}_Alliances"]["HouseAllyOne"] = $p2IndexV;
                    $spawnStruct["spawn"]["Multi{$p2Index}_Alliances"]["HouseAllyOne"] = $p1IndexV;
                }
            }
        }

        return $spawnStruct;
    }

    /**
     * Checks if any players are observers and writes to spawnstruct
     * @param mixed $spawnStruct 
     * @param mixed $qmPlayer 
     * @param mixed $otherQmMatchPlayers 
     * @return mixed 
     */
    public static function appendObservers($spawnStruct, $qmPlayer, $otherQmMatchPlayers)
    {
        # Checks if current player is observer
        if ($qmPlayer->isObserver())
        {
            $playerIndex = 1;
            $spawnStruct["spawn"]["Settings"]["IsSpectator"] = "True";
            $spawnStruct["isspectator"]["Multi$playerIndex"] = "True";
        }

        # Make sure we mark other players too
        foreach ($otherQmMatchPlayers as $playerIndex => $opn)
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
     * Prepend quick-coop ini file to allow 2 real players vs 2 ai
     * @param mixed $spawnStruct 
     * @return mixed 
     */
    public static function addQuickMatchCoopAISpawnIni($spawnStruct, $difficulty)
    {
        $spawnStruct["spawn"]["Settings"]["AIPlayers"] = 2;
        $spawnStruct["spawn"]["Settings"]["GameSpeed"] = 1;
        $spawnStruct["prepends"][] = ["to" => "spawn.ini", "from" => "INI/Quick Match/QuickMatchAI.ini"];


        # TODO: SpawnMap modificiations for different difficulty AI
        switch ($difficulty)
        {
            case AIHelper::BRUTAL_AI:
                $spawnStruct["prepends"][] = ["to" => "spawnmap.ini", "from" => "INI/Game Options/Brutal AI.ini"];
                break;

            default:
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
