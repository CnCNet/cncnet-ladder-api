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

> These files were split to avoid exposing vars to services that don't need them and for a better separation of concern.

**`.app.env`** example
```
APP_ENV="production"
APP_DEBUG=false
APP_KEY="..."
APP_URL=https://ladder.cncnet.org

# Laravel mysql config
DB_CONNECTION="mariadb"
DB_HOST="mysql"
DB_DATABASE="cncnet_api"
DB_USERNAME="cncnet"
DB_PASSWORD="cncnet"

CACHE_DRIVER="file"
SESSION_DRIVER="cookie"
QUEUE_DRIVER="redis"

LOG_LEVEL="info"
LOG_CHANNEL="stack"

MAIL_DRIVER="smtp"
MAIL_HOST="mailtrap.io"
MAIL_PORT="2525"
MAIL_USERNAME=null
MAIL_PASSWORD=null

JWT_SECRET=

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

OCTANE_SERVER=frankenphp
```

**``.backup.env``** example
```
# Backups
DB_TYPE="mariadb"
DB_HOST="mysql"
DB_NAME="cncnet_api"
DB_USER="cncnet"
DB_PASS="cncnet"
DB_DUMP_FREQ="1440"
DB_DUMP_BEGIN="0000"
DB_CLEANUP_TIME="8640"
CHECKSUM="SHA1"
COMPRESSION="GZ"
SPLIT_DB="FALSE"
CONTAINER_ENABLE_MONITORING="false"
MYSQL_SINGLE_TRANSACTION="true"
```

**``.env``** example
```
# Docker related env
# Important for permissions, host machine user should match container as we have a volume sharing
HOST_USER=cncnet
HOST_UID=1001
HOST_GID=1001

ENV_SUFFIX=_prod

APP_TAG=latest
APP_PORT=3000

# Mysql config
MYSQL_DATABASE="cncnet_api"
MYSQL_USER="cncnet"
MYSQL_PASSWORD="cncnet"
MYSQL_ALLOW_EMPTY_PASSWORD="false"
MYSQL_ROOT_PASSWORD="yourRandomRootPass"
```

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
[README_DEVELOPER](./README_DEVELOPER.md)

# Sponsored by
<a href="https://www.digitalocean.com/?refcode=337544e2ec7b&utm_campaign=Referral_Invite&utm_medium=opensource&utm_source=CnCNet" title="Powered by Digital Ocean" target="_blank">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/PoweredByDO/DO_Powered_by_Badge_blue.svg" width="201px" alt="Powered By Digital Ocean" />
</a>
