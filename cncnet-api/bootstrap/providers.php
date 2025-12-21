<?php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,

    Barryvdh\Debugbar\ServiceProvider::class,

    App\Providers\Navigation::class,
    App\Providers\IrcCache::class,
    App\LockedCache\LockedCacheServiceProvider::class,

    Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
    Intervention\Image\ImageServiceProvider::class,
];
