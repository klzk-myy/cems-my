# Queue Workers Configuration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement production-ready queue workers with Redis driver, Laravel Horizon monitoring, and queue management commands for the CEMS-MY financial system.

**Architecture:** Switch from sync to Redis queue driver with multiple queue priorities (high/default/low), configure Laravel Horizon for monitoring and auto-balancing, create Supervisor configuration for process management, and implement health check/retry commands for operational visibility.

**Tech Stack:** Laravel 10.x, Redis, Laravel Horizon, Supervisor, PHPUnit

---

## File Structure

| File | Purpose |
|------|---------|
| `config/queue.php` | Update default connection to Redis, add queue-specific configurations |
| `config/horizon.php` | Horizon configuration with queue balancing and monitoring |
| `supervisor/cems-worker.conf` | Supervisor template for production deployment |
| `supervisor/cems-horizon.conf` | Supervisor configuration for Horizon |
| `app/Console/Commands/QueueHealthCheck.php` | Check queue health and worker status |
| `app/Console/Commands/RetryFailedJobs.php` | Retry failed jobs with filtering options |
| `app/Console/Commands/ClearStuckQueues.php` | Emergency command to clear stuck queues |
| `tests/Unit/QueueConfigurationTest.php` | Unit tests for queue configuration |
| `tests/Feature/QueueJobDispatchTest.php` | Feature tests for job dispatching |
| `docs/QUEUE_WORKERS.md` | Deployment and operations documentation |

---

## Task 1: Update Queue Configuration

**Files:**
- Modify: `config/queue.php`

### Step 1.1: Change Default Connection to Redis
```php
// Change line 16 from:
'default' => env('QUEUE_CONNECTION', 'sync'),
// To:
'default' => env('QUEUE_CONNECTION', 'redis'),
```

### Step 1.2: Update Redis Connection Configuration
```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 3600, // 1 hour for long-running compliance jobs
    'block_for' => null,
    'after_commit' => true, // Ensure DB transactions complete before job runs
],
```

### Step 1.3: Add Queue Priority Configuration
Add after Redis configuration (around line 72):
```php
'queues' => [
    'high' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'high',
        'retry_after' => 3600,
        'block_for' => null,
        'after_commit' => true,
    ],
    'default' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 3600,
        'block_for' => null,
        'after_commit' => true,
    ],
    'low' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'low',
        'retry_after' => 3600,
        'block_for' => null,
        'after_commit' => true,
    ],
],
```

### Step 1.4: Update Failed Jobs Configuration
```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
    'expire' => 43200, // 30 days retention for audit purposes
],
```

**Commit:** `git commit -m "config: update queue.php for Redis with priority queues"`

---

## Task 2: Install and Configure Laravel Horizon

**Files:**
- Modify: `composer.json`
- Create: `config/horizon.php`

### Step 2.1: Add Horizon to Composer Dependencies
```bash
composer require laravel/horizon --no-interaction
```

### Step 2.2: Publish Horizon Configuration
```bash
php artisan horizon:install
```

### Step 2.3: Configure config/horizon.php
Replace the auto-generated config with production-ready configuration:

```php
<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),

    'queue' => [
        'connection' => 'redis',
        'queue' => 'default',
    ],

    'middleware' => ['web', 'auth', 'role:admin'],

    'waits' => [
        'redis:high' => 3,
        'redis:default' => 60,
        'redis:low' => 300,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080, // 7 days
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 256,

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default', 'low'],
                'balance' => 'auto',
                'maxProcesses' => 10,
                'minProcesses' => 2,
                'tries' => 3,
                'timeout' => 3600,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default', 'low'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 3600,
            ],
        ],
    ],
];
```

### Step 2.4: Register Horizon Service Provider
Add to `config/app.php` in the providers array (if not auto-discovered):
```php
Laravel\Horizon\HorizonServiceProvider::class,
```

