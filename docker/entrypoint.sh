#!/bin/sh
set -e

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch -a database/database.sqlite
fi

# Variables réelles fournies par la plateforme d'hébergement à ce stade,
# donc le cache reflète bien l'environnement de production/test.
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
