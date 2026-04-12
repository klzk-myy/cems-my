# Hardened Rate Limiting Implementation

## Summary

This document describes the hardened rate limiting implementation for CEMS-MY to meet Bank Negara Malaysia (BNM) security requirements.

## Security Improvements

### 1. Stricter Rate Limits

| Endpoint | Old Limit | New Limit | Change |
|----------|-----------|-----------|--------|
| Login | 30/min | 5/min | -83% |
| API General | 60/min | 30/min | -50% |
| Transactions | 30/min | 10/min | -67% |
| STR Submission | 10/min | 3/min | -70% |
| Bulk Operations | - | 1/5min | NEW |
| Export | - | 5/min | NEW |
| Sensitive Ops | - | 3/min | NEW |

### 2. IP-Based Blocking

- **Auto-blocking**: IPs blocked after 10 failed login attempts in 5 minutes
- **Progressive penalties**: Block duration doubles for repeat offenders (max 24 hours)
- **Whitelist support**: Trusted IPs can be exempted from blocking
- **Fast lookup**: Blocked IPs stored in Redis for immediate rejection

### 3. Burst Protection

All rate limiters include burst allowance:
- Allows small bursts of traffic
- Enforces average rate over time
- Prevents sudden legitimate traffic from being blocked

## Files Created/Modified

### Configuration
- `config/security.php` - Updated with stricter rate limits

### Middleware
- `app/Http/Middleware/StrictRateLimit.php` - Strict rate limiting middleware
- `app/Http/Middleware/IpBlocker.php` - IP blocking middleware
- `app/Http/Kernel.php` - Added middleware aliases and global middleware

### Services
- `app/Services/RateLimitService.php` - Centralized rate limit logic

### Commands
- `app/Console/Commands/IpBlockerCommand.php` - CLI for managing blocked IPs

### Providers
- `app/Providers/RouteServiceProvider.php` - Updated rate limiters

### Tests
- `tests/Unit/RateLimitServiceTest.php` - Unit tests for configuration
- `tests/Feature/RateLimitingTest.php` - Feature tests (requires DB)

## CLI Commands

### List blocked IPs
```bash
php artisan security:ip list
```

### Block an IP manually
```bash
php artisan security:ip block --ip=192.168.1.100 --duration=60 --reason="Suspicious activity"
```

### Unblock an IP
```bash
php artisan security:ip unblock --ip=192.168.1.100
```

### Check IP status
```bash
php artisan security:ip check --ip=192.168.1.100
```

### View statistics
```bash
php artisan security:ip stats
php artisan security:ip stats --ip=192.168.1.100
```

### Unblock all IPs
```bash
php artisan security:ip clear
```

## Middleware Usage

### Apply rate limiting to routes:

```php
// Web routes - strict rate limit
Route::middleware(['ip.blocker', 'throttle:login'])->post('/login', [LoginController::class, 'login']);

// API routes - transaction rate limit
Route::middleware(['throttle:transactions'])->post('/api/transactions', [TransactionController::class, 'store']);

// STR submission - strict limit
Route::middleware(['throttle:str-submission'])->post('/api/str', [StrController::class, 'submit']);

// Bulk operations
Route::middleware(['throttle:bulk'])->post('/api/bulk-import', [ImportController::class, 'import']);

// Export operations
Route::middleware(['throttle:export'])->get('/api/export', [ExportController::class, 'export']);

// Sensitive operations (MFA, password change)
Route::middleware(['throttle:sensitive'])->post('/api/mfa/verify', [MfaController::class, 'verify']);
```

### Available middleware aliases:

- `ip.blocker` - IP blocking check
- `rate.limit.strict` - Strict rate limiting middleware
- `throttle:login` - Login rate limit (5/min)
- `throttle:transactions` - Transaction rate limit (10/min)
- `throttle:str-submission` - STR submission rate limit (3/min)
- `throttle:bulk` - Bulk operations rate limit (1/5min)
- `throttle:export` - Export operations rate limit (5/min)
- `throttle:sensitive` - Sensitive operations rate limit (3/min)