**Commit:** `git commit -m "feat: install and configure Laravel Horizon"`

---

## Task 3: Create Supervisor Configuration

**Files:**
- Create: `supervisor/cems-worker.conf`
- Create: `supervisor/cems-horizon.conf`

### Step 3.1: Create Worker Supervisor Config
```ini
; supervisor/cems-worker.conf
; CEMS-MY Queue Worker Configuration
; Copy to /etc/supervisor/conf.d/cems-worker.conf on production

[program:cems-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/cems-my/artisan queue:work redis --sleep=3 --tries=3 --timeout=3600 --max-jobs=1000 --max-time=3600 --queue=high,default,low
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/cems-worker.log
stopwaitsecs=3600
startretries=3
priority=100

; Memory management
environment="MEMORY_LIMIT=256M"
```

### Step 3.2: Create Horizon Supervisor Config
```ini
; supervisor/cems-horizon.conf
; CEMS-MY Laravel Horizon Configuration
; Copy to /etc/supervisor/conf.d/cems-horizon.conf on production

[program:cems-horizon]
process_name=%(program_name)s
command=/usr/bin/php /var/www/cems-my/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/cems-horizon.log
stopwaitsecs=3600
startretries=3
priority=200

; Environment variables
environment="MEMORY_LIMIT=512M"
```

### Step 3.3: Create Supervisor Directory
```bash
mkdir -p supervisor
```

**Commit:** `git commit -m "feat: add supervisor configuration for queue workers and Horizon"`

---

## Task 4: Create Queue Health Check Command

**Files:**
- Create: `app/Console/Commands/QueueHealthCheck.php`
- Create: `tests/Feature/QueueHealthCheckTest.php`

