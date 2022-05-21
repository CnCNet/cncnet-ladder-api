<?php

use Illuminate\Http\Request;
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

Route::group(['prefix' => 'api/v1/auth/', 'middleware' => 'auth.basic.once'], function ()
{
    Route::get('/token', 'ApiAuthController@getAuth');
});

Route::group(['prefix' => 'api/v1/auth/'], function ()
{
    Route::get('/refresh', 'ApiAuthController@refresh');
    Route::post('/login', 'ApiAuthController@login');
});

Route::group(['prefix' => 'api/v1/', 'middleware' => 'jwt.auth'], function ()
{
    Route::get('/user/account', 'ApiUserController@getAccount');
    Route::get('/user/ladders', 'ApiUserController@getPrivateLadders');
    Route::post('/user/create', 'ApiUserController@createUser');

    // Result Endpoints
    Route::post('/result/{game}/{username}', 'ApiLadderController@postLadder');
    Route::post('/result/{game}/{username}/{pingSent}/{pingReceived}', 'ApiLadderController@postLadder');
    Route::post('/result/ladder/{ladderId}/game/{gameId}/player/{playerId}/pings/{pingsSent}/{pingsReceived}', 'ApiLadderController@newPostLadder');

    // General Endpoints
    Route::get('/ping', 'ApiLadderController@pingLadder');

    // Player Endpoints
    Route::post('/player/{username}', 'ApiPlayerController@createPlayer');

    // Debug
    Route::get('/ladder/raw/{gameId}', 'ApiLadderController@viewRawGame');

    // QuickMatch Endpoints
    Route::get('/qm/version/{platform}', 'ApiQuickMatchController@clientVersion');
    Route::get('/qm/ladder/{ladderAbbrev}/maps', 'ApiQuickMatchController@mapListRequest');
    Route::post('/qm/{ladderAbbrev}/{playerName}', 'ApiQuickMatchController@matchRequest');
});

Route::group(['prefix' => 'api/v2/', 'middleware' => 'jwt.auth', 'namespace' => '\v2'], function ()
{
    Route::get('/user/account', 'ApiUserController@getAccount');
});

Route::group(['prefix' => 'api/v1/', 'middleware' => 'cache.short.public'], function ()
{
    Route::get('/qm/ladder/{ladderAbbrev}/stats', 'ApiQuickMatchController@statsRequest');
});

// Ladder Endpoints
Route::group(['prefix' => 'api/v1/ladder', 'middleware' => 'cache.long.public'], function ()
{
    Route::get('/', 'ApiLadderController@getCurrentLadders');
    Route::get('/{game}/games/recent/{count}', 'ApiLadderController@getLadderRecentGamesList');
    Route::get('/{game}', 'ApiLadderController@getLadder');
    Route::get('/{game}/game/{gameId}', 'ApiLadderController@getLadderGame');
    Route::get('/{game}/winners/', 'ApiLadderController@getLadderWinners');
    Route::get('/{game}/games/recent/{count}', 'ApiLadderController@getLadderRecentGamesList');

    Route::get('/{abbreviation}/players', 'ApiIrcController@getPlayerNames');
    Route::get('/{abbreviation}/active', 'ApiIrcController@getActivePlayers');
});

// Short cache ladder endpoints
Route::group(['prefix' => 'api/v1/ladder', 'middleware' => 'cache.public'], function ()
{
    Route::get('/{game}/top/{count}', 'ApiLadderController@getLadderTopList');
    Route::get('/{game}/player/{player}', 'ApiLadderController@getLadderPlayer');
    Route::get('/{game}/player/{player}/webview', 'ApiLadderStatsProfile@getWebview');
});

// Ultra short cache ladder endpoints
Route::group(['prefix' => 'api/v1/irc', 'middleware' => 'cache.ultra.public'], function ()
{
    Route::get('/{abbreviation}/active', 'ApiIrcController@getActive');
    Route::get('/{abbreviation}/players', 'ApiIrcController@getPlayerNames');
    Route::get('/{abbreviation}/clans', ['middleware' => 'cache.public', 'uses' => 'ApiIrcController@getClans']);
    Route::get('/hostmasks', 'ApiIrcController@getHostmasks');
});
