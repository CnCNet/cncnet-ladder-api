#!/usr/bin/env bash

set -e

cd /app

php artisan optimize

php artisan octane:frankenphp