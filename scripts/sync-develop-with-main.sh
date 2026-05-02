#!/bin/bash

# Script to sync develop branch with main
# Usage: ./scripts/sync-develop-with-main.sh

set -ex  # Exit on error, print commands

git checkout develop
git pull origin develop

git checkout main
git pull origin main

git checkout develop
git merge main --no-ff -m "Merge main back to develop after release"

git push origin develop
