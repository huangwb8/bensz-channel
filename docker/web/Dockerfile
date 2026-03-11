FROM composer:2 AS vendor

WORKDIR /app

# 复制 composer 文件
COPY app/composer.json app/composer.lock ./

# 安装依赖，使用 COMPOSER_VENDOR_DIR 环境变量覆盖本地配置
# 支持本地缓存目录挂载（通过 --mount=type=bind）
ARG COMPOSER_CACHE_DIR
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=${COMPOSER_CACHE_DIR:-/tmp/composer-cache} \
    COMPOSER_VENDOR_DIR=/app/vendor composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:22.12-alpine AS frontend

WORKDIR /app

COPY app/package.json app/package-lock.json ./
COPY app/scripts ./scripts

# 支持本地缓存目录挂载
ARG NPM_CACHE_DIR_WEB
RUN --mount=type=cache,target=/tmp/npm-cache,sharing=locked \
    npm_config_cache=${NPM_CACHE_DIR_WEB:-/tmp/npm-cache} \
    npm ci --ignore-scripts

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

# 复制应用代码（排除 vendor，.dockerignore 会处理）
COPY app ./
# config.toml 已包含在 app/ 目录中，无需单独复制
COPY config /var/www/config

# 从 vendor 阶段复制已安装的依赖
COPY --from=vendor /app/vendor ./vendor

# 从 frontend 阶段复制构建的前端资源
COPY --from=frontend /app/public/build ./public/build

# 复制 Docker 配置
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/web/php-upload.ini /usr/local/etc/php/conf.d/zz-upload.ini
COPY docker/web/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/web/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 运行 Laravel 优化命令和权限设置
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx /var/log/supervisor \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
