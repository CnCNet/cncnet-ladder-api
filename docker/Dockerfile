# PHP-FPM is a FastCGI implementation for PHP.
# Read more here: https://hub.docker.com/_/php
FROM php:8.3-fpm

# Args from Docker-compose
ARG user
ARG uid
ARG debug

RUN apt-get update && \
    apt-get install -y --force-yes --no-install-recommends \
    libmemcached-dev \
    libzip-dev \
    libz-dev \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libssl-dev \
    libmcrypt-dev \
    libmagickwand-dev \
    openssh-server \
    git \
    cron \
    nano \
    zip \
    libxml2-dev \
    default-mysql-client

COPY docker/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Install soap extention
RUN docker-php-ext-install soap

# Install for image manipulation
RUN docker-php-ext-install exif

# Install the PHP pcntl extention
RUN docker-php-ext-install pcntl

# Install the PHP zip extention
RUN docker-php-ext-install zip

# Install the PHP pdo_mysql extention
RUN docker-php-ext-install pdo_mysql

# Install the PHP pdo_pgsql extention
RUN docker-php-ext-install pdo_pgsql

# Install the PHP bcmath extension
RUN docker-php-ext-install bcmath

# Install ext-sockets
RUN docker-php-ext-install sockets

#####################################
# Imagick:
#####################################

RUN pecl install imagick && \
    docker-php-ext-enable imagick

#####################################
# mcrypt:
#####################################

RUN pecl install mcrypt && \
    docker-php-ext-enable mcrypt

#####################################
# GD:
#####################################

# Install the PHP gd library
RUN docker-php-ext-install gd && \
    docker-php-ext-configure gd \
    --with-jpeg \
    --with-freetype \
    && docker-php-ext-install gd

#####################################
# xDebug:
#####################################

# Install and enable the xdebug extension when debug
RUN if [ "$debug" = 1 ] ; then \
    pecl install xdebug-3.3.0 && \
    docker-php-ext-enable xdebug; \
fi

#####################################
# PHP Memcached:
#####################################

# Install the php memcached extension
RUN pecl install memcached && docker-php-ext-enable memcached

#####################################
# PHP OPCache:
#####################################

# Install the php opcache extension
RUN if [ "$debug" = 1 ] ; then \
    docker-php-ext-install opcache &&  \
    docker-php-ext-enable opcache \
fi
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /home/$user/site

# Ensure our user is not root
USER $user