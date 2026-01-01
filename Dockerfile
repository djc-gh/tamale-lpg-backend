FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY --chown=www-data:www-data . .

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Cache configuration for production
RUN php artisan config:cache 2>/dev/null || true
RUN php artisan route:cache 2>/dev/null || true
RUN php artisan view:cache 2>/dev/null || true

# Generate app key if not exists
RUN if [ ! -f .env ]; then cp .env.example .env; fi

USER www-data

EXPOSE 9000