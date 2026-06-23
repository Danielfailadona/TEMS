FROM node:22-slim AS node-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js index.html ./
COPY resources/ resources/
RUN npm run build

FROM composer:2 AS composer-build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --ignore-platform-req=ext-gd --no-scripts
COPY --from=node-build /app/public/build /app/public/build

FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    postgresql-dev \
    oniguruma-dev \
    libzip-dev \
    libpng-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    bcmath \
    gd \
    zip \
    opcache \
    && rm -rf /var/cache/apk/*

COPY --from=composer-build /app /var/www/html
COPY . /var/www/html

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

RUN mkdir -p /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && touch /var/www/html/storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN rm -rf /var/www/html/node_modules /var/www/html/.env /var/www/html/docker/nginx.conf \
    /var/www/html/docker/supervisord.conf

COPY docker/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80
CMD ["start-container"]
