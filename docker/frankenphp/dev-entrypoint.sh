#!/bin/bash
set -xe

# Optional: do things like migrations, logs, permissions, etc.
# echo "Running pre-flight setup..."

# Run the command passed to the container
composer install
exec "$@"
