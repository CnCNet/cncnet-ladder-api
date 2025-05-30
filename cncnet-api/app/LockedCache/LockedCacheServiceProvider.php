<?php namespace App\LockedCache;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Cache\Console\CacheTableCommand;
use App\LockedCache\LockedCacheManager;

class LockedCacheServiceProvider extends CacheServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('cache', function($app)
		{
			return new LockedCacheManager($app);
		});

		$this->app->singleton('cache.store', function($app)
		{
			return $app['cache']->driver();
		});

		$this->app->singleton('memcached.connector', function()
		{
			return new MemcachedConnector;
		});

        // this has been removed in 5.4
		//$this->registerCommands();
	}
}
