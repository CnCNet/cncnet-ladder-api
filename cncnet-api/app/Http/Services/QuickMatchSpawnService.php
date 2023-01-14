<?php

namespace App\Http\Services;

use App\Commands\FindOpponent;
use App\Game;
use App\Ladder;
use App\LadderHistory;
use App\QmQueueEntry;
use App\SpawnOptionType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QuickMatchSpawnService
{

    public function createInitialSpawnStruct($qmMatch, $qmPlayer, $ladder, $ladderRules)
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
    }

    public function createOthersSpawnSection($spawnStruct, $qmPlayer, $allPlayers)
    {
        $other_idx = 1;
        $multi_idx = $qmPlayer->color + 1;
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

        $qmPlayer->waiting = false;
        $qmPlayer->save();

        return $spawnStruct;
    }
}
