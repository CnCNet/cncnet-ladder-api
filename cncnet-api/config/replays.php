<?php

return [

    // Largest single replay upload accepted, in megabytes. The Quick Match client applies the same
    // limit before uploading, so exceeding it means a malformed or tampered file.
    'max_upload_mb' => env('REPLAY_MAX_UPLOAD_MB', 20),

    // Total replay storage budget, in megabytes. A size budget rather than a file count, since
    // replay sizes vary a lot with match length. Oldest replays are evicted to stay under it.
    'max_total_mb' => env('REPLAY_MAX_TOTAL_MB', 2000),

    // Must be a private disk - replays are only ever handed out through ReplayController.
    'disk' => env('REPLAY_DISK', 'local'),

    'directory' => 'replays',

];
