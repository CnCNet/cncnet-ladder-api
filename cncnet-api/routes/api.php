<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


# API V1 Endpoints
Route::group(['prefix' => 'v1'], function ()
{

    Route::group(['prefix' => 'auth', 'middleware' => 'auth.basic.once'], function ()
    {
        Route::get('token', [\App\Http\Controllers\ApiAuthController::class, 'getAuth']);
    });

    Route::group(['prefix' => 'auth'], function ()
    {
        Route::get('refresh', [\App\Http\Controllers\ApiAuthController::class, 'refresh']);
        Route::post('login', [\App\Http\Controllers\ApiAuthController::class, 'login']);
    });


    Route::get("news", [\App\Http\Controllers\ApiNewsController::class, "getNews"]);
    // Route::get('/test-reward-points', [\App\Http\Controllers\ApiLadderController::class, 'reprocessTeamPointsByGameId']);
    Route::post('/test', [\App\Http\Controllers\ApiLadderController::class, 'testStatsDump']);

    Route::group(['middleware' => 'jwt.auth'], function ()
    {

        Route::get('/user/info', [\App\Http\Controllers\ApiUserController::class, 'getUserInfo']);
        Route::get('/user/account', [\App\Http\Controllers\ApiUserController::class, 'getAccount']);
        Route::get('/user/ladders', [\App\Http\Controllers\ApiUserController::class, 'getPrivateLadders']);
        Route::get('/user/preferences', [\App\Http\Controllers\ApiUserController::class, 'getUserPreferences']);
        Route::post('/user/preferences', [\App\Http\Controllers\ApiUserController::class, 'updateUserPreferences']);

        // Result Endpoints
        Route::post('/result/{game}/{username}', [\App\Http\Controllers\ApiLadderController::class, 'postLadder']);
        Route::post('/result/{game}/{username}/{pingSent}/{pingReceived}', [\App\Http\Controllers\ApiLadderController::class, 'postLadder']);
        Route::post('/result/ladder/{ladderId}/game/{gameId}/player/{playerId}/pings/{pingsSent}/{pingsReceived}', [\App\Http\Controllers\ApiLadderController::class, 'newPostLadder']);
        Route::post('/result/video-clip', [\App\Http\Controllers\ApiLadderController::class, 'saveVideoClip']);

        // General Endpoints
        Route::get('/ping', [\App\Http\Controllers\ApiLadderController::class, 'pingLadder']);

        // Player Endpoints
        Route::post('/player/usernames', [\App\Http\Controllers\ApiPlayerController::class, 'getUsernames']);
        Route::post('/player/create', [\App\Http\Controllers\ApiPlayerController::class, 'createPlayer']);
        Route::post('/player/status', [\App\Http\Controllers\ApiPlayerController::class, 'togglePlayerStatus']);

        // Debug
        Route::get('/ladder/raw/{gameId}', [\App\Http\Controllers\ApiLadderController::class, 'viewRawGame']);

        // QuickMatch Endpoints
        Route::get('/qm/version/{platform}', [\App\Http\Controllers\ApiQuickMatchController::class, 'clientVersion']);
        Route::get('/qm/ladder/{ladderAbbrev}/maps', [\App\Http\Controllers\ApiQuickMatchController::class, 'mapListRequest']);

        // commented out to try the refactored version
        //Route::post('/qm/{ladderAbbrev}/{playerName}', [\App\Http\Controllers\ApiQuickMatchController::class, 'matchRequest']);
    });

    Route::group(['middleware' => 'cache.short.public'], function ()
    {

        Route::get('/qm/ladder/rankings', [\App\Http\Controllers\ApiQuickMatchController::class, 'getPlayerRankings']);
        Route::get('/qm/ladder/{ladderAbbrev}/rankings', [\App\Http\Controllers\ApiQuickMatchController::class, 'getPlayerRankingsByLadder']);
        Route::get('/qm/ladder/{ladderAbbrev}/maps/public', [\App\Http\Controllers\ApiQuickMatchController::class, 'mapListRequest']);
        Route::get('/qm/ladder/{ladderAbbrev}/stats', [\App\Http\Controllers\ApiQuickMatchController::class, 'statsRequest']);
        Route::get('/qm/ladder/{ladderAbbrev}/stats/{tierId}', [\App\Http\Controllers\ApiQuickMatchController::class, 'statsRequest']);
        Route::get('/qm/ladder/{ladderAbbrev}/current_matches', [\App\Http\Controllers\ApiQuickMatchController::class, 'getActiveMatches']);
        Route::get('/qm/ladder/{ladderAbbrev}/erroredGames', [\App\Http\Controllers\ApiQuickMatchController::class, 'getErroredGames']);
        Route::get('/qm/ladder/{ladderAbbrev}/{hours}/recentlyWashedGames', [\App\Http\Controllers\ApiQuickMatchController::class, 'getRecentLadderWashedGamesCount']);
    });

    // Ladder Endpoints
    Route::group([
        'prefix' => 'ladder',
        'middleware' => 'cache.long.public'
    ], function ()
    {
        Route::get('/', [\App\Http\Controllers\ApiLadderController::class, 'getAllLadders']);
        Route::get('/{game}/games/recent', [\App\Http\Controllers\ApiLadderController::class, 'getLadderRecentGames']);
        Route::get('/{game}/games/recent/{count}', [\App\Http\Controllers\ApiLadderController::class, 'getLadderRecentGamesList']);
        Route::get('/{game}', [\App\Http\Controllers\ApiLadderController::class, 'getLadder']);
        Route::get('/{game}/game/{gameId}', [\App\Http\Controllers\ApiLadderController::class, 'getLadderGame']);
        Route::get('/{game}/winners/', [\App\Http\Controllers\ApiLadderController::class, 'getLadderWinners']);
        Route::get('/{game}/games/recent/{count}', [\App\Http\Controllers\ApiLadderController::class, 'getLadderRecentGamesList']);

        Route::get('/{abbreviation}/players', [\App\Http\Controllers\ApiIrcController::class, 'getPlayerNames']);
        Route::get('/{abbreviation}/active', [\App\Http\Controllers\ApiIrcController::class, 'getActivePlayers']);
    });

    // Short cache ladder endpoints
    Route::group(['prefix' => 'ladder', 'middleware' => 'cache.public'], function ()
    {

        Route::get('/{game}/top/{count}', [\App\Http\Controllers\ApiLadderController::class, 'getLadderTopList']);
        Route::get('/{game}/player/{player}', [\App\Http\Controllers\ApiLadderController::class, 'getLadderPlayerFromPublicApi']);
        Route::get('/{game}/player/{player}/webview', [\App\Http\Controllers\ApiLadderStatsProfile::class, 'getWebview']);
    });

    // Ultra short cache ladder endpoints
    Route::group(['prefix' => 'irc', 'middleware' => 'cache.ultra.public'], function ()
    {

        Route::get('/{abbreviation}/active', [\App\Http\Controllers\ApiIrcController::class, 'getActive']);
        Route::get('/{abbreviation}/players', [\App\Http\Controllers\ApiIrcController::class, 'getPlayerNames']);

        Route::get('/{abbreviation}/clans', [\App\Http\Controllers\ApiIrcController::class, 'getClans'])->middleware('cache.public');

        Route::get('/hostmasks', [\App\Http\Controllers\ApiIrcController::class, 'getHostmasks']);
    });
});


# API V2 Endpoints
Route::group(['prefix' => 'v2'], function ()
{

    Route::group(['middleware' => 'jwt.auth'], function ()
    {
        Route::get('/user/account', [\App\Http\Controllers\Api\V2\User\ApiUserController::class, 'getAccount']);
    });
});


Route::group(['prefix' => 'v1'], function ()
{
    Route::group(['middleware' => 'jwt.auth'], function ()
    {
        Route::post('/qm/{ladder:abbreviation}/{playerName}', \App\Http\Controllers\Api\V2\Qm\MatchUpController::class)
            ->middleware([
                \App\Http\Middleware\Api\ClientUpToDateMiddleware::class,
                \App\Http\Middleware\Api\ShadowBanMiddleware::class,
                \App\Http\Middleware\Api\BanMiddleware::class,
                \App\Http\Middleware\Api\VerifiedEmailMiddleware::class,
            ]);
    });
});
