## CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.

### Getting started
- Set up docker on your computer (either docker desktop or docker engine with docker-compose)
- Copy `.env-example` to `.env` and configure docker related env.  
- Copy `cncnet-api/.env-example` to `.env` and configure laravel related env.

#### Production

> If you are setting up your developpement environment, you can skip this chapter and go to the [Development](#Development) section.

- Build docker image: `docker-compose build`
- Start docker container: `docker-compose up -d`
- Install dependencies and run any migrations inside the app service container: `docker exec cncnet_ladder_app composer install && php artisan migrate`
- Generate laravel key inside the container `docker exec cncnet_ladder_app php artisan key:generate`.
- Compile latest scss: `npm run prod`

#### Development

> If you are setting up a prod or staging environment, you can skip this chapter and go to the [Production](#Production) section.

- Run `docker-compose -f docker-dev-compose.yml up -d` to build and start the docker container
- Install dependencies : `docker exec cncnet_ladder_app composer install`
- Generate laravel key inside the container `docker exec cncnet_ladder_app php artisan key:generate`. This will output the new key, you must manually copy past it into your `cncnet-api/.env`.

> After changing the .env : 
> - rebuild docker with `docker-compose -f .\docker-dev-compose.yml up -d`.
> - clear cache with `docker exec cncnet_ladder_app php artisan optimize:clear`

- Restore a database backup or migrate with `docker exec cncnet_ladder_app php artisan migrate` for an empty database

You are ready to go !

- Open up [http://localhost](http://localhost)
- Watch scss for changes: `npm run watch`
- Access to phpmyadmin: open up [http://localhost:8080](http://localhost:8080)
- Access to database with you dbms : host:localhost port:3307 user:DB_USER pass:DB_PASS
- Open a shell : `docker exec -it cncnet_ladder_app /bin/bash`

### Storage links
Replace user with the host user
`ln -s /home/user/site/storage/app/avatars /home/user/site/public/avatars`
`ln -s /home/user/site/storage/app/media /home/user/site/public/media`

### Built with
- PHP 8.3
- Laravel 11
- Docker (PHP, Nginx, Mysql (Mariadb))

### Useful resources
- [API Endpoints](./API.md)
- [Laravel Documentation](https://laravel.com/docs)
- [Ladder URL](https://ladder.cncnet.org)

### Troubleshooting

#### No supported encrypter found. The cipher and / or key length are invalid

You either forgot to generate the key or your env variable are not up to date

- Generate key : `docker exec cncnet_ladder_app php artisan key:generate`
or
- Update env : rebuild docker with `docker-compose -f .\docker-dev-compose.yml up -d`.

### SQLSTATE[42S02]: Base table or view not found ...

Your database is empty or missing tables

- Restore from a backup
or
- Run migrations : `docker exec cncnet_ladder_app php artisan migrate`