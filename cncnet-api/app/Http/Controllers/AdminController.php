<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\AdminService;
use \Carbon\Carbon;

class AdminController extends Controller
{
    private $ladderService;
    private $adminService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->adminService = new AdminService();
    }

    public function getAdminIndex(Request $request)
    {
        return view("admin.index", ["ladders" => $this->ladderService->getLatestLadders()]);
    }

    public function getLadderSetupIndex(Request $request)
    {
        $ladderRules = \App\QmLadderRules::all();
        $qmMaps = \App\QmMap::orderby('bit_idx', 'ASC')->get();
        $ladderMaps = \App\Map::orderby('name', 'ASC')->get();

        return view("admin.ladder-setup", ["ladders" => $this->ladderService->getLatestLadders(),
                                           "rules" => $ladderRules,
                                           "qmMaps" => $qmMaps,
                                           "maps" => $ladderMaps]);
    }

    public function postLadderSetupRules(Request $request)
    {
        return $this->adminService->saveQMLadderRulesRequest($request);
    }

    public function getManageUsersIndex(Request $request)
    {
        return view("admin.manage-users", ["ladders" => $this->ladderService->getLatestLadders()]);
    }

    public function getManageGameIndex(Request $request, $cncnetGame = null)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder == null)
            return "No ladder";

        $date = Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $history = \App\LadderHistory::where("starts", "=", $start)
            ->where("ends", "=", $end)
            ->where("ladder_id", "=", $ladder->id)
            ->first();

        $games = \App\Game::where("ladder_history_id", "=", $history->id)->orderBy("id", "DESC");
        return view("admin.manage-games", ["games" => $games, "ladder" => $ladder]);
    }

    public function deleteGame(Request $request)
    {
        $game = \App\Game::find($request->game_id);
        if ($game == null) return "Game not found";

        $playerGames = \App\PlayerGame::where("game_id", "=", $game->id)->get();
        foreach($playerGames as $pg)
        {
            $pg->delete();
        }

        $playerPoints = \App\PlayerPoint::where("game_id", "=", $game->id)->get();
        foreach($playerPoints as $pp)
        {
            $pp->delete();
        }

        $ladderGames = \App\LadderGame::where("game_id", "=", $game->id)->get();
        foreach($ladderGames as $lg)
        {
            $lg->delete();
        }

        $game->delete();

        return redirect()->back();
    }

    public function postQuickMatchMap(Request $request)
    {
        if ($request->id == "new")
        {
            $qmMap = new \App\QmMap;
            $message = "Successfully created new map";
        }
        else {
            $qmMap = \App\QmMap::where('id', $request->id)->first();
            $message = "Sucessfully updated map";
        }

        if ($qmMap == null)
        {
            $request->session()->flash('error', 'Unable to update Map');
            return redirect()->back();
        }

        $qmMap->ladder_id = $request->ladder_id;
        $qmMap->map_id = $request->map_id;
        $qmMap->description = $request->description;
        $qmMap->bit_idx = $request->bit_idx;
        $qmMap->valid = $request->valid;
        $qmMap->game_mode = $request->game_mode;
        $qmMap->spawn_order = $request->spawn_order;
        $qmMap->speed = $request->speed;
        $qmMap->credits = $request->credits;
        $qmMap->units = $request->units;
        $qmMap->tech = $request->tech;
        $qmMap->bases = ini_to_b($request->bases);
        $qmMap->short_game = ini_to_b($request->short_game);
        $qmMap->fog = ini_to_b($request->fog);
        $qmMap->redeploy = ini_to_b($request->redeploy);
        $qmMap->crates = ini_to_b($request->crates);
        $qmMap->multi_eng = ini_to_b($request->multi_eng);
        $qmMap->multi_factory = ini_to_b($request->multi_factory);
        $qmMap->allies = ini_to_b($request->allies);
        $qmMap->dog_kill = ini_to_b($request->dog_kill);
        $qmMap->bridges = ini_to_b($request->bridges);
        $qmMap->supers = ini_to_b($request->supers);
        $qmMap->build_ally = ini_to_b($request->build_ally);
        $qmMap->spawn_preview = ini_to_b($request->spawn_preview);
        $qmMap->firestorm = ini_to_b($request->firestorm);
        $qmMap->ra2_mode = ini_to_b($request->ra2_mode);
        $qmMap->aimable_sams = ini_to_b($request->aimable_sams);
        $qmMap->attack_neutral = ini_to_b($request->attack_neutral);
        $qmMap->harv_truce = ini_to_b($request->harv_truce);

        $qmMap->save();

        $request->session()->flash('success', $message);
        return redirect()->back();
    }

    public function removeQuickMatchMap(Request $request)
    {
        $qmMap = \App\QmMap::where('id', '=', $request->map_id)->first();

        if ($qmMap !== null)
        {
            \App\QmMap::where('ladder_id', '=', $request->ladder_id)
                      ->where('bit_idx', '>', $qmMap->bit_idx)
                      ->decrement('bit_idx');
            $qmMap->delete();
        }

        $request->session()->flash('success', "Map Deleted");
        return redirect()->back();
    }
}

function ini_to_b($string)
{
    if ($string == "Null") return null;
    return $string == "Yes" ? true : false;
}
