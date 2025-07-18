<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

Route::get('/', [\App\Http\Controllers\SiteController::class, 'getIndex']);
Route::get('/ladder-champions/{game}', [\App\Http\Controllers\LeagueChampionsController::class, 'getLeagueChampions']);
Route::get('/help/obs', [\App\Http\Controllers\SiteController::class, 'getOBSHelp']);
Route::get('/donate', [\App\Http\Controllers\SiteController::class, 'getDonate']);
Route::get('/styleguide', [\App\Http\Controllers\SiteController::class, 'getStyleguide']);
Route::get('/ranking', [\App\Http\Controllers\RankingController::class, 'getIndex']);
Route::get('/news', [\App\Http\Controllers\NewsController::class, 'getNews']);
Route::get('/news/{slug}', [\App\Http\Controllers\NewsController::class, 'getNewsBySlug']);
// Route::get("/stats", "SiteController@getStats");
Route::get('/canceledMatches/{ladderAbbreviation}', [\App\Http\Controllers\LadderController::class, 'getCanceledMatches']);

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

Route::group(['prefix' => 'auth'], function ()
{
    Auth::routes();
    Route::get('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout']);
});

Route::group(['prefix' => 'admin'], function ()
{

    Route::get('/', [\App\Http\Controllers\AdminController::class, 'getAdminIndex'])->middleware('restrict:canEditAnyLadders');

    Route::group(['middleware' => 'auth'], function ()
    {

        Route::get('podium', [\App\Http\Controllers\Admin\PodiumController::class, 'getPodiumForm'])->name('admin.podium');
        Route::get('podium/compute', [\App\Http\Controllers\Admin\PodiumController::class, 'computePodium'])->name('admin.podium.compute');

        Route::group(['middleware' => 'restrict:canEditAnyLadders'], function ()
        {

            Route::get('podium', [\App\Http\Controllers\Admin\PodiumController::class, 'getPodiumForm'])->name('admin.podium');
            Route::get('podium/compute', [\App\Http\Controllers\Admin\PodiumController::class, 'computePodium'])->name('admin.podium.compute');

            Route::get('players/ratings', [\App\Http\Controllers\AdminController::class, 'getPlayerRatings']);
            Route::get('players/ratings/{ladderAbbreviation}', [\App\Http\Controllers\AdminController::class, 'getPlayerRatings']);
        });

        Route::group(['middleware' => 'restrict:adminRequired'], function ()
        {

            Route::get('users/', [\App\Http\Controllers\AdminController::class, 'getManageUsersIndex']);
            Route::get('users/chatbans', [\App\Http\Controllers\AdminController::class, 'getChatBannedUsers']);
            Route::get('users/edit/{userId}', [\App\Http\Controllers\AdminController::class, 'getEditUser']);
            Route::post('users/edit/{userId}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
            Route::post('users/tier/update', [\App\Http\Controllers\AdminController::class, 'updateUserLadderTier']);
            Route::post('users/duplicates/confirm', [AdminController::class, 'confirmDuplicate']);
            Route::post('users/duplicates/unlink', [AdminController::class, 'unlinkDuplicate']);
            Route::post('users/duplicates/resetprimary', [AdminController::class, 'resetToUnconfirmedPrimary']);

            Route::get('clans', [\App\Http\Controllers\AdminController::class, 'getManageClansIndex']);
            Route::post('clans', [\App\Http\Controllers\AdminController::class, 'updateClan']);
        });

        Route::group(['prefix' => 'news', 'middleware' => ['restrict:adminRequired', 'restrict:isNewsAdmin']], function ()
        {

            Route::get('/', [\App\Http\Controllers\AdminNewsController::class, 'getIndex']);
            Route::get('/create', [\App\Http\Controllers\AdminNewsController::class, 'getCreate']);
            Route::get('/edit/{id}', [\App\Http\Controllers\AdminNewsController::class, 'getEdit']);
            Route::post('/save', [\App\Http\Controllers\AdminNewsController::class, 'save']);
        });

        Route::group(['middleware' => ['restrict:canAdminLadder']], function ()
        {

            Route::post('ladder/new', [\App\Http\Controllers\LadderController::class, 'saveLadder'])->middleware('restrict:isGod');
            Route::get('washedGames/{ladderAbbreviation}', [\App\Http\Controllers\AdminController::class, 'getWashedGames']);
        });

        Route::group(['prefix' => 'setup/{ladderId}', 'middleware' => ['restrict:canModLadder']], function ()
        {

            Route::get('edit', [\App\Http\Controllers\AdminController::class, 'getLadderSetupIndex']);

            Route::group(['middleware' => ['restrict:canAdminLadder']], function ()
            {

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

            Route::group(['middleware' => ['group:' . User::God]], function ()
            {
                Route::post('add/admin', [\App\Http\Controllers\AdminController::class, 'addAdmin']);
                Route::post('remove/admin', [\App\Http\Controllers\AdminController::class, 'removeAdmin']);
            });

            Route::post('add/tester', [\App\Http\Controllers\AdminController::class, 'addTester']);
            Route::post('remove/tester', [\App\Http\Controllers\AdminController::class, 'removeTester']);
        });

        Route::group(['prefix' => 'schema/{gameSchemaId}', 'middleware' => ['restrict:objectSchemaManager']], function ()
        {

            Route::get('/', [\App\Http\Controllers\AdminController::class, 'getGameSchemaSetup']);
            Route::post('/', [\App\Http\Controllers\AdminController::class, 'saveGameSchema']);
            Route::post('/object/{objectId}', [\App\Http\Controllers\AdminController::class, 'saveGameObject']);
            Route::post('/manager', [\App\Http\Controllers\AdminController::class, 'saveSchemaManager']);
        });

        Route::group(['prefix' => 'moderate/{ladderId}', 'middleware' => ['restrict:canModLadder']], function ()
        {

            Route::get('/games/{cncnetGame}', [AdminController::class, 'getManageGameIndex']);
            Route::post('/games/{cncnetGame}/delete', [AdminController::class, 'deleteGame']);
            Route::post('/games/switch', [AdminController::class, 'switchGameReport']);
            Route::post('/games/wash', [AdminController::class, 'washGame']);
            Route::post('/games/fix-points', [AdminController::class, 'fixPoints']);

            Route::get('/player/{playerId}', [AdminController::class, 'getLadderPlayer']);
            Route::get('/player/{playerId}/newban/{banType}', [AdminController::class, 'getUserBan']);
            Route::get('/player/{playerId}/editban/{banId}', [AdminController::class, 'editUserBan']);
            Route::post('/player/{playerId}/editban/{banId}', [AdminController::class, 'saveUserBan']);
            Route::post('/player/{playerId}/editban', [AdminController::class, 'saveUserBan']);
            Route::post('/player/{playerId}/alert', [AdminController::class, 'editPlayerAlert']);
            Route::post('/player/{playerId}/laundry', [AdminController::class, 'laundryService']);
            Route::post('/player/{playerId}/undoLaundry', [AdminController::class, 'undoLaundryService']);
            Route::post('/player/{playerId}/editName', [AdminController::class, 'editPlayerName']);
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
