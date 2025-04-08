<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PlayerGameReport;
use App\Models\Game;
use App\Models\Ladder;
use Illuminate\Support\Facades\Log;

class UpdatePlayerGameReportsSeeder extends Seeder
{

    /**
     * Populate team in player_game_reports. It is missing or incorrect on a lot of reports.
     */
    public function run(): void
    {
        $ladder = Ladder::where('abbreviation', 'blitz-2v2')->first();

        $batchSize = 500;

        Game
            ::whereHas('ladderHistory', function ($query) use ($ladder)
            {
                $query->where('ladder_id', $ladder->id);
            })
            ->where('created_at', '>', now()->subMonths(6))
            ->whereHas('report', function ($query)
            {
                $query->where('manual_report', false)->where('oos', false)->where('duration', '>', 60);
            })
            ->chunk($batchSize, function ($games)
            {
                // $this->printMsg("Processing batch of " . count($games) . " games");

                foreach ($games as $game)
                {
                    $gameId = $game->id;
                    $short = $game->ladderHistory->short;

                    // bugged game, no pts given
                    $allZeroPoints = !$game->playerGameReports()
                        ->where('points', '!=', 0)
                        ->exists();
                    if ($allZeroPoints)
                    {
                        $msg = "Skipping all players 0 pts, game_id: " . $gameId . ', short: ' . $short;
                        // $this->printMsg($msg);
                        continue;
                    }

                    if ($game->report->playerGameReports()->count() != 4)
                    {
                        $msg = "Missing reports game_id: " . $gameId . ', short: ' . $short;
                        $this->printMsg($msg);
                        continue;
                    }

                    $winners = 0;
                    $losers = 0;

                    $maxPointsReport = $game->playerGameReports()->orderByDesc('points')->first();
                    $maxPoints = $maxPointsReport->points;

                    foreach ($game->playerGameReports as $playerGameReport)
                    {
                        $p1 = $playerGameReport->player->username;

                        $won = $playerGameReport->won || ($playerGameReport->points >= $maxPoints - 2 && $playerGameReport->points <= $maxPoints + 2);

                        // Find the teammate with similar points
                        $teammate = $game->playerGameReports()
                            ->where('player_game_reports.player_id', '!=', $playerGameReport->player_id)
                            ->where(function ($query) use ($playerGameReport)
                            {
                                $query->whereBetween('points', [$playerGameReport->points - 2, $playerGameReport->points + 2])
                                    ->orWhereBetween('points', [$playerGameReport->backupPts - 2, $playerGameReport->backupPts + 2])
                                    ->orWhereBetween('backupPts', [$playerGameReport->points - 2, $playerGameReport->points + 2]);
                            })
                            ->first();

                        if ($teammate == null)
                        {
                            $this->printMsg("--------------------- No teammate found for " . $p1 . ', report_id: ' . $playerGameReport->id . ', game_id: ' . $gameId . ', short: ' . $short . '----------------------------');
                            continue;
                        }

                        $p2 = $teammate->player->username;

                        if ($won)
                        {
                            $winners++;
                            $this->printMsg("A: playerGameReport (" . $p1 . ", " . $p2 . ") won, pts: " . $playerGameReport->points . ', report_id: ' . $playerGameReport->id . ', game_id: ' . $gameId . ', short: ' . $short);
                            $playerGameReport->update(['team' => 'A']);
                            $teammate->update(['team' => 'A']);
                        }
                        else
                        {
                            $losers++;
                            $this->printMsg("B: playerGameReport (" . $p1 . ", " . $p2 . ") lost, pts: " . $playerGameReport->points . ', report_id: ' . $playerGameReport->id . ', game_id: ' . $gameId . ', short: ' . $short);
                            $playerGameReport->update(['team' => 'B']);
                            $teammate->update(['team' => 'B']);
                        }
                    }

                    if ($winners != 2 || $losers != 2)
                    {
                        $msg = "======================================== winners: " . $winners . ", losers: " . $losers . ", gameId: " . $gameId . ", short: " . $short . " ====================================================";
                        $this->printMsg($msg);
                    }

                    $this->printMsg("");
                }
            });
    }

    public function printMsg($msg)
    {
        echo $msg . "\n";
        Log::info($msg);
    }
}
