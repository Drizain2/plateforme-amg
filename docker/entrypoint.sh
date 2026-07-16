#!/bin/sh
set -e

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch -a database/database.sqlite
fi

if [ "${PROCESS_TYPE:-web}" = "web" ]; then
    php artisan migrate --force
    php artisan db:seed --force --class=RoleSeeder
    php artisan db:seed --force --class=PlatformAdminSeeder
    php artisan db:seed --force --class=PlanSeeder
    php artisan storage:link 2>/dev/null || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

case "${PROCESS_TYPE:-web}" in
    worker)
        exec php artisan queue:work --tries=3 --sleep=3
        ;;
    scheduler)
        exec sh -c 'while true; do php artisan schedule:run --no-interaction; sleep 60; done'
        ;;
    *)
        exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
        ;;
esac