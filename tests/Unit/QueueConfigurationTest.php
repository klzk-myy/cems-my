<?php

namespace Tests\Unit;

use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_default_queue_connection_is_configured(): void
    {
        $default = config('queue.default');
        $this->assertNotNull($default);
        // In production this should be 'redis', testing may use 'sync'
        $this->assertContains($default, ['redis', 'database', 'sync']);
    }

    public function test_redis_queue_connection_exists(): void
    {
        $this->assertArrayHasKey('redis', config('queue.connections'));
        $this->assertEquals('redis', config('queue.connections.redis.driver'));
    }

    public function test_redis_connection_is_configured(): void
    {
        $redis = config('queue.connections.redis');
        $this->assertNotNull($redis);
        $this->assertEquals('redis', $redis['driver']);
    }

    public function test_priority_queues_exist(): void
    {
        $this->assertArrayHasKey('high', config('queue.connections'));
        $this->assertArrayHasKey('default', config('queue.connections'));
        $this->assertArrayHasKey('low', config('queue.connections'));
    }

    public function test_high_priority_queue_is_redis(): void
    {
        $high = config('queue.connections.high');
        $this->assertEquals('redis', $high['driver']);
        $this->assertEquals('high', $high['queue']);
    }

    public function test_low_priority_queue_is_redis(): void
    {
        $low = config('queue.connections.low');
        $this->assertEquals('redis', $low['driver']);
        $this->assertEquals('low', $low['queue']);
    }

    public function test_redis_retry_after_is_appropriate_for_financial_operations(): void
    {
        $retryAfter = config('queue.connections.redis.retry_after');
        $this->assertGreaterThanOrEqual(3600, $retryAfter);
    }

    public function test_after_commit_is_enabled_for_data_integrity(): void
    {
        $this->assertTrue(config('queue.connections.redis.after_commit'));
        $this->assertTrue(config('queue.connections.high.after_commit'));
        $this->assertTrue(config('queue.connections.low.after_commit'));
    }

    public function test_failed_jobs_configuration_exists(): void
    {
        $failed = config('queue.failed');
        $this->assertNotNull($failed);
        $this->assertArrayHasKey('table', $failed);
        $this->assertEquals('failed_jobs', $failed['table']);
    }

    public function test_failed_jobs_has_expiration(): void
    {
        $this->assertArrayHasKey('expire', config('queue.failed'));
        $this->assertGreaterThan(0, config('queue.failed.expire'));
    }

    public function test_horizon_configuration_exists(): void
    {
        $config = config('horizon');
        $this->assertNotNull($config);
        $this->assertArrayHasKey('environments', $config);
        $this->assertArrayHasKey('production', $config['environments']);
    }

    public function test_horizon_middleware_includes_auth(): void
    {
        $middleware = config('horizon.middleware');
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
    }

    public function test_horizon_production_configuration_has_multiple_queues(): void
    {
        $prod = config('horizon.environments.production.supervisor-1');
        $this->assertArrayHasKey('queue', $prod);
        $this->assertContains('high', $prod['queue']);
        $this->assertContains('default', $prod['queue']);
        $this->assertContains('low', $prod['queue']);
    }

    public function test_horizon_memory_limit_is_configured(): void
    {
        $this->assertEquals(256, config('horizon.memory_limit'));
    }

    public function test_horizon_wait_times_are_configured(): void
    {
        $waits = config('horizon.waits');
        $this->assertArrayHasKey('redis:high', $waits);
        $this->assertArrayHasKey('redis:default', $waits);
        $this->assertArrayHasKey('redis:low', $waits);
    }

    public function test_horizon_trimming_configuration_exists(): void
    {
        $trim = config('horizon.trim');
        $this->assertArrayHasKey('recent', $trim);
        $this->assertArrayHasKey('failed', $trim);
        $this->assertArrayHasKey('recent_failed', $trim);
    }
}
