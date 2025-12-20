# GitHub Actions Deployment Setup Guide

## Overview
This workflow automatically:
1. ✅ Builds a Docker image when you push to `main`
2. ✅ Pushes the image to GitHub Container Registry (GHCR)
3. ✅ Deploys to your remote server
4. ✅ Runs migrations and cache clearing
5. ✅ Verifies the deployment

## Prerequisites

1. **GitHub Container Registry**
   - Automatically available with your GitHub account
   - No additional setup required

2. **Remote Server**
   - SSH access to your production server
   - Git installed on the server
   - Docker & Docker Compose installed on the server

3. **SSH Key**
   - Generate SSH key pair for GitHub Actions:
     ```bash
     ssh-keygen -t ed25519 -f deploy_key -N ""
     ```
   - Add public key to server's `~/.ssh/authorized_keys`

## GitHub Secrets Setup

Go to your repository → Settings → Secrets and variables → Actions and add:

### Required Secrets (4 total)

| Secret Name | Value | Example |
|-------------|-------|---------|
| `REMOTE_HOST` | Your server's IP or domain | `192.168.1.100` |
| `REMOTE_USER` | SSH username on server | `ubuntu` |
| `REMOTE_PORT` | SSH port (usually 22) | `22` |
| `DEPLOY_DIR` | Full path to deploy directory | `/var/DOCKER/lpg-backend` |
| `DEPLOY_SSH_KEY` | Private SSH key content | (Output of `cat deploy_key`) |

**Note:** `GITHUB_TOKEN` is automatically available in all GitHub Actions workflows, so you don't need to create it.

### Step-by-Step Secret Configuration

1. **REMOTE_HOST**
   ```bash
   # Your server IP or domain
   echo "your-server-ip.com"
   ```

2. **REMOTE_USER**
   ```bash
   # The user that will run Docker
   echo "ubuntu"  # or your username
   ```

3. **REMOTE_PORT**
   ```bash
   # Usually 22, unless custom SSH port
   echo "22"
   ```

4. **DEPLOY_DIR**
   ```bash
   # Where the app will be deployed (must exist)
   echo "/var/DOCKER/lpg-backend"
   ```

5. **DEPLOY_SSH_KEY**
   ```bash
   # Paste the entire private key content
   cat deploy_key
   ```

## Server Setup

### 1. Create Deploy Directory
```bash
sudo mkdir -p /var/DOCKER/lpg-backend
sudo chown $USER:$USER /var/DOCKER/lpg-backend
cd /var/DOCKER/lpg-backend
```

### 2. Clone Repository
```bash
git clone https://github.com/your-username/lpg-tamale-backend.git .
```

### 3. Create `.env` File
```bash
cp .env.example .env
# Edit .env with production values
nano .env
```

### 4. Install Docker (if not already installed)
```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

### 5. Add SSH Key to Server
```bash
# On your local machine
cat deploy_key.pub | ssh ubuntu@your-server-ip "cat >> ~/.ssh/authorized_keys"

# Verify connection
ssh -i deploy_key ubuntu@your-server-ip
```

### 6. Configure GitHub Container Registry Access on Server
```bash
# Create a GitHub Personal Access Token (Classic)
# Go to GitHub → Settings → Developer settings → Personal access tokens (classic)
# Select: read:packages scope

# Login to GHCR on your server
echo "YOUR_GITHUB_TOKEN" | docker login ghcr.io -u YOUR_GITHUB_USERNAME --password-stdin

# Or use credentials in docker-compose if needed
docker login ghcr.io
```
6. First Time Manual Setup
```bash
cd /var/DOCKER
cd /home/ubuntu/apps/lpg-backend
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link
```

## Workflow Execution

### Automatic Deployment
```bash
# Just push to main branch
git push origin main
```

The workflow will:
1. Build Docker image (tagged with commit SHA and `latest`)
2. Push to Docker Hub
3. SSH into your server
4. Pull latest code
5. Pull latest Docker image
6. Stop old containers
7. Start new containers
8. Run migrations
9. Clear caches
10. Verify deployment

### View Workflow Status
- Go to repository → Actions tab
- Click on the workflow run
- View real-time logs

## Environment Variables

Your `.env` file on the server should contain:
```env
APP_NAME="LPG Tamale"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lpg_db
DB_USERNAME=lpg_user
DB_PASSWORD=strong-password

DOCKER_BUILDKIT=1
COMPOSE_DOCKER_CLI_BUILD=1
```
you're using GitHub token with `read:packages` scope
- Token should have permission to pull from packages
- Test locally: `docker login ghcr.io -u your-username -p your-token`
- Check if repository is public (private repos need auth)
### SSH Connection Failed
```bash
# Test SSH connection
ssh -i deploy_key -p 22 ubuntu@your-server-ip "echo Connection OK"

# Check known_hosts
ssh-keyscan your-server-ip >> ~/.ssh/known_hosts
```

### Docker Login Failed
- Verify `DOCKER_PASSWORD` is an access token, not password
- Token should have read/write permissions
- Test locally: `docker login -u your-username`

### Migrations Failed
```bash
# SSH into server and debug
ssh ubuntu@your-server-ip
cd /home/ubuntu/apps/lpg-backend
docker-compose exec app php artisan migrate --verbose
```

### Container Won't Start
```bash
# Check logs
docker-compose logs app
docker-compose logs nginx
docker-compose logs db
```

## Security Best Practices

1. ✅ Never commit `.env` files
2. ✅ Use strong database passwords
3. ✅ Rotate SSH keys periodically
4. ✅ Limit SSH access by IP (in firewall)
5. ✅ Use HTTPS on production
6. ✅ Keep Docker images updated
7. ✅ Monitor deployment logs

## Manual Rollback

If deployment goes wrong:
```bash
# SSH into server
ssh ubuntu@your-server-ip

cd /var/DOCKER/lpg-backend

# Check available images
docker images

# Stop current containers
docker-compose down

# Update docker-compose.yaml to use previous image
sed -i 's|image:.*|image: ghcr.io/your-username/lpg-tamale-backend:previous-tag|g' docker-compose.yaml

# Restart with old version
docker-compose up -d
docker-compose exec app php artisan migrate
```

## Next Steps
required secrets to GitHub (4 secrets only)
2. Set up SSH key on server
3. Login to GHCR on server if using private repo
4. Push to `main` branch
5. Watch the Actions tab for deployment progress
6. Verify app is running: `curl https://your-domain.com/api/health`

## GitHub Container Registry Benefits

✅ **Free** - No cost, unlike Docker Hub paid tiers
✅ **Integrated** - Built-in with GitHub, automatic authentication in CI/CD
✅ **Private by Default** - Easy to keep images private
✅ **No Separate Account** - Uses your GitHub account
✅ **Better for Teams** - Integrated permissions with GitHub org
✅ **Automatic Cleanup** - Can set policies for old images
5. Verify app is running: `curl https://your-domain.com/api/health`

## Support

For issues:
1. Check workflow logs in GitHub Actions
2. SSH into server and check container logs
3. Verify all secrets are correctly set
4. Ensure server has sufficient resources (CPU, RAM, disk)
