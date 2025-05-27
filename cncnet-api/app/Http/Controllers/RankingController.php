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
        $gameModes = ["Blitz", "Blitz 2v2", "Red Alert 2", "Yuri's Revenge"];

        $players = ["Active", "New", "All time best", "All players" ];
        $upsets = ["All time", "Last 12 month", "Last 30 days"];
        $stats = [];

        $gameModesShort = [
            GameHelper::$GAME_BLITZ,
            GameHelper::$GAME_BLITZ . "-2v2",
            GameHelper::$GAME_RA2,
            GameHelper::$GAME_YR
        ];

        $gameMode = isset($request->mode) ? strval($request->mode) : "blitz";

        // $players first, upsets next, stats last.
        $jsonFiles = ["active_players.json", "new_players.json", "bestofalltime.json", "players_all_alphabetical.json", "upsets_alltime.json", "upsets_last12month.json", "upsets_last30days.json"];

        if (Storage::disk('rating')->exists($gameMode . "_mapstats_avs.json"))
        {
            array_push($stats, "Maps AvS");
            array_push($jsonFiles, "mapstats_avs.json");
        }

        if (Storage::disk('rating')->exists($gameMode . "_mapstats_avy.json"))
        {
            array_push($stats, "Maps AvY");
            array_push($jsonFiles, "mapstats_avy.json");
        }

        if (Storage::disk('rating')->exists($gameMode . "_mapstats_yvs.json"))
        {
            array_push($stats, "Maps YvS");
            array_push($jsonFiles, "mapstats_yvs.json");
        }

        $index = isset($request->list) ? max(min(intval($request->list), sizeof($players) + sizeof($upsets) + sizeof($stats) - 1), 0) : 0;


        if (!in_array($gameMode, $gameModesShort))
        {
            $gameMode = GameHelper::$GAME_BLITZ;
        }

        $jsonPath = $gameMode . "_" . $jsonFiles[$index];
        $jsonFile = Storage::disk('rating')->get($jsonPath);
        $jsonData = json_decode($jsonFile, true);

        $dateLastUpdated = Carbon::createFromTimestamp(Storage::disk("rating")->lastModified($jsonPath));

        $mixedFactionImage = ($gameMode == GameHelper::$GAME_YR) ? "resources/images/games/yr/allfactions.png" : "resources/images/games/ra2/ra2-icon.png";

        # Make sure to have a fallback for specialized game modes (e.g, blitz-2v2 or ra2-new-maps).
        # Prefer custom logo, but use standard logo if it does not exists.
        $primaryLogoPath = "resources/images/games/{$gameMode}/logo.png";
        $fallbackGameMode = explode('-', $gameMode)[0];
        $logoToUse = "resources/images/games/{$fallbackGameMode}/logo.png";

        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest[$primaryLogoPath])) {
                $logoToUse = $primaryLogoPath;
            }
        }

        return view(
            "ranking.index",
            [
                "data" => $jsonData["data"],
                "gameMode" => $gameMode,
                "gameModes" => $gameModes,
                "gameModesShort" => $gameModesShort,
                "players" => $players,
                "upsets" => $upsets,
                "stats" => $stats,
                "index" => $index,
                "columns" => $jsonData["columns"],
                "description" => $jsonData["description"],
                "factionImages" => array("all" => "resources/images/game-icons/allied.png",
                                         "sov" => "resources/images/game-icons/ra2.png",
                                         "mix" => $mixedFactionImage,
                                         "yur" => "resources/images/games/yr/yr-icon.png"),
                "dateLastUpdated" => $dateLastUpdated,
                "logoToUse" => $logoToUse
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
