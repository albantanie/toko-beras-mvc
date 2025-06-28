#!/bin/sh

# ===================================================================
# STARTUP SCRIPT UNTUK APLIKASI TOKO BERAS
# ===================================================================
#
# Script ini bertanggung jawab untuk:
# 1. Inisialisasi environment dan permissions
# 2. Setup database dan migrasi
# 3. Optimasi cache untuk production
# 4. Menjalankan semua services melalui Supervisor
#
# Script ini dijalankan sebagai entrypoint utama container
# ===================================================================

# Keluar jika ada error (fail-fast approach)
set -e

echo "🚀 Starting Toko Beras Application..."

# Tunggu dan cek kesiapan aplikasi
# Berguna jika menggunakan external database yang butuh waktu startup
echo "⏳ Checking application readiness..."

# ===================================================================
# SETUP PERMISSIONS DAN DIREKTORI
# ===================================================================

# Set permissions yang tepat untuk direktori Laravel
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage      # Storage Laravel
chown -R www-data:www-data /var/www/html/bootstrap/cache  # Bootstrap cache
chmod -R 775 /var/www/html/storage                   # Read/write untuk web server
chmod -R 775 /var/www/html/bootstrap/cache           # Read/write untuk cache

# Buat direktori yang diperlukan Laravel jika belum ada
mkdir -p /var/www/html/storage/framework/cache/data   # Cache data Laravel
mkdir -p /var/www/html/storage/framework/sessions     # Session storage
mkdir -p /var/www/html/storage/framework/views        # Compiled Blade views
mkdir -p /var/www/html/storage/logs                   # Application logs
mkdir -p /var/www/html/database                       # Database directory

# ===================================================================
# SETUP DATABASE
# ===================================================================

# Buat file database SQLite jika belum ada
# SQLite digunakan untuk kemudahan deployment dan portabilitas
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "📄 Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite      # Read/write untuk owner dan group
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# ===================================================================
# SETUP ENVIRONMENT CONFIGURATION
# ===================================================================

# Buat file .env dari template jika belum ada
# File .env berisi konfigurasi environment-specific
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Creating .env file..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate application key Laravel jika belum ada
# APP_KEY digunakan untuk enkripsi data sensitif
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 Generating application key..."
    php /var/www/html/artisan key:generate --force
fi

# ===================================================================
# DATABASE MIGRATION DAN SETUP
# ===================================================================

# Jalankan migrasi database terlebih dahulu
# --force: jalankan tanpa konfirmasi (untuk production)
echo "🗄️ Running database migrations..."
php /var/www/html/artisan migrate --force

# ===================================================================
# OPTIMASI CACHE DAN PERFORMANCE
# ===================================================================

# Clear semua cache yang ada untuk memastikan fresh start
echo "⚡ Optimizing application..."
php /var/www/html/artisan config:clear    # Clear config cache
php /var/www/html/artisan route:clear     # Clear route cache
php /var/www/html/artisan view:clear      # Clear compiled views
php /var/www/html/artisan cache:clear     # Clear application cache

# ===================================================================
# ENVIRONMENT-SPECIFIC SETUP
# ===================================================================

# Seed database hanya untuk development environment
# Production tidak perlu data dummy
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
    echo "🌱 Seeding database..."
    php /var/www/html/artisan db:seed --force
fi

# Cache konfigurasi untuk production environment
# Meningkatkan performance dengan pre-compile konfigurasi
if [ "$APP_ENV" = "production" ]; then
    echo "🚀 Caching for production..."
    php /var/www/html/artisan config:cache    # Cache config untuk performance
    php /var/www/html/artisan route:cache     # Cache routes untuk performance
    php /var/www/html/artisan view:cache      # Pre-compile Blade templates
fi

# ===================================================================
# FINALISASI SETUP
# ===================================================================

# Buat symbolic link untuk storage public
# Memungkinkan akses file upload melalui web
echo "🔗 Creating storage link..."
php /var/www/html/artisan storage:link

# Set permissions final untuk memastikan web server bisa akses
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "✅ Application ready! Starting services..."

# ===================================================================
# START SERVICES
# ===================================================================

# Jalankan Supervisor sebagai process manager utama
# Supervisor akan mengelola semua services:
# - Nginx (web server)
# - PHP-FPM (application server)
# - Laravel Queue Workers (background jobs)
# - Laravel Scheduler (cron jobs)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
