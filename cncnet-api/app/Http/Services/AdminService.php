<?php

namespace App\Http\Services;

use App\Models\LadderHistory;
use Illuminate\Support\Facades\DB;
use App\Models\QmLadderRules;

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
        $ladderRule->match_ai_after_seconds = $request->match_ai_after_seconds;
        $ladderRule->max_active_players = $request->max_active_players;
        $ladderRule->use_ranked_map_picker = $request->use_ranked_map_picker == "on" ? true : false;
        $ladderRule->use_elo_map_picker = $request->use_elo_map_picker == "on" ? true : false;
        $ladderRule->save();

        $request->session()->flash('success', 'Changes Saved');
        return redirect()->back();
    }

    public function doWashGame($gameId, $userName)
    {
        $game = \App\Models\Game::find($gameId);
        if ($game === null) return "Game not found";

        $gameReport = $game->report()->first();
        if ($gameReport === null) return "Game Report not found";

        $gameReport->best_report = false;

        $wash = new \App\Models\GameReport();
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
        $this->ladderService->undoCache($gameReport);

        //log the user who washed the game
        $gameAudit = new \App\Models\GameAudit;
        $gameAudit->game_id = $game->id;
        $gameAudit->username = $userName;
        $gameAudit->ladder_history_id = $game->ladderHistory->id;
        $gameAudit->save();
    }

    /**
     * Reprocess points for a specific game
     * Creates a new manual game report to preserve original data and maintain audit trail
     *
     * @param int $gameId - The game ID to reprocess
     * @param string $userName - Admin username performing the action
     * @param string|null $winningTeam - Optional team override (e.g., "A", "B"). If provided, manually sets won flags for that team.
     */
    public function reprocessGamePoints($gameId, $userName, $winningTeam = null)
    {
        \Log::info("=== REPROCESS GAME POINTS START ===", [
            'game_id' => $gameId,
            'admin' => $userName,
            'winning_team_override' => $winningTeam
        ]);

        $game = \App\Models\Game::find($gameId);
        if ($game === null) return "Game not found";

        $currentGameReport = $game->report()->first();
        if ($currentGameReport === null) return "Game Report not found";

        $history = $game->ladderHistory;
        if ($history === null) return "Ladder History not found";

        // Undo existing points from cache using current report
        $this->ladderService->undoCache($currentGameReport);

        // Mark current report as no longer the best
        $currentGameReport->best_report = false;
        $currentGameReport->save();

        // Create new manual game report (preserves original data)
        $newGameReport = new \App\Models\GameReport();
        $newGameReport->game_id = $currentGameReport->game_id;
        $newGameReport->player_id = $currentGameReport->player_id;
        $newGameReport->best_report = true;
        $newGameReport->manual_report = true; // Mark as admin-generated
        $newGameReport->duration = $currentGameReport->duration;
        $newGameReport->valid = $currentGameReport->valid;
        $newGameReport->finished = $currentGameReport->finished;
        $newGameReport->fps = $currentGameReport->fps;
        $newGameReport->oos = $currentGameReport->oos;
        $newGameReport->pings_sent = $currentGameReport->pings_sent;
        $newGameReport->pings_received = $currentGameReport->pings_received;
        $newGameReport->clan_id = $currentGameReport->clan_id;
        $newGameReport->save();

        // Detect if this is a 1v1 player override (format: "player_123") or team override (format: "A", "B", etc)
        $is1v1PlayerOverride = false;
        $winningPlayerId = null;
        if ($winningTeam !== null && $winningTeam !== '' && str_starts_with($winningTeam, 'player_'))
        {
            $is1v1PlayerOverride = true;
            $winningPlayerId = (int) str_replace('player_', '', $winningTeam);
        }

        // Clone all player game reports from current report
        $currentPlayerGameReports = $currentGameReport->playerGameReports()->get();
        foreach ($currentPlayerGameReports as $oldPgr)
        {
            $newPgr = new \App\Models\PlayerGameReport();
            $newPgr->game_report_id = $newGameReport->id;
            $newPgr->game_id = $oldPgr->game_id;
            $newPgr->player_id = $oldPgr->player_id;
            $newPgr->local_id = $oldPgr->local_id;
            $newPgr->local_team_id = $oldPgr->local_team_id;
            $newPgr->points = 0; // Will be recalculated
            $newPgr->stats_id = $oldPgr->stats_id;
            $newPgr->team = $oldPgr->team;
            $newPgr->spawn = $oldPgr->spawn;
            $newPgr->clan_id = $oldPgr->clan_id;
            $newPgr->spectator = $oldPgr->spectator;

            // If admin selected winner, apply override
            if (($winningTeam !== null && $winningTeam !== '' || $is1v1PlayerOverride) && !$oldPgr->spectator)
            {
                $isWinner = false;

                // For 1v1: match by player_id
                if ($is1v1PlayerOverride)
                {
                    $isWinner = ($oldPgr->player_id == $winningPlayerId);
                }
                // For 2v2/clan: match by team
                else
                {
                    $isWinner = ($oldPgr->team === $winningTeam);
                }

                if ($isWinner)
                {
                    $newPgr->won = true;
                    $newPgr->defeated = false;
                    $newPgr->draw = false;
                    $newPgr->disconnected = false;
                    $newPgr->no_completion = false;
                    $newPgr->quit = false;
                }
                else
                {
                    $newPgr->won = false;
                    $newPgr->defeated = true;
                    $newPgr->draw = false;
                    $newPgr->disconnected = false;
                    $newPgr->no_completion = false;
                    $newPgr->quit = false;
                }
            }
            else
            {
                // Keep original outcome
                $newPgr->won = $oldPgr->won;
                $newPgr->defeated = $oldPgr->defeated;
                $newPgr->draw = $oldPgr->draw;
                $newPgr->disconnected = $oldPgr->disconnected;
                $newPgr->no_completion = $oldPgr->no_completion;
                $newPgr->quit = $oldPgr->quit;
            }

            $newPgr->save();
        }

        // Update game to point to new report
        $game->game_report_id = $newGameReport->id;
        $game->save();

        // Determine ladder type and call appropriate award method
        $apiLadderController = new \App\Http\Controllers\ApiLadderController();

        if ($history->ladder->ladder_type == \App\Models\Ladder::CLAN_MATCH)
        {
            $status = $apiLadderController->awardClanPoints($newGameReport, $history);
        }
        else if ($history->ladder->ladder_type == \App\Models\Ladder::TWO_VS_TWO)
        {
            $status = $apiLadderController->awardTeamPoints($newGameReport, $history);
        }
        else // 1vs1
        {
            $status = $apiLadderController->awardPlayerPoints($newGameReport, $history);
        }

        // Update cache with new points
        $this->ladderService->updateCache($newGameReport);

        // Log the reprocess action
        $logMessage = 'Game points reprocessed';
        if ($is1v1PlayerOverride)
        {
            $winningPlayer = \App\Models\Player::find($winningPlayerId);
            $logMessage .= " with winner override: " . ($winningPlayer ? $winningPlayer->username : "Player #$winningPlayerId");
        }
        else if ($winningTeam)
        {
            $logMessage .= " with team override: Team $winningTeam";
        }

        activity()
            ->performedOn($game)
            ->causedBy(auth()->user())
            ->withProperties([
                'status' => $status,
                'admin' => $userName,
                'winning_team_override' => $winningTeam,
                'winning_player_id' => $winningPlayerId,
                'old_report_id' => $currentGameReport->id,
                'new_report_id' => $newGameReport->id
            ])
            ->log($logMessage);

        \Log::info("=== REPROCESS GAME POINTS END ===", [
            'status' => $status,
            'old_report_id' => $currentGameReport->id,
            'new_report_id' => $newGameReport->id
        ]);

        return $status;
    }

    /**
     * Identify games where only one player submitted a game_report.
     * Return the player who did not submit a gamereport, also return the game_id and map
     * TODO return who the opponent was too
     */
    public function fetchBailedGames(LadderHistory $ladderHistory)
    {
        // get games where one of the players did not submit a game report

        // TODO identify games where one player force closed the QM client prior to game launching, might be covered but need to confirm

        return DB::table('qm_match_players')
            ->where('qm_match_players.ladder_id', $ladderHistory->ladder->id)
            ->where('games.created_at', '>', $ladderHistory->starts)
            ->where('games.created_at', '<', $ladderHistory->ends)
            ->join('players', 'players.id', '=', 'qm_match_players.player_id')
            ->join('games', 'games.qm_match_id', '=', 'qm_match_players.qm_match_id')
            ->leftJoin('game_reports', function ($join)
            {
                $join->on('games.id', '=', 'game_reports.game_id')
                    ->on('game_reports.player_id', '=', 'qm_match_players.player_id');
            })
            ->whereNull('game_reports.id')
            ->whereNotNull('games.hash')
            ->select('players.id as player_id', 'players.username', 'game.scen', 'qm_match_players.id as qm_match_player_id', 'games.id as game_id', 'game_reports.id as game_report_id')
            ->orderBy('game_id');
    }
}
