# ===================================================================
# DOCKERFILE UNTUK APLIKASI TOKO BERAS (RICE STORE MANAGEMENT SYSTEM)
# ===================================================================
#
# Dockerfile ini menggunakan multi-stage build untuk mengoptimalkan
# ukuran image final dan memisahkan proses build frontend dan backend
#
# Arsitektur Build:
# - Stage 1: Frontend Builder (Node.js) - Compile asset frontend
# - Stage 2: PHP Base - Setup aplikasi Laravel dengan dependencies
# - Stage 3: Production - Image final dengan Nginx + PHP-FPM + Supervisor
# ===================================================================

# ===================================================================
# STAGE 1: FRONTEND BUILDER
# ===================================================================
# Stage ini bertanggung jawab untuk:
# - Mengcompile asset frontend (CSS, JS, TypeScript) menggunakan Vite
# - Mengoptimalkan asset untuk production (minifikasi, bundling)
# - Menghasilkan file build yang siap untuk deployment
FROM node:18-alpine AS frontend-builder

# Set working directory untuk proses build frontend
WORKDIR /app

# Copy file konfigurasi package dan build tools
# Dilakukan terpisah untuk memanfaatkan Docker layer caching
COPY package*.json ./          # Package dependencies (npm)
COPY vite.config.ts ./          # Konfigurasi Vite bundler
COPY tsconfig.json ./           # Konfigurasi TypeScript compiler
COPY tailwind.config.js ./      # Konfigurasi Tailwind CSS framework

# Install dependencies Node.js untuk production
# --only=production: hanya install dependencies yang diperlukan untuk production
# npm ci: install berdasarkan package-lock.json (lebih cepat dan konsisten)
RUN npm ci --only=production

# Copy source code frontend yang akan di-compile
COPY resources/ ./resources/    # File Vue/React components, CSS, JS
COPY public/ ./public/          # Asset statis (images, fonts, dll)

# Build dan compile asset frontend untuk production
# Menghasilkan file optimized di folder public/build
RUN npm run build

# ===================================================================
# STAGE 2: PHP BASE APPLICATION
# ===================================================================
# Stage ini bertanggung jawab untuk:
# - Setup environment PHP dengan semua dependencies yang diperlukan
# - Install dan configure PHP extensions untuk Laravel
# - Install Composer dependencies
# - Setup aplikasi Laravel dengan konfigurasi production
FROM php:8.2-fpm-alpine AS php-base

# Install system dependencies yang diperlukan untuk aplikasi
# Menggunakan Alpine Linux package manager (apk)
RUN apk add --no-cache \
    git \                       # Version control (diperlukan Composer)
    curl \                      # HTTP client untuk health checks
    libpng-dev \               # Library untuk image processing
    libxml2-dev \              # Library untuk XML processing
    zip \                      # Archive utility
    unzip \                    # Archive utility
    sqlite \                   # SQLite database client
    sqlite-dev \               # SQLite development headers
    oniguruma-dev \            # Regular expression library (untuk mbstring)
    freetype-dev \             # Font rendering library
    libjpeg-turbo-dev \        # JPEG image library
    libwebp-dev \              # WebP image library
    supervisor \               # Process manager untuk multi-service
    nginx                      # Web server

# Install dan configure PHP extensions yang diperlukan Laravel
# GD extension dikonfigurasi dengan support untuk berbagai format gambar
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo \                      # PHP Data Objects (database abstraction)
    pdo_sqlite \               # SQLite driver untuk PDO
    mbstring \                 # Multi-byte string handling
    exif \                     # EXIF data reading dari images
    pcntl \                    # Process control (untuk queue workers)
    bcmath \                   # Arbitrary precision mathematics
    gd \                       # Image processing library
    xml \                      # XML processing
    soap                       # SOAP protocol support

# Install Composer (PHP dependency manager)
# Copy dari official Composer image untuk mendapatkan versi terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory untuk aplikasi Laravel
WORKDIR /var/www/html

# Copy seluruh source code aplikasi ke container
# Dilakukan setelah install system dependencies untuk optimasi layer caching
COPY . .

# Install PHP dependencies menggunakan Composer
# --no-dev: tidak install dependencies development (testing, debugging)
# --optimize-autoloader: optimasi autoloader untuk performance production
# --no-interaction: tidak meminta input user selama instalasi
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy hasil build frontend dari stage sebelumnya
# File-file yang sudah di-compile dan di-optimize oleh Vite
COPY --from=frontend-builder /app/public/build ./public/build

# Set ownership dan permissions yang tepat untuk aplikasi Laravel
# www-data: user yang digunakan oleh web server (Nginx/Apache)
# 755: read/write untuk owner, read/execute untuk group dan others
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ===================================================================
# STAGE 3: PRODUCTION IMAGE
# ===================================================================
# Stage ini adalah image final yang akan di-deploy ke production
# Berisi semua konfigurasi dan service yang diperlukan untuk menjalankan
# aplikasi Toko Beras secara lengkap dan optimal
FROM php-base AS production

# ===================================================================
# KONFIGURASI WEB SERVER DAN SERVICES
# ===================================================================

# Copy konfigurasi Nginx (web server)
# nginx.conf: konfigurasi global Nginx
# default.conf: konfigurasi virtual host untuk aplikasi Laravel
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy konfigurasi Supervisor (process manager)
# Supervisor mengelola multiple processes dalam satu container:
# - Nginx (web server)
# - PHP-FPM (application server)
# - Laravel Queue Workers (background jobs)
# - Laravel Scheduler (cron jobs)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy konfigurasi PHP-FPM dan PHP
# php-fpm.conf: konfigurasi FastCGI Process Manager
# php.ini: konfigurasi runtime PHP (memory limit, upload size, dll)
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# ===================================================================
# SETUP DIREKTORI DAN PERMISSIONS
# ===================================================================

# Buat direktori yang diperlukan untuk logging dan runtime
RUN mkdir -p /var/log/supervisor \                    # Log Supervisor
    && mkdir -p /var/log/nginx \                      # Log Nginx
    && mkdir -p /var/run/nginx \                      # Runtime files Nginx
    && mkdir -p /var/www/html/storage/logs \          # Log aplikasi Laravel
    && mkdir -p /var/www/html/storage/framework/cache \    # Cache Laravel
    && mkdir -p /var/www/html/storage/framework/sessions \ # Session Laravel
    && mkdir -p /var/www/html/storage/framework/views      # Compiled views Laravel

# ===================================================================
# STARTUP SCRIPT DAN ENTRYPOINT
# ===================================================================

# Copy dan setup startup script
# Script ini akan menjalankan semua inisialisasi yang diperlukan:
# - Setup environment variables
# - Database migration
# - Cache optimization
# - Permission setup
# - Start all services via Supervisor
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# ===================================================================
# NETWORK DAN HEALTH CHECK
# ===================================================================

# Expose port 80 untuk HTTP traffic
# Container akan menerima request di port ini
EXPOSE 80

# Setup health check untuk monitoring container
# --interval=30s: cek setiap 30 detik
# --timeout=3s: timeout setelah 3 detik
# --start-period=5s: tunggu 5 detik sebelum mulai health check
# --retries=3: retry 3 kali sebelum dianggap unhealthy
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# ===================================================================
# CONTAINER ENTRYPOINT
# ===================================================================

# Jalankan startup script sebagai main command
# Script ini akan start semua services dan keep container running
CMD ["/start.sh"]
