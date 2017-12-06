<?php

use \App\User;

Route::get('/', function ()
{
    return redirect('ladder/');
});

//Route::get('/patch', 'LadderController@addLadder');

Route::group(['prefix' => 'ladder/'], function()
{
    Route::get('/', 'LadderController@getLadders');
    Route::get('{date}/{game}', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/games', 'LadderController@getLadderGames');
    Route::get('{date}/{tier}/{game}', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/player/', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/player/{player}', 'LadderController@getLadderPlayer');
    Route::get('{date}/{game}/games/{gameId}', 'LadderController@getLadderGame');
    Route::get('{date}/{game}/badges', 'LadderController@getBadgesIndex');
});

Route::controllers
([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);

Route::group(['prefix' => 'admin', 'middleware' => 'auth', 'group' => [User::Admin, User::God]], function ()
{
    Route::get('/', 'AdminController@getAdminIndex');
    Route::get('/users', 'AdminController@getManageUsersIndex');
    Route::get('/setup', 'AdminController@getLadderSetupIndex');
    Route::post('/setup/rules', 'AdminController@postLadderSetupRules');
    Route::post('/setup/qmmap', 'AdminController@postQuickMatchMap');
    Route::post('/setup/remqmap', 'AdminController@removeQuickMatchMap');
    Route::get('/setup/downmap/{mapId}', 'AdminController@moveDownQuickMatchMap');
    Route::get('/setup/upmap/{mapId}', 'AdminController@moveUpQuickMatchMap');

    Route::get('/games/{cncnetGame}', 'AdminController@getManageGameIndex');
    Route::post('/games/{cncnetGame}/delete', 'AdminController@deleteGame');
    Route::post('/games/switch', 'AdminController@switchGameReport');
});

Route::group(['prefix' => 'account', 'middleware' => 'auth'], function ()
{
    Route::get('/', 'AccountController@getAccountIndex');
    Route::post('/username', 'AccountController@createUsername');
    Route::post('/card', 'AccountController@updatePlayerCard');
});

Route::group(['prefix' => 'api/v1/auth/', 'middleware' => 'auth.basic.once'], function()
{
    Route::get('/token', 'ApiAuthController@getAuth');
});

Route::group(['prefix' => 'api/v1/'], function ()
{
    Route::get('/user/account', 'ApiUserController@getAccount');
    Route::post('/user/create', 'ApiUserController@createUser');

    // Result Endpoints
    Route::post('/result/{game}/{username}', 'ApiLadderController@postLadder');
    Route::post('/result/{game}/{username}/{pingSent}/{pingReceived}', 'ApiLadderController@postLadder');

    // General Endpoints
    Route::get('/ping', 'ApiLadderController@pingLadder');

    // Player Endpoints
    Route::post('/player/{username}', 'ApiPlayerController@createPlayer');

    // Ladder Endpoints
    Route::get('/ladder', 'ApiLadderController@getCurrentLadders');
    Route::get('/ladder/{game}', 'ApiLadderController@getLadder');
    Route::get('/ladder/{game}/game/{gameId}', 'ApiLadderController@getLadderGame');
    Route::get('/ladder/{game}/player/{player}', 'ApiLadderController@getLadderPlayer');
    Route::get('/ladder/{game}/top/{count}', 'ApiLadderController@getLadderTopList');

    // Debug
    Route::get('/ladder/raw/{gameId}', 'ApiLadderController@viewRawGame');
    Route::get('/ladder/elo/{gameId}', 'ApiLadderController@awardPoints');

    // QuickMatch Endpoints
    Route::get('/qm/version/{platform}', 'ApiQuickMatchController@clientVersion');
    Route::get('/qm/ladder/{ladderAbbrev}/stats', 'ApiQuickMatchController@statsRequest');
    Route::get('/qm/ladder/{ladderAbbrev}/maps', 'ApiQuickMatchController@mapListRequest');
    Route::post('/qm/{ladderAbbrev}/{playerName}', 'ApiQuickMatchController@matchRequest');
});