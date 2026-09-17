FROM node:22-alpine AS frontend

WORKDIR /build

COPY src/package.json ./
RUN npm install

COPY src/ ./
RUN npm run build

FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        chromium \
        nodejs \
        npm \
        unzip \
        libonig-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        bcmath \
        gd \
        mbstring \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY src/composer.json src/composer.lock ./
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY src/ ./
COPY --from=frontend /build/public/build ./public/build
RUN npm install --omit=dev --ignore-scripts \
    && test -x /usr/bin/chromium

RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi

RUN if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate --force; fi \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R ug+rw storage bootstrap/cache database/database.sqlite

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000 --no-reload"]
