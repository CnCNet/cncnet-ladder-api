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

        // Just remove the game_report_id linkage rather than actually delete anything
        $game->game_report_id = null;
        $game->save;

        return redirect()->back();
    }

    public function switchGameReport(Request $request)
    {
        $game = \App\Game::find($request->game_id);
        if ($game === null) return "Game not found";

        $gameReport = $game->allReports()->find($request->game_report_id);
        if ($gameReport === null) return "Game Report not found";

        $currentReport = $game->report()->first();
        if ($currentReport !== null)
        {
            $currentReport->best_report = false;
            $currentReport->save();
        }

        $game->game_report_id = $request->game_report_id;
        $game->save();

        $gameReport->best_report = true;
        $gameReport->save();

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
        $qmMap->admin_description = $request->admin_description;
        $qmMap->bit_idx = $request->bit_idx;
        $qmMap->valid = $request->valid;
        $qmMap->allowed_sides = implode(",", $request->allowed_sides);
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
        $qmMap->ore_regenerates = ini_to_b($request->ore_regenerates);
        $qmMap->aftermath = ini_to_b($request->aftermath);
        $qmMap->fix_ai_ally = ini_to_b($request->fix_ai_ally);
        $qmMap->ally_reveal = ini_to_b($request->ally_reveal);
        $qmMap->am_fast_build = ini_to_b($request->am_fast_build);
        $qmMap->parabombs = ini_to_b($request->parabombs);
        $qmMap->fix_formation_speed = ini_to_b($request->fix_formation_speed);
        $qmMap->fix_magic_build = ini_to_b($request->fix_magic_build);
        $qmMap->fix_range_exploit = ini_to_b($request->fix_range_exploit);
        $qmMap->super_tesla_fix = ini_to_b($request->super_tesla_fix);
        $qmMap->forced_alliances = ini_to_b($request->forced_alliances);
        $qmMap->tech_center_fix = ini_to_b($request->tech_center_fix);
        $qmMap->no_screen_shake = ini_to_b($request->no_screen_shake);
        $qmMap->no_tesla_delay = ini_to_b($request->no_tesla_delay);
        $qmMap->dead_player_radar = ini_to_b($request->dead_player_radar);
        $qmMap->capture_flag = ini_to_b($request->capture_flag);
        $qmMap->slow_unit_build = ini_to_b($request->slow_unit_build);
        $qmMap->shroud_regrows = ini_to_b($request->shroud_regrows);
        $qmMap->ai_player_count = $request->ai_player_count;
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
