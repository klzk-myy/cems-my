<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\Currency;
use App\Models\User;
use App\Services\CounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CounterService $counterService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->counterService = new CounterService;
    }

    public function test_can_open_counter_session(): void
    {
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        // Create a unique test currency
        $currency = Currency::updateOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        // Currency uses 'code' as primary key, so we pass the code as currency_id
        $openingFloats = [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ];

        $session = $this->counterService->openSession($counter, $user, $openingFloats);

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('till_balances', [
            'till_id' => (string) $counter->id,
            'currency_code' => $currency->code,
            'opening_balance' => 10000.00,
        ]);
    }

    public function test_cannot_open_if_already_open(): void
    {
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency 2', 'symbol' => 'T2', 'decimal_places' => 2, 'is_active' => true]
        );

        $this->counterService->openSession($counter, $user, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Counter is already open today');

        $this->counterService->openSession($counter, $user, [
            ['currency_id' => $currency->code, 'amount' => 5000.00],
        ]);
    }

    public function test_cannot_open_if_user_at_another_counter(): void
    {
        $counter1 = Counter::factory()->create();
        $counter2 = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency 3', 'symbol' => 'T3', 'decimal_places' => 2, 'is_active' => true]
        );

        $this->counterService->openSession($counter1, $user, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is already at another counter');

        $this->counterService->openSession($counter2, $user, [
            ['currency_id' => $currency->code, 'amount' => 5000.00],
        ]);
    }

    public function test_can_close_counter_session(): void
    {
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency 4', 'symbol' => 'T4', 'decimal_places' => 2, 'is_active' => true]
        );

        $session = $this->counterService->openSession($counter, $user, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        // Close with exact same amount - 0 variance
        $this->counterService->closeSession($session, $user, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'closed_by' => $user->id,
        ]);
    }

    public function test_calculates_variance_correctly(): void
    {
        $opening = 10000.00;
        $closing = 10200.00;

        $variance = $this->counterService->calculateVariance($opening, $closing);

        $this->assertEquals(200.00, $variance);
    }

    public function test_requires_supervisor_for_large_variance(): void
    {
        $counter = Counter::factory()->create();
        $teller = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency 5', 'symbol' => 'T5', 'decimal_places' => 2, 'is_active' => true]
        );

        $session = $this->counterService->openSession($counter, $teller, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        // Variance > 500 requires supervisor
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Variance exceeds red threshold, requires supervisor approval');

        $this->counterService->closeSession(
            $session,
            $teller,
            [['currency_id' => $currency->code, 'amount' => 10600.00]], // +600 variance
            null,
            null // No supervisor
        );
    }

    public function test_get_available_counters(): void
    {
        $counter1 = Counter::factory()->create();
        $counter2 = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency 6', 'symbol' => 'T6', 'decimal_places' => 2, 'is_active' => true]
        );

        // Open counter1
        $this->counterService->openSession($counter1, $user, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $available = $this->counterService->getAvailableCounters();

        // counter2 should be available, counter1 should not
        $availableIds = collect($available)->pluck('id')->toArray();
        $this->assertNotContains($counter1->id, $availableIds);
        $this->assertContains($counter2->id, $availableIds);
    }
}
