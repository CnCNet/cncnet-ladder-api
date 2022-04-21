<?php namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

	/**
	 * The application's global HTTP middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [
        'App\Http\Middleware\BlockBadBots',
        'App\Http\Middleware\ApiMiddleware',
        'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
        'Illuminate\Cookie\Middleware\EncryptCookies',
        'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
        // 'App\Http\Middleware\StartLazySession',
        // 'App\Http\Middleware\ShareErrorsLazySession',
        'Illuminate\Session\Middleware\StartSession',
        'Illuminate\View\Middleware\ShareErrorsFromSession',
        'App\Http\Middleware\VerifyCsrfToken',
        'App\Http\Middleware\CorsMiddleware',
        'App\Http\Middleware\HackStatApiHeaders',
	];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [
        'auth' => 'App\Http\Middleware\Authenticate',
        'auth.basic' => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        'auth.basic.once' => 'App\Http\Middleware\AuthenticateOnceWithBasicAuth',
        'guest' => 'App\Http\Middleware\RedirectIfAuthenticated',
        'jwt.auth' => 'Tymon\JWTAuth\Middleware\GetUserFromToken',
        'jwt.refresh' => 'Tymon\JWTAuth\Middleware\RefreshToken',
        'cache.public' => 'App\Http\Middleware\CachePublicMiddleware',
        'cache.private' => 'App\Http\Middleware\CachePrivateMiddleware',
        'cache.long.public' => 'App\Http\Middleware\CacheLongPublicMiddleware',
        'cache.long.private' => 'App\Http\Middleware\CacheLongPrivateMiddleware',
        'cache.short.public' => 'App\Http\Middleware\CacheShortPublic',
        'cache.ultra.public' => 'App\Http\Middleware\CacheUltraShortPublic'
	];
}
