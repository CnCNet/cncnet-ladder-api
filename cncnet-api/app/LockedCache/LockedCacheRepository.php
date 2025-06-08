<?php namespace App\LockedCache;

use \Illuminate\Cache\Repository;
use Closure;
use Illuminate\Contracts\Cache\Store;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Exception;

class LockedCacheRepository extends Repository {

    protected $lockStore;
    protected $lockFactory;

    public function __construct(Store $store)
    {
        $this->lockStore = new FlockStore(storage_path() . '/locks/');
        $this->lockFactory = new LockFactory($this->lockStore);
        parent::__construct($store);
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  \DateTime|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        // If the item exists in the cache we will just return this immediately
        // otherwise we will execute the given Closure and cache the result
        // of that execution for the given number of minutes in storage.
        if ( ! is_null($value = $this->get($key)))
        {
            return $value;
        }

        $lock = $this->lockFactory->createLock($key);

        $sleepFor = 0.25;
        $tryCount = 8;

        while( (!$lock->acquire()) && $tryCount-- > 0)
        {
            usleep((int)($sleepFor * 1000000));

            if ( ! is_null($value = $this->get($key)))
            {
                return $value;
            }
        }

        try
        {
            $this->put($key, $value = $callback(), $minutes);
            $lock->release();
        }
        catch (Exception $e)
        {
            $lock->release();
            throw $e;
        }

        return $value;
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        // If the item exists in the cache we will just return this immediately
        // otherwise we will execute the given Closure and cache the result
        // of that execution for the given number of minutes. It's easy.
        if ( ! is_null($value = $this->get($key)))
        {
            return $value;
        }

        $lock = $this->lockFactory->createLock($key);

        $sleepFor = 0.25;
        $tryCount = 8;

        while(!$lock->acquire() && $tryCount-- > 0)
        {
            usleep((int)($sleepFor * 1000000));

            if ( ! is_null($value = $this->get($key)))
            {
                return $value;
            }
        }

        try
        {
            $this->forever($key, $value = $callback());
            $lock->release();
        }
        catch (Exception $e)
        {
            $lock->release();
            throw $e;
        }

        return $value;
    }
}
