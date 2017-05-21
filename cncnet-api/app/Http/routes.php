<?php

Route::get('/', function () {
    return redirect('ladder/');
});

Route::group(['prefix' => 'ladder/'], function()
{
    Route::get('/', 'LadderController@getLadders');
    Route::get('/{game}', 'LadderController@getLadderIndex');
    Route::get('/{game}/player/', 'LadderController@getLadderIndex');
    Route::get('/{game}/player/{player}', 'LadderController@getLadderPlayer');
    Route::get('/{game}/games/{gameId}', 'LadderController@getLadderGame');
});

Route::controllers
([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);

Route::group(['prefix' => 'account', 'middleware' => 'auth'], function () 
{
    Route::get('/', 'AccountController@getAccountIndex');
    Route::post('/username', 'AccountController@createUsername');
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

    // General Endpoints
    Route::get('/ping', 'ApiLadderController@pingLadder');

    // Player Endpoints
    Route::post('/player/{username}', 'ApiPlayerController@createPlayer');
    
    // Ladder Endpoints
    Route::get('/ladder/{game}', 'ApiLadderController@getLadder');
    Route::get('/ladder/{game}/game/{gameId}', 'ApiLadderController@getLadderGame');
    Route::get('/ladder/{game}/player/{player}', 'ApiLadderController@getLadderPlayer');

    // Debug
    Route::get('/ladder/raw/{rawId}', 'ApiLadderController@viewRawGame');
    Route::get('/ladder/elo/{gameId}', 'ApiLadderController@awardPoints');
});