#!/bin/bash

# Auto-reload script for Laravel development
# Watches for file changes and reloads PHP-FPM

echo "$(date '+%Y-%m-%d %H:%M:%S') - Starting auto-reload watcher..."

# Directories to watch
WATCH_DIRS="/var/www/html/app /var/www/html/config /var/www/html/routes /var/www/html/database"

# Function to reload PHP-FPM
reload_php_fpm() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - File change detected: $FILE ($EVENT)"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Reloading PHP-FPM..."
    
    # Find PHP-FPM master process and send USR2 signal
    PHP_FPM_PID=$(pgrep -f "php-fpm: master process" | head -1)
    if [ -n "$PHP_FPM_PID" ]; then
        kill -USR2 "$PHP_FPM_PID" 2>/dev/null && \
        echo "$(date '+%Y-%m-%d %H:%M:%S') - PHP-FPM reloaded successfully (PID: $PHP_FPM_PID)" || \
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Failed to reload PHP-FPM"
    else
        echo "$(date '+%Y-%m-%d %H:%M:%S') - PHP-FPM master process not found"
    fi
}

# Watch for file changes
inotifywait -m -r -e modify,create,delete,move \
    --format '%w%f %e' \
    $WATCH_DIRS 2>/dev/null | while read FILE EVENT; do
    
    # Ignore certain files/directories
    if [[ "$FILE" == *".git"* ]] || \
       [[ "$FILE" == *"node_modules"* ]] || \
       [[ "$FILE" == *"vendor"* ]] || \
       [[ "$FILE" == *"storage/logs"* ]] || \
       [[ "$FILE" == *"storage/framework/cache"* ]] || \
       [[ "$FILE" == *"storage/framework/views"* ]] || \
       [[ "$FILE" == *".env"* ]] || \
       [[ "$FILE" == *"storage/api-docs"* ]]; then
        continue
    fi
    
    # Only reload for PHP files, config files, or route files
    if [[ "$FILE" == *.php ]] || \
       [[ "$FILE" == *"config/"* ]] || \
       [[ "$FILE" == *"routes/"* ]] || \
       [[ "$FILE" == *"database/"* ]]; then
        reload_php_fpm
    fi
done

