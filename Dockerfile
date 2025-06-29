# Use an official Node.js runtime as a parent image
FROM node:20-alpine as node

# Set working directory
WORKDIR /var/www

# Set environment variables to fix crypto issues
ENV NODE_OPTIONS="--max-old-space-size=4096"
ENV VITE_CJS_IGNORE_WARNING=true

# Copy package.json and package-lock.json
COPY package*.json ./

# Install npm dependencies (including dev dependencies for build)
RUN npm ci

# Copy the rest of the application files
COPY . .

# Build assets using Vite
RUN npm run build

# Production stage
FROM php:8.3.8-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions one by one to avoid conflicts
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install pgsql
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Copy .env file
COPY .env /var/www/.env

# Copy built assets from Node stage
COPY --from=node /var/www/public/build /var/www/public/build

# Create necessary directories and set permissions
RUN mkdir -p /var/www/storage/app/livewire-tmp \
    && mkdir -p /var/www/storage/framework/{cache,sessions,testing,views} \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Install Composer dependencies
RUN composer install --prefer-dist --no-dev --no-scripts --no-autoloader \
    && composer require laravel/ui \
    && composer dump-autoload --optimize

# Copy configuration files
COPY ./docker/nginx/app.conf /etc/nginx/sites-available/default
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY ./docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create startup script
RUN echo '#!/bin/bash\n\
# Wait for database if needed\n\
# php artisan migrate --force\n\
# Start supervisor\n\
exec supervisord -c /etc/supervisor/supervisord.conf' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start the application
CMD ["/usr/local/bin/start.sh"]
