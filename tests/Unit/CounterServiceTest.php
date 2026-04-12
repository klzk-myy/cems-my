<?php

namespace Tests\Unit;

use App\Enums\CounterSessionStatus;
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

    public function test_initiate_handover_fails_when_from_user_not_session_user(): void
    {
        $counter = Counter::factory()->create();
        $teller1 = User::factory()->create(['role' => 'teller']);
        $teller2 = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency XYZ', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        // Open session with teller1
        $session = $this->counterService->openSession($counter, $teller1, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        // teller2 tries to handover as if they were teller1
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Session does not belong to the specified user');

        $this->counterService->initiateHandover($session, $teller2, $teller1, $manager, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);
    }

    public function test_initiate_handover_fails_when_to_user_at_another_counter(): void
    {
        $counter1 = Counter::factory()->create(['code' => 'C01']);
        $counter2 = Counter::factory()->create(['code' => 'C02']);
        $teller1 = User::factory()->create(['role' => 'teller']);
        $teller2 = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency ABC', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        // Open counter1 with teller1
        $session = $this->counterService->openSession($counter1, $teller1, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        // teller2 is already at counter2
        $this->counterService->openSession($counter2, $teller2, [
            ['currency_id' => $currency->code, 'amount' => 5000.00],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is already at another counter');

        $this->counterService->initiateHandover($session, $teller1, $teller2, $manager, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);
    }

    public function test_initiate_handover_fails_when_session_not_open(): void
    {
        $counter = Counter::factory()->create();
        $teller = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency DEF', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        $session = $this->counterService->openSession($counter, $teller, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        // Close the session first
        $this->counterService->closeSession($session, $teller, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Session is not open');

        $this->counterService->initiateHandover($session, $teller, $manager, $manager, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);
    }

    public function test_initiate_handover_fails_when_supervisor_not_manager(): void
    {
        $counter = Counter::factory()->create();
        $teller1 = User::factory()->create(['role' => 'teller']);
        $teller2 = User::factory()->create(['role' => 'teller']);
        $tellerSupervisor = User::factory()->create(['role' => 'teller']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency GHI', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        $session = $this->counterService->openSession($counter, $teller1, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Supervisor must be a manager or admin');

        $this->counterService->initiateHandover($session, $teller1, $teller2, $tellerSupervisor, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);
    }

    public function test_initiate_handover_with_zero_variance(): void
    {
        $counter = Counter::factory()->create();
        $teller1 = User::factory()->create(['role' => 'teller']);
        $teller2 = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency JKL', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        $session = $this->counterService->openSession($counter, $teller1, [
            ['currency_id' => $currency->code, 'amount' => 10000.00],
        ]);

        $result = $this->counterService->initiateHandover($session, $teller1, $teller2, $manager, [
            ['currency_id' => $currency->code, 'amount' => 10000.00], // Exact match - zero variance
        ]);

        $this->assertEquals('0.00', $result['handover']->variance_myr);

        // Verify session was handed over
        $session->refresh();
        $this->assertEquals(CounterSessionStatus::HandedOver, $session->status);

        // Verify new session was created for teller2
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $teller2->id,
            'status' => 'open',
        ]);
    }

    public function test_close_session_query_only_returns_expected_currencies(): void
    {
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        // Create multiple currencies - some will be in the session, some won't
        $usdCurrency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );

        $eurCurrency = Currency::firstOrCreate(
            ['code' => 'EUR'],
            ['name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_active' => true]
        );

        $gbpCurrency = Currency::firstOrCreate(
            ['code' => 'GBP'],
            ['name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'is_active' => true]
        );

        // Open session with only USD
        $session = $this->counterService->openSession($counter, $user, [
            ['currency_id' => $usdCurrency->code, 'amount' => 10000.00],
        ]);

        // Close session with USD only
        $this->counterService->closeSession($session, $user, [
            ['currency_id' => $usdCurrency->code, 'amount' => 10000.00],
        ]);

        // Verify only till balance for USD was updated, not EUR or GBP
        $this->assertDatabaseHas('till_balances', [
            'till_id' => (string) $counter->id,
            'currency_code' => 'USD',
            'closing_balance' => 10000.00,
        ]);

        // Verify no till balances exist for other currencies
        $this->assertDatabaseMissing('till_balances', [
            'till_id' => (string) $counter->id,
            'currency_code' => 'EUR',
        ]);
        $this->assertDatabaseMissing('till_balances', [
            'till_id' => (string) $counter->id,
            'currency_code' => 'GBP',
        ]);
    }
}
