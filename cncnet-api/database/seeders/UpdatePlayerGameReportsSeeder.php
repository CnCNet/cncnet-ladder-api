<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PlayerGameReport;

class UpdatePlayerGameReportsSeeder extends Seeder
{

    public function run(): void
    {
        // Fetch all player_game_reports where the related ladder_id = 15
        $playerGameReports = PlayerGameReport::whereHas('gameReport.game.qmMatch', function ($query)
        {
            $query->where('ladder_id', 15);
        })->whereNull('team')->get();

        foreach ($playerGameReports as $playerGameReport)
        {
            $qmMatch = $playerGameReport->gameReport->game->qmMatch;
            if ($qmMatch)
            {
                $team = $qmMatch->findQmPlayerByPlayerId($playerGameReport->player_id)?->team;
                if ($team !== null)
                {
                    $playerGameReport->update(['team' => $team]);
                }
            }
        }
    }
}
