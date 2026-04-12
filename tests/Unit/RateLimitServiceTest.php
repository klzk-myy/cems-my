<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for RateLimitService configuration.
 *
 * Tests the rate limiting configuration matches BNM requirements.
 */
class RateLimitServiceTest extends TestCase
{
    /** @test */
    public function configuration_has_required_keys(): void
    {
        // Load the config file
        $config = require __DIR__.'/../../config/security.php';

        $this->assertArrayHasKey('rate_limits', $config);
        $this->assertArrayHasKey('ip_blocking', $config);
        $this->assertArrayHasKey('rate_limit_monitoring', $config);

        // Check rate limits
        $this->assertArrayHasKey('login', $config['rate_limits']);
        $this->assertArrayHasKey('api', $config['rate_limits']);
        $this->assertArrayHasKey('transactions', $config['rate_limits']);
        $this->assertArrayHasKey('str', $config['rate_limits']);
        $this->assertArrayHasKey('bulk', $config['rate_limits']);
        $this->assertArrayHasKey('export', $config['rate_limits']);
        $this->assertArrayHasKey('sensitive', $config['rate_limits']);

        // Check IP blocking config
        $this->assertArrayHasKey('enabled', $config['ip_blocking']);
        $this->assertArrayHasKey('failed_attempts_threshold', $config['ip_blocking']);
        $this->assertArrayHasKey('time_window_minutes', $config['ip_blocking']);
        $this->assertArrayHasKey('block_duration_minutes', $config['ip_blocking']);
        $this->assertArrayHasKey('max_block_duration_minutes', $config['ip_blocking']);

        // Check monitoring config
        $this->assertArrayHasKey('enabled', $config['rate_limit_monitoring']);
        $this->assertArrayHasKey('alert_threshold', $config['rate_limit_monitoring']);
        $this->assertArrayHasKey('alert_window_minutes', $config['rate_limit_monitoring']);
    }

    /** @test */
    public function rate_limit_values_match_bnm_requirements(): void
    {
        $config = require __DIR__.'/../../config/security.php';

        // Login: 5 attempts per minute
        $this->assertEquals(5, $config['rate_limits']['login']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['login']['per_minutes']);

        // API: 30 per minute (reduced from 60)
        $this->assertEquals(30, $config['rate_limits']['api']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['api']['per_minutes']);

        // Transactions: 10 per minute (reduced from 30)
        $this->assertEquals(10, $config['rate_limits']['transactions']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['transactions']['per_minutes']);

        // STR: 3 per minute (reduced from 10)
        $this->assertEquals(3, $config['rate_limits']['str']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['str']['per_minutes']);

        // Bulk: 1 per 5 minutes
        $this->assertEquals(1, $config['rate_limits']['bulk']['attempts']);
        $this->assertEquals(5, $config['rate_limits']['bulk']['per_minutes']);

        // Export: 5 per minute
        $this->assertEquals(5, $config['rate_limits']['export']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['export']['per_minutes']);

        // Sensitive operations: 3 per minute
        $this->assertEquals(3, $config['rate_limits']['sensitive']['attempts']);
        $this->assertEquals(1, $config['rate_limits']['sensitive']['per_minutes']);
    }

    /** @test */
    public function rate_limits_have_burst_protection(): void
    {
        $config = require __DIR__.'/../../config/security.php';

        foreach ($config['rate_limits'] as $name => $limit) {
            $this->assertArrayHasKey('burst_allowance', $limit, "{$name} rate limit should have burst_allowance");
            $this->assertArrayHasKey('decay_minutes', $limit, "{$name} rate limit should have decay_minutes");
            $this->assertGreaterThanOrEqual(1, $limit['burst_allowance']);
        }
    }

    /** @test */
    public function ip_blocking_configuration_is_complete(): void
    {
        $config = require __DIR__.'/../../config/security.php';

        // Check that the enabled key exists (value depends on env, not the config structure)
        $this->assertArrayHasKey('enabled', $config['ip_blocking']);
        $this->assertIsBool($config['ip_blocking']['enabled']);

        // Verify configuration values are correctly set (independent of enabled state)
        $this->assertEquals(10, $config['ip_blocking']['failed_attempts_threshold']);
        $this->assertEquals(5, $config['ip_blocking']['time_window_minutes']);
        $this->assertEquals(60, $config['ip_blocking']['block_duration_minutes']);
        $this->assertEquals(1440, $config['ip_blocking']['max_block_duration_minutes']);
        $this->assertIsArray($config['ip_blocking']['whitelist']);
    }

    /** @test */
    public function rate_limit_monitoring_configuration_is_complete(): void
    {
        $config = require __DIR__.'/../../config/security.php';

        $this->assertTrue($config['rate_limit_monitoring']['enabled']);
        $this->assertEquals(3, $config['rate_limit_monitoring']['alert_threshold']);
        $this->assertEquals(10, $config['rate_limit_monitoring']['alert_window_minutes']);
        $this->assertTrue($config['rate_limit_monitoring']['log_hits']);
        $this->assertEquals(60, $config['rate_limit_monitoring']['hit_history_ttl']);
    }

    /** @test */
    public function middleware_classes_exist(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\StrictRateLimit::class));
        $this->assertTrue(class_exists(\App\Http\Middleware\IpBlocker::class));
    }

    /** @test */
    public function service_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Services\RateLimitService::class));
    }

    /** @test */
    public function command_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\IpBlockerCommand::class));
    }
}
