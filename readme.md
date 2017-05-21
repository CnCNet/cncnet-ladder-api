# CnCNet Ladder API
This is currently a Work in progress CnCNet Ladder API & UI for Command & Conquer. 
The aim is to support all CnCNet games at https://cncnet.org which feature recording in-game statistics.

## Local Development
* Installing the projects dependencies `npm install` 
* Configure `.env-example` to your local mysql database
* Run `composer install`
* Run `php artisan serve`

## REST v1 API Endpoints
Prefix requests with `api/v1/`
 
###### General Endpoints
* GET `/ping` to ensure that the server is online

###### Ladder Endpoints
* POST `/result/:game` accepts gameres packet (via POST body) for the supplied `:game`
* GET `/ladder/:game` will return the top 150 leaderboard players for the supplied `:game`
* GET `/ladder/:game/game/:gameId` will return all data for a given `:gameId`
* GET `/ladder/:game/player/:player` will return most data for given `:player`

###### Clan Endpoints
* Not yet implemented *

###### User Authentication
* GET `/auth/token` HTTP authentication using Account credentials
Successful authentication of this endpoint will return an auth token for future requests.

###### User Account
* GET `/user/account`
Returns a list of player usernames linked to this account.

###### User Creation
* POST `/user/create` using HTTP authentication
Request requires email, password. Returns token.

###### Player Creation
* POST `/player/:username`
