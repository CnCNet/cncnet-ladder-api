## CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.

### Getting started
- Copy `.env-example` to `.env` and configure docker related env.  Copy `cncnet-api/.env-example` to `.env` and configure laravel related env. 
- Build docker image: `docker-compose build`
- Start docker container: `docker-compose up -d`
- Install dependencies and run any migrations inside the app service container: `docker exec cncnet_ladder_app composer install && php artisan migrate`
- Generate laravel key inside the container `docker exec docker exec cncnet_ladder_app php artisan key:generate`. 

### Development
- `docker-compose -f docker-dev-compose.yml -up -d`
- Open up [http://localhost](http://localhost)
- Watch scss for changes: `npm run watch`


### Storage links
Replace user with the host user
`ln -s /home/user/site/storage/app/avatars /home/user/site/public/avatars`

### Production
- `docker-compose build` and `docker-compose up -d`
- Compile latest scss: `npm run prod`

### Built with
- PHP 7.0
- Laravel 5
- Docker (PHP, Nginx, Mysql (Mariadb))

### Useful resources
- [API Endpoints](./API.md)
- [Laravel 5 Documentation](https://laravel.com/docs/5.0/releases)
- [Ladder URL](https://ladder.cncnet.org)