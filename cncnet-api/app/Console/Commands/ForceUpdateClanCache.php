<?php

namespace App\Console\Commands;

use App\ClanCache;
use App\ClanCacheUpdate;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ForceUpdateClanCache extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'force_update_clan_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force updates all the clan_caches table';

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
        $forceUpdate = ClanCache::all();
        foreach ($forceUpdate as $fu)
        {
            $this->update($fu);
        }
    }

    private function update($clanCache)
    {
        $clan = $clanCache->clan;
        $history = $clanCache->history;

        $clanCache->ladder_history_id = $history->id;
        $clanCache->clan_id = $clan->id;
        $clanCache->clan_name = $clan->short;
        $clanCache->points = $clan->points($history);
        $clanCache->wins = $clan->wins($history);
        $clanCache->games = $clan->totalGames($history);

        $v = $clan->sideUsage($history)->first();
        $clanCache->side = $v !== null ? $v->sid : null;

        $v = $clan->countryUsage($history)->first();
        $clanCache->country = $v !== null ? $v->cty : null;
        $clanCache->fps = $clan->averageFPS($history);
        $clanCache->save();
    }
}