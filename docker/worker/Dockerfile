FROM php:8.2-cli

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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd opcache

# Configure opcache for development (disable for real-time code updates)
RUN echo "opcache.enable=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor /var/run

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Default command (can be overridden in docker-compose)
CMD ["php", "artisan", "horizon"]

