<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;

class Navigation extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        view()->composer('components.navigation', function ($view)
        {
            $playerService = new PlayerService();
            $ladderService = new LadderService();

            $user = $view->app->request->user();
            $ladders = $ladderService->getLatestLadders();
            $clan_ladders = $ladderService->getLatestClanLadders();
            $private_ladders = collect();

            if ($user !== null)
            {
                $private_ladders = $ladderService->getLatestPrivateLadders($user);
            }

            $view->with(compact('user', 'ladders', 'clan_ladders', 'private_ladders'));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
