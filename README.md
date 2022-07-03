## CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.


### TODO's for upgrade
Find all of them by searching `// @TODO - Upgrade`

- `App\LockedCache\LockedCacheServiceProvider` needs re-adding with Laravel 9

### Development

- Copy `.env-example` to `.env` and configure docker related env.  Copy `src/.env-example` to `.env` and configure laravel related env. 
- Build docker image: `docker-compose -f docker-dev-compose.yml build`
- Start docker container: `docker-compose -f docker-dev-compose.yml up -d`
- Install dependencies and run any migrations inside the app service container: `composer install && php artisan migrate`
- Generate laravel key inside the container `php artisan key:generate`. Re-start container after changing as this will change `.env` values.
- Open up [http://localhost](http://localhost)
- Watch scss for changes: `npm run watch`

### Production
- `docker-compose -f docker-compose.yml build` and `docker-compose up -d`
- Compile latest scss: `npm run prod`

### Built with
- PHP
- Laravel 9
- Docker (PHP 8, Nginx, Mysql)

### Useful resources
- [API Endpoints](./API.md)
- [Laravel 9 Documentation](https://laravel.com/docs/9.x/releases)
- [Ladder URL](https://ladder.cncnet.org)
