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
     * Absolute path to the bot restart script.
     *
     * Example: /usr/local/bin/restart-ladder-bot
     *
     * This script should be executable and configured in sudoers:
     * www-data ALL=(root) NOPASSWD: /usr/local/bin/restart-ladder-bot
     *
     * Leave null to disable bot restart functionality.
     */
    'restart_script_path' => env('BOT_RESTART_SCRIPT', '/usr/local/bin/restart-ladder-bot'),

    /**
     * Rate limit cooldown period in minutes for bot restarts.
     * Prevents abuse by limiting how frequently the bot can be restarted.
     */
    'restart_cooldown_minutes' => env('BOT_RESTART_COOLDOWN', 30),

    /**
     * Process timeout in seconds for restart operation.
     * Prevents hanging processes from blocking the system.
     */
    'restart_timeout_seconds' => env('BOT_RESTART_TIMEOUT', 120),

];
