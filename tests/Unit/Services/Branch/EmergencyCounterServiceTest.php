<?php

namespace Tests\Unit\Services\Branch;

use App\Enums\CounterSessionStatus;
use App\Enums\UserRole;
use App\Exceptions\Domain\EmergencyCloseSessionTooNewException;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\EmergencyClosure;
use App\Models\User;
use App\Services\Branch\EmergencyCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmergencyCounterServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmergencyCounterService $service;

    private Branch $branch;

    private Counter $counter;

    private User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->counter = Counter::factory()->for($this->branch, 'branch')->create();
        $this->teller = User::factory()->for($this->branch, 'branch')->create(['role' => UserRole::Teller]);

        $this->service = app(EmergencyCounterService::class);
    }

    #[Test]
    public function initiate_emergency_close_creates_closure(): void
    {
        CounterSession::create([
            'counter_id' => $this->counter->id,
            'user_id' => $this->teller->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now()->subMinutes(31),
            'opened_by' => $this->teller->id,
            'status' => CounterSessionStatus::Open,
        ]);

        $before = EmergencyClosure::count();

        $closure = $this->service->initiateEmergencyClose(
            $this->counter,
            $this->teller,
            'Suspicious activity detected'
        );

        $this->assertNotNull($closure);
        $this->assertSame($this->counter->id, $closure->counter_id);
        $this->assertSame('Suspicious activity detected', $closure->reason);
        $this->assertSame($before + 1, EmergencyClosure::count());
    }

    #[Test]
    public function initiate_emergency_close_rejects_sessions_younger_than_30_minutes(): void
    {
        CounterSession::create([
            'counter_id' => $this->counter->id,
            'user_id' => $this->teller->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now()->subMinutes(5),
            'opened_by' => $this->teller->id,
            'status' => CounterSessionStatus::Open,
        ]);

        $this->expectException(EmergencyCloseSessionTooNewException::class);
        $this->service->initiateEmergencyClose($this->counter, $this->teller, 'too soon');
    }
}
