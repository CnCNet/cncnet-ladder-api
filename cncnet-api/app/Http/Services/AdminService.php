<?php

namespace App\Http\Services;

use App\QmLadderRules;

class AdminService
{
    // Name doesn't seem quite right, but for now 'twill do

    public function __construct()
    {

    }

    public function saveQMLadderRulesRequest($request)
    {
        $ladderRule = QmLadderRules::where("id", "=", $request->id)->first();
        if ($ladderRule == null)
        {
            $request->session()->flash('error', 'Error no ladder rules found');
            return redirect()->back();
        }

        $ladderRule->player_count = $request->player_count;
        $ladderRule->map_vetoes = $request->map_vetoes;
        $ladderRule->max_difference = $request->max_difference;
        $ladderRule->rating_per_second = $request->rating_per_second;
        $ladderRule->max_points_difference = $request->max_points_difference;
        $ladderRule->points_per_second = $request->points_per_second;

        $ladderRule->frame_send_rate = $request->frame_send_rate;
        $ladderRule->bail_time = $request->bail_time;
        $ladderRule->bail_fps = $request->bail_fps;
        $ladderRule->tier2_rating = $request->tier2_rating;
        $ladderRule->all_sides = $request->all_sides;
        $ladderRule->allowed_sides = implode(",", $request->allowed_sides);
        $ladderRule->save();

        $request->session()->flash('success', 'Changes Saved');
        return redirect()->back();
    }
}
