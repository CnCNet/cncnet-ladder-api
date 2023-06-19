# PHP-FPM is a FastCGI implementation for PHP.
# Read more here: https://hub.docker.com/_/php
FROM php:7.0-fpm

# Args from Docker-compose
ARG user
ARG uid

RUN apt-get update && \
    apt-get install -y --force-yes --no-install-recommends \
    libmemcached-dev \
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
    libxml2-dev

# Install soap extention
RUN docker-php-ext-install soap

# Install for image manipulation
RUN docker-php-ext-install exif

# Install the PHP mcrypt extention
RUN docker-php-ext-install mcrypt

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
# GD:
#####################################

# Install the PHP gd library
RUN docker-php-ext-install gd && \
    docker-php-ext-configure gd \
    --enable-gd-native-ttf \
    --with-jpeg-dir=/usr/lib \
    --with-freetype-dir=/usr/include/freetype2 && \
    docker-php-ext-install gd

#####################################
# xDebug:
#####################################

# Install the xdebug extension
RUN pecl install xdebug-2.6.0

#####################################
# PHP Memcached:
#####################################

# Install the php memcached extension
RUN pecl install memcached && docker-php-ext-enable memcached


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