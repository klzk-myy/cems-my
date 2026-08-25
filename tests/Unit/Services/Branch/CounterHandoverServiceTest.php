<?php

namespace Tests\Unit\Services\Branch;

use App\Services\Branch\CounterHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CounterHandoverServiceTest extends TestCase
{
    use RefreshDatabase;

    private CounterHandoverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CounterHandoverService::class);
    }

    #[Test]
    public function find_pending_handover_returns_null_when_none_exists(): void
    {
        $result = $this->service->findPendingHandover(
            userId: 1,
            counterId: 1,
            date: now()->toDateString()
        );

        $this->assertNull($result);
    }
}
