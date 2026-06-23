#!/bin/sh
set -e

if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

if ! grep -q "^APP_KEY=" /var/www/html/.env || [ "$(grep '^APP_KEY=' /var/www/html/.env | cut -d= -f2)" = "" ]; then
    php /var/www/html/artisan key:generate --force
fi

php /var/www/html/artisan storage:link --force 2>/dev/null || true
php /var/www/html/artisan package:discover --ansi 2>/dev/null || true
php /var/www/html/artisan migrate --force 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
