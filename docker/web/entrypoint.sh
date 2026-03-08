#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --ansi
fi

echo "Waiting for PostgreSQL..."
until php -r 'try { new PDO("pgsql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo "ok"; } catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }' >/dev/null 2>&1; do
  sleep 2
done

mkdir -p storage/app/private
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

php artisan optimize:clear --ansi
php artisan migrate --force --ansi
php artisan db:seed --class=SystemBootstrapSeeder --force --ansi
php artisan storage:link --ansi || true
mkdir -p public/$(php -r 'echo trim((string) getenv("STATIC_SITE_OUTPUT_DIR") ?: "static", "/");')
chown -R www-data:www-data storage bootstrap/cache public/$(php -r 'echo trim((string) getenv("STATIC_SITE_OUTPUT_DIR") ?: "static", "/");')
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
php artisan site:build-static --ansi
chown -R www-data:www-data public/$(php -r 'echo trim((string) getenv("STATIC_SITE_OUTPUT_DIR") ?: "static", "/");')

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
