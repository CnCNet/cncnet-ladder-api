#!/usr/bin/env bash

set -e

cd /app

php artisan optimize

# Default to 'default' if QUEUE_NAME is not set
QUEUE_NAME="${QUEUE_NAME:-default}"

php artisan queue:work \
  --queue="$QUEUE_NAME" \
  --memory=2048 \
  --timeout=60 \
  --tries=3 \
  --backoff=0
