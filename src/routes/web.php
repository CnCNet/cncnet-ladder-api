<?php

use App\Http\Controllers\LadderController;
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
    return view('welcome');
});

Route::get('/dashboard', function ()
{
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';


Route::get('/ladder-champions/{game}', 'LeagueChampionsController@getLeagueChampions');
Route::get('/help/obs', 'HelpController@getOBSHelp');

Route::middleware(['auth', 'cache.public'])->prefix('ladder')->group(function ()
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


/*
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
*/

// @TODO: Upgrade
/*
Route::controllers([
    'auth' => 'Auth\AuthController',
    'password' => 'Auth\PasswordController',
]);
*/

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

## Clans
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
