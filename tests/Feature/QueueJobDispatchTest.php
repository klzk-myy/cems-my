<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

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
        $job = new \stdClass;

        Queue::pushOn('high', $job);

        Queue::assertPushedOn('high', \stdClass::class);
    }

    public function test_jobs_can_be_dispatched_to_default_queue(): void
    {
        $job = new \stdClass;

        Queue::push($job);

        Queue::assertPushed(\stdClass::class);
    }

    public function test_jobs_can_be_dispatched_to_low_priority_queue(): void
    {
        $job = new \stdClass;

        Queue::pushOn('low', $job);

        Queue::assertPushedOn('low', \stdClass::class);
    }

    public function test_job_is_dispatched_with_after_commit_enabled(): void
    {
        $redisConfig = config('queue.connections.redis');
        $this->assertTrue($redisConfig['after_commit']);
    }

    public function test_queue_connection_exists_in_config(): void
    {
        $default = config('queue.default');
        $this->assertNotNull($default);
        $this->assertContains($default, ['redis', 'database', 'sync']);
    }

    public function test_high_priority_queue_uses_redis_connection(): void
    {
        $config = config('queue.connections.high');
        $this->assertEquals('redis', $config['driver']);
        $this->assertEquals('default', $config['connection']);
    }

    public function test_low_priority_queue_uses_redis_connection(): void
    {
        $config = config('queue.connections.low');
        $this->assertEquals('redis', $config['driver']);
        $this->assertEquals('default', $config['connection']);
    }

    public function test_all_priority_queues_have_same_database_connection(): void
    {
        $high = config('queue.connections.high.connection');
        $default = config('queue.connections.default.connection');
        $low = config('queue.connections.low.connection');

        $this->assertEquals($high, $default);
        $this->assertEquals($default, $low);
    }

    public function test_all_queues_have_appropriate_retry_after(): void
    {
        $this->assertEquals(3600, config('queue.connections.high.retry_after'));
        $this->assertEquals(3600, config('queue.connections.default.retry_after'));
        $this->assertEquals(3600, config('queue.connections.low.retry_after'));
    }
}
