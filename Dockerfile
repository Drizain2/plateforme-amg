# syntax=docker/dockerfile:1

############################################
# Stage 1 : build (Composer + Node/Vite + Wayfinder)
# Tout ce qui sert uniquement à compiler n'atterrit pas dans l'image finale.
############################################
FROM php:8.4-cli-bookworm AS build

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_sqlite pdo_mysql mbstring gd exif zip bcmath pcntl intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends curl ca-certificates gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Couche mise en cache séparément : si seul le code change (pas composer.json),
# Docker réutilise les dépendances déjà installées.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .

# .env minimal : sert uniquement à ce que `artisan` puisse démarrer pendant le
# build (le plugin Vite Wayfinder appelle `php artisan wayfinder:generate`).
# Remplacé entièrement par les vraies variables d'env au démarrage du conteneur.
RUN cp .env.example .env \
    && php artisan key:generate --ansi \
    && touch database/database.sqlite \
    && php artisan package:discover --ansi

COPY package.json package-lock.json ./
RUN npm ci

RUN npm run build

############################################
# Stage 2 : runtime (image finale, sans Node ni outils de build)
############################################
FROM php:8.4-cli-bookworm AS runtime

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_sqlite pdo_mysql mbstring gd exif zip bcmath pcntl intl opcache

WORKDIR /app

COPY . .
COPY ca.pem /app/ca.pem
COPY --from=build /app/vendor ./vendor
COPY --from=build /app/public/build ./public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
