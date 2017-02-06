<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function(){ return "Api"; });
Route::controllers
([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);

Route::group(['prefix' => 'api/v1/'], function () 
{
    // Auth Endpoints
    Route::get('/auth/{player?}', 'ApiAuthController@getAuth');
    Route::put('/auth/{player}', 'ApiAuthController@putAuth');

    // General Endpoints
    Route::get('/ping', 'ApiLadderController@pingLadder');
    
    // Ladder Endpoints
    // https://github.com/sean3z/cncnet-api
    // :game can be any of the following ^(td|d2k?|ra2?|ts|dta|fs|yr|am)$
    // :gameId can only be numeric (\d+)
    // :player and :clan can be alpha-numeric with some special characters (\w\d\[\])

    Route::post('/ladder/{game}', 'ApiLadderController@postLadder');
    Route::get('/ladder/{game}', 'ApiLadderController@getLadder');
    Route::get('/ladder/{game}/game/{gameId}', 'ApiLadderController@getLadderGame');
    Route::get('/ladder/{game}/player/{player}', 'ApiLadderController@getLadderPlayer');

    // Clan Endpoints
});