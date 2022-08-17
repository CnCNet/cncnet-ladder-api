# PHP-FPM is a FastCGI implementation for PHP.
# Read more here: https://hub.docker.com/_/php
FROM php:8.0-fpm

# Args from Docker-compose
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    unzip \
    libzip-dev \
    libonig-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql exif pcntl bcmath gd zip iconv mbstring \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd


# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /home/$user/site

# Ensure our user is not root
USER $user
