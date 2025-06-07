# CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.

[Onboarding new ladders](New%20Ladder%20Onboarding.md)

## Getting started
- Set up docker on your computer (either docker desktop or docker engine with docker-compose)
- Copy `.env-example` to `.env` and configure docker related env.  
- Copy `cncnet-api/.env-example` to `.env` and configure laravel related env.

### Production

> If you are setting up your developpement environment, you can skip this chapter and go to the [Development](#Development) section.

#### Configuration

##### Envs

Configurations are made using `.env` files :

- `.app.env` : file for the laravel containers (app, queue, scheduler)
- `.backup.env` : file for the backup container (`tiredofit/db-backup`)
- `.env` : file for the configurations used in the `docker-compose.yml` file

> These files were split to avoid exposing vars to services that don't needs them and for a better separation of concern.

##### Volumes

The docker-compose.yml will mount a few volumes : 
- `./storage` on `/app/storage` in `app` container to store laravel storage.
- Two directory used by caddy under the hood : `./caddy/data` and `./caddy/config` in `app` container
- The named volume `cncnet-ladder-db` on `/var/lib/mysql` in `mysql` container for the database.
- `./backups` on `/storage` in `db-backups` container to store database backups.
- `./docker/elogen/crontab` in `/etc/cron.d/elogen-cron` in `elogen` container to configure the cron for the elogen.
- `./storage/app/rating` in `/data` in `elogen` container to store the generated files for the elo ranking.

##### Elogen

Elogen can be configured using the crontab file `/docker/elogen/crontab`.

#### Workflow

Deployment to production are automated using GitHub Actions. Everytime a commit is made on the main branch
the workflow "build-and-deploy" run. This workflow consists of two jobs.

##### Build

It will build all containers by using the Dockerfile at `docker/frankenphp`.
There is a container for the **app**, one for the **queues** and one for the **scheduler** (cron). Once all these 3 containers are built, they are 
published to the GitHub Container Registry (ghcr) and tagged with `latest`.

**Containers**
- `cncnet-ladder-app:latest`
- `cncnet-ladder-queue:latest`
- `cncnet-ladder-scheduler:latest`

Once containers are ready, the next job will run.

##### Deploy

This job will simply :

- Uplaod the docker-compose file `docker-compose.yml` to the server in the target directory using scp.
- Connect in ssh and stop the current service, pull the new images and start again the service.
- It will also run `php artisan config:cache` in the containers.

### Development

> If you are setting up a prod or staging environment, you can skip this chapter and go to the [Production](#Production) section.

- Build `docker-compose -f docker-compose.dev.yml build`
- Run `docker-compose -f docker-compose.dev.yml up -d` to build and start the docker container
- Generate laravel key inside the container `docker exec dev_cncnet_ladder_app php artisan key:generate`. This will output the new key, you must manually copy past it into your `cncnet-api/.env`.

> After changing the .env : 
> - rebuild docker with `docker-compose -f .\docker-dev-compose.yml up -d`.
> - clear cache with `docker exec dev_cncnet_ladder_app php artisan optimize:clear`

- Restore a database backup or migrate with `docker exec dev_cncnet_ladder_app php artisan migrate` for an empty database

You are ready to go !

- Open up [http://localhost](http://localhost)
- Watch scss for changes: `npm run watch`
- Access to phpmyadmin: open up [http://localhost:8080](http://localhost:8080)
- Access to database with you dbms : host:localhost port:3307 user:DB_USER pass:DB_PASS
- Open a shell : `docker exec -it dev_cncnet_ladder_app /bin/bash`

#### Queue

By default, the queue does not run in the dev env. You can manually start a queue using the command :

```
docker exec -it dev_cncnet_ladder_app php artisan queue:listen ...
```

#### Scheduler

By default, the cron does not run in the dev env. You can manually start it using the command :

```
docker exec -it dev_cncnet_ladder_app php artisan scheduler:run
```

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

# Sponsored by
<a href="https://www.digitalocean.com/?refcode=337544e2ec7b&utm_campaign=Referral_Invite&utm_medium=opensource&utm_source=CnCNet" title="Powered by Digital Ocean" target="_blank">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/PoweredByDO/DO_Powered_by_Badge_blue.svg" width="201px" alt="Powered By Digital Ocean" />
</a>
