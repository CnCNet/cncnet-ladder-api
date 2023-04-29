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

    /**
     * Appends the "other" section of all players to the spawn.ini 
     * @param mixed $spawnStruct 
     * @param mixed $qmPlayer 
     * @param mixed $otherQmPlayers 
     * @return mixed 
     */
    public static function appendOthersAndTeamAlliancesToSpawnIni($spawnStruct, $qmPlayer, $otherQmPlayers)
    {
        $otherIdx = 1;
        $multiIdx = $qmPlayer->color + 1;
        $myIndex = $multiIdx;
        $observerIndex = -1;

        # Checks if player is observer
        $observerPlayerName = null; # Set to "neogrant" or "burg" for tests
        $myPlayerUsername = $qmPlayer->player->username;

        if ($myPlayerUsername == $observerPlayerName)
        {
            $observerIndex = $myIndex;
            $spawnStruct["spawn"]["Settings"]["IsSpectator"] = "True";
            Log::info("Setting $myPlayerUsername getting set as spectator");
        }

        $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIdx}"] = $qmPlayer->location;

        $myTeamIndices = [];
        foreach ($otherQmPlayers as $opn)
        {
            $spawnStruct["spawn"]["Other{$otherIdx}"] = [
                "Name" => $opn->player()->first()->username,
                "Side" => $opn->actual_side,
                "Color" => $opn->color,
                "Ip" =>   $opn->ipAddress ? $opn->ipAddress->address : "",
                "Port" => $opn->port,
                "IPv6" => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                "PortV6" => $opn->ipv6_port,
                "LanIP" => $opn->lan_address ? $opn->lan_address->address : "",
                "LanPort" => $opn->lan_port,
                "IsSpectator" => false
            ];

            # Set the observer
            if ($opn->player->username == $observerPlayerName)
            {
                Log::info("$observerPlayerName is set to spec in other players spawn.ini");
                $spawnStruct["spawn"]["Other{$otherIdx}"]["IsSpectator"] = true;
                $observerIndex = $otherIdx;
            }

            $multiIdx = $opn->color + 1;
            $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multiIdx}"] = $opn->location;

            # Check if other player is in my clan, if so add alliance
            if ($qmPlayer->clan_id && $qmPlayer->clan_id == $opn->clan_id)
            {
                $p1Name = $qmPlayer->player->username;
                $p2Name = $opn->player->username;

                Log::info("PlayerIndex ** assigning $p1Name with $p2Name");
                $spawnStruct["spawn"]["Multi{$myIndex}_Alliances"]["HouseAllyOne"] = $multiIdx - 1;
                $spawnStruct["spawn"]["Multi{$multiIdx}_Alliances"]["HouseAllyOne"] = $myIndex - 1;
                $myTeamIndices[] = $multiIdx;
            }

            $otherIdx++;

            if (array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) && $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes")
            {
                # if p1 is allied and p2 is yuri, or if p1 is yuri and p2 is allied then disable SW for this match
                if (($qmPlayer->actual_side < 5 && $opn->actual_side == 9) || ($opn->actual_side < 5 && $qmPlayer->actual_side == 9))
                {
                    $spawnStruct["spawn"]["Settings"]["Superweapons"] = "False";
                }
            }
        }

        //create multi alliance for opponent's team
        $completed = false;
        foreach ($otherQmPlayers as $opn)
        {
            $multiIdx = $opn->color + 1;

            if (!in_array($multiIdx, $myTeamIndices)) //this index is opponent's team
            {
                foreach ($otherQmPlayers as $opn2) //find teammate(s)
                {
                    $otherIdx = $opn2->color + 1;

                    if ($otherIdx == $multiIdx) //self
                        continue;

                    if (!in_array($otherIdx, $myTeamIndices)) //this index is opponent's teammate
                    {
                        $p1Name = $opn->player->username;
                        $p2Name = $opn2->player->username;

                        Log::info("PlayerIndex ** assigning $p1Name with $p2Name");
                        $spawnStruct["spawn"]["Multi{$otherIdx}_Alliances"]["HouseAllyOne"] = $multiIdx - 1;
                        $spawnStruct["spawn"]["Multi{$multiIdx}_Alliances"]["HouseAllyOne"] = $otherIdx - 1;
                        $completed = true;
                    }
                }
            }

            if ($completed)
                break;
        }

        # Set observer index if they exist
        if ($observerIndex !== null)
        {
            foreach ($otherQmPlayers as $opn)
            {
                $spawnStruct["isspectator"]["Multi$observerIndex"] = "True";
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
