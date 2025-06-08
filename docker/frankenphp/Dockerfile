# Stage base
# Install php extensions and composer dependencies
FROM dunglas/frankenphp:php8.3 AS base
LABEL authors="rohsyl"
ARG USER=cncnet
ARG UID=1001
ARG GID=1001
ENV SERVER_NAME="http://"
RUN install-php-extensions \
 pcntl pdo_mysql curl yaml \
 gd mbstring \
 intl xml bcmath \
 zip intl readline redis sockets && \
 pecl install excimer && \
 mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN useradd -u ${UID} -U -d /app ${USER} && \
    setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
    chown -R ${USER}:${USER} /data/caddy /config/caddy /app && \
    chmod 775 /app

# Stage phpbuilder
# Copy code and Install composer dependencies
FROM base AS phpbuilder
WORKDIR /app
USER ${USER}
COPY --chown=${USER}:${USER} ./cncnet-api/composer.json ./cncnet-api/composer.lock ./
RUN composer install --no-autoloader
COPY --chown=${USER}:${USER} ./cncnet-api .


# Stage : assetsbuilder
# Install nodejs and npm to build the assets
FROM node:22 AS assetsbuilder
WORKDIR /app
COPY ./cncnet-api/package*.json .
RUN npm install
COPY --from=phpbuilder /app .
RUN ls -l node_modules/.bin/vite && file node_modules/.bin/vite && cat -A node_modules/.bin/vite
RUN npm run build


# Stage queue
# Run the queue using supervisor
FROM phpbuilder AS queue
USER root
COPY docker/frankenphp/queue.sh /usr/local/bin/queue
RUN chmod +x /usr/local/bin/queue
WORKDIR /app
USER ${USER}
RUN composer dump-autoload -o
ENTRYPOINT ["/usr/local/bin/queue"]
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 CMD pgrep -f "artisan queue:work" > /dev/null || exit 1


# Stage scheduler
# Run the scheduler using supervisor
FROM phpbuilder AS scheduler
USER root
COPY docker/frankenphp/scheduler.sh /usr/local/bin/scheduler
RUN chmod +x /usr/local/bin/scheduler
WORKDIR /app
USER ${USER}
RUN composer dump-autoload -o
ENTRYPOINT ["/usr/local/bin/scheduler"]
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 CMD pgrep -f 'php artisan schedule:run' || exit 1


# Stage : app
# Build the app with needed composer dependencies, add assets dependencies
# and run laravel octane to serve the app
FROM phpbuilder AS app
COPY --from=assetsbuilder --chown=${USER}:${USER} /app/public /app/public
WORKDIR /app
USER ${USER}
RUN composer dump-autoload -o
EXPOSE 8000
ENTRYPOINT ["php", "/app/artisan", "octane:frankenphp"]
HEALTHCHECK --start-period=5s --interval=10s --timeout=5s --retries=8 CMD php /app/artisan octane:status || exit 1

# Stage : development
# Set up the app for dev purpose.
# and run laravel octane to serve the app
FROM base AS development
USER root
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g chokidar-cli
USER ${USER}
WORKDIR /app
RUN pwd
RUN ls -la
EXPOSE 8000
ENTRYPOINT ["php", "/app/artisan", "octane:frankenphp"]
HEALTHCHECK --start-period=5s --interval=10s --timeout=5s --retries=8 CMD php /app/artisan octane:status || exit 1