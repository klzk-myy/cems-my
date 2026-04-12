<?php

namespace Tests\Unit;

use Tests\TestCase;

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

    public function test_health_check_shows_worker_status(): void
    {
        $this->artisan('queue:health-check')
            ->assertSuccessful()
            ->expectsOutputToContain('workers');
    }

    public function test_health_check_accepts_queue_option(): void
    {
        $this->artisan('queue:health-check', ['--queue' => 'high'])
            ->assertSuccessful();
    }

    public function test_health_check_accepts_threshold_options(): void
    {
        $this->artisan('queue:health-check', [
            '--threshold' => 50,
            '--failed-threshold' => 5,
        ])->assertSuccessful();
    }
}
