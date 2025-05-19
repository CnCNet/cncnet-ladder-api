#!/bin/bash

set -e

COMPOSE_FILE="docker-dev-compose.yml"
APP_CONTAINER="cncnet_ladder_app"

echo "🧱 Starting containers..."
docker compose -f $COMPOSE_FILE up -d

echo "📦 Installing Laravel dependencies..."
docker exec $APP_CONTAINER composer install

echo "🔐 Generating Laravel app key..."
APP_KEY=$(docker exec $APP_CONTAINER php artisan key:generate --show)

echo "✅ Laravel app key generated: $APP_KEY"

# Optional: automatically update .env file with new key
if grep -q "^APP_KEY=" .env; then
    echo "📝 Updating APP_KEY in .env..."
    sed -i.bak "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
else
    echo "APP_KEY=$APP_KEY" >> .env
    echo "🆕 APP_KEY added to .env."
fi

echo "♻️ Rebuilding containers (after .env change)..."
docker compose -f $COMPOSE_FILE up -d

echo "🧹 Clearing Laravel cache..."
docker exec $APP_CONTAINER php artisan optimize:clear

echo "🗃️ Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate

echo "📟 Opening shell in $APP_CONTAINER..."
docker exec -it $APP_CONTAINER /bin/bash

