# Queue Workers Configuration

This document describes the queue worker configuration for CEMS-MY production deployment.

## Overview

CEMS-MY uses **Redis-backed Laravel queues** with **Laravel Horizon** monitoring for:

- STR (Suspicious Transaction Report) submission processing
- Sanctions screening
- Compliance monitoring jobs (velocity, structuring, location anomalies)
- Report generation
- Audit log rotation
- Background data processing

## Queue Priorities

Three queues are configured with priority ordering:

| Queue | Purpose | Examples |
|-------|---------|----------|
| **high** | Critical compliance operations | STR submission, sanctions matches, real-time alerts |
| **default** | Standard operations | Report generation, CTOS reporting, notifications |
| **low** | Background tasks | Cleanup, maintenance, monthly rescreening |

Workers process queues in order: `high,default,low`

## Configuration

### Environment Variables

Add to `.env`:

```bash
# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_QUEUE=default

# Horizon Configuration
HORIZON_DOMAIN=
HORIZON_PATH=horizon
HORIZON_PREFIX=cems_horizon:
```

### Redis Configuration

Ensure Redis is configured in `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
],
```

### Queue Settings

Key settings in `config/queue.php`:

- **Default connection**: `redis`
- **Retry after**: 3600 seconds (1 hour) for long-running compliance jobs
- **After commit**: `true` - ensures database transactions complete before job runs
- **Failed job retention**: 30 days (43200 minutes)

### Horizon Configuration

Key settings in `config/horizon.php`:

- **Authentication**: Requires `web`, `auth`, and `role:admin` middleware
- **Dashboard**: Accessible at `/horizon`
- **Queue wait times**: high=3s, default=60s, low=300s
- **Memory limit**: 256MB
- **Auto-balancing**: Enabled in production
- **Production workers**: 2-10 processes with auto-scaling

## Deployment

### 1. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
php artisan horizon:install
```

### 2. Configure Supervisor

Copy supervisor configs to the system directory:

```bash
sudo cp supervisor/cems-worker.conf /etc/supervisor/conf.d/
sudo cp supervisor/cems-horizon.conf /etc/supervisor/conf.d/
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cems-worker:*
sudo supervisorctl start cems-horizon
```

### 3. Verify Workers

```bash
php artisan queue:health-check
```

### 4. Access Horizon Dashboard

Navigate to `/horizon` (requires admin role).

## Queue Management Commands

### Health Check

Check overall queue health:

```bash
php artisan queue:health-check
php artisan queue:health-check --threshold=500 --failed-threshold=20
```

Exit codes:
- `0` - All systems operational
- `1` - Warning (high queue depth, some failed jobs)
- `2` - Critical (connection failure, workers down)

### Retry Failed Jobs

```bash
# Retry all failed jobs
php artisan queue:retry-failed --all --force

# Retry with limits
php artisan queue:retry-failed --limit=100 --force

# Retry specific queue only
php artisan queue:retry-failed --queue=high --force
```

### Clear Stuck Queues (Emergency)

⚠️ **Use with caution - removes jobs from queue**

```bash
# Dry run first to see what would be cleared
php artisan queue:clear-stuck --dry-run --hours=48

# Clear jobs older than 48 hours with confirmation
php artisan queue:clear-stuck --hours=48

# Force clear without confirmation
php artisan queue:clear-stuck --hours=48 --force
```

### Monitor Worker Logs

```bash
# Worker logs
sudo tail -f /var/log/supervisor/cems-worker.log

# Horizon logs
sudo tail -f /var/log/supervisor/cems-horizon.log

# Laravel logs
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Workers Not Processing Jobs

1. **Check Redis connection**:
   ```bash
   php artisan queue:health-check
   redis-cli ping
   ```

2. **Verify supervisor processes**:
   ```bash
   sudo supervisorctl status
   ```

3. **Check for memory limits**:
   ```bash
   sudo tail -f /var/log/supervisor/cems-worker.log | grep -i memory
   ```

4. **Ensure queue workers have database permissions**:
   ```bash
   php artisan migrate:status
   ```

### Jobs Failing

1. **Check failed_jobs table**:
   ```bash
   php artisan queue:retry-failed --dry-run
   mysql -e "SELECT COUNT(*) FROM failed_jobs;"
   ```

2. **Review application logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i "failed\|error"
   ```

3. **Check job timeout settings** (currently 3600 seconds):
   - Verify in `config/queue.php` and `config/horizon.php`
   - Adjust if compliance jobs need more time

4. **Review specific failed job**:
   ```bash
   mysql -e "SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 1\G"
   ```

### Redis Connection Issues

1. **Verify Redis is running**:
   ```bash
   redis-cli ping
   # Should return: PONG
   ```

2. **Check credentials in `.env`**:
   ```bash
   grep REDIS .env
   ```

3. **Ensure firewall allows Redis**:
   ```bash
   sudo ufw status | grep redis
   ```

4. **Test Redis connection from Laravel**:
   ```bash
   php artisan tinker
   >>> Redis::connection()->ping();
   ```

### Horizon Dashboard Not Loading

1. **Check Horizon is running**:
   ```bash
   sudo supervisorctl status cems-horizon
   ```

2. **Verify route middleware** (must be admin):
   ```bash
   php artisan route:list --path=horizon
   ```

3. **Check Horizon configuration**:
   ```bash
   php artisan config:show horizon | grep middleware
   ```

## Performance Tuning

### Worker Processes

Production environment uses:
- **Min processes**: 2 (always running)
- **Max processes**: 10 (auto-scales based on load)
- **Auto-balancing**: Enabled (adjusts workers per queue based on demand)
- **Memory per worker**: 256MB

Adjust in `config/horizon.php` if needed:

```php
'production' => [
    'supervisor-1' => [
        'maxProcesses' => 10,
        'minProcesses' => 2,
        'balanceMaxShift' => 1,
        'balanceCooldown' => 3,
    ],
],
```

### Memory Limits

- **Horizon supervisor**: 256MB
- **Individual workers**: 256MB
- **Adjust in supervisor config** if experiencing memory issues

### Queue Depth Monitoring

Set up monitoring alerts for:
- Queue depth > 100 jobs for > 5 minutes
- Failed jobs > 10 in 1 hour
- Worker processes < minimum threshold
- Redis connection failures

## Security

- **Horizon dashboard**: Requires admin role (`role:admin` middleware)
- **Queue commands**: Run as `www-data` user in production
- **Redis**: Use password authentication in production
- **Supervisor**: Process isolation with proper user permissions

## Monitoring Integration

Add health check to monitoring system:

```bash
# Run health check and alert on non-zero exit
php artisan queue:health-check || send_alert "Queue health check failed"
```

Monitor these metrics in Horizon:
- Throughput (jobs/minute)
- Wait times per queue
- Failed job rate
- Memory usage
- Process count

## Backup and Recovery

### Failed Jobs

Failed jobs are stored in MySQL `failed_jobs` table:

```bash
# Backup before retry
mysqldump cems_my failed_jobs > failed_jobs_backup.sql

# Restore if needed
mysql cems_my < failed_jobs_backup.sql
```

### Redis Data

Redis should not contain critical persistent data, but consider:

```bash
# Backup Redis (if needed)
redis-cli BGSAVE

# Monitor memory usage
redis-cli INFO memory
```

## Related Documentation

- [Laravel Queues Documentation](https://laravel.com/docs/10.x/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/10.x/horizon)
- [Supervisor Documentation](http://supervisord.org/)
- See `DEPLOYMENT.md` for full deployment procedures