## Rate Limit Configuration Reference

### config/security.php

```php
'rate_limits' => [
    'login' => [
        'attempts' => 5,
        'per_minutes' => 1,
        'burst_allowance' => 3,
        'decay_minutes' => 1,
    ],
    'api' => [
        'attempts' => 30,
        'per_minutes' => 1,
        'burst_allowance' => 10,
        'decay_minutes' => 1,
    ],
    'transactions' => [
        'attempts' => 10,
        'per_minutes' => 1,
        'burst_allowance' => 3,
        'decay_minutes' => 1,
    ],
    'str' => [
        'attempts' => 3,
        'per_minutes' => 1,
        'burst_allowance' => 1,
        'decay_minutes' => 1,
    ],
    'bulk' => [
        'attempts' => 1,
        'per_minutes' => 5,
        'burst_allowance' => 1,
        'decay_minutes' => 5,
    ],
    'export' => [
        'attempts' => 5,
        'per_minutes' => 1,
        'burst_allowance' => 2,
        'decay_minutes' => 1,
    ],
    'sensitive' => [
        'attempts' => 3,
        'per_minutes' => 1,
        'burst_allowance' => 1,
        'decay_minutes' => 1,
    ],
],

'ip_blocking' => [
    'enabled' => true,
    'failed_attempts_threshold' => 10,
    'time_window_minutes' => 5,
    'block_duration_minutes' => 60,
    'max_block_duration_minutes' => 1440,
    'whitelist' => [],
],

'rate_limit_monitoring' => [
    'enabled' => true,
    'alert_threshold' => 3,
    'alert_window_minutes' => 10,
    'log_hits' => true,
    'hit_history_ttl' => 60,
],
```

## Environment Variables

Add these to your `.env` file:

```env
# IP Blocking
SECURITY_IP_BLOCKING_ENABLED=true
SECURITY_IP_BLOCK_DURATION=60
SECURITY_IP_WHITELIST=127.0.0.1,10.0.0.1

# Rate Limit Monitoring
SECURITY_RATE_LIMIT_MONITORING=true

# HSTS (already configured)
SECURITY_HSTS_MAX_AGE=31536000
SECURITY_HSTS_INCLUDE_SUBDOMAINS=true
```

## Deployment Notes

### Prerequisites
- Redis must be configured and running for IP blocking
- Cache configuration must support Redis

### Deployment Steps

1. **Install the changes:**
   ```bash
   git pull origin main
   composer dump-autoload
   ```

2. **Update environment variables** (see above)

3. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

4. **Verify middleware registration:**
   ```bash
   php artisan route:list
   ```

5. **Test rate limiting:**
   ```bash
   php artisan test tests/Unit/RateLimitServiceTest.php
   ```

6. **Monitor logs for rate limit hits:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Rate limit"
   ```

## Monitoring

Rate limit hits are logged with:
- IP address
- User ID (if authenticated)
- Limiter name
- URL and method
- Timestamp

Alerts are triggered when an IP hits rate limits 3+ times in 10 minutes.

## Security Considerations

1. **Redis is required** for IP blocking to function properly
2. **Whitelist critical IPs** (monitoring systems, etc.)
3. **Monitor blocked IPs** regularly for false positives
4. **Progressive penalties** prevent brute force attacks while allowing legitimate users to recover
5. **Burst protection** prevents legitimate high-frequency operations from being blocked

## Compliance

This implementation meets BNM requirements for:
- ✅ Rate limiting on sensitive endpoints
- ✅ IP-based blocking after repeated failures
- ✅ Audit logging of security events
- ✅ Configurable thresholds
- ✅ Automatic progressive penalties

## Testing

Run unit tests to verify configuration:
```bash
php artisan test tests/Unit/RateLimitServiceTest.php
```

All configuration tests should pass:
- Configuration has required keys
- Rate limit values match BNM requirements
- Rate limits have burst protection
- IP blocking configuration is complete
- Rate limit monitoring configuration is complete
- Middleware classes exist
- Service class exists
- Command class exists
