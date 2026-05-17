<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot Container Management Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for managing the CnCNet ladder bot
    | container remotely via the admin panel.
    |
    */

    /**
     * Absolute path to the bot's docker-compose.yml file.
     *
     * Example: /opt/cncnet-ladder-bot/docker-compose.yml
     *
     * This path is validated before execution to prevent path traversal attacks.
     * Leave null to disable bot restart functionality.
     */
    'compose_path' => env('BOT_COMPOSE_PATH'),

    /**
     * Rate limit cooldown period in minutes for bot restarts.
     * Prevents abuse by limiting how frequently the bot can be restarted.
     */
    'restart_cooldown_minutes' => env('BOT_RESTART_COOLDOWN', 30),

    /**
     * Whether to show docker compose output to admins.
     * Set to false in production to prevent information disclosure.
     */
    'show_output_to_admins' => env('BOT_SHOW_OUTPUT', false),

];
