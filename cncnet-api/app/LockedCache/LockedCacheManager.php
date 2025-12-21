<?php namespace App\LockedCache;

use Illuminate\Cache\CacheManager;
use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use App\LockedCache\LockedCacheRepository;

class LockedCacheManager extends CacheManager {

	/**
	 * Create a new cache repository with the given implementation.
	 *
     * @param Store $store
     * @param array $config
     * @return \Illuminate\Cache\Repository
	 */
	public function repository(Store $store, array $config = [])
	{
		$repository = new LockedCacheRepository($store);

		if ($this->app->bound('Illuminate\Contracts\Events\Dispatcher'))
		{
			$repository->setEventDispatcher(
				$this->app['Illuminate\Contracts\Events\Dispatcher']
			);
		}

		return $repository;
	}
}
