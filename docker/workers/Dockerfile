FROM php:8.3-cli-alpine AS base
LABEL authors="rohsyl"
# Set build arguments for custom user
ARG USER=cncnet
ARG UID=1001
ARG GID=1001
# Install dependencies and required PHP extensions
RUN apk add --no-cache \
        bash git curl curl-dev libzip-dev icu-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        libxml2-dev yaml-dev autoconf gcc g++ make \
    && docker-php-source extract \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install excimer \
    && docker-php-ext-enable excimer \
    && docker-php-ext-install \
        pcntl pdo_mysql curl \
        gd mbstring \
        intl xml bcmath \
        zip intl \
    && docker-php-source delete \
    && apk del autoconf gcc g++ make
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Add custom user
RUN addgroup -g ${GID} ${USER} \
    && adduser -u ${UID} -G ${USER} -s /bin/bash -D -H ${USER} \
    && mkdir -p /app \
    && chown -R ${USER}:${USER} /app \
    && chmod 755 /app
# Set working directory
WORKDIR /app
# Copy Composer binary from official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Copy composer files and install dependencies
COPY --chown=${USER}:${USER} ./cncnet-api/composer.json ./cncnet-api/composer.lock ./
USER ${USER}
RUN composer install --no-autoloader
# Copy rest of the application
COPY --chown=${USER}:${USER} ./cncnet-api .

FROM base AS queue
USER root
COPY docker/workers/queue.sh /usr/local/bin/queue
RUN chmod +x /usr/local/bin/queue
USER ${USER}
WORKDIR /app
RUN composer dump-autoload -o
ENTRYPOINT ["/usr/local/bin/queue"]
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD pgrep -f "artisan queue:work" || exit 1

FROM base AS scheduler
USER root
COPY docker/workers/scheduler.sh /usr/local/bin/scheduler
RUN chmod +x /usr/local/bin/scheduler
USER ${USER}
WORKDIR /app
RUN composer dump-autoload -o
ENTRYPOINT ["/usr/local/bin/scheduler"]
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD pgrep -f "artisan schedule:run" || exit 1