## CnCNet Ladder API
This is the main repository for the CnCNet Ladder API.


### TODO's for upgrade
Find all of them by searching `// @TODO - Upgrade`
`MaxMind\Db\Reader`. Need to supply db

### Development

- Copy `.env-example` to `.env`
- Build docker image and spin up `docker-compose build` and `docker-compose up -d`
- Install composer dependencies `composer install` within docker container. Enter container by using `docker exec -it <container_id> bash`
- Open up [http://localhost](http://localhost)

### Built with
- PHP
- Laravel 9
- Docker (PHP 8, Nginx, Mysql)

### Useful resources
- [API Endpoints](./API.md)
- [Laravel 9 Documentation](https://laravel.com/docs/9.x/releases)
- [Ladder URL](https://ladder.cncnet.org)
