#!/bin/sh
set -e

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch -a database/database.sqlite
fi

# Variables réelles fournies par la plateforme d'hébergement à ce stade,
# donc le cache reflète bien l'environnement de production/test.
php artisan migrate --force

# Rôles/permissions/plans : seeders idempotents (firstOrCreate), requis
# au fonctionnement de l'app (ex: inscription -> assignRole('admin')).
# Les seeders de données de démo (ShopSeeder et suivants) ne tournent
# pas ici : ils dépendent de fakerphp/faker, qui est en require-dev et
# absent de cette image --no-dev. Créez un compte via /register.
php artisan db:seed --force --class=RoleSeeder
php artisan db:seed --force --class=PlatformAdminSeeder
php artisan db:seed --force --class=PlanSeeder

php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
