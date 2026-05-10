<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\PlayerService;
use App\Http\Services\LadderService;

class Navigation extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Cache navigation data per request to avoid duplicate queries when rendering multiple nav views
        $navigationData = null;

        view()->composer(['components.navigation.navbar', 'components.navigation.fullscreen-menu'], function ($view) use (&$navigationData)
        {
            // Compute navigation data only once per request
            if ($navigationData === null)
            {
                $playerService = new PlayerService();
                $ladderService = new LadderService();

                $user = $view->app->request->user();
                $ladders = $ladderService->getLatestLadders();
                $clan_ladders = $ladderService->getLatestClanLadders();
                $private_ladders = [];

                if ($user !== null)
                {
                    $private_ladders = $ladderService->getLatestPrivateLadderHistory($user);
                }

                $navigationData = compact('user', 'ladders', 'clan_ladders', 'private_ladders');
            }

            $view->with($navigationData);
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
