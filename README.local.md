# Local Development Setup

This guide will help you set up the LPG Tamale backend for local development.

## Prerequisites

- Docker and Docker Compose installed
- Git

## Quick Start

1. **Clone the repository** (if not already done)
   ```bash
   git clone <repository-url>
   cd lpg-tamale-backend
   ```

2. **Create local environment file**
   ```bash
   cp .env.local.example .env
   ```

3. **Update .env file**
   - Edit `.env` and update any necessary values
   - The default values should work for local development

4. **Build and start the containers**
   ```bash
   docker-compose -f docker-compose.local.yaml up -d --build
   ```

5. **Generate application key**
   ```bash
   docker-compose -f docker-compose.local.yaml exec app php artisan key:generate
   ```

6. **Run migrations**
   ```bash
   docker-compose -f docker-compose.local.yaml exec app php artisan migrate --seed
   ```

7. **Access the application**
   - API: http://localhost:8080
   - PHPMyAdmin: http://localhost:8081
   - Database: localhost:3306

## Available Services

- **app**: Laravel application (PHP-FPM)
- **nginx**: Web server (port 8080)
- **db**: MySQL database (port 3306)
- **phpmyadmin**: Database management tool (port 8081)

## Common Commands

### Start containers
```bash
docker-compose -f docker-compose.local.yaml up -d
```

### Stop containers
```bash
docker-compose -f docker-compose.local.yaml down
```

### View logs
```bash
docker-compose -f docker-compose.local.yaml logs -f app
```

### Access app container shell
```bash
docker-compose -f docker-compose.local.yaml exec app bash
```

### Run artisan commands
```bash
docker-compose -f docker-compose.local.yaml exec app php artisan <command>
```

### Run composer commands
```bash
docker-compose -f docker-compose.local.yaml exec app composer <command>
```

### Run migrations
```bash
docker-compose -f docker-compose.local.yaml exec app php artisan migrate
```

### Fresh migration with seeding
```bash
docker-compose -f docker-compose.local.yaml exec app php artisan migrate:fresh --seed
```

### Run tests
```bash
docker-compose -f docker-compose.local.yaml exec app php artisan test
```

### Clear caches
```bash
docker-compose -f docker-compose.local.yaml exec app php artisan cache:clear
docker-compose -f docker-compose.local.yaml exec app php artisan config:clear
docker-compose -f docker-compose.local.yaml exec app php artisan route:clear
docker-compose -f docker-compose.local.yaml exec app php artisan view:clear
```

## Database Access

### Using PHPMyAdmin
- URL: http://localhost:8081
- Server: db
- Username: lpg_user (or as set in .env)
- Password: secret (or as set in .env)

### Using MySQL Client
```bash
mysql -h 127.0.0.1 -P 3306 -u lpg_user -p
# Enter password: secret (or as set in .env)
```

### Using Docker
```bash
docker-compose -f docker-compose.local.yaml exec db mysql -u lpg_user -p lpg_tamale
```

## Debugging

The local Dockerfile includes Xdebug for debugging. Configure your IDE to connect to:
- Host: localhost
- Port: 9003
- IDE Key: PHPSTORM

## Troubleshooting

### Permission issues
```bash
docker-compose -f docker-compose.local.yaml exec app chmod -R 777 storage bootstrap/cache
```

### Rebuild containers
```bash
docker-compose -f docker-compose.local.yaml down -v
docker-compose -f docker-compose.local.yaml up -d --build
```

### View container status
```bash
docker-compose -f docker-compose.local.yaml ps
```

## Differences from Production

- **Debug Mode**: Enabled (`APP_DEBUG=true`)
- **Environment**: Set to `local`
- **Volumes**: Full source code mounted for live changes
- **Dependencies**: Includes dev dependencies
- **Xdebug**: Enabled for debugging
- **PHPMyAdmin**: Available for easy database management
- **Ports**: Exposed for direct access
- **Mail**: Uses log driver instead of SMTP
- **Error Reporting**: Full error messages displayed

## Notes

- The local setup uses different container names to avoid conflicts with production
- All data is stored in Docker volumes (`db_data_local`)
- Changes to code are reflected immediately (no rebuild needed)
- Database port is exposed, so you can connect with any MySQL client
