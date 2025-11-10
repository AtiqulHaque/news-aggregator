#!/bin/bash

# Script to clear all Laravel caches in Docker container
# This ensures code changes are reflected immediately

echo "Clearing Laravel caches..."

docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan optimize:clear

echo "Caches cleared! Code changes should now be visible."

