<?php

use App\Http\Controllers\LadderController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LeagueChampionsController;
use App\Http\Controllers\MapPoolController;
use App\Http\Controllers\ClanController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function ()
{
    return redirect('ladder');
});

require __DIR__ . '/auth.php';

Route::get('/ladder-champions/{game}', [LeagueChampionsController::class, 'getLeagueChampions']);
Route::get('/help/obs', [HelpController::class, 'getOBSHelp']);

Route::middleware(['cache.public'])->prefix('ladder')->group(function ()
{

    Route::get('/', [LadderController::class, 'getLadders']);

    Route::get('{date}/{game}', [LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/games', [LadderController::class, 'getLadderGames']);
    Route::get('{date}/{tier}/{game}', [LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/player/', [LadderController::class, 'getLadderIndex']);
    Route::get('{date}/{game}/player/{player}', [LadderController::class, 'getLadderPlayer']);
    Route::get('{date}/{game}/games/{gameId}', [LadderController::class, 'getLadderGame']);
    Route::get('{date}/{game}/games/{gameId}/{reportId}', [LadderController::class, 'getLadderGame']);
    Route::get('{date}/{game}/badges', [LadderController::class, 'getBadgesIndex']);
});

Route::group(['prefix' => 'admin/', 'canEditAnyLadders' => true, 'middleware' => 'auth'], function ()
{
    Route::get('/', [AdminController::class, 'getAdminIndex']);
});

Route::group(['prefix' => 'admin/', 'middleware' => 'auth', 'canAdminLadder' => true], function ()
{
    Route::get('users/', [AdminController::class, 'getManageUsersIndex']);
    Route::post('ladder/new', ['middleware' => 'auth', 'isGod' => true, LadderController::class, 'saveLadder']);
});

Route::group(['prefix' => 'admin/setup/{ladderId}', 'middleware' => 'auth', 'canModLadder' => true], function ()
{
    Route::get('edit', [AdminController::class, 'getLadderSetupIndex']);
    Route::post('ladder', ['middleware' => 'auth', 'canAdminLadder' => true, LadderController::class, 'saveLadder']);

    Route::post('addSide', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'addSide']);
    Route::post('remSide', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'remSide']);

    Route::post('rules', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'postLadderSetupRules']);

    Route::post('editmap', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'editMap']);

    Route::post('optval', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'editSpawnOptionValue']);
    Route::post('mappool/{mapPoolId}/optval', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'editSpawnOptionValue']);

    Route::post('mappool/{mapPoolId}/rempoolmap', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'removeQuickMatchMap']);
    Route::get('mappool/{mapPoolId}/edit', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'editMapPool']);
    Route::post('mappool/{mapPoolId}/edit', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'postQuickMatchMap']);
    Route::post('mappool/{mapPoolId}/rename',  ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'renameMapPool']);
    Route::post('mappool', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'changeMapPool']);
    Route::post('mappool/new', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'newMapPool']);
    Route::post('mappool/{mapPoolId}/remove', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'removeMapPool']);
    Route::post('mappool/{mapPoolId}/reorder', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'reorderMapPool']);
    Route::post('mappool/clone', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'cloneMapPool']);
    Route::post('mappool/{mapPoolId}/cloneladdermaps', ['middleware' => 'auth', 'canAdminLadder' => true, MapPoolController::class, 'copyMaps']);

    Route::post('add/admin', ['middleware' => 'auth', 'group' => User::God, AdminController::class, 'addAdmin']);
    Route::post('remove/admin', ['middleware' => 'auth', 'group' => User::God, AdminController::class, 'removeAdmin']);

    Route::post('add/moderator', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'addModerator']);
    Route::post('remove/moderator', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'removeModerator']);

    Route::post('add/tester', [AdminController::class, 'addTester']);
    Route::post('remove/tester', [AdminController::class, 'removeTester']);

    Route::post('alert', ['middleware' => 'auth', 'canAdminLadder' => true, AdminController::class, 'editLadderAlert']);
});

