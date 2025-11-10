#!/bin/bash
set -e

# Make auto-reload script executable
chmod +x /var/www/html/docker/php/auto-reload.sh 2>/dev/null || true

# Start auto-reload watcher in background (only if inotifywait is available)
if command -v inotifywait &> /dev/null; then
    echo "Starting auto-reload watcher..."
    /var/www/html/docker/php/auto-reload.sh &
fi

# Start PHP-FPM in foreground
exec php-fpm

