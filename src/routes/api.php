<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;

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


Route::group(['prefix' => 'v1/auth/', 'middleware' => 'auth.basic.once'], function ()
{
    Route::get('/token', [ApiAuthController::class, 'getAuth']);
});

Route::group(['prefix' => 'v1/auth/'], function ()
{
    Route::get('/refresh', [ApiAuthController::class, 'refresh']);
    Route::post('/login', [ApiAuthController::class, 'login']);
});

Route::group(['prefix' => 'v1/', 'middleware' => 'jwt.auth'], function ()
{
    Route::get('/user/account', [ApiUserController::class, 'getAccount']);
    Route::get('/user/ladders', [ApiUserController::class, 'getPrivateLadders']);
    Route::post('/user/create', [ApiUserController::class, 'createUser']);

    // Result Endpoints
    Route::post('/result/{game}/{username}', [ApiLadderController::class, 'postLadder']);
    Route::post('/result/{game}/{username}/{pingSent}/{pingReceived}', [ApiLadderController::class, 'postLadder']);
    Route::post('/result/ladder/{ladderId}/game/{gameId}/player/{playerId}/pings/{pingsSent}/{pingsReceived}', [ApiLadderController::class, 'newPostLadder']);

    // General Endpoints
    Route::get('/ping', [ApiLadderController::class, 'pingLadder']);

    // Player Endpoints
    Route::post('/player/{username}', [ApiPlayerController::class, 'createPlayer']);

    // Debug
    Route::get('/ladder/raw/{gameId}', [ApiLadderController::class, 'viewRawGame']);

    // QuickMatch Endpoints
    Route::get('/qm/version/{platform}', [ApiQuickMatchController::class, 'clientVersion']);
    Route::get('/qm/ladder/{ladderAbbrev}/maps', [ApiQuickMatchController::class, 'mapListRequest']);
    Route::post('/qm/{ladderAbbrev}/{playerName}', [ApiQuickMatchController::class, 'matchRequest']);
});

# Err, ey v2?
Route::group(['prefix' => 'v2/', 'middleware' => 'jwt.auth', 'namespace' => '\v2'], function ()
{
    Route::get('/user/account', [ApiUserController::class, 'getAccount']);
});

Route::group(['prefix' => 'v1/', 'middleware' => 'cache.short.public'], function ()
{
    Route::get('/qm/ladder/{ladderAbbrev}/stats', [ApiQuickMatchController::class, 'statsRequest']);
});

// Ladder Endpoints
Route::group(['prefix' => 'v1/ladder', 'middleware' => 'cache.long.public'], function ()
{
    Route::get('/', [ApiLadderController::class, 'getCurrentLadders']);
    Route::get('/{game}/games/recent/{count}', [ApiLadderController::class, 'getLadderRecentGamesList']);
    Route::get('/{game}', [ApiLadderController::class, 'getLadder']);
    Route::get('/{game}/game/{gameId}', [ApiLadderController::class, 'getLadderGame']);
    Route::get('/{game}/winners/', [ApiLadderController::class, 'getLadderWinners']);
    Route::get('/{game}/games/recent/{count}', [ApiLadderController::class, 'getLadderRecentGamesList']);

    Route::get('/{abbreviation}/players', [ApiIrcController::class, 'getPlayerNames']);
    Route::get('/{abbreviation}/active', [ApiIrcController::class, 'getActivePlayers']);
});

// Short cache ladder endpoints
Route::group(['prefix' => 'v1/ladder', 'middleware' => 'cache.public'], function ()
{
    Route::get('/{game}/top/{count}', [ApiLadderController::class, 'getLadderTopList']);
    Route::get('/{game}/player/{player}', [ApiLadderController::class, 'getLadderPlayer']);
    Route::get('/{game}/player/{player}/webview', [ApiLadderStatsProfile::class, 'getWebview']);
});

// Ultra short cache ladder endpoints
Route::group(['prefix' => 'v1/irc', 'middleware' => 'cache.ultra.public'], function ()
{
    Route::get('/{abbreviation}/active', [ApiIrcController::class, 'getActive']);
    Route::get('/{abbreviation}/players', [ApiIrcController::class, 'getPlayerNames']);
    Route::get('/{abbreviation}/clans', ['middleware' => 'cache.public', [ApiIrcController::class, 'getClans']]);
    Route::get('/hostmasks', [ApiIrcController::class, 'getHostmasks']);
});
