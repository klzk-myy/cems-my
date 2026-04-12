<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetryFailedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_command_exists(): void
    {
        $this->artisan('queue:retry-failed')
            ->assertSuccessful();
    }

    public function test_retry_shows_no_jobs_message_when_empty(): void
    {
        $this->artisan('queue:retry-failed')
            ->assertSuccessful()
            ->expectsOutput('No failed jobs found.');
    }

    public function test_retry_accepts_queue_option(): void
    {
        $this->artisan('queue:retry-failed', ['--queue' => 'high'])
            ->assertSuccessful();
    }

    public function test_retry_accepts_limit_option(): void
    {
        $this->artisan('queue:retry-failed', ['--limit' => 10])
            ->assertSuccessful();
    }

    public function test_retry_accepts_all_option(): void
    {
        $this->artisan('queue:retry-failed', ['--all' => true])
            ->assertSuccessful();
    }

    public function test_retry_accepts_force_option(): void
    {
        $this->artisan('queue:retry-failed', ['--force' => true])
            ->assertSuccessful();
    }
}
