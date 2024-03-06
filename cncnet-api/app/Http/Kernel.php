<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\BlockBadBots::class,
        // \App\Http\Middleware\ApiMiddleware::class, ???
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\HackStatApiHeaders::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\StartLazySession::class,
            \App\Http\Middleware\ShareErrorsLazySession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\CorsMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.basic.once' => \App\Http\Middleware\AuthenticateOnceWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        'jwt.auth' => 'Tymon\JWTAuth\Middleware\GetUserFromToken',
        'jwt.refresh' => 'Tymon\JWTAuth\Middleware\RefreshToken',

        'cache.public' => 'App\Http\Middleware\CachePublicMiddleware',
        'cache.private' => 'App\Http\Middleware\CachePrivateMiddleware',
        'cache.long.public' => 'App\Http\Middleware\CacheLongPublicMiddleware',
        'cache.long.private' => 'App\Http\Middleware\CacheLongPrivateMiddleware',
        'cache.short.public' => 'App\Http\Middleware\CacheShortPublic',
        'cache.ultra.public' => 'App\Http\Middleware\CacheUltraShortPublic',

    ];
}
