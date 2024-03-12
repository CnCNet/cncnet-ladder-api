<?php
use App\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [\App\Http\Controllers\SiteController::class, 'getIndex']);
Route::get('/ladder-champions/{game}', [\App\Http\Controllers\LeagueChampionsController::class, 'getLeagueChampions']);
Route::get('/help/obs', [\App\Http\Controllers\SiteController::class, 'getOBSHelp']);
Route::get('/donate', [\App\Http\Controllers\SiteController::class, 'getDonate']);
Route::get('/styleguide', [\App\Http\Controllers\SiteController::class, 'getStyleguide']);
Route::get('/ranking', [\App\Http\Controllers\RankingController::class, 'getIndex']);
Route::get('/news', [\App\Http\Controllers\NewsController::class, 'getNews']);
Route::get('/news/{slug}', [\App\Http\Controllers\NewsController::class, 'getNewsBySlug']);
// Route::get("/stats", "SiteController@getStats");


# 1vs1 Player Ladders
Route::group(['prefix' => 'ladder/', 'middleware' => ['cache.public']], function ()
{
    Route::get('/', [\App\Http\Controllers\LadderController::class, 'getLadders']);
    Route::get('/play', [\App\Http\Controllers\LadderController::class, 'getPopularTimes']);
    Route::get('{date}/{game}', [\App\Http\Controllers\LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/games', [\App\Http\Controllers\LadderController::class, 'getLadderGames']);
    Route::get('{date}/{tier}/{game}', [\App\Http\Controllers\LadderController::class, 'getLadderIndex']);

    Route::get('{date}/{game}/player/', [\App\Http\Controllers\LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/player/{player}', [\App\Http\Controllers\LadderController::class, 'getLadderPlayer']);
    Route::get('{date}/{game}/player/{player}/achievements', [\App\Http\Controllers\LadderController::class, 'getPlayerAchievementsPage']);

    Route::get('{date}/{game}/clan/', [\App\Http\Controllers\LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/clan/{clan}', [\App\Http\Controllers\LadderController::class, 'getLadderClan']);
    Route::get('{date}/{game}/clan/{clan}/achievements', [\App\Http\Controllers\LadderController::class, 'getPlayerAchievementsPage']);

    Route::get('{date}/{game}/games/{gameId}', [\App\Http\Controllers\LadderController::class, 'getLadderGame']);
    Route::get('{date}/{game}/games/{gameId}/{reportId}', [\App\Http\Controllers\LadderController::class, 'getLadderGame']);
});

# Clan Ladders
Route::group(['prefix' => 'clans/{ladderAbbrev}', 'middleware' => 'auth'], function ()
{
    Route::get('/edit/{clanId}/main', [\App\Http\Controllers\ClanController::class, 'editLadderClan']);

    Route::post('/edit/{clanId}', [\App\Http\Controllers\ClanController::class, 'saveLadderClan']);
    Route::post('/edit/{clanId}/members', [\App\Http\Controllers\ClanController::class, 'saveMembers']);
    Route::post('/edit/{clanId}/avatar', [\App\Http\Controllers\ClanController::class, 'saveLadderAvatar']);
    //Route::post('/edit/new', 'ClanController@saveLadderClan');

    Route::post('/invite/{clanId}', [\App\Http\Controllers\ClanController::class, 'saveInvitation']);
    Route::post('/invite/{clanId}/process', [\App\Http\Controllers\ClanController::class, 'processInvitation']);
    Route::post('/invite/{clanId}/cancel', [\App\Http\Controllers\ClanController::class, 'cancelInvitation']);
    Route::post('/activate/{id}', [\App\Http\Controllers\ClanController::class, 'activateClan']);
    Route::post('/role/{clanId}', [\App\Http\Controllers\ClanController::class, 'role']);
    Route::post('/kick/{clanId}', [\App\Http\Controllers\ClanController::class, 'kick']);
    Route::post('/leave/{clanId}', [\App\Http\Controllers\ClanController::class, 'leave']);
});

Route::group(['prefix' => 'auth'], function() {
    Auth::routes();
    Route::get('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout']);
});

Route::group(['prefix' => 'admin'], function() {

    Route::get('/', [\App\Http\Controllers\AdminController::class, 'getAdminIndex'])->middleware('restrict:canEditAnyLadders');

    Route::group(['middleware' => 'auth'], function() {

        Route::group(['middleware' => 'restrict:canEditAnyLadders'], function () {

            Route::get('players/ratings', [\App\Http\Controllers\AdminController::class, 'getPlayerRatings']);
            Route::get('players/ratings/{ladderAbbreviation}', [\App\Http\Controllers\AdminController::class, 'getPlayerRatings']);
        });

        Route::group(['middleware' => 'restrict:adminRequired'], function () {

            Route::get('users/', [\App\Http\Controllers\AdminController::class, 'getManageUsersIndex']);
            Route::get('users/chatbans', [\App\Http\Controllers\AdminController::class, 'getChatBannedUsers']);
            Route::get('users/edit/{userId}', [\App\Http\Controllers\AdminController::class, 'getEditUser']);
            Route::post('users/edit/{userId}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
            Route::post('users/tier/update', [\App\Http\Controllers\AdminController::class, 'updateUserLadderTier']);

            Route::get('clans', [\App\Http\Controllers\AdminController::class, 'getManageClansIndex']);
            Route::post('clans', [\App\Http\Controllers\AdminController::class, 'updateClan']);
        });

        Route::group(['prefix' => 'news', 'middleware' => ['restrict:adminRequired', 'restrict:isNewsAdmin']], function () {

            Route::get('/', [\App\Http\Controllers\AdminNewsController::class, 'getIndex']);
            Route::get('/create', [\App\Http\Controllers\AdminNewsController::class, 'getCreate']);
            Route::get('/edit/{id}', [\App\Http\Controllers\AdminNewsController::class, 'getEdit']);
            Route::post('/save', [\App\Http\Controllers\AdminNewsController::class, 'save']);
        });

        Route::group(['middleware' => ['restrict:canAdminLadder']], function () {

            Route::post('ladder/new', [\App\Http\Controllers\LadderController::class, 'saveLadder'])->middleware('restrict:isGod');
            Route::get('canceledMatches/{ladderAbbreviation}', [\App\Http\Controllers\AdminController::class, 'getCanceledMatches']);
            Route::get('washedGames/{ladderAbbreviation}', [\App\Http\Controllers\AdminController::class, 'getWashedGames']);
        });

        Route::group(['prefix' => 'setup/{ladderId}', 'middleware' => ['restrict:canModLadder']], function () {

            Route::get('edit', [\App\Http\Controllers\AdminController::class, 'getLadderSetupIndex']);

            Route::group(['middleware' => ['restrict:canAdminLadder']], function () {

                Route::post('ladder', [\App\Http\Controllers\LadderController::class, 'saveLadder']);

                Route::post('addSide', [\App\Http\Controllers\AdminController::class, 'addSide']);
                Route::post('remSide', [\App\Http\Controllers\AdminController::class, 'remSide']);

                Route::post('rules', [\App\Http\Controllers\AdminController::class, 'postLadderSetupRules']);

                Route::post('editmap', [\App\Http\Controllers\MapPoolController::class, 'editMap']);

                // update/delete map tier endpoints
                Route::post('mappool/{mapPoolId}/editMapTier', [\App\Http\Controllers\MapPoolController::class, 'editMapTier']);
                Route::post('mappool/{mapPoolId}/deleteMapTier', [\App\Http\Controllers\MapPoolController::class, 'deleteMapTier']);

                Route::post('optval', [\App\Http\Controllers\AdminController::class, 'editSpawnOptionValue']);
                Route::post('mappool/{mapPoolId}/optval', [\App\Http\Controllers\AdminController::class, 'editSpawnOptionValue']);

                Route::post('mappool/{mapPoolId}/rempoolmap', [\App\Http\Controllers\MapPoolController::class, 'removeQuickMatchMap']);
                Route::get('mappool/{mapPoolId}/edit', [\App\Http\Controllers\MapPoolController::class, 'editMapPool']);
                Route::post('mappool/{mapPoolId}/edit', [\App\Http\Controllers\MapPoolController::class, 'postQuickMatchMap']);
                Route::post('mappool/{mapPoolId}/rename',  [\App\Http\Controllers\MapPoolController::class, 'renameMapPool']);
                Route::post('mappool', [\App\Http\Controllers\MapPoolController::class, 'changeMapPool']);
                Route::post('mappool/new', [\App\Http\Controllers\MapPoolController::class, 'newMapPool']);
                Route::post('mappool/{mapPoolId}/remove', [\App\Http\Controllers\MapPoolController::class, 'removeMapPool']);
                Route::post('mappool/{mapPoolId}/reorder', [\App\Http\Controllers\MapPoolController::class, 'reorderMapPool']);
                Route::post('mappool/clone', [\App\Http\Controllers\MapPoolController::class, 'cloneMapPool']);
                Route::post('mappool/{mapPoolId}/cloneladdermaps', [\App\Http\Controllers\MapPoolController::class, 'copyMaps']);

                Route::post('add/moderator', [\App\Http\Controllers\AdminController::class, 'addModerator']);
                Route::post('remove/moderator', [\App\Http\Controllers\AdminController::class, 'removeModerator']);

                Route::post('alert', [\App\Http\Controllers\AdminController::class, 'editLadderAlert']);
            });

            Route::group(['middleware' => ['group:'.User::God]], function () {
                Route::post('add/admin', [\App\Http\Controllers\AdminController::class, 'addAdmin']);
                Route::post('remove/admin', [\App\Http\Controllers\AdminController::class, 'removeAdmin']);
            });

            Route::post('add/tester', [\App\Http\Controllers\AdminController::class, 'addTester']);
            Route::post('remove/tester', [\App\Http\Controllers\AdminController::class, 'removeTester']);

        });

        Route::group(['prefix' => 'schema/{gameSchemaId}', 'objectSchemaManager' => true], function () {

            Route::get('/', 'AdminController@getGameSchemaSetup');
            Route::post('/', 'AdminController@saveGameSchema');
            Route::post('/object/{objectId}', 'AdminController@saveGameObject');
            Route::post('/manager', 'AdminController@saveSchemaManager');
        });

        Route::group(['prefix' => 'moderate/{ladderId}', 'canModLadder' => true], function () {

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
            Route::post('/player/{playerId}/editName', 'AdminController@editPlayerName');
        });
    });

});


Route::group(['prefix' => 'account', 'middleware' => 'auth'], function ()
{
    Route::get('/', [\App\Http\Controllers\AccountController::class, 'getAccountIndex']);
    Route::get('/{ladderAbbrev}/list', [\App\Http\Controllers\AccountController::class, 'getLadderAccountIndex']);
    Route::post('/{ladderAbbrev}/username-status', [\App\Http\Controllers\AccountController::class, 'toggleUsernameStatus']);
    Route::post('/rename', [\App\Http\Controllers\AccountController::class, 'rename']);

    Route::post('/{ladderAbbrev}/username', [\App\Http\Controllers\AccountController::class, 'createUsername']);
    Route::get('/verify', [\App\Http\Controllers\AccountController::class, 'getNewVerification']);
    Route::post('/verify', [\App\Http\Controllers\AccountController::class, 'createNewVerification']);
    Route::get('/verify/{verify_token}', [\App\Http\Controllers\AccountController::class, 'verifyEmail']);
    Route::get('/settings', [\App\Http\Controllers\AccountController::class, 'getUserSettings']);
    Route::post('/settings', [\App\Http\Controllers\AccountController::class, 'updateUserSettings']);
});

# API Endpoints
Route::group(['prefix' => 'api/v1/auth/', 'middleware' => 'auth.basic.once'], function ()
{
    Route::get('/token', 'ApiAuthController@getAuth');
});

Route::group(['prefix' => 'api/v1/auth/'], function ()
{
    Route::get('/refresh', 'ApiAuthController@refresh');
    Route::post('/login', 'ApiAuthController@login');
});


Route::get("api/v1/news", "ApiNewsController@getNews");

Route::group([
    'prefix' => 'api/v1/',
    'middleware' => 'jwt.auth'
], function ()
{
    Route::get('/user/info', 'ApiUserController@getUserInfo');
    Route::get('/user/account', 'ApiUserController@getAccount');
    Route::get('/user/ladders', 'ApiUserController@getPrivateLadders');
    Route::get('/user/preferences', 'ApiUserController@getUserPreferences');
    Route::post('/user/preferences', 'ApiUserController@updateUserPreferences');

    // Result Endpoints
    Route::post('/result/{game}/{username}', 'ApiLadderController@postLadder');
    Route::post('/result/{game}/{username}/{pingSent}/{pingReceived}', 'ApiLadderController@postLadder');
    Route::post('/result/ladder/{ladderId}/game/{gameId}/player/{playerId}/pings/{pingsSent}/{pingsReceived}', 'ApiLadderController@newPostLadder');

    // General Endpoints
    Route::get('/ping', 'ApiLadderController@pingLadder');

    // Player Endpoints
    Route::post('/player/usernames', 'ApiPlayerController@getUsernames');
    Route::post('/player/create', 'ApiPlayerController@createPlayer');
    Route::post('/player/status', 'ApiPlayerController@togglePlayerStatus');

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
    Route::get('/qm/ladder/rankings', 'ApiQuickMatchController@getPlayerRankings');
    Route::get('/qm/ladder/{ladderAbbrev}/rankings', 'ApiQuickMatchController@getPlayerRankingsByLadder');
    Route::get('/qm/ladder/{ladderAbbrev}/maps/public', 'ApiQuickMatchController@mapListRequest');
    Route::get('/qm/ladder/{ladderAbbrev}/stats', 'ApiQuickMatchController@statsRequest');
    Route::get('/qm/ladder/{ladderAbbrev}/stats/{tierId}', 'ApiQuickMatchController@statsRequest');
    Route::get('/qm/ladder/{ladderAbbrev}/current_matches', 'ApiQuickMatchController@getActiveMatches');
    Route::get('/qm/ladder/{ladderAbbrev}/erroredGames', 'ApiQuickMatchController@getErroredGames');
    Route::get('/qm/ladder/{ladderAbbrev}/{hours}/recentlyWashedGames', 'ApiQuickMatchController@getRecentLadderWashedGamesCount');
});

// Ladder Endpoints
Route::group(['prefix' => 'api/v1/ladder', 'middleware' => 'cache.long.public'], function ()
{
    Route::get('/', 'ApiLadderController@getAllLadders');
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
    Route::get('/{game}/player/{player}', 'ApiLadderController@getLadderPlayerFromPublicApi');
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