Route::group(['prefix' => 'admin/schema/{gameSchemaId}', 'middleware' => 'auth', 'objectSchemaManager' => true], function ()
{
    Route::get('/', [AdminController::class, 'getGameSchemaSetup']);
    Route::post('/', [AdminController::class, 'saveGameSchema']);
    Route::post('/object/{objectId}', [AdminController::class, 'saveGameObject']);
    Route::post('/manager', [AdminController::class, 'saveSchemaManager']);
});

Route::group(['prefix' => 'admin/moderate/{ladderId}', 'middleware' => 'auth', 'canModLadder' => true], function ()
{
    Route::get('/games/{cncnetGame}', [AdminController::class, 'getManageGameIndex']);
    Route::post('/games/{cncnetGame}/delete', [AdminController::class, 'deleteGame']);
    Route::post('/games/switch', [AdminController::class, 'switchGameReport']);
    Route::post('/games/wash', [AdminController::class, 'washGame']);

    Route::get('/player/{playerId}', [AdminController::class, 'getLadderPlayer']);
    Route::get('/player/{playerId}/newban/{banType}', [AdminController::class, 'getUserBan']);
    Route::get('/player/{playerId}/editban/{banId}', [AdminController::class, 'editUserBan']);
    Route::post('/player/{playerId}/editban/{banId}', [AdminController::class, 'saveUserBan']);
    Route::post('/player/{playerId}/editban', [AdminController::class, 'saveUserBan']);
    Route::post('/player/{playerId}/alert', [AdminController::class, 'editPlayerAlert']);
    Route::post('/player/{playerId}/laundry', [AdminController::class, 'laundryService']);
    Route::post('/player/{playerId}/undoLaundry', [AdminController::class, 'undoLaundryService']);
});


Route::group(['prefix' => 'account', 'middleware' => 'auth'], function ()
{
    Route::get('/', [AccountController::class, 'getAccountIndex']);
    Route::get('/{ladderAbbrev}/list', [AccountController::class, 'getLadderAccountIndex']);
    Route::post('/{ladderAbbrev}/username-status', [AccountController::class, 'toggleUsernameStatus']);
    Route::post('/rename', [AccountController::class, 'rename']);

    Route::post('/{ladderAbbrev}/username', [AccountController::class, 'createUsername']);
    Route::post('/{ladderAbbrev}/card', [AccountController::class, 'updatePlayerCard']);
    Route::get('/verify', [AccountController::class, 'getNewVerification']);
    Route::post('/verify', [AccountController::class, 'createNewVerification']);
    Route::get('/verify/{verify_token}', [AccountController::class, 'verifyEmail']);
});

## Clans
Route::group(['prefix' => 'clans', 'middleware' => ['auth', 'cache.public'], 'guestsAllowed' => true], function ()
{
    Route::get('/', [ClanController::class, 'getIndex']);
    Route::get('/{ladderAbbrev}/leaderboards/{date}', [ClanController::class, 'getListing']);
});

Route::group(['prefix' => 'clans/{ladderAbbrev}', 'middleware' => 'auth'], function ()
{
    Route::get('/edit/{clanId}/main', [ClanController::class, 'editLadderClan']);
    Route::post('/edit/{clanId}', [ClanController::class, 'saveLadderClan']);
    Route::post('/edit/{clanId}/members', [ClanController::class, 'saveMembers']);
    Route::post('/edit/new', [ClanController::class, 'saveLadderClan']);

    Route::post('/invite/{clanId}', [ClanController::class, 'saveInvitation']);
    Route::post('/invite/{clanId}/process', [ClanController::class, 'processInvitation']);
    Route::post('/invite/{clanId}/cancel', [ClanController::class, 'cancelInvitation']);
    Route::post('/role/{clanId}', [ClanController::class, 'role']);
    Route::post('/kick/{clanId}', [ClanController::class, 'kick']);
    Route::post('/leave/{clanId}', [ClanController::class, 'leave']);
});
