#!/usr/bin/env bash

set -e

php artisan optimize

cd /app
while [ true ]
do
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done
