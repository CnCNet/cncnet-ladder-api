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
* POST `/result/:game/:player` accepts gameres packet (via POST body) for the supplied `:game`
* GET `/ladder/:game` will return the top 150 leaderboard players for the supplied `:game`
* GET `/ladder/:game/game/:gameId` will return all data for a given `:gameId`
* GET `/ladder/:game/player/:player` will return most data for given `:player`
* GET `/ladder/:game/player/:player/today?hours=N` will return `:player`'s wins, losses and points over the last `N` hours
* GET `/ladder/:game/today?hours=N` will return the top 5 players by net point gain (`best`) and net point loss (`worst`) over the last `N` hours. Each entry has `player`, `wins`, `losses`, `points_won`, `points_lost`, `points` (net) and `games`. Not supported for clan ladders (404)
  * `hours` is optional: default `24`, maximum `48`, anything else returns 422. The window is rolling (ending now) and never starts before the current month, since previous months' ladders are not included. Responses include `hours`, `from` and `to` (ISO 8601, UTC)

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

### Copyright belonging to CnCNet
Using the following images without our consent is strictly forbidden. Do not use these on your own site that is public.
* Player Badges, found in `/public/images/badges`
* Logo