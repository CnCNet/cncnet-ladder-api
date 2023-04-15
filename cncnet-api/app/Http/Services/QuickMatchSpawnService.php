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
                "IsSpectator" =>    "No",
                "Name" =>           $qmPlayer->player()->first()->username,
                "Port" =>           $qmPlayer->port,
                "Side" =>           $qmPlayer->actual_side,
                "Color" =>          $qmPlayer->color
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
     * @param mixed $allPlayers 
     * @return mixed 
     */
    public static function appendOthersToSpawnIni($spawnStruct, $qmPlayer, $allPlayers)
    {
        $other_idx = 1;
        $multi_idx = $qmPlayer->color + 1;
        $myIndex = $multi_idx;
        $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multi_idx}"] = $qmPlayer->location;

        foreach ($allPlayers as $opn)
        {
            $spawnStruct["spawn"]["Other{$other_idx}"] = [
                "Name" => $opn->player()->first()->username,
                "Side" => $opn->actual_side,
                "Color" => $opn->color,
                "Ip" =>   $opn->ipAddress ? $opn->ipAddress->address : "",
                "Port" => $opn->port,
                "IPv6" => $opn->ipv6Address ? $opn->ipv6Address->address : "",
                "PortV6" => $opn->ipv6_port,
                "LanIP" => $opn->lan_address ? $opn->lan_address->address : "",
                "LanPort" => $opn->lan_port
            ];
            $multi_idx = $opn->color + 1;
            $spawnStruct["spawn"]["SpawnLocations"]["Multi{$multi_idx}"] = $opn->location;

            //check if other player is in my clan, if so add alliance
            if ($qmPlayer->clan_id == $opn->clan_id)
            {
                $p1Name = $qmPlayer->player->username;
                $p2Name = $opn->player->username;

                Log::info("PlayerIndex ** assigning $p1Name with $p2Name");
                $spawnStruct["spawn"]["Multi{$myIndex}_Alliances"]["HouseAllyOne"] = $other_idx - 1;
                $spawnStruct["spawn"]["Multi{$other_idx}_Alliances"]["HouseAllyOne"] = $myIndex - 1;
            }

            $other_idx++;

            if (array_key_exists("DisableSWvsYuri", $spawnStruct["spawn"]["Settings"]) && $spawnStruct["spawn"]["Settings"]["DisableSWvsYuri"] === "Yes")
            {
                //if p1 is allied and p2 is yuri, or if p1 is yuri and p2 is allied then disable SW for this match
                if (($qmPlayer->actual_side < 5 && $opn->actual_side == 9) || ($opn->actual_side < 5 && $qmPlayer->actual_side == 9))
                {
                    $spawnStruct["spawn"]["Settings"]["Superweapons"] = "False";
                }
            }
        }

        // $clans = QuickMatchSpawnService::mapPlayerToClans($allPlayers); //map each player to their clan

        // foreach ($players as $multiIdx => $qmPlayer)
        // {
        //     $playerIndex = $multiIdx;

        //     Log::info("PlayerIndex ** $playerIndex");

        //     if (!in_array($qmPlayer->player_id, $clans))
        //     {
        //         $i = count($clans) + 1;
        //         $spawnStruct["spawn"]["Multi{$i}_Alliances"]["HouseAllyOne"] = $playerIndex - 1;
        //         $clans[] = $qmPlayer->player_id;
        //     }
        // }


        return $spawnStruct;
    }

    /**
     * Map each player to their clan.
     */
    public static function mapPlayerToClans($players)
    {
        $clans = []; //{clanName1 -> [p1, p2, p3..], clanName2 -> [p4, p5, p6..], ..}

        foreach ($players as $player)
        {
            $clanName = $player->clanPlayer->clan->name;

            $clans[$clanName][] = $player; // append player to clan obj
        }

        return $clans;
    }

    public static function appendAlliances($spawnStruct, $qmPlayer, $allPlayers)
    {


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
