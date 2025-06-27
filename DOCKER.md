# 🐳 Docker Setup - Toko Beras

Panduan lengkap untuk menjalankan aplikasi Toko Beras menggunakan Docker.

## 📋 Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Make (optional, untuk kemudahan command)

## 🚀 Quick Start

### Development Environment

```bash
# Clone repository
git clone <repository-url>
cd toko-beras-mvc

# Start development environment
make dev

# Or without make
docker-compose -f docker-compose.dev.yml up -d
```

Aplikasi akan tersedia di:
- **Laravel App**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173

### Production Environment

```bash
# Build and start production
make prod-build

# Or without make
docker-compose up -d --build
```

Aplikasi akan tersedia di: http://localhost:8080

## 📁 Docker Structure

```
docker/
├── nginx/
│   ├── nginx.conf          # Nginx main config
│   └── default.conf        # Server configuration
├── php/
│   ├── php-fpm.conf        # PHP-FPM configuration
│   └── php.ini             # PHP settings
├── supervisor/
│   └── supervisord.conf    # Process management
└── start.sh                # Startup script
```

## 🛠️ Available Commands

### Development
```bash
make dev              # Start development environment
make dev-build        # Build and start development
make dev-down         # Stop development environment
make dev-logs         # Show development logs
make shell-dev        # Access development shell
```

### Production
```bash
make build            # Build production image
make prod             # Start production environment
make prod-build       # Build and start production
make prod-down        # Stop production environment
make prod-logs        # Show production logs
```

### Database
```bash
make migrate          # Run migrations
make seed             # Seed database
make fresh            # Fresh migration with seed
```

### Cache Management
```bash
make cache-clear      # Clear all caches
make cache-optimize   # Optimize for production
```

### Utilities
```bash
make shell            # Access application shell
make logs             # Show application logs
make clean            # Clean up containers
make reset            # Reset everything
make status           # Show container status
make health           # Check application health
```

## 🔧 Configuration

### Environment Variables

Create `.env` file or modify environment in docker-compose:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-app-key-here
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
```

### Database Options

#### SQLite (Default)
```yaml
environment:
  - DB_CONNECTION=sqlite
  - DB_DATABASE=/var/www/html/database/database.sqlite
```

#### MySQL
```yaml
environment:
  - DB_CONNECTION=mysql
  - DB_HOST=mysql
  - DB_PORT=3306
  - DB_DATABASE=toko_beras
  - DB_USERNAME=toko_beras_user
  - DB_PASSWORD=toko_beras_password
```

#### Redis (Optional)
```yaml
environment:
  - CACHE_DRIVER=redis
  - SESSION_DRIVER=redis
  - REDIS_HOST=redis
  - REDIS_PORT=6379
```

## 📊 Monitoring

### Health Check
```bash
curl http://localhost:8080/health
```

### Container Status
```bash
docker-compose ps
```

### Resource Usage
```bash
docker stats
```

### Logs
```bash
# Application logs
docker-compose logs -f app

# Nginx logs
docker-compose exec app tail -f /var/log/nginx/access.log

# PHP-FPM logs
docker-compose exec app tail -f /var/log/php-fpm-error.log
```

## 🔒 Security Features

- **Nginx Security Headers**: XSS protection, CSRF protection
- **PHP Security**: Disabled dangerous functions
- **File Permissions**: Proper ownership and permissions
- **Hidden Files**: Denied access to sensitive files
- **Resource Limits**: Memory and execution time limits

## 🚀 Production Deployment

### Build Optimized Image
```bash
docker build -t toko-beras:v1.0.0 .
```

### Deploy with Docker Swarm
```bash
docker stack deploy -c docker-compose.yml toko-beras
```

### Deploy with Kubernetes
```bash
# Generate Kubernetes manifests
kompose convert -f docker-compose.yml
kubectl apply -f .
```

## 🐛 Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   docker-compose exec app chown -R www-data:www-data /var/www/html/storage
   ```

2. **Database Not Found**
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed
   ```

3. **Cache Issues**
   ```bash
   make cache-clear
   ```

4. **Build Failures**
   ```bash
   docker system prune -f
   make reset
   ```

### Debug Mode
```bash
# Enable debug in development
docker-compose -f docker-compose.dev.yml exec app php artisan tinker
```

## 📈 Performance Optimization

### Production Optimizations
- **OPcache**: Enabled for PHP bytecode caching
- **Nginx Gzip**: Compression for static assets
- **Laravel Caching**: Config, routes, and views cached
- **Asset Optimization**: Minified CSS/JS files

### Scaling
```yaml
# Scale workers
docker-compose up -d --scale app=3
```

## 🔄 Updates

### Update Application
```bash
git pull origin main
make prod-build
```

### Update Dependencies
```bash
docker-compose exec app composer update
docker-compose exec app npm update
make prod-build
```
