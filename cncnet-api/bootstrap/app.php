<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_FORWARDED |
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PREFIX |
            Request::HEADER_X_FORWARDED_TRAEFIK
        );
        $middleware->alias([
            'cache.public' => \App\Http\Middleware\CachePublicMiddleware::class,
            'cache.private' => \App\Http\Middleware\CachePrivateMiddleware::class,
            'cache.long.public' => \App\Http\Middleware\CacheLongPublicMiddleware::class,
            'cache.long.private' => \App\Http\Middleware\CacheLongPrivateMiddleware::class,
            'cache.short.public' => \App\Http\Middleware\CacheShortPublic::class,
            'cache.ultra.public' => \App\Http\Middleware\CacheUltraShortPublic::class,

            'restrict' => \App\Http\Middleware\Restrict::class,
            'group' => \App\Http\Middleware\Group::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })
    ->create();
