<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UpdatePlayerCache extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'update_player_cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Updates the player cache table';

    private $ladderService;
    private $playerService;

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */

	public function handle()
	{
        $this->ladderService = new \App\Http\Services\LadderService();
        $this->playerService = new \App\Http\Services\PlayerService();

        $date = Carbon::now()->format('m-Y');
        $ladders = \App\Ladder::all();
        foreach ($ladders as $ladder)
        {
            $history = $this->ladderService->getActiveLadderByDate($date, $ladder->abbreviation);
            $players = $this->ladderService->getLadderPlayers($date, $ladder->abbreviation, null, false);

            foreach ($players as $player)
            {
                $pc = \App\PlayerCache::where("ladder_history_id", '=', $history->id)
                                       ->where('player_id', '=', $player->id)->first();

                if ($pc === null)
                    $pc = new \App\PlayerCache;

                $lp = $this->ladderService->getLadderPlayer($history, $player->username);

                $pc->ladder_history_id = $history->id;
                $pc->player_id = $player->id;
                $pc->player_name = $player->username;
                $pc->card = $player->card;
                $pc->points = $player->points;
                $pc->wins = $player->total_wins;
                $pc->games = $player->total_games;
                $pc->percentile = $player->percentile();
                $pc->side = null;
                $pc->fps = $lp["average_fps"]; // TODO
                $pc->save();
            }
        }
    }
}
