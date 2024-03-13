<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateBulkRecords extends Command
{


    protected $name = 'generate_bulk';
    protected $description = "Generate Bulk Game stats for last month";
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        parent::__construct();
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '40960M');
        $month = Carbon::now()->subMonth(0)->format('m') + 0;
        $year = Carbon::now()->subMonth(0)->format('Y');

        echo "Querying for data from ".$month."-".$year;

        $histories = \App\Models\LadderHistory::where('short', '=', $month . "-" . $year)->get();

        $ladders = [];

        foreach ($histories as $history) {

            \App\Models\Game::where('ladder_history_id', '=', $history->id)->whereNotNull('game_report_id')->chunk(500, function ($games) use (&$history, &$ladders) {

                foreach ($games as $game) {
 
                    $game_out = [
                        "game_id" => $game->id,
                        "date" => $game->created_at,
                        "map" => [
                            "hash" => $game->hash,
                            "id" => $game->map ? $game->map->id : -1,
                        ],
                        "duration" => $game->report->duration,
                        "fps" => $game->report->fps,
                        "wol_game_id" => $game->wol_game_id,
                        "bamr" => $game->bamr,
                        "crat" => $game->crat,
                        "cred" => $game->cred,
                        "shrt" => $game->shrt,
                        "supr" => $game->supr,
                        "unit" => $game->unit,
                        "plrs" => $game->plrs,
                        "scen" => $game->scen,
                        "players" => []
                    ];

                    foreach ($game->report->playerGameReports as $playerGR) {
                        $player_out = [
                            "name" => $playerGR->player->username,
                            "local_id" => $playerGR->local_id,
                            "local_team_id" => $playerGR->local_team_id,
                            "points" => $playerGR->points,
                            "disconnected" => $playerGR->disconnected,
                            "no_completion" => $playerGR->no_completion,
                            "quit" => $playerGR->quit,
                            "won" => $playerGR->won,
                            "defeated" => $playerGR->defeated,
                            "draw" => $playerGR->draw,
                            "spectator" => $playerGR->spectator,
                        ];

                        $counts_out = [];
                        $stats_out = [];
                        if ($playerGR->stats !== null) {
                            $stats_out = [
                                "side" => $playerGR->stats->sid,
                                "country" => $playerGR->stats->cty,
                                "color" => $playerGR->stats->col,
                                "credits" => $playerGR->stats->crd,
                                "harvested" => $playerGR->stats->harv,
                            ];
                            foreach ($playerGR->stats->gameObjectCounts as $goc) {
                                $counts_out[$goc->countableGameObject->heap_name][] = [$goc->countableGameObject->name => $goc->count];
                            }
                        }
                        $player_out = array_merge($player_out, $stats_out);

                        $player_out["stats"] = $counts_out;

                        $game_out["players"][] = $player_out;
                    }

                    $ladders[$history->ladder->abbreviation][] = $game_out;
                }
            });
        }

        $fileName = $month."-".$year."-bulk.json";
        echo "\n\rStoring data to ".$fileName;
        file_put_contents($fileName, json_encode($ladders));
    }
}
