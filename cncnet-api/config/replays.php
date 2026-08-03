<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum size of a single replay upload, in megabytes
    |--------------------------------------------------------------------------
    |
    | Uploads larger than this are rejected. The Quick Match client applies the
    | same limit before uploading, so exceeding it here means a malformed or
    | tampered file.
    |
    */

    'max_upload_mb' => env('REPLAY_MAX_UPLOAD_MB', 20),

    /*
    |--------------------------------------------------------------------------
    | Total replay storage budget, in megabytes
    |--------------------------------------------------------------------------
    |
    | Deliberately a size budget rather than a file count, since replay sizes
    | vary a lot with match length. Once stored replays exceed this, the oldest
    | are deleted until the total is back under budget.
    |
    */

    'max_total_mb' => env('REPLAY_MAX_TOTAL_MB', 2000),

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | Must be a private disk. Replays are served through an authorised
    | controller route, never as directly reachable files.
    |
    */

    'disk' => env('REPLAY_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Directory within the disk
    |--------------------------------------------------------------------------
    */

    'directory' => 'replays',

    /*
    |--------------------------------------------------------------------------
    | Restrict downloads to staff
    |--------------------------------------------------------------------------
    |
    | True during the beta so only staff can pull replays. Set to false to open
    | downloads up to any authenticated user once testing is finished.
    |
    */

    'staff_only_downloads' => env('REPLAY_STAFF_ONLY_DOWNLOADS', true),

];
