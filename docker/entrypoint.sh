#!/usr/bin/env sh
set -e

cd /var/www

# Ensure writable dirs (on some hosts volume permissions differ)
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# Remove stale cached config/services that may reference dev-only packages (e.g. Pail).
find bootstrap/cache -maxdepth 1 -type f -name "*.php" ! -name ".gitignore" -delete 2>/dev/null || true

# If vendor is missing (common when mounting the project on Windows), install deps into the vendor volume.
if [ ! -f "vendor/autoload.php" ]; then
  echo "vendor/ missing -> running composer install..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

# App key (only if missing and APP_KEY not set)
if [ -z "${APP_KEY:-}" ] && [ -f ".env" ]; then
  if ! grep -q "^APP_KEY=" .env || [ "$(grep "^APP_KEY=" .env | cut -d= -f2 | tr -d ' ')" = "" ]; then
    php artisan key:generate --force || true
  fi
fi

exec "$@"



