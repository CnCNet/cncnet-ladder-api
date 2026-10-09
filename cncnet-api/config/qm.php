<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Casual matchmaking tunnels
    |--------------------------------------------------------------------------
    |
    | CnCNet V2 tunnels that casual matches are relayed through. The server
    | requests ports from the first tunnel that can serve all players of a
    | match, and no match is made while no tunnel is available. For local
    | development, set QM_CASUAL_TUNNELS_ENABLED=false to disable tunnel
    | allocation, in which case players connect to each other directly.
    |
    */

    'casual_tunnels' => env('QM_CASUAL_TUNNELS_ENABLED', true) ? [
        ['ip' => '138.2.138.104', 'port' => 50000, 'name' => 'Frankfurt Relay'],
        ['ip' => '54.36.14.241', 'port' => 50000, 'name' => 'France Kisiek'],
        ['ip' => '23.88.49.17', 'port' => 50000, 'name' => 'Germany Clan-Server'],
        ['ip' => '88.99.76.254', 'port' => 50000, 'name' => 'Fast Server Germany'],
    ] : [],

];
