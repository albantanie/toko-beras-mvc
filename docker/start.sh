#!/bin/sh

# Exit on any error
set -e

echo "🚀 Starting Toko Beras Application..."

# Wait for database to be ready (if using external database)
echo "⏳ Checking application readiness..."

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Create required directories
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Generate application key if not exists
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Creating .env file..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 Generating application key..."
    php /var/www/html/artisan key:generate --force
fi

# Clear and cache configuration
echo "⚡ Optimizing application..."
php /var/www/html/artisan config:clear
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear
php /var/www/html/artisan cache:clear

# Run database migrations
echo "🗄️ Running database migrations..."
php /var/www/html/artisan migrate --force

# Seed database if needed (only in development)
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
    echo "🌱 Seeding database..."
    php /var/www/html/artisan db:seed --force
fi

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "🚀 Caching for production..."
    php /var/www/html/artisan config:cache
    php /var/www/html/artisan route:cache
    php /var/www/html/artisan view:cache
fi

# Create storage link
echo "🔗 Creating storage link..."
php /var/www/html/artisan storage:link

# Set final permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "✅ Application ready! Starting services..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
