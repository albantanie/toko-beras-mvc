# 🚀 Deployment Guide - Toko Beras MVC

## 📋 Overview

Aplikasi Toko Beras menggunakan GitHub Actions untuk CI/CD otomatis dengan 3 workflow utama:

1. **Continuous Integration** (`ci.yml`) - Testing dan quality checks
2. **Staging Deployment** (`staging.yml`) - Deploy ke staging environment
3. **Production Deployment** (`deploy.yml`) - Deploy ke production server

## 🔧 Setup GitHub Secrets

Sebelum menggunakan GitHub Actions, setup secrets berikut di repository:

### Required Secrets

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `SSH_PRIVATE_KEY` | SSH private key untuk akses server | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SSH_HOST` | IP address atau hostname server | `faiz-dev.com` |
| `SSH_USER` | Username SSH | `faiz` |
| `APP_KEY` | Laravel application key | `base64:generated_key_here` |
| `DB_PASSWORD` | Password database PostgreSQL | `secure_password_123` |

### Setup SSH Key

1. **Generate SSH Key** (jika belum ada):
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@toko-beras"
   ```

2. **Copy Public Key ke Server**:
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519.pub faiz@your-server-ip
   ```

3. **Add Private Key ke GitHub Secrets**:
   ```bash
   cat ~/.ssh/id_ed25519
   ```
   Copy output dan paste ke GitHub → Repository → Settings → Secrets → Actions → New secret

## 🌊 Workflow Details

### 1. Continuous Integration (ci.yml)

**Trigger**: Push/PR ke `main` atau `develop`

**Jobs**:
- **Lint**: Code quality checks (PHP CS Fixer, PHPStan, ESLint)
- **Test**: Unit tests dengan multiple PHP versions
- **Security**: Security audit untuk dependencies
- **Build**: Build frontend assets
- **Docker**: Test Docker image build

**Features**:
- ✅ Multi-PHP version testing (8.1, 8.2)
- ✅ Code coverage dengan Codecov
- ✅ Security scanning
- ✅ Docker build testing
- ✅ Asset build verification

### 2. Staging Deployment (staging.yml)

**Trigger**: Push ke `develop` branch

**Environment**: `https://staging.toko-beras.faiz-dev.com`

**Flow**:
1. Run tests
2. Deploy ke staging server
3. Verify deployment
4. Run E2E tests
5. Health checks

**Features**:
- ✅ Automatic staging deployment
- ✅ Database seeding dengan test data
- ✅ E2E testing
- ✅ Health monitoring
- ✅ Rollback on failure

### 3. Production Deployment (deploy.yml)

**Trigger**: Push ke `main` branch

**Environment**: `http://faiz-dev.com:8080`

**Flow**:
1. Run comprehensive tests
2. Create backup
3. Deploy to production
4. Run migrations
5. Verify deployment
6. Auto-rollback on failure

**Features**:
- ✅ Zero-downtime deployment
- ✅ Automatic backup
- ✅ Database migrations
- ✅ Health verification
- ✅ Automatic rollback
- ✅ Docker cleanup

## 📁 Server Directory Structure

```
/home/faiz/docker/
├── toko-beras-compose/          # Production
│   ├── docker-compose.yml
│   ├── .env
│   └── ...
├── toko-beras-staging/          # Staging
│   ├── docker-compose.yml
│   ├── .env
│   └── ...
└── toko-beras-backup-*/         # Automatic backups
```

## 🔄 Deployment Process

### Staging Deployment

1. **Push ke develop branch**:
   ```bash
   git checkout develop
   git add .
   git commit -m "feat: new feature"
   git push origin develop
   ```

2. **GitHub Actions akan**:
   - Run tests
   - Deploy ke staging
   - Run E2E tests
   - Notify hasil

3. **Access staging**: `https://staging.toko-beras.faiz-dev.com`

### Production Deployment

1. **Merge ke main branch**:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```

2. **GitHub Actions akan**:
   - Run comprehensive tests
   - Create backup
   - Deploy to production
   - Verify deployment

3. **Access production**: `http://faiz-dev.com:8080`

## 🛠️ Manual Deployment

Jika perlu deploy manual:

### Production

```bash
ssh faiz@your-server-ip
cd /home/faiz/docker/toko-beras-compose
git pull origin main
docker compose down
docker compose pull
docker compose up -d --build
```

### Staging

```bash
ssh faiz@your-server-ip
cd /home/faiz/docker/toko-beras-staging
git pull origin develop
docker compose down
docker compose pull
docker compose up -d --build
```

## 🔍 Monitoring & Debugging

### Check Deployment Status

```bash
# Check containers
docker compose ps

# Check logs
docker compose logs -f app
docker compose logs -f nginx

# Check application health
curl https://toko-beras.faiz-dev.com/health
```

### Common Issues

1. **Container not starting**:
   ```bash
   docker compose logs app
   docker compose exec app php artisan config:clear
   ```

2. **Database connection issues**:
   ```bash
   docker compose exec toko-beras php artisan migrate:status
   docker compose exec postgres psql -U toko_beras_user -d toko_beras
   ```

3. **Permission issues**:
   ```bash
   docker compose exec toko-beras chown -R www-data:www-data storage bootstrap/cache
   ```

## 📊 Environment URLs

| Environment | URL | Purpose |
|-------------|-----|---------|
| Production | `http://faiz-dev.com:8080` | Live application |
| Staging | `http://staging.faiz-dev.com:8081` | Testing environment |
| Mailhog (Production) | `http://faiz-dev.com:8025` | Email testing |
| Mailhog (Staging) | `http://staging.faiz-dev.com:8026` | Email testing |

## 🔐 Security Considerations

1. **SSH Keys**: Use ed25519 keys dengan passphrase
2. **Secrets**: Rotate secrets secara berkala
3. **Database**: Use strong passwords
4. **SSL**: Ensure HTTPS untuk semua environments
5. **Firewall**: Restrict access ke necessary ports only

## 📝 Troubleshooting

### GitHub Actions Failing

1. **Check secrets**: Pastikan semua secrets sudah diset
2. **SSH access**: Test SSH connection manual
3. **Server space**: Check disk space di server
4. **Docker**: Ensure Docker daemon running

### Deployment Issues

1. **Check logs**: `docker compose logs -f`
2. **Verify environment**: Check `.env` file
3. **Database**: Ensure database accessible
4. **Permissions**: Check file permissions

## 🎯 Best Practices

1. **Always test di staging** sebelum production
2. **Use feature branches** untuk development
3. **Write tests** untuk semua features baru
4. **Monitor deployments** dan check health
5. **Keep backups** dan test restore process
6. **Document changes** di commit messages
7. **Review code** sebelum merge ke main

## 📞 Support

Jika ada issues dengan deployment:

1. Check GitHub Actions logs
2. Check server logs
3. Verify secrets dan configuration
4. Test manual deployment steps
5. Contact DevOps team jika diperlukan
