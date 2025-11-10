FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    supervisor \
    inotify-tools \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor /var/run

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Copy entrypoint and auto-reload scripts
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/php/auto-reload.sh /var/www/html/docker/php/auto-reload.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /var/www/html/docker/php/auto-reload.sh

# Expose port 9000
EXPOSE 9000

# Use custom entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

