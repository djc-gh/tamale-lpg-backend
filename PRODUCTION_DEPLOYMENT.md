# Production Deployment Guide

## Automatic Deployment Flow (via GitHub Actions)

When you push to `main`:
1. ✅ Builds Docker image (app only - nginx and db use public images)
2. ✅ Pushes to GitHub Container Registry
3. ✅ SSH into server
4. ✅ Pulls latest code
5. ✅ Pulls latest app image
6. ✅ Stops old containers (all: app, nginx, db)
7. ✅ Starts new containers (with correct health checks)
8. ✅ Runs migrations
9. ✅ Clears caches

## How Deployment Works

### Images Used:
- **app**: Your custom Laravel image (built from Dockerfile, pushed to GHCR)
- **nginx**: Public `nginx:alpine` image (standard, no build needed)
- **db**: Public `mysql:8.0` image (standard, no build needed)

### Environment Variables Required on Server

Create `.env` in `/var/DOCKER/lpg-backend/`:

```env
# App
APP_NAME="LPG Tamale"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:your-app-key

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lpg_db
DB_USERNAME=lpg_user
DB_PASSWORD=strong-secure-password-here

# Docker Image (will be set by workflow)
DOCKER_IMAGE=ghcr.io/your-github-username/lpg-tamale-backend:latest
```

## First Time Server Setup

### 1. SSH into Server
```bash
ssh ubuntu@your-server-ip
```

### 2. Create Deploy Directory
```bash
sudo mkdir -p /var/DOCKER/lpg-backend
sudo chown $USER:$USER /var/DOCKER/lpg-backend
cd /var/DOCKER/lpg-backend
```

### 3. Clone Repository
```bash
git clone https://github.com/your-username/lpg-tamale-backend.git .
```

### 4. Create .env File
```bash
cp .env.example .env
nano .env  # Edit with your values
```

### 5. Create Docker Network
```bash
docker network create lpg-network || true
```

### 6. Create Database Volume
```bash
docker volume create lpg_db_data || true
```

### 7. Start All Services
```bash
docker-compose up -d
```

This will start:
- ✅ db (MySQL) - with health check
- ✅ app (Laravel FPM) - waits for db to be healthy
- ✅ nginx (web server) - waits for app to be healthy

### 8. Run Initial Migrations
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan key:generate  # If not in .env
```

### 9. Verify Everything is Running
```bash
docker-compose ps

# Should show:
# NAME          STATUS
# lpg-db        Up (healthy)
# lpg_app       Up (healthy)  
# lpg_nginx     Up (healthy)
```

## Subsequent Deployments (Push to main)

GitHub Actions automatically handles everything:

```bash
# Just push to main
git push origin main
```

The workflow will:
1. Build image
2. Push to GHCR
3. SSH to server and run deployment script

## Manual Deployment (No GitHub)

If you need to deploy without GitHub Actions:

```bash
# On server
cd /var/DOCKER/lpg-backend

# Pull latest code
git pull origin main

# Login to GHCR
echo "your-token" | docker login ghcr.io -u your-username --password-stdin

# Update .env if needed
nano .env

# Pull latest image
docker pull ghcr.io/your-username/lpg-tamale-backend:latest

# Update docker-compose.yaml
export DOCKER_IMAGE=ghcr.io/your-username/lpg-tamale-backend:latest

# Restart services
docker-compose down
docker-compose up -d

# Run migrations if needed
docker-compose exec app php artisan migrate

# Clear caches
docker-compose exec app php artisan cache:clear
```

## Health Checks

All containers have health checks that verify:

- **db**: MySQL responds to ping
- **app**: PHP-FPM responds on port 9000
- **nginx**: HTTP responds on port 80

View health status:
```bash
docker-compose ps
# or
docker ps --format "table {{.Names}}\t{{.Status}}"
```

## Monitoring Logs

```bash
# View all logs
docker-compose logs

# View specific service
docker-compose logs app
docker-compose logs nginx
docker-compose logs db

# Follow logs in real-time
docker-compose logs -f app

# Last 100 lines
docker-compose logs --tail=100 app
```

## Database Persistence

The `db_data` volume is persistent - database survives container restarts:

```bash
# View volumes
docker volume ls

# Inspect volume location
docker volume inspect lpg_db_data
```

## Storage Persistence

Important directories are mounted as volumes:
- `./storage` → `/var/www/storage` (logs, uploads)
- `./bootstrap/cache` → `/var/www/bootstrap/cache`

Make sure these directories exist:
```bash
mkdir -p storage bootstrap/cache
chmod 777 storage bootstrap/cache
```

## Troubleshooting

### Container Won't Start
```bash
docker-compose logs app
docker-compose logs db
docker-compose logs nginx
```

### Database Connection Failed
```bash
# Check if db is healthy
docker-compose ps db

# Test connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data /var/www/storage
docker-compose exec app chmod -R 775 /var/www/storage
```

### Clear Everything and Start Fresh
```bash
docker-compose down -v  # Remove volumes
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

## Rollback to Previous Version

```bash
cd /var/DOCKER/lpg-backend

# View available images
docker images | grep lpg-tamale-backend

# Pull previous version
docker pull ghcr.io/your-username/lpg-tamale-backend:sha-abc123

# Update docker-compose.yaml
DOCKER_IMAGE=ghcr.io/your-username/lpg-tamale-backend:sha-abc123 docker-compose up -d

# Run migrations if needed
docker-compose exec app php artisan migrate
```

## Environment Variables

### Required on Server
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database user
- `DB_PASSWORD` - Database password
- `APP_KEY` - Laravel app key (base64 encoded)

### Recommended on Server
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`

### Set by Workflow
- `DOCKER_IMAGE` - Image to deploy

## Performance Tuning

### Increase Docker Memory Limit
Edit docker-compose.yaml:
```yaml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 2G
  db:
    deploy:
      resources:
        limits:
          memory: 2G
```

### Database Backups
```bash
# Backup database
docker-compose exec db mysqldump -u${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} > backup.sql

# Restore database
docker-compose exec -T db mysql -u${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} < backup.sql
```

## Summary

**Easy deployment in 3 steps:**

1. **Initial Setup**: Run setup script once on server
2. **Code Updates**: Push to main branch
3. **Automatic Deployment**: GitHub Actions handles everything else

**All three containers work together:**
- nginx ← routes traffic
- app ← PHP/Laravel logic
- db ← MySQL database

No manual image builds needed for nginx/db - they use public images!
