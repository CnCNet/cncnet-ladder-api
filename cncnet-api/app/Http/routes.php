<?php

use \App\User;

Route::get('/', function ()
{
    return redirect('ladder/');
});

Route::get('/ladder-champions/{game}', 'LeagueChampionsController@getLeagueChampions');
Route::get('/help/obs', 'HelpController@getOBSHelp');

Route::group(['prefix' => 'ladder/', 'middleware' => ['auth', 'cache.public'], 'guestsAllowed' => true], function ()
{
    Route::get('/', 'LadderController@getLadders');
    Route::get('{date}/{game}', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/games', 'LadderController@getLadderGames');
    Route::get('{date}/{tier}/{game}', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/player/', 'LadderController@getLadderIndex');
    Route::get('{date}/{game}/player/{player}', 'LadderController@getLadderPlayer');
    Route::get('{date}/{game}/games/{gameId}', 'LadderController@getLadderGame');
    Route::get('{date}/{game}/games/{gameId}/{reportId}', 'LadderController@getLadderGame');
    Route::get('{date}/{game}/badges', 'LadderController@getBadgesIndex');
});

Route::controllers([
    'auth' => 'Auth\AuthController',
    'password' => 'Auth\PasswordController',
]);

Route::get('/admin', ['middleware' => 'auth', 'canEditAnyLadders' => true, 'uses' => 'AdminController@getAdminIndex']);


Route::group(['prefix' => 'admin/', 'middleware' => 'auth', 'canAdminLadder' => true], function ()
{
    Route::get('users/', 'AdminController@getManageUsersIndex');
    Route::post('ladder/new', ['middleware' => 'auth', 'isGod' => true, 'uses' => 'LadderController@saveLadder']);
});

Route::group(['prefix' => 'admin/setup/{ladderId}', 'middleware' => 'auth', 'canModLadder' => true], function ()
{
    Route::get('edit', 'AdminController@getLadderSetupIndex');
    Route::post('ladder', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'LadderController@saveLadder']);

    Route::post('addSide', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@addSide']);
    Route::post('remSide', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@remSide']);

    Route::post('rules', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@postLadderSetupRules']);

    Route::post('editmap', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@editMap']);

    Route::post('optval', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@editSpawnOptionValue']);
    Route::post('mappool/{mapPoolId}/optval', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@editSpawnOptionValue']);

    Route::post('mappool/{mapPoolId}/rempoolmap', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@removeQuickMatchMap']);
    Route::get('mappool/{mapPoolId}/edit', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@editMapPool']);
    Route::post('mappool/{mapPoolId}/edit', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@postQuickMatchMap']);
    Route::post('mappool/{mapPoolId}/rename',  ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@renameMapPool']);
    Route::post('mappool', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@changeMapPool']);
    Route::post('mappool/new', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@newMapPool']);
    Route::post('mappool/{mapPoolId}/remove', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@removeMapPool']);
    Route::post('mappool/{mapPoolId}/reorder', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@reorderMapPool']);
    Route::post('mappool/clone', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@cloneMapPool']);
    Route::post('mappool/{mapPoolId}/cloneladdermaps', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'MapPoolController@copyMaps']);

    Route::post('add/admin', ['middleware' => 'auth', 'group' => User::God, 'uses' => 'AdminController@addAdmin']);
    Route::post('remove/admin', ['middleware' => 'auth', 'group' => User::God, 'uses' => 'AdminController@removeAdmin']);

    Route::post('add/moderator', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@addModerator']);
    Route::post('remove/moderator', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@removeModerator']);

    Route::post('add/tester', 'AdminController@addTester');
    Route::post('remove/tester', 'AdminController@removeTester');

    Route::post('alert', ['middleware' => 'auth', 'canAdminLadder' => true, 'uses' => 'AdminController@editLadderAlert']);
});

Route::group(['prefix' => 'admin/schema/{gameSchemaId}', 'middleware' => 'auth', 'objectSchemaManager' => true], function ()
{
    Route::get('/', 'AdminController@getGameSchemaSetup');
    Route::post('/', 'AdminController@saveGameSchema');
    Route::post('/object/{objectId}', 'AdminController@saveGameObject');
    Route::post('/manager', 'AdminController@saveSchemaManager');
});

Route::group(['prefix' => 'admin/moderate/{ladderId}', 'middleware' => 'auth', 'canModLadder' => true], function ()
{
    Route::get('/games/{cncnetGame}', 'AdminController@getManageGameIndex');
    Route::post('/games/{cncnetGame}/delete', 'AdminController@deleteGame');
    Route::post('/games/switch', 'AdminController@switchGameReport');
    Route::post('/games/wash', 'AdminController@washGame');

    Route::get('/player/{playerId}', 'AdminController@getLadderPlayer');
    Route::get('/player/{playerId}/newban/{banType}', 'AdminController@getUserBan');
    Route::get('/player/{playerId}/editban/{banId}', 'AdminController@editUserBan');
    Route::post('/player/{playerId}/editban/{banId}', 'AdminController@saveUserBan');
    Route::post('/player/{playerId}/editban', 'AdminController@saveUserBan');
    Route::post('/player/{playerId}/alert', 'AdminController@editPlayerAlert');
    Route::post('/player/{playerId}/laundry', 'AdminController@laundryService');
    Route::post('/player/{playerId}/undoLaundry', 'AdminController@undoLaundryService');
});

Route::group(['prefix' => 'account', 'middleware' => 'auth'], function ()
{
    Route::get('/', 'AccountController@getAccountIndex');
    Route::get('/{ladderAbbrev}/list', 'AccountController@getLadderAccountIndex');
    Route::post('/{ladderAbbrev}/username-status', 'AccountController@toggleUsernameStatus');
    Route::post('/rename', 'AccountController@rename');

    Route::post('/{ladderAbbrev}/username', 'AccountController@createUsername');
    Route::post('/{ladderAbbrev}/card', 'AccountController@updatePlayerCard');
    Route::get('/verify', 'AccountController@getNewVerification');
    Route::post('/verify', 'AccountController@createNewVerification');
    Route::get('/verify/{verify_token}', 'AccountController@verifyEmail');
});

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

Route::group(['prefix' => 'clans', 'middleware' => ['auth', 'cache.public'], 'guestsAllowed' => true], function ()
{
    Route::get('/', 'ClanController@getIndex');
    Route::get('/{ladderAbbrev}/leaderboards/{date}', 'ClanController@getListing');
});

Route::group(['prefix' => 'clans/{ladderAbbrev}', 'middleware' => 'auth'], function ()
{
    Route::get('/edit/{clanId}/main', 'ClanController@editLadderClan');
    Route::post('/edit/{clanId}', 'ClanController@saveLadderClan');
    Route::post('/edit/{clanId}/members', 'ClanController@saveMembers');
    //Route::post('/edit/new', 'ClanController@saveLadderClan');

    Route::post('/invite/{clanId}', 'ClanController@saveInvitation');
    Route::post('/invite/{clanId}/process', 'ClanController@processInvitation');
    Route::post('/invite/{clanId}/cancel', 'ClanController@cancelInvitation');
    Route::post('/role/{clanId}', 'ClanController@role');
    Route::post('/kick/{clanId}', 'ClanController@kick');
    Route::post('/leave/{clanId}', 'ClanController@leave');
});
