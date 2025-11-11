#!/bin/bash

# Don't exit on error for cleanup commands, but do exit for critical failures
set +e

echo "🚀 Setting up Laravel 12 with Horizon and Docker..."

# Stop and remove existing containers if they exist
echo "🧹 Cleaning up existing containers..."
docker-compose down 2>/dev/null || true

# Also stop any orphaned laravel_* containers from previous runs
echo "🧹 Stopping orphaned Laravel containers..."
ORPHANED_CONTAINERS=$(docker ps -a --format "{{.Names}}" | grep "^laravel_" || true)
if [ -n "$ORPHANED_CONTAINERS" ]; then
    echo "$ORPHANED_CONTAINERS" | xargs docker stop 2>/dev/null || true
    echo "$ORPHANED_CONTAINERS" | xargs docker rm 2>/dev/null || true
fi

# Function to stop containers using a specific port
stop_port_conflict() {
    local PORT=$1
    local SERVICE_NAME=$2

    if lsof -i :${PORT} >/dev/null 2>&1; then
        echo "⚠️  Port ${PORT} is in use. Stopping conflicting containers..."

        # Find containers using the port by checking port mappings
        CONFLICTING_CONTAINERS=""
        for container_id in $(docker ps --format "{{.ID}}" 2>/dev/null); do
            if docker port "$container_id" 2>/dev/null | grep -q ":${PORT}"; then
                CONFLICTING_CONTAINERS="${CONFLICTING_CONTAINERS} ${container_id}"
            fi
        done

        if [ -n "$CONFLICTING_CONTAINERS" ]; then
            echo "$CONFLICTING_CONTAINERS" | xargs docker stop 2>/dev/null || true
            echo "$CONFLICTING_CONTAINERS" | xargs docker rm 2>/dev/null || true
        fi

        # Also check for containers by name pattern
        if [ -n "$SERVICE_NAME" ]; then
            # Check for common naming patterns like service-db, service_db, etc.
            # This will catch containers like postgres-db, redis-db, etc.
            for name_pattern in "${SERVICE_NAME}-db" "${SERVICE_NAME}_db" "${SERVICE_NAME}"; do
                CONFLICTING_BY_NAME=$(docker ps -a -q --filter "name=^${name_pattern}$" 2>/dev/null || true)
                if [ -n "$CONFLICTING_BY_NAME" ]; then
                    echo "$CONFLICTING_BY_NAME" | xargs docker stop 2>/dev/null || true
                    echo "$CONFLICTING_BY_NAME" | xargs docker rm 2>/dev/null || true
                fi
            done
        fi
    fi
}

# Check and resolve port conflicts for all services
echo "🔍 Checking for port conflicts..."
stop_port_conflict 6379 "redis"
stop_port_conflict 5432 "postgres"
stop_port_conflict 8080 "nginx"
stop_port_conflict 5050 "pgadmin"
stop_port_conflict 9200 "elasticsearch"
stop_port_conflict 9300 "elasticsearch"

# Build and start containers
echo "📦 Building and starting Docker containers..."
set -e  # Exit on error for critical commands
docker-compose up -d --build
set +e  # Allow errors for non-critical commands

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 15

# Check if app service is running
echo "🔍 Checking if services are running..."
if ! docker-compose ps app | grep -q "Up"; then
    echo "❌ App service is not running. Checking logs..."
    docker-compose logs app | tail -20
    exit 1
fi

# Install PHP dependencies
echo "📥 Installing PHP dependencies..."
COMPOSER_OUTPUT=$(docker-compose exec -T app composer install --no-interaction 2>&1)
COMPOSER_EXIT_CODE=$?

if [ $COMPOSER_EXIT_CODE -ne 0 ]; then
    # Check if the error is due to lock file being out of sync
    if echo "$COMPOSER_OUTPUT" | grep -q "lock file is not up to date\|is not present in the lock file"; then
        echo "⚠️  Lock file is out of sync with composer.json. Updating composer.lock..."
        docker-compose exec -T app composer update --no-interaction --no-scripts || {
            echo "❌ Failed to update composer dependencies"
            exit 1
        }
        echo "✅ Composer lock file updated. Installing dependencies..."
        docker-compose exec -T app composer install --no-interaction || {
            echo "❌ Failed to install PHP dependencies"
            exit 1
        }
    else
        echo "$COMPOSER_OUTPUT"
        echo "❌ Failed to install PHP dependencies"
        exit 1
    fi
else
    echo "$COMPOSER_OUTPUT"
fi

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    docker-compose exec -T app cp .env.example .env 2>/dev/null || echo "Note: .env.example may not exist, please create .env manually"
fi

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate --force || {
    echo "⚠️  Failed to generate application key (may already exist)"
}

# Install Horizon
echo "📊 Installing Laravel Horizon..."
docker-compose exec -T app php artisan horizon:install --force || {
    echo "⚠️  Failed to install Horizon (may already be installed)"
}

# Publish Horizon assets
echo "📦 Publishing Horizon assets..."
docker-compose exec -T app php artisan horizon:publish --force || {
    echo "⚠️  Failed to publish Horizon assets"
}

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force || {
    echo "⚠️  Failed to run migrations"
}

# Set permissions
echo "🔐 Setting storage permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage || true
docker-compose exec -T app chmod -R 775 /var/www/html/storage || true

# Clear cache
echo "🧹 Clearing cache..."
docker-compose exec -T app php artisan config:clear || true
docker-compose exec -T app php artisan cache:clear || true

echo "✅ Setup complete!"
echo ""
echo "🌐 Access your application at: http://localhost:8080/api/documentation"
echo "📊 Access Horizon dashboard at: http://localhost:8080/horizon"
echo "🗄️  Access pgAdmin at: http://localhost:5050"
echo "   Email: admin@admin.com"
echo "   Password: admin"
echo ""
echo "📝 Don't forget to update your .env file with the correct database settings!"

