<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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

        // View::composer('post.list', function ($view)
        // {

        //     // following code will create $posts variable which we can use
        //     // in our post.list view you can also create more variables if needed
        //     $view->with('posts', Post::orderByDesc('created_at')->paginate());
        // });

        View::composer('components.navigation', function ($view)
        {
            $playerService = new PlayerService();
            $ladderService = new LadderService();
            $user = $view->app->request->user();
            $ladders = $ladderService->getLatestLadders();
            $clan_ladders = $ladderService->getLatestClanLadders();
            $private_ladders = collect();

            if ($user !== null)
            {
                $private_ladders = $ladderService->getPrivateLadders($user);
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
