#!/usr/bin/env sh
set -e

cd /var/www

# Ensure writable dirs (on some hosts volume permissions differ)
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# App key (only if missing and APP_KEY not set)
if [ -z "${APP_KEY:-}" ] && [ -f ".env" ]; then
  if ! grep -q "^APP_KEY=" .env || [ "$(grep "^APP_KEY=" .env | cut -d= -f2 | tr -d ' ')" = "" ]; then
    php artisan key:generate --force || true
  fi
fi

exec "$@"



