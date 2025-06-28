# 🚀 Toko Beras - Docker Deployment Guide

## 📋 Overview

Toko Beras adalah sistem manajemen toko beras berbasis web yang dibangun dengan Laravel dan dikemas dalam Docker container untuk deployment yang mudah dan konsisten.

## 🎯 Features

- **🏪 Complete Rice Store Management System**
- **👥 Role-Based Access Control** (Admin, Kasir, Karyawan, Owner, Pelanggan)
- **📊 Real-time Dashboards** with business analytics
- **🛒 E-commerce Frontend** for customer orders
- **📱 Responsive Design** - Mobile-friendly interface
- **🔒 Secure Authentication** with Laravel Sanctum
- **📈 Reports & Analytics** for business insights

## 🐳 Docker Information

- **Image Name**: `toko-beras:latest`
- **Image Size**: 217MB (Alpine-based)
- **Architecture**: Multi-stage production build
- **Base OS**: Alpine Linux
- **Web Server**: Nginx + PHP-FPM
- **Database**: SQLite (included)

## 🚀 Quick Start

### Option 1: Using Deployment Script (Recommended)

```bash
# Start the application
./deploy.sh start

# Check status
./deploy.sh status

# View logs
./deploy.sh logs

# Stop the application
./deploy.sh stop

# Restart the application
./deploy.sh restart
```

### Option 2: Using Docker Commands

```bash
# Start container
docker run -d \
  --name toko-beras-app \
  -p 8080:80 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_URL=http://localhost:8080 \
  --restart unless-stopped \
  toko-beras:latest

# Check status
docker ps | grep toko-beras

# View logs
docker logs -f toko-beras-app

# Stop container
docker stop toko-beras-app && docker rm toko-beras-app
```

### Option 3: Using Docker Compose

```bash
# Start with docker-compose
docker-compose -f docker-compose.production.yml up -d

# Stop with docker-compose
docker-compose -f docker-compose.production.yml down

# View logs
docker-compose -f docker-compose.production.yml logs -f
```

## 🌐 Access Points

After deployment, the application will be available at:

- **🏠 Homepage**: http://localhost:8080
- **🔍 Health Check**: http://localhost:8080/health
- **📊 Admin Dashboard**: http://localhost:8080/admin/dashboard
- **💰 Kasir Dashboard**: http://localhost:8080/kasir/dashboard
- **📦 Karyawan Dashboard**: http://localhost:8080/karyawan/dashboard
- **👑 Owner Dashboard**: http://localhost:8080/owner/dashboard

## 👥 Default Users

The system comes with pre-seeded users for testing:

| Role | Email | Password | Access |
|------|-------|----------|---------|
| Admin | admin@tokoberas.com | password | Full system access |
| Kasir | kasir@tokoberas.com | password | Transaction management |
| Karyawan | karyawan@tokoberas.com | password | Inventory management |
| Owner | owner@tokoberas.com | password | Business analytics |
| Pelanggan | pelanggan@tokoberas.com | password | Shopping interface |

## 🔧 Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | production | Application environment |
| `APP_DEBUG` | false | Debug mode |
| `APP_URL` | http://localhost:8080 | Application URL |
| `DB_CONNECTION` | sqlite | Database connection |
| `CACHE_DRIVER` | file | Cache driver |
| `SESSION_DRIVER` | file | Session driver |

### Port Configuration

- **Default Port**: 8080
- **Container Port**: 80
- **Health Check**: /health endpoint

## 📊 Monitoring

### Health Check

```bash
# Check application health
curl -f http://localhost:8080/health

# Expected response: "healthy"
```

### Container Status

```bash
# Check container status
docker ps | grep toko-beras

# Check container logs
docker logs toko-beras-app

# Check resource usage
docker stats toko-beras-app
```

## 🔄 Backup & Restore

### Backup Image

```bash
# Save image to tar file
docker save toko-beras:latest -o toko-beras-backup.tar

# Backup with compression
docker save toko-beras:latest | gzip > toko-beras-backup.tar.gz
```

### Restore Image

```bash
# Load image from tar file
docker load -i toko-beras-backup.tar

# Load compressed backup
gunzip -c toko-beras-backup.tar.gz | docker load
```

### Data Backup

```bash
# Backup container data
docker cp toko-beras-app:/var/www/html/database ./backup/
docker cp toko-beras-app:/var/www/html/storage ./backup/
```

## 🛠️ Troubleshooting

### Common Issues

1. **Port Already in Use**
   ```bash
   # Use different port
   docker run -p 3000:80 toko-beras:latest
   ```

2. **Container Won't Start**
   ```bash
   # Check logs
   docker logs toko-beras-app
   
   # Check image
   docker images | grep toko-beras
   ```

3. **Health Check Fails**
   ```bash
   # Wait for startup (can take 30-60 seconds)
   sleep 60 && curl http://localhost:8080/health
   ```

### Log Locations

- **Application Logs**: `/var/www/html/storage/logs/`
- **Nginx Logs**: `/var/log/nginx/`
- **PHP-FPM Logs**: `/var/log/php-fpm/`

## 🔒 Security

### Production Considerations

1. **Change Default Passwords** - Update all default user passwords
2. **Environment Variables** - Use secure environment configuration
3. **HTTPS Setup** - Configure SSL/TLS for production
4. **Database Security** - Use external database for production
5. **Firewall Rules** - Restrict access to necessary ports only

### Security Headers

The application includes:
- XSS Protection
- CSRF Protection
- Content Security Policy
- Secure Headers

## 📈 Performance

### Optimizations Included

- **OPcache** - PHP bytecode caching
- **Laravel Caching** - Config, routes, views cached
- **Gzip Compression** - Asset compression
- **Alpine Linux** - Minimal base image
- **Multi-stage Build** - Optimized image size

### Resource Requirements

- **Minimum RAM**: 512MB
- **Recommended RAM**: 1GB
- **CPU**: 1 core minimum
- **Storage**: 1GB minimum

## 🆘 Support

For issues and support:

1. Check the logs: `./deploy.sh logs`
2. Verify health: `curl http://localhost:8080/health`
3. Check container status: `./deploy.sh status`
4. Review this documentation

## 📝 License

This project is licensed under the MIT License.

---

**🎉 Happy Deploying! Your Toko Beras application is ready for production use!**
