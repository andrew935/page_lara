#!/bin/sh
set -e

# Install cron
apt-get update && apt-get install -y cron

# Copy crontab
cp /var/www/docker/scheduler-cron /etc/cron.d/scheduler-cron
chmod 0644 /etc/cron.d/scheduler-cron

# Apply cron job
crontab /etc/cron.d/scheduler-cron

# Create log file
touch /var/log/cron.log

# Start cron
service cron start

# Tail log file to keep container running
tail -f /var/log/cron.log