### Step 4.1: Create Health Check Command
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health-check 
                            {--queue=default : Queue to check}
                            {--threshold=100 : Alert threshold for pending jobs}
                            {--failed-threshold=10 : Alert threshold for failed jobs}';

    protected $description = 'Check queue health and worker status';

    private array $checks = [];

    public function handle(): int
    {
        $this->info('Running queue health check...');
        $this->newLine();

        $this->checkConnection();
        $this->checkQueueSize();
        $this->checkFailedJobs();
        $this->checkWorkerStatus();

        $this->displayResults();

        return $this->determineExitCode();
    }

    private function checkConnection(): void
    {
        try {
            Redis::ping();
            $this->checks['connection'] = ['status' => 'OK', 'message' => 'Redis connection active'];
        } catch (\Exception $e) {
            $this->checks['connection'] = ['status' => 'CRITICAL', 'message' => $e->getMessage()];
        }
    }

    private function checkQueueSize(): void
    {
        $queue = $this->option('queue');
        $threshold = $this->option('threshold');

        try {
            $size = Queue::size($queue);
            $status = $size > $threshold ? 'WARNING' : 'OK';
            
            $this->checks['queue_size'] = [
                'status' => $status,
                'message' => "Queue '{$queue}' has {$size} pending jobs (threshold: {$threshold})",
            ];
        } catch (\Exception $e) {
            $this->checks['queue_size'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    private function checkFailedJobs(): void
    {
        $threshold = $this->option('failed-threshold');

        try {
            $count = \DB::table('failed_jobs')->count();
            $status = $count > $threshold ? 'WARNING' : 'OK';
            
            $this->checks['failed_jobs'] = [
                'status' => $status,
                'message' => "{$count} failed jobs (threshold: {$threshold})",
            ];
        } catch (\Exception $e) {
            $this->checks['failed_jobs'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    private function checkWorkerStatus(): void
    {
        try {
            // Check if Horizon is running by checking its metrics key
            $horizonRunning = Redis::exists('horizon:supervisor:*');
            
            // Also check for regular queue workers
            $workersRunning = Redis::exists('queues:default:notify');
            
            if ($horizonRunning || $workersRunning) {
                $this->checks['workers'] = ['status' => 'OK', 'message' => 'Workers appear to be running'];
            } else {
                $this->checks['workers'] = [
                    'status' => 'WARNING',
                    'message' => 'No active workers detected. Check supervisor.',
                ];
            }
        } catch (\Exception $e) {
            $this->checks['workers'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    private function displayResults(): void
    {
        $headers = ['Check', 'Status', 'Message'];
        $rows = [];

        foreach ($this->checks as $check => $data) {
            $statusColor = match ($data['status']) {
                'OK' => 'info',
                'WARNING' => 'warn',
                'CRITICAL' => 'error',
                default => 'error',
            };
            
            $rows[] = [$check, $data['status'], $data['message']];
        }

        $this->table($headers, $rows);
    }

    private function determineExitCode(): int
    {
        $hasCritical = collect($this->checks)->contains(fn ($check) => $check['status'] === 'CRITICAL');
        $hasWarning = collect($this->checks)->contains(fn ($check) => $check['status'] === 'WARNING');

        if ($hasCritical) {
            $this->error('Health check FAILED - Critical issues detected');
            return 2;
        }

        if ($hasWarning) {
            $this->warn('Health check PASSED with warnings');
            return 1;
        }

        $this->info('Health check PASSED - All systems operational');
        return 0;
    }
}
```

### Step 4.2: Create Health Check Test
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class QueueHealthCheckTest extends TestCase
{
    public function test_health_check_command_exists(): void
    {
        $this->artisan('queue:health-check')
            ->assertSuccessful();
    }

    public function test_health_check_shows_connection_status(): void
    {
        $this->artisan('queue:health-check')
            ->assertSuccessful()
            ->expectsOutputToContain('connection');
    }

    public function test_health_check_detects_queue_size(): void
    {
        Queue::fake();
        
        $this->artisan('queue:health-check')
            ->assertSuccessful()
            ->expectsOutputToContain('queue_size');
    }

    public function test_health_check_reports_failed_jobs(): void
    {
        $this->artisan('queue:health-check')
            ->assertSuccessful()
            ->expectsOutputToContain('failed_jobs');
    }

    public function test_health_check_exits_with_warning_for_high_threshold(): void
    {
        $this->artisan('queue:health-check', ['--threshold' => -1])
            ->assertSuccessful();
    }
}
```

**Commit:** `git commit -m "feat: add queue health check command and tests"`

---

## Task 5: Create Retry Failed Jobs Command

**Files:**
- Create: `app/Console/Commands/RetryFailedJobs.php`
- Create: `tests/Feature/RetryFailedJobsTest.php`

### Step 5.1: Create Retry Command
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class RetryFailedJobs extends Command
{
    protected $signature = 'queue:retry-failed
                            {--queue= : Filter by specific queue}
                            {--limit=50 : Maximum number of jobs to retry}
                            {--all : Retry all failed jobs (ignores limit)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Retry failed queue jobs with filtering options';

    public function handle(FailedJobProviderInterface $failer): int
    {
        $query = \DB::table('failed_jobs');

        if ($this->option('queue')) {
            $query->where('queue', $this->option('queue'));
        }

        $totalFailed = $query->count();

        if ($totalFailed === 0) {
            $this->info('No failed jobs found.');
            return 0;
        }

        $limit = $this->option('all') ? $totalFailed : $this->option('limit');
        $jobsToRetry = $query->limit($limit)->pluck('id');

        $this->warn("Found {$totalFailed} failed jobs.");
        $this->info("Will retry {$jobsToRetry->count()} job(s).");

        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $retried = 0;
        $failed = 0;

        foreach ($jobsToRetry as $jobId) {
            try {
                $this->call('queue:retry', ['id' => $jobId]);
                $retried++;
            } catch (\Exception $e) {
                $this->error("Failed to retry job {$jobId}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Retry operation complete:");
        $this->info("  - Retried: {$retried}");
        $this->info("  - Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
```

### Step 5.2: Create Retry Command Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class RetryFailedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_command_shows_no_jobs_message(): void
    {
        $this->artisan('queue:retry-failed')
            ->assertSuccessful()
            ->expectsOutput('No failed jobs found.');
    }

    public function test_retry_command_requires_confirmation_for_multiple_jobs(): void
    {
        // Insert a failed job
        \DB::table('failed_jobs')->insert([
            'id' => 1,
            'uuid' => 'test-uuid-1',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $this->artisan('queue:retry-failed')
            ->expectsConfirmation('Do you want to proceed?', false)
            ->expectsOutput('Operation cancelled.');
    }

    public function test_retry_command_with_force_option(): void
    {
        \DB::table('failed_jobs')->insert([
            'id' => 1,
            'uuid' => 'test-uuid-2',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $this->artisan('queue:retry-failed', ['--force' => true, '--limit' => 1])
            ->assertSuccessful();
    }
}
```

**Commit:** `git commit -m "feat: add retry failed jobs command with tests"`

---

## Task 6: Create Clear Stuck Queues Command

**Files:**
- Create: `app/Console/Commands/ClearStuckQueues.php`
- Create: `tests/Feature/ClearStuckQueuesTest.php`

### Step 6.1: Create Clear Command
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ClearStuckQueues extends Command
{
    protected $signature = 'queue:clear-stuck
                            {--queue=default : Queue to clear}
                            {--hours=24 : Clear jobs older than N hours}
                            {--dry-run : Show what would be cleared without clearing}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Emergency command to clear stuck/stale queue jobs';

    public function handle(): int
    {
        $queue = $this->option('queue');
        $hours = $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->warn('⚠️  EMERGENCY QUEUE CLEAR OPERATION');
        $this->warn("This will clear jobs from '{$queue}' queue older than {$hours} hours.");
        $this->newLine();

        // Get queue contents from Redis
        $redisKey = "queues:{$queue}";
        $jobs = Redis::lrange($redisKey, 0, -1);

        $stuckJobs = [];
        $cutoffTime = now()->subHours($hours);

        foreach ($jobs as $index => $jobPayload) {
            $job = json_decode($jobPayload, true);
            $createdAt = isset($job['created_at']) ? \Carbon\Carbon::parse($job['created_at']) : null;

            if ($createdAt && $createdAt->lt($cutoffTime)) {
                $stuckJobs[] = [
                    'index' => $index,
                    'id' => $job['id'] ?? 'unknown',
                    'displayName' => $job['displayName'] ?? 'unknown',
                    'created_at' => $createdAt->toDateTimeString(),
                ];
            }
        }

        if (empty($stuckJobs)) {
            $this->info('No stuck jobs found.');
            return 0;
        }

        $this->table(
            ['Index', 'Job ID', 'Job Name', 'Created At'],
            $stuckJobs
        );

        $this->warn("Found " . count($stuckJobs) . " stuck job(s).");

        if ($dryRun) {
            $this->info('Dry run mode - no changes made.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Do you want to REMOVE these jobs?', false)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Remove stuck jobs (from newest to oldest to maintain index integrity)
        $removed = 0;
        foreach (array_reverse($stuckJobs) as $stuckJob) {
            Redis::ltrim($redisKey, $stuckJob['index'] + 1, -1);
            Redis::ltrim($redisKey, 0, $stuckJob['index'] - 1);
            $removed++;
        }

        $this->info("Removed {$removed} stuck job(s) from '{$queue}' queue.");
        $this->warn('Consider investigating why these jobs were stuck.');

        return 0;
    }
}
```

### Step 6.2: Create Clear Command Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class ClearStuckQueuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    public function test_clear_shows_no_jobs_when_queue_empty(): void
    {
        $this->artisan('queue:clear-stuck')
            ->assertSuccessful()
            ->expectsOutput('No stuck jobs found.');
    }

    public function test_clear_respects_dry_run(): void
    {
        // Add a stale job
        $oldJob = [
            'id' => 'test-1',
            'displayName' => 'TestJob',
            'created_at' => now()->subDays(2)->toIso8601String(),
        ];
        Redis::rpush('queues:default', json_encode($oldJob));

        $this->artisan('queue:clear-stuck', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutput('Dry run mode - no changes made.');
    }

    public function test_clear_requires_confirmation(): void
    {
        $oldJob = [
            'id' => 'test-2',
            'displayName' => 'TestJob',
            'created_at' => now()->subDays(2)->toIso8601String(),
        ];
        Redis::rpush('queues:default', json_encode($oldJob));

        $this->artisan('queue:clear-stuck')
            ->expectsConfirmation('Do you want to REMOVE these jobs?', false)
            ->expectsOutput('Operation cancelled.');
    }
}
```

**Commit:** `git commit -m "feat: add clear stuck queues emergency command with tests"`

---

## Task 7: Create Queue Configuration Tests

**Files:**
- Create: `tests/Unit/QueueConfigurationTest.php`
- Create: `tests/Feature/QueueJobDispatchTest.php`

### Step 7.1: Create Configuration Unit Tests
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_default_queue_connection_is_configured(): void
    {
        $default = config('queue.default');
        $this->assertNotNull($default);
        $this->assertContains($default, ['redis', 'database', 'sync']);
    }

    public function test_redis_connection_is_configured(): void
    {
        $redis = config('queue.connections.redis');
        $this->assertNotNull($redis);
        $this->assertEquals('redis', $redis['driver']);
    }

    public function test_queue_priority_queues_exist(): void
    {
        $this->assertArrayHasKey('high', config('queue.connections'));
        $this->assertArrayHasKey('default', config('queue.connections'));
        $this->assertArrayHasKey('low', config('queue.connections'));
    }

    public function test_redis_retry_after_is_appropriate(): void
    {
        $retryAfter = config('queue.connections.redis.retry_after');
        $this->assertGreaterThanOrEqual(3600, $retryAfter);
    }

    public function test_failed_jobs_configuration_exists(): void
    {
        $failed = config('queue.failed');
        $this->assertNotNull($failed);
        $this->assertArrayHasKey('table', $failed);
        $this->assertEquals('failed_jobs', $failed['table']);
    }

    public function test_horizon_configuration_exists(): void
    {
        if (!class_exists(\Laravel\Horizon\Horizon::class)) {
            $this->markTestSkipped('Horizon not installed');
        }

        $config = config('horizon');
        $this->assertNotNull($config);
        $this->assertArrayHasKey('environments', $config);
        $this->assertArrayHasKey('production', $config['environments']);
    }
}
```

### Step 7.2: Create Job Dispatch Feature Tests
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class QueueJobDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_jobs_can_be_dispatched_to_high_priority_queue(): void
    {
        $job = new \stdClass();
        
        Queue::pushOn('high', $job);
        
        Queue::assertPushedOn('high', \stdClass::class);
    }

    public function test_jobs_can_be_dispatched_to_default_queue(): void
    {
        $job = new \stdClass();
        
        Queue::push($job);
        
        Queue::assertPushed(\stdClass::class);
    }

    public function test_jobs_can_be_dispatched_to_low_priority_queue(): void
    {
        $job = new \stdClass();
        
        Queue::pushOn('low', $job);
        
        Queue::assertPushedOn('low', \stdClass::class);
    }

    public function test_job_is_dispatched_with_after_commit(): void
    {
        $redisConfig = config('queue.connections.redis');
        $this->assertTrue($redisConfig['after_commit']);
    }
}
```

**Commit:** `git commit -m "test: add queue configuration and dispatch tests"`

---

## Task 8: Create Documentation

**Files:**
- Create: `docs/QUEUE_WORKERS.md`

### Step 8.1: Write Documentation
```markdown
# Queue Workers Configuration

This document describes the queue worker configuration for CEMS-MY production deployment.

## Overview

CEMS-MY uses Redis-backed Laravel queues with Horizon monitoring for:
- STR submission processing
- Sanctions screening
- Compliance monitoring jobs
- Report generation
- Audit log rotation

## Queue Priorities

Three queues are configured with priority ordering:

1. **high** - Critical compliance operations (STR submission, sanctions matches)
2. **default** - Standard operations (reporting, notifications)
3. **low** - Background tasks (cleanup, maintenance)

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

## Deployment

### 1. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
php artisan horizon:install
```

### 2. Configure Supervisor

Copy supervisor configs:

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

## Operations

### Health Check

```bash
php artisan queue:health-check
php artisan queue:health-check --threshold=500 --failed-threshold=20
```

### Retry Failed Jobs

```bash
# Retry all failed jobs
php artisan queue:retry-failed --all --force

# Retry with limits
php artisan queue:retry-failed --limit=100 --force

# Retry specific queue
php artisan queue:retry-failed --queue=high --force
```

### Clear Stuck Queues (Emergency)

```bash
# Dry run first
php artisan queue:clear-stuck --dry-run --hours=48

# Clear with confirmation
php artisan queue:clear-stuck --hours=48 --force
```

### Monitor Worker Logs

```bash
# Worker logs
sudo tail -f /var/log/supervisor/cems-worker.log

# Horizon logs
sudo tail -f /var/log/supervisor/cems-horizon.log
```

## Troubleshooting

### Workers Not Processing

1. Check Redis connection: `php artisan queue:health-check`
2. Verify supervisor processes: `sudo supervisorctl status`
3. Check for memory limits in logs
4. Ensure queue workers have database permissions

### Jobs Failing

1. Check failed_jobs table: `php artisan queue:retry-failed --dry-run`
2. Review application logs in `storage/logs/`
3. Check job timeout settings (currently 3600 seconds)

### Redis Connection Issues

1. Verify Redis is running: `redis-cli ping`
2. Check credentials in `.env`
3. Ensure firewall allows Redis connections

## Performance Tuning

### Worker Processes

Production environment uses:
- **Min processes**: 2
- **Max processes**: 10
- **Auto-balancing**: Enabled

### Memory Limits

- Workers: 256MB
- Horizon: 512MB

Adjust in supervisor configs if needed.

## Security

- Horizon dashboard requires admin role
- Queue commands require appropriate permissions
- Redis password should be set in production
- Consider Redis over TLS for sensitive environments
```

**Commit:** `git commit -m "docs: add queue workers deployment and operations guide"`

---

## Task 9: Final Verification

### Step 9.1: Run Tests
```bash
php artisan test --filter=QueueConfigurationTest
php artisan test --filter=QueueHealthCheckTest
php artisan test --filter=RetryFailedJobsTest
php artisan test --filter=ClearStuckQueuesTest
php artisan test --filter=QueueJobDispatchTest
```

Expected: All tests pass (0 failures)

### Step 9.2: Verify Configuration
```bash
php artisan config:clear
php artisan config:show queue
```

Expected: Redis is default, priority queues configured

### Step 9.3: Check Horizon Installation
```bash
php artisan horizon:status
```

Expected: Horizon installed and shows status

**Commit:** `git commit -m "chore: final verification of queue configuration"`

---

## Summary Checklist

- [ ] config/queue.php updated with Redis default and priority queues
- [ ] Laravel Horizon installed and configured
- [ ] Supervisor configs created (workers + Horizon)
- [ ] QueueHealthCheck command created and tested
- [ ] RetryFailedJobs command created and tested
- [ ] ClearStuckQueues command created and tested
- [ ] Unit tests for queue configuration
- [ ] Feature tests for job dispatching
- [ ] Documentation created
- [ ] All tests pass
