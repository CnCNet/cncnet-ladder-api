<?php

namespace App\Console\Commands;

use App\Http\Services\UserRatingService;
use App\LadderHistory;
use App\StatsCache;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdatePlayerRatings extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'update_player_ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the player ratings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Current ladder month only
        $now = Carbon::now();
        $start = $now->startOfMonth()->toDateTimeString();
        $end = $now->endOfMonth()->toDateTimeString();
        $ladderHistories = \App\LadderHistory::whereBetween("starts", [$start, $start])
            ->whereBetween("ends", [$end, $end])
            ->get();

        $userRatingService = new UserRatingService();

        foreach ($ladderHistories as $history)
        {
            $userRatingService->recalculatePlayersTiersByLadderHistory($history);
        }
    }
}
