<?php

namespace App\Http\Services;

use App\QmLadderRules;

class AdminService
{
    // Name doesn't seem quite right, but for now 'twill do
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function saveQMLadderRulesRequest($request, $ladderId)
    {
        $ladderRule = QmLadderRules::where("id", "=", $request->id)->first();

        if ($request->id == "new")
        {
            $ladderRule = QmLadderRules::newDefault($ladderId);
            $ladderRule->save();
            $request->session()->flash('success', 'Default Quick Match rules added');
            return redirect()->back();
        }
        else if ($ladderRule == null)
        {
            $request->session()->flash('error', 'Error no ladder rules found');
            return redirect()->back();
        }

        if ($request->has('submit') && $request->submit == "delete")
        {
            $ladderRule->delete();
            $request->session()->flash('success', 'Quick Match Rules have been deleted');
            return redirect()->back();
        }

        $ladderRule->player_count = $request->player_count;
        $ladderRule->map_vetoes = $request->map_vetoes;
        $ladderRule->max_difference = $request->max_difference;
        $ladderRule->rating_per_second = $request->rating_per_second;
        $ladderRule->max_points_difference = $request->max_points_difference;
        $ladderRule->points_per_second = $request->points_per_second;

        $ladderRule->show_map_preview = $request->show_map_preview;
        $ladderRule->use_elo_points = $request->use_elo_points;
        $ladderRule->wol_k = $request->wol_k;
        $ladderRule->bail_time = $request->bail_time;
        $ladderRule->bail_fps = $request->bail_fps;
        $ladderRule->tier2_rating = $request->tier2_rating;
        $ladderRule->all_sides = $request->all_sides;
        $ladderRule->allowed_sides = implode(",", $request->allowed_sides);
        $ladderRule->reduce_map_repeats = $request->reduce_map_repeats;
        $ladderRule->point_filter_rank_threshold = $request->point_filter_rank_threshold;
        $ladderRule->ladder_rules_message = $request->ladder_rules_message;
        $ladderRule->ladder_discord = $request->ladder_discord;
        $ladderRule->save();

        if ($request->max_clans_allowed)
        {
            $clanLadderRules = $ladderRule->ladder->clanLadderRules;
            $clanLadderRules->max_clans_allowed = $request->max_clans_allowed;
            $clanLadderRules->save();
        }

        $request->session()->flash('success', 'Changes Saved');
        return redirect()->back();
    }

    public function doWashGame($gameId, $userName)
    {
        $game = \App\Game::find($gameId);
        if ($game === null) return "Game not found";

        $gameReport = $game->report()->first();
        if ($gameReport === null) return "Game Report not found";

        $gameReport->best_report = false;

        $wash = new \App\GameReport();
        $wash->game_id = $gameReport->game_id;
        $wash->player_id = $gameReport->player_id;
        $wash->best_report = true;
        $wash->manual_report = true;
        $wash->duration = $gameReport->duration;
        $wash->valid = true;
        $wash->finished = false;
        $wash->fps = $gameReport->fps;
        $wash->oos = false;
        $wash->save();

        $game->game_report_id = $wash->id;
        $game->save();
        $gameReport->save();
        $this->ladderService->undoPlayerCache($gameReport);

        //log the user who washed the game
        $gameAudit = new \App\GameAudit;
        $gameAudit->game_id = $game->id;
        $gameAudit->username = $userName;
        $gameAudit->ladder_history_id = $game->ladderHistory->id;
        $gameAudit->save();
    }
}
