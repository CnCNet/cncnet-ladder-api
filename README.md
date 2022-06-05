## CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.


### TODO's for upgrade
Find all of them by searching `// @TODO - Upgrade`

- `MaxMind\Db\Reader`. Need to supply db path
- `App\LockedCache\LockedCacheServiceProvider` needs re-adding with Laravel 9
- Kernel commands need re-aligning to Laravel 9 
```
// 'App\Console\Commands\PruneRawLogs',
// 'App\Console\Commands\PruneOldStats',
// 'App\Console\Commands\UpdatePlayerCache',
// 'App\Console\Commands\GenerateBulkRecords',
// 'App\Console\Commands\UpdateIrc',
// 'App\Console\Commands\AprilFoolsPurge',
// 'App\Console\Commands\CleanupQmMatchPlayers',
// 'App\Console\Commands\CleanupQmMatches',
// 'App\Console\Commands\CleanupGameReports',
```
- SCSS source needs re-adding and utilised with Laravel mix.

### Development

- Copy `.env-example` to `.env`
- Build docker image and spin up `docker-compose -f docker-dev-compose.yml build` and `docker-compose -f docker-dev-compose.yml up -d`
- Install composer dependencies `composer install` within docker container. Enter container by using `docker exec -it <container_id> bash`
- Open up [http://localhost](http://localhost)

### Production
- `docker-compose -f docker-compose.yml build` and `docker-compose up -d`

### Built with
- PHP
- Laravel 9
- Docker (PHP 8, Nginx, Mysql)

### Useful resources
- [API Endpoints](./API.md)
- [Laravel 9 Documentation](https://laravel.com/docs/9.x/releases)
- [Ladder URL](https://ladder.cncnet.org)
