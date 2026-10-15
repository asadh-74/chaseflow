#!/bin/sh
# Runs inside the Render web container on every deploy.
set -e
cd /var/www/html

echo "ChaseFlow deploy: caching config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "ChaseFlow deploy: linking storage"
php artisan storage:link || true

echo "ChaseFlow deploy: running migrations"
php artisan migrate --force

echo "ChaseFlow deploy: done"
