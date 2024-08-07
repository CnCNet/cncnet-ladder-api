<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $gameMode = GameHelper::$GAME_BLITZ;
        }

        $jsonFiles = ["players_active.json", "players_inactive.json", "players_new.json", "players_all.json"];
        $jsonPath = $gameMode . "_" . $jsonFiles[$index];
        $jsonFile = Storage::disk('rating')->get($jsonPath);
        $jsonData = json_decode($jsonFile, true);

        $dateLastUpdated = Carbon::createFromTimestamp(Storage::disk("rating")->lastModified($jsonPath));

        return view(
            "ranking.index",
            [
                "data" => $jsonData,
                "gameMode" => $gameMode,
                "gameModes" => $gameModes,
                "gameModesShort" => $gameModesShort,
                "links" => $links,
                "index" => $index,
                "dateLastUpdated" => $dateLastUpdated
            ]
        );
    }

    public function getEloProfileByKnownUsernames($gameMode, $usernames)
    {
        try
        {
            $gameModesShort = [
                GameHelper::$GAME_BLITZ,
                GameHelper::$GAME_RA2,
                GameHelper::$GAME_YR
            ];

            if (!in_array($gameMode, $gameModesShort))
            {
                $gameMode = GameHelper::$GAME_BLITZ;
            }

            $mode = isset($gameMode) ? strval($gameMode) : "blitz";

            $jsonFiles = ["players_active.json", "players_inactive.json", "players_new.json", "players_all.json"];
            $jsonPath = $mode . "_" . $jsonFiles[0];
            $jsonFile = Storage::disk('rating')->get($jsonPath);
            $jsonData = json_decode($jsonFile, true);

            $eloProfile = null;
            foreach ($jsonData as $json)
            {
                foreach ($usernames as $username)
                {
                    if (strtolower($username) == strtolower($json["name"]))
                    {
                        $eloProfile = $json;
                        break;
                    }
                }
            }

            return $eloProfile;
        }
        catch (Exception $e)
        {
            return null;
        }
    }
}
