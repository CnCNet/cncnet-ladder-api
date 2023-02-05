<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function getIndex(Request $request)
    {
        $links = ["Active players", "Active and inactive players", "New players", "All players"];
        $gameModes = ["Blitz", "Red Alert 2", "Yuri's Revenge"];

        $gameModesShort = [
            GameHelper::$GAME_BLITZ,
            GameHelper::$GAME_RA2,
            GameHelper::$GAME_YR
        ];

        $index = isset($request->list) ? max(min(intval($request->list), sizeof($links) - 1), 0) : 0;
        $gameMode = isset($request->mode) ? strval($request->mode) : "blitz";

        if (!in_array($gameMode, $gameModesShort))
        {
            $gameMode = "blitz";
        }

        $jsonFiles = ["players_active.json", "players_inactive.json", "players_new.json", "players_all.json"];
        $jsonData = file_get_contents(resource_path("data/" . $gameMode . "_" . $jsonFiles[$index]));
        $data = json_decode($jsonData, true);

        return view(
            "ranking.index",
            [
                "data" => $data,
                "gameMode" => $gameMode,
                "gameModes" => $gameModes,
                "gameModesShort" => $gameModesShort,
                "links" => $links,
                "index" => $index
            ]
        );
    }
}
