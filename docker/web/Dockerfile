FROM composer:2 AS vendor

WORKDIR /app

COPY app/composer.json app/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY app ./
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

FROM node:22.12-alpine AS frontend

WORKDIR /app

COPY app/package.json app/package-lock.json ./
COPY app/scripts ./scripts
RUN npm ci

COPY app/resources ./resources
COPY app/public ./public
COPY app/vite.config.js ./vite.config.js
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    curl \
    icu-dev \
    libzip-dev \
    nginx \
    postgresql-dev \
    supervisor \
    $PHPIZE_DEPS \
    && docker-php-ext-install intl opcache pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/web/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/web/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx /var/log/supervisor \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
