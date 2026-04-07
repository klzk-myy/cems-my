# CEMS-MY Deployment Guide

**Currency Exchange Management System - Malaysia**

**Version**: 1.1  
**Last Updated**: April 2026  
**Environment**: Production

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Server Requirements](#2-server-requirements)
3. [Installation Steps](#3-installation-steps)
4. [Configuration](#4-configuration)
5. [Security Setup](#5-security-setup)
6. [Database Setup](#6-database-setup)
7. [Web Server Configuration](#7-web-server-configuration)
8. [SSL/TLS Setup](#8-ssltls-setup)
9. [Backup Strategy](#9-backup-strategy)
10. [Monitoring & Logging](#10-monitoring--logging)
11. [Troubleshooting](#11-troubleshooting)
12. [Maintenance](#12-maintenance)

---

## 1. Prerequisites

### Required Knowledge
- Linux server administration (Ubuntu/CentOS)
- PHP and Laravel framework basics
- MySQL/PostgreSQL database management
- Web server configuration (Apache/Nginx)
- SSL certificate management
- Basic networking and security concepts

### Required Access
- Root or sudo access to server
- Domain DNS management
- Database server access
- Firewall configuration access

---

## 2. Server Requirements

### Minimum Hardware Specifications

```
Component          Minimum        Recommended
─────────────────────────────────────────────
CPU                2 cores        4+ cores
Memory             4 GB RAM       8 GB RAM
Storage            50 GB SSD      100 GB SSD
Network            100 Mbps       1 Gbps
OS                 Ubuntu 22.04   Ubuntu 22.04 LTS
```

### Software Requirements

**PHP (8.1+)**
```bash
# Required PHP Extensions
php8.1-cli     (or php8.2-cli or php8.3-cli)
php8.1-fpm     (or php8.2-fpm or php8.3-fpm)
php8.1-mysql
php8.1-mbstring
php8.1-xml
php8.1-bcmath
php8.1-json
php8.1-ctype
php8.1-openssl
php8.1-tokenizer
php8.1-pdo
php8.1-curl
php8.1-zip
php8.1-gd
php8.1-intl
```

**Database**
- MySQL 8.0+ OR
- PostgreSQL 14+

**Web Server**
- Apache 2.4+ with mod_rewrite OR
- Nginx 1.18+

**Cache/Queue (Optional but Recommended)**
- Redis 6.0+ (for session and cache)
- Supervisor (for queue workers)

---

## 3. Installation Steps

### Step 1: Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    nano \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg2
```

### Step 2: Install PHP 8.1+

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-json \
    php8.3-ctype \
    php8.3-openssl \
    php8.3-tokenizer \
    php8.3-pdo \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl

# Verify installation
php -v
```

### Step 3: Install Composer

```bash
# Download Composer installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Verify installer signature
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2f3c425bd5b55b1b29c6f0a2f7e9f1e8b1f5d2b8c7e9f0a3d6b4c8e') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# Install Composer
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Remove installer
php -r "unlink('composer-setup.php');"

# Verify installation
composer --version
```

### Step 4: Install MySQL 8.0

```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p <<EOF
CREATE DATABASE cems_my CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cems_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON cems_my.* TO 'cems_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# Test connection
mysql -u cems_user -p cems_my
```

### Step 5: Install Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verify installation
sudo systemctl status nginx
```

### Step 6: Install Redis (Optional but Recommended)

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Update these settings:
# supervised systemd
# maxmemory 256mb
# maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### Step 7: Install Node.js and NPM (for asset compilation)

```bash
# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node -v
npm -v
```

---

## 4. Application Deployment

### Step 1: Clone Repository

```bash
# Create web directory
sudo mkdir -p /var/www/cems-my
sudo chown -R $USER:$USER /var/www/cems-my

# Clone repository
cd /var/www/cems-my
git clone https://github.com/your-org/cems-my.git .

# Or upload files via SCP/SFTP
# scp -r /local/path/to/cems-my/* user@server:/var/www/cems-my/
```

### Step 2: Install Dependencies

```bash
cd /var/www/cems-my

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install JavaScript dependencies
npm ci
npm run build

# Set correct permissions
sudo chown -R www-data:www-data /var/www/cems-my
sudo chmod -R 755 /var/www/cems-my
sudo chmod -R 775 /var/www/cems-my/storage
sudo chmod -R 775 /var/www/cems-my/bootstrap/cache
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

**Production .env Configuration:**

```env
APP_NAME="CEMS-MY"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cems_my
DB_USERNAME=cems_user
DB_PASSWORD=your-strong-database-password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=480

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

ENCRYPTION_KEY=your-32-character-encryption-key-here

# Rate limiting
RATE_LIMIT_PER_MINUTE=60
```

### Step 4: Database Migration

```bash
cd /var/www/cems-my

# Run migrations
php artisan migrate --force

# Run seeders (if needed)
php artisan db:seed --force

# Create storage link
php artisan storage:link
```

### Step 5: Cache Optimization

```bash
cd /var/www/cems-my

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## 5. Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/cems-my`:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/cems-my/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Logging
    access_log /var/log/nginx/cems-my-access.log;
    error_log /var/log/nginx/cems-my-error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Laravel specific
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Update to 8.2 or 8.3 if needed
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.(env|git|gitignore|gitattributes|lock)$ {
        deny all;
    }

    location ~ ^/(storage|bootstrap)/ {
        deny all;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/cems-my /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### PHP-FPM Configuration

Edit `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 6. SSL/TLS Setup

### Using Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run

# Certbot will automatically update Nginx configuration
```

### Manual SSL Configuration

If using commercial certificate:

```bash
# Create SSL directory
sudo mkdir -p /etc/nginx/ssl

# Upload certificates
sudo cp /path/to/your/certificate.crt /etc/nginx/ssl/
sudo cp /path/to/your/private.key /etc/nginx/ssl/
sudo cp /path/to/your/ca_bundle.crt /etc/nginx/ssl/

# Set permissions
sudo chmod 600 /etc/nginx/ssl/*

# Update Nginx configuration to include SSL
```

---

## 7. Security Setup

### Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Set defaults
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH
sudo ufw allow ssh

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

### Fail2Ban Configuration

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create local configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[phpmyadmin-syslog]
enabled = true
port = http,https
filter = phpmyadmin-syslog
logpath = /var/log/auth.log
```

```bash
# Restart Fail2Ban
sudo systemctl restart fail2ban
```

### Directory Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/cems-my

# Set directory permissions
sudo find /var/www/cems-my -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/cems-my -type f -exec chmod 644 {} \;

# Set writable directories
sudo chmod -R 775 /var/www/cems-my/storage
sudo chmod -R 775 /var/www/cems-my/bootstrap/cache

# Protect sensitive files
sudo chmod 600 /var/www/cems-my/.env
```

---

## 8. Queue Workers (Optional)

### Using Supervisor

```bash
# Install Supervisor
sudo apt install -y supervisor

# Create configuration
sudo nano /etc/supervisor/conf.d/cems-my-worker.conf
```

```ini
[program:cems-my-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cems-my/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/cems-my-worker.log
stopwaitsecs=3600
```

```bash
# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cems-my-worker:*
```

---

## 9. Backup Strategy

### Automated Database Backup

Create backup script `/var/www/cems-my/backup.sh`:

```bash
#!/bin/bash

# Configuration
DB_NAME="cems_my"
DB_USER="cems_user"
DB_PASS="your-database-password"
BACKUP_DIR="/var/backups/cems-my"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/cems-my --exclude=/var/www/cems-my/vendor --exclude=/var/www/cems-my/node_modules

# Clean old backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "files_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Log
 echo "Backup completed: $DATE" >> /var/log/cems-my-backup.log
```

```bash
# Make executable
chmod +x /var/www/cems-my/backup.sh

# Add to crontab
crontab -e

# Add line for daily backup at 2 AM
0 2 * * * /var/www/cems-my/backup.sh
```

---

## 10. Monitoring & Logging

### Log Rotation

Create `/etc/logrotate.d/cems-my`:

```
/var/www/cems-my/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
```

### Application Health Check

Add to crontab:

```bash
# Check every 5 minutes
*/5 * * * * curl -f https://your-domain.com/health || echo "Site down" | mail -s "CEMS-MY Alert" admin@your-domain.com
```

---

## 11. Maintenance

### Regular Maintenance Tasks

**Daily:**
- Check error logs: `tail -f /var/www/cems-my/storage/logs/laravel.log`
- Monitor disk space: `df -h`
- Check application health

**Weekly:**
- Review backup logs
- Update system packages: `sudo apt update && sudo apt upgrade -y`
- Clear old sessions: `php artisan session:clear`

**Monthly:**
- Database optimization: `php artisan db:optimize`
- Log cleanup
- Security audit

### Updating the Application

```bash
cd /var/www/cems-my

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data /var/www/cems-my
sudo chmod -R 775 /var/www/cems-my/storage

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## 12. Troubleshooting

### Common Issues

**502 Bad Gateway**
- Check PHP-FPM: `sudo systemctl status php8.1-fpm` (or php8.2-fpm / php8.3-fpm)
- Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`
- Verify socket path in Nginx config

**Database Connection Error**
- Check credentials in .env
- Verify MySQL is running: `sudo systemctl status mysql`
- Check firewall rules

**Permission Denied**
- Set correct ownership: `sudo chown -R www-data:www-data /var/www/cems-my`
- Set correct permissions on storage

**404 Not Found**
- Check Nginx configuration
- Verify `try_files` directive
- Check if public directory exists

**Slow Performance**
- Enable OPcache
- Check Redis connection
- Review slow query log
- Enable Gzip compression

### Emergency Contacts

- System Administrator: admin@your-domain.com
- Technical Support: support@cems-my.com
- Emergency Hotline: +60-XXX-XXXXXXX

---

## Appendix

### Useful Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check application status
php artisan about

# Run tests
php artisan test

# Database commands
php artisan migrate:status
php artisan migrate:rollback
php artisan db:seed

# Queue commands
php artisan queue:work
php artisan queue:restart

# Schedule
php artisan schedule:list
php artisan schedule:run
```

### File Locations

| File/Directory | Purpose |
|----------------|---------|
| `/var/www/cems-my` | Application root |
| `/var/www/cems-my/.env` | Environment configuration |
| `/var/www/cems-my/storage/logs` | Application logs |
| `/var/log/nginx/` | Web server logs |
| `/var/log/mysql/` | Database logs |
| `/etc/nginx/sites-available/` | Web server configs |
| `/etc/php/8.1/fpm/` | PHP-FPM configuration (or 8.2/8.3) |
| `/var/backups/cems-my/` | Backup location |

---

## 13. Implemented Features (v1.1)

### Enhanced Due Diligence (EDD)

The EDD module automates enhanced customer due diligence for high-risk transactions.

**Routes:**
- `/compliance/edd` - EDD records list
- `/compliance/edd/create` - Create new EDD record

**EDD Questionnaire Fields:**
- Source of funds
- Purpose of transaction
- Employment information
- Source of wealth

**Workflow:** Incomplete → Pending Review → Approved/Rejected

### Journal Entry Workflow

Manual journal entries now require multi-level approval before posting.

**Routes:**
- `/accounting/journal/workflow` - View pending entries
- Entries: Draft → Pending → Posted (or Reversed)

### Cash Flow Statement

New cash flow reporting with operating, investing, and financing activities.

**Route:** `/accounting/cash-flow`

### Financial Ratios

Key performance metrics including liquidity, profitability, leverage, and efficiency ratios.

**Route:** `/accounting/ratios`

### Fiscal Year Management

Annual fiscal year closing with automatic income summary entries.

**Routes:**
- `/accounting/fiscal-years` - View and manage fiscal years
- Close year generates: Revenue → Income Summary → Retained Earnings

### Department & Cost Center Support

Enhanced chart of accounts with department and cost center tracking.

**Database Tables:**
- `departments` - Organizational departments
- `cost_centers` - Cost center tracking per department

### Report Enhancements

New report cards on `/reports`:
- LMCA Report (Monthly Large Money Changers Act)
- Quarterly LVR (Quarterly Large Value Report)
- Position Limit Report
- Report History & Compare

---

## 14. Database Migrations

When updating to v1.1, run migrations:

```bash
php artisan migrate
```

**New tables created:**
- `departments` - Organizational structure
- `cost_centers` - Cost center tracking
- `fiscal_years` - Annual fiscal year records
- `enhanced_diligence_records` - EDD questionnaire data
- Enhanced `journal_entries` - Workflow status columns
- Enhanced `chart_of_accounts` - Department/cost center columns

---

**END OF DEPLOYMENT GUIDE**
