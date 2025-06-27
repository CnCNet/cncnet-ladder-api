### Development

> If you are setting up a prod or staging environment, you can skip this chapter and go to the [Production](./README.md/#Production) section.

- Build `docker compose -f docker-compose.dev.yml build`
- Run `docker compose -f docker-compose.dev.yml up -d` to build and start the containers
- Generate laravel key inside the container `docker exec dev_cncnet_ladder_app php artisan key:generate`. This will output the new key, you might manually copy past it into your `cncnet-api/.env`.

> After changing the .env : 
> - rebuild docker with `docker compose -f .\docker-compose.dev.yml up -d`.
> - clear cache with `docker exec dev_cncnet_ladder_app php artisan optimize:clear`

- Restore a database backup or migrate with `docker exec dev_cncnet_ladder_app php artisan migrate` for an empty database

You are ready to go !

- Open up [http://localhost:3000](http://localhost:3000)
- Watch scss for changes: `npm run watch`
- Access to phpmyadmin: open up [http://localhost:8080](http://localhost:8080)
- Access to database with you dbms : host:localhost port:3307 user:DB_USER pass:DB_PASS
- Open a shell : `docker exec -it dev_cncnet_ladder_app bash`

#### Queue

By default, the queue does not run in the dev env. You can manually start a queue using the command :

```
docker exec -it dev_cncnet_ladder_app php artisan queue:listen --queue=findmatch,saveladderresult
```

#### Scheduler

By default, the cron does not run in the dev env. You can manually start it using the command :

```
docker exec -it dev_cncnet_ladder_app php artisan scheduler:run
```

#### npm

Run npm commands:

Start vite dev server
```
docker exec -it dev_cncnet_ladder_app npm run dev
```

> You don't need to manually build assets using `npm run build`. It's automatically done during container build.

## Storage links
Replace user with the host user
`ln -s /home/user/site/storage/app/avatars /home/user/site/public/avatars`
`ln -s /home/user/site/storage/app/media /home/user/site/public/media`

## Built with
- PHP 8.3
- Laravel 11
- Docker (PHP, Nginx, Mysql (Mariadb))

## Useful resources
- [API Endpoints](./API.md)
- [Laravel Documentation](https://laravel.com/docs)
- [Ladder URL](https://ladder.cncnet.org)

## Troubleshooting

### No supported encrypter found. The cipher and / or key length are invalid

You either forgot to generate the key or your env variable are not up to date

- Generate key : `docker exec cncnet_ladder_app php artisan key:generate`
or
- Update env : rebuild docker with `docker-compose -f .\docker-dev-compose.yml up -d`.

## SQLSTATE[42S02]: Base table or view not found ...

Your database is empty or missing tables

- Restore from a backup
or
- Run migrations : `docker exec cncnet_ladder_app php artisan migrate`
