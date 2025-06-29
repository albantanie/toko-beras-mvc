#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Setting up Toko Beras Production Environment...${NC}"

# Generate APP_KEY if not exists
if [ -z "$APP_KEY" ]; then
    echo -e "${YELLOW}🔑 Generating APP_KEY...${NC}"
    APP_KEY=$(php artisan key:generate --show 2>/dev/null || echo "base64:$(openssl rand -base64 32)")
    export APP_KEY
fi

echo -e "${GREEN}✅ APP_KEY: $APP_KEY${NC}"

# Create production environment file
echo -e "${YELLOW}📝 Creating production environment file...${NC}"
cat > .env.production << EOF
# Laravel Configuration
APP_NAME="Toko Beras"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080
APP_KEY=$APP_KEY

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database Configuration (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=toko_beras
DB_USERNAME=toko_beras_user
DB_PASSWORD=toko_beras_password

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tokoberas.com"
MAIL_FROM_NAME="\${APP_NAME}"

# File System
FILESYSTEM_DISK=local

# Broadcasting
BROADCAST_DRIVER=log

# Vite Configuration
VITE_APP_NAME="\${APP_NAME}"
EOF

echo -e "${GREEN}✅ Production environment file created: .env.production${NC}"

# Build and start services
echo -e "${YELLOW}🐳 Building and starting Docker services...${NC}"
docker-compose -f docker-compose.production.yml up -d --build

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to start services!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Services started successfully!${NC}"

# Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 30

# Run migrations
echo -e "${YELLOW}🗄️ Running database migrations...${NC}"
docker-compose -f docker-compose.production.yml exec toko-beras php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migrations completed!${NC}"
else
    echo -e "${RED}❌ Migrations failed!${NC}"
fi

# Seed database (optional)
read -p "Do you want to seed the database? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}🌱 Seeding database...${NC}"
    docker-compose -f docker-compose.production.yml exec toko-beras php artisan db:seed --force
    echo -e "${GREEN}✅ Database seeded!${NC}"
fi

# Clear caches
echo -e "${YELLOW}🧹 Clearing caches...${NC}"
docker-compose -f docker-compose.production.yml exec toko-beras php artisan config:clear
docker-compose -f docker-compose.production.yml exec toko-beras php artisan cache:clear
docker-compose -f docker-compose.production.yml exec toko-beras php artisan view:clear

echo -e "${GREEN}✅ Caches cleared!${NC}"

# Show service status
echo -e "${YELLOW}📊 Service Status:${NC}"
docker-compose -f docker-compose.production.yml ps

echo -e "${GREEN}🎉 Production setup completed!${NC}"
echo -e "${YELLOW}🌐 Access your application at: http://localhost:8080${NC}"
echo -e "${YELLOW}📧 Mail interface at: http://localhost:8025${NC}"
echo -e "${YELLOW}📋 To check logs: docker-compose -f docker-compose.production.yml logs${NC}"
echo -e "${YELLOW}🔧 To access container: docker-compose -f docker-compose.production.yml exec toko-beras bash${NC}" 