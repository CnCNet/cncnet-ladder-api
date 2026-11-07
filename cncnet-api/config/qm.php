<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Casual matchmaking factions
    |--------------------------------------------------------------------------
    |
    | The countries of the two factions of a game, keyed by the game of the
    | ladder. Casual matches give the players random countries so that both
    | factions are played equally, using only countries that are allowed on
    | the map. Matches of games without factions keep the sides of the players.
    |
    */

    'casual_factions' => [
        // Red Alert 2 and Yuri's Revenge ladders: the Allied and Soviet countries, like the random
        // side selectors of the client's GameOptions.ini. Yuri is not used.
        'yr' => [
            [0, 1, 2, 3, 4], // America, Korea, France, Germany, Great Britain
            [5, 6, 7, 8],    // Libya, Iraq, Cuba, Russia
        ],
    ],

];
