# Kartenant - Production Deployment Guide

Complete guide for deploying Kartenant multi-tenant SaaS to production on Ubuntu/Debian servers.

---

## Table of Contents

1. [Server Requirements](#server-requirements)
2. [Quick Start](#quick-start)
3. [Step-by-Step Manual Installation](#step-by-step-manual-installation)
4. [Database Setup](#database-setup)
5. [Application Configuration](#application-configuration)
6. [Web Server Configuration](#web-server-configuration)
7. [SSL Certificate Setup](#ssl-certificate-setup)
8. [Queue Workers & Scheduled Tasks](#queue-workers--scheduled-tasks)
9. [Post-Deployment Checks](#post-deployment-checks)
10. [Backup & Maintenance](#backup--maintenance)
11. [Troubleshooting](#troubleshooting)

---

## Server Requirements

### Minimum Specifications
- **OS**: Ubuntu 22.04 LTS or Debian 12
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 20GB minimum, 50GB recommended (for backups)
- **CPU**: 2 cores minimum

### Software Stack
- **PHP**: 8.3+
- **PostgreSQL**: 15+ (16 recommended)
- **Node.js**: 20 LTS
- **Nginx**: Latest stable
- **Redis**: Latest stable (for cache and queues)
- **Supervisor**: For queue workers
- **Certbot**: For SSL certificates

---

## Quick Start

### Option A: Automated Installation (Recommended)

```bash
# Download and run deployment script
cd /tmp
wget https://raw.githubusercontent.com/YOUR_REPO/kartenant/main/deploy-production.sh
chmod +x deploy-production.sh
sudo ./deploy-production.sh
```

This script installs all system dependencies automatically.

### Option B: Manual Installation

Follow the [Step-by-Step Manual Installation](#step-by-step-manual-installation) section below.

---

## Step-by-Step Manual Installation

### 1. Update System

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

### 2. Install PHP 8.3

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

# Install PHP and extensions
sudo apt-get install -y \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-common \
    php8.3-pgsql \
    php8.3-zip \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-redis

# Verify installation
php -v
```

### 3. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 4. Install Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
node --version
npm --version
```

### 5. Install PostgreSQL 16

```bash
# Add PostgreSQL repository
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt-get update

# Install PostgreSQL
sudo apt-get install -y postgresql-16 postgresql-contrib-16

# Verify installation
sudo systemctl status postgresql
```

### 6. Install Nginx

```bash
sudo apt-get install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 7. Install Redis

```bash
sudo apt-get install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 8. Install Supervisor

```bash
sudo apt-get install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

---

## Database Setup

### 1. Create PostgreSQL User and Databases

```bash
# Switch to postgres user
sudo -u postgres psql

# In PostgreSQL prompt:
CREATE USER kartenant WITH PASSWORD 'your_secure_password_here';
CREATE DATABASE kartenant_landlord OWNER kartenant;

# Grant privileges
GRANT ALL PRIVILEGES ON DATABASE kartenant_landlord TO kartenant;

# Exit
\q
```

### 2. Configure PostgreSQL for Remote Connections (if needed)

Edit `/etc/postgresql/16/main/postgresql.conf`:
```
listen_addresses = 'localhost'  # Keep localhost only for security
```

Edit `/etc/postgresql/16/main/pg_hba.conf`:
```
# Add this line (adjust IP range as needed)
host    all             kartenant         127.0.0.1/32            scram-sha-256
```

Restart PostgreSQL:
```bash
sudo systemctl restart postgresql
```

---

## Application Configuration

### 1. Clone Repository

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/YOUR_REPO/kartenant.git
cd kartenant
```

### 2. Set Ownership & Permissions

```bash
sudo chown -R www-data:www-data /var/www/kartenant
sudo chmod -R 755 /var/www/kartenant
sudo chmod -R 775 /var/www/kartenant/storage
sudo chmod -R 775 /var/www/kartenant/bootstrap/cache
```

### 3. Configure Environment Variables

```bash
cd /var/www/kartenant
cp .env.example .env
nano .env
```

**Critical .env settings:**

```bash
# Application
APP_NAME="Kartenant"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (Landlord - Main DB)
DB_CONNECTION=landlord
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kartenant_landlord
DB_USERNAME=kartenant
DB_PASSWORD=your_secure_password_here

# Tenant Database Connection (used as template)
LANDLORD_DB_HOST=127.0.0.1
LANDLORD_DB_PORT=5432
LANDLORD_DB_DATABASE=kartenant_landlord
LANDLORD_DB_USERNAME=kartenant
LANDLORD_DB_PASSWORD=your_secure_password_here

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Configure with your SMTP provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Slack Alerts (Optional but recommended)
LOG_SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
LOG_SLACK_USERNAME="Kartenant Security"
LOG_SLACK_EMOJI=:rotating_light:

# Backups
BACKUP_RETENTION_DAYS=7
```

### 4. Install Dependencies

```bash
cd /var/www/kartenant

# Install PHP dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Install Node dependencies and build assets
sudo -u www-data npm install
sudo -u www-data npm run build

# Generate application key
sudo -u www-data php artisan key:generate

# Create storage symlink
sudo -u www-data php artisan storage:link
```

### 5. Run Database Migrations

```bash
# Run landlord migrations (main database)
sudo -u www-data php artisan migrate --database=landlord --force

# Seed superadmin and permissions
sudo -u www-data php artisan db:seed --class="Database\\Seeders\\LandlordAdminSeeder" --database=landlord --force
```

**Default superadmin credentials:**
- Email: `admin@kartenant.com`
- Password: `password`

⚠️ **CRITICAL**: Change this password immediately after first login!

### 6. Optimize Application

```bash
sudo -u www-data php artisan optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan filament:cache-components
```

---

## Web Server Configuration

### 1. Create Nginx Virtual Host

Create `/etc/nginx/sites-available/kartenant`:

```nginx
# Main domain configuration
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com *.yourdomain.com;

    # Redirect to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    # Wildcard domain for multi-tenancy
    server_name yourdomain.com *.yourdomain.com;

    root /var/www/kartenant/public;
    index index.php index.html;

    # SSL certificates (will be configured by certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Logs
    access_log /var/log/nginx/kartenant-access.log;
    error_log /var/log/nginx/kartenant-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Increase timeouts for long-running requests
    proxy_connect_timeout 600s;
    proxy_send_timeout 600s;
    proxy_read_timeout 600s;
    fastcgi_send_timeout 600s;
    fastcgi_read_timeout 600s;

    # Client max body size (file uploads)
    client_max_body_size 100M;

    # PHP-FPM configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 2. Enable Virtual Host

```bash
sudo ln -s /etc/nginx/sites-available/kartenant /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## SSL Certificate Setup

### 1. Install Certbot

```bash
sudo apt-get install -y certbot python3-certbot-nginx
```

### 2. Generate Wildcard Certificate

```bash
# For wildcard subdomain support (requires DNS validation)
sudo certbot certonly --manual --preferred-challenges dns -d yourdomain.com -d *.yourdomain.com

# Follow the instructions to add DNS TXT records
# Wait for DNS propagation (can take 5-10 minutes)
```

### 3. Configure Auto-Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Add cron job for auto-renewal
sudo crontab -e

# Add this line:
0 3 * * * certbot renew --quiet --nginx
```

---

## Queue Workers & Scheduled Tasks

### 1. Configure Supervisor for Queue Workers

Create `/etc/supervisor/conf.d/kartenant-queue.conf`:

```ini
[program:kartenant-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kartenant/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kartenant/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kartenant-queue:*
sudo supervisorctl status
```

### 2. Configure Cron for Scheduled Tasks

```bash
sudo crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/kartenant && php artisan schedule:run >> /dev/null 2>&1
```

This runs:
- Daily database backups at 3:00 AM
- Activity log cleanup
- Backup retention cleanup

---

## Post-Deployment Checks

### 1. Health Check

```bash
curl https://yourdomain.com/health
```

Expected response:
```json
{
  "status": "healthy",
  "services": {
    "database": "ok",
    "cache": "ok",
    "storage": "ok",
    "tenants": "ok"
  }
}
```

### 2. Access Superadmin Panel

Navigate to: `https://yourdomain.com/admin`

Login with:
- Email: `admin@kartenant.com`
- Password: `password`

⚠️ **Change password immediately!**

### 3. Create First Tenant

1. Go to: Sistema > Tenants > Crear Tenant
2. Fill tenant information (name, domain, database name)
3. System will automatically:
   - Create tenant database
   - Run tenant migrations
   - Seed roles/permissions
   - Send onboarding email (if SMTP configured)

### 4. Access Tenant Panel

Navigate to: `https://tenant-domain.yourdomain.com/app`

---

## Backup & Maintenance

### Automated Backups

The system includes automated backup functionality:

**What's backed up:**
- Landlord database (main)
- All tenant databases
- Stored in: `storage/app/backups/daily/YYYY-MM-DD/`

**Backup schedule:**
- Daily at 3:00 AM
- Retention: 7 days
- Old backups automatically cleaned

**Manual backup:**
```bash
cd /var/www/kartenant
sudo -u www-data php artisan backup:tenants
```

### External Backup (Recommended)

⚠️ **CRITICAL**: Automated backups are stored on the same server.

**For production, configure external backup:**

1. **Option A: AWS S3**
   - Configure Laravel Filesystem S3 driver
   - Migrate backup storage to S3

2. **Option B: DigitalOcean Spaces**
   - Similar to S3, compatible API
   - Configure in `config/filesystems.php`

3. **Option C: rsync to External Server**
   ```bash
   # Add to cron
   0 4 * * * rsync -avz /var/www/kartenant/storage/app/backups/ user@backup-server:/backups/kartenant/
   ```

### Restore from Backup

**From Superadmin Panel:**
1. Go to: Sistema > Backups
2. Find the backup you want to restore
3. Click "Preview Contenido" to verify
4. Click "Restaurar Base de Datos"
5. Confirm (type "CONFIRMAR")
6. Wait for completion

**From CLI:**
```bash
cd /var/www/kartenant
sudo -u www-data php artisan backup:restore {backup_log_id}
```

---

## Troubleshooting

### Application Errors

**Check logs:**
```bash
tail -f /var/www/kartenant/storage/logs/laravel.log
```

**Clear caches:**
```bash
cd /var/www/kartenant
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
```

### Database Connection Issues

**Test connection:**
```bash
sudo -u www-data php artisan tinker
>>> DB::connection('landlord')->getPdo();
```

**Check PostgreSQL:**
```bash
sudo systemctl status postgresql
sudo -u postgres psql -l
```

### Queue Workers Not Running

**Check supervisor:**
```bash
sudo supervisorctl status kartenant-queue:*
sudo supervisorctl tail kartenant-queue:kartenant-queue_00
```

**Restart workers:**
```bash
sudo supervisorctl restart kartenant-queue:*
```

### Permission Issues

**Reset permissions:**
```bash
cd /var/www/kartenant
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache
```

### SSL Certificate Issues

**Check certificate:**
```bash
sudo certbot certificates
```

**Renew manually:**
```bash
sudo certbot renew --force-renewal
sudo systemctl reload nginx
```

### High Memory Usage

**Optimize PHP-FPM:**

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:
```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500
```

Restart:
```bash
sudo systemctl restart php8.3-fpm
```

---

## Security Checklist

- [ ] Changed default superadmin password
- [ ] Configured firewall (UFW or iptables)
- [ ] Disabled root SSH login
- [ ] Configured SSH key-only authentication
- [ ] Enabled 2FA for superadmin accounts
- [ ] Configured external backups
- [ ] Set up monitoring (UptimeRobot, Pingdom)
- [ ] Configured Slack alerts for errors
- [ ] Reviewed and limited database user permissions
- [ ] Enabled fail2ban for SSH protection
- [ ] Configured automatic security updates
- [ ] Set strong PostgreSQL passwords
- [ ] Limited PHP execution to necessary directories
- [ ] Configured rate limiting on sensitive endpoints

---

## Performance Optimization

### Enable OPcache

Edit `/etc/php/8.3/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Configure Redis

Edit `/etc/redis/redis.conf`:
```
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### Enable Gzip Compression

Already configured in Nginx config above, verify it's active:
```bash
curl -H "Accept-Encoding: gzip" -I https://yourdomain.com
```

---

## Monitoring & Alerting

### Setup External Monitoring

**UptimeRobot (Free):**
1. Create account at https://uptimerobot.com
2. Add monitor for: `https://yourdomain.com/health`
3. Set check interval: 5 minutes

**Slack Integration:**
- Configured in `.env` with `LOG_SLACK_WEBHOOK_URL`
- Receives critical error alerts automatically
- Test with: `php artisan monitoring:test --type=critical`

---

## Support & Resources

- **GitHub Repository**: https://github.com/YOUR_REPO/kartenant
- **Documentation**: See `CLAUDE.md` for development guide
- **Issues**: Report at GitHub Issues

---

**Last Updated**: 2025-10-22
**Version**: 1.0.0
