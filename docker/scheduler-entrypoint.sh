#!/bin/sh
set -e

# Avoid apt prompts in containers
export DEBIAN_FRONTEND=noninteractive

cd /var/www

# Ensure Laravel has a .env file for cron-launched artisan commands.
# Cron jobs often run with a minimal environment; without .env Laravel defaults to sqlite.
if [ ! -f ".env" ] && [ -f "docker/env.docker" ]; then
  cp docker/env.docker .env
  sed -i 's/\r$//' .env
fi

# Install cron
apt-get update && apt-get install -y --no-install-recommends cron

# Copy crontab
cp /var/www/docker/scheduler-cron /etc/cron.d/scheduler-cron
chmod 0644 /etc/cron.d/scheduler-cron
# Normalize line endings in case the repo was edited on Windows (CRLF breaks cron parsing)
sed -i 's/\r$//' /etc/cron.d/scheduler-cron
# Also strip UTF-8 BOM if present (can break cron parsing with "bad minute")
sed -i '1s/^\xEF\xBB\xBF//' /etc/cron.d/scheduler-cron

# Apply cron job
crontab /etc/cron.d/scheduler-cron

# Create log file
touch /var/log/cron.log

# Start cron
cron

# Tail log file to keep container running
tail -f /var/log/cron.log

