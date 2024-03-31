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
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\BlockBadBots::class,
        // \App\Http\Middleware\ApiMiddleware::class, ???
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
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            //\App\Http\Middleware\StartLazySession::class,
            //\App\Http\Middleware\ShareErrorsLazySession::class,

            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\CorsMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // @TODO: Value needs altering based on how many requests qm client will do in a minute.
            // For now commenting out
            // 'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.basic.once' => \App\Http\Middleware\AuthenticateOnceWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        'cache.public' => \App\Http\Middleware\CachePublicMiddleware::class,
        'cache.private' => \App\Http\Middleware\CachePrivateMiddleware::class,
        'cache.long.public' => \App\Http\Middleware\CacheLongPublicMiddleware::class,
        'cache.long.private' => \App\Http\Middleware\CacheLongPrivateMiddleware::class,
        'cache.short.public' => \App\Http\Middleware\CacheShortPublic::class,
        'cache.ultra.public' => \App\Http\Middleware\CacheUltraShortPublic::class,

        'restrict' => \App\Http\Middleware\Restrict::class,
        'group' => \App\Http\Middleware\Group::class,
    ];
}
