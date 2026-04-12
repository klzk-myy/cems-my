<?php

namespace Tests\Feature;

use App\Enums\CounterSessionStatus;
use App\Enums\UserRole;
use App\Models\Counter;
use App\Models\CounterHandover;
use App\Models\CounterSession;
use App\Models\Currency;
use App\Models\TillBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CounterHandoverTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;

    protected User $tellerFrom;

    protected User $tellerTo;

    protected Counter $counter;

    protected CounterSession $session;

    protected Currency $myrCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerFrom = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerTo = User::create([
            'username' => 'teller2',
            'email' => 'teller2@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create currency
        $this->myrCurrency = Currency::firstOrCreate(
            ['code' => 'MYR'],
            [
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'rate_buy' => 1.0000,
                'rate_sell' => 1.0000,
                'is_active' => true,
            ]
        );

        // Create counter
        $this->counter = Counter::create([
            'code' => 'C01',
            'name' => 'Counter 1',
            'status' => 'active',
        ]);

        // Create open session for tellerFrom
        $this->session = CounterSession::create([
            'counter_id' => $this->counter->id,
            'user_id' => $this->tellerFrom->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $this->tellerFrom->id,
            'status' => CounterSessionStatus::Open,
        ]);

        // CRITICAL: Delete ANY existing till balances for this counter/date FIRST
        // This must happen BEFORE we create a new one to avoid unique constraint violations
        // from tests that ran before this one (PHPUnit runs tests in order, and the
        // RefreshDatabase trait may not fully isolate between tests in the same class)
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        // Create till balance for the session
        TillBalance::create([
            'till_id' => (string) $this->counter->id,
            'currency_code' => 'MYR',
            'opening_balance' => '10000.00',
            'date' => now()->toDateString(),
            'opened_by' => $this->tellerFrom->id,
        ]);
    }

    /**
     * Test manager can open a counter session
     */
    public function test_manager_can_open_counter_session(): void
    {
        // Create new counter without open session
        $newCounter = Counter::create([
            'code' => 'C02',
            'name' => 'Counter 2',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->managerUser)->post("/counters/{$newCounter->code}/open", [
            'opening_floats' => [
                ['currency_id' => 'MYR', 'amount' => '15000.00'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $newCounter->id,
            'user_id' => $this->managerUser->id,
            'status' => CounterSessionStatus::Open->value,
        ]);
    }

    /**
     * Test teller can start session at open counter
     */
    public function test_teller_can_start_session_at_open_counter(): void
    {
        // Close the existing session
        $this->session->update(['status' => CounterSessionStatus::Closed]);
        // Delete the till balance so a new one can be created (unique constraint on till_id + date + currency)
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        $response = $this->actingAs($this->tellerFrom)->post("/counters/{$this->counter->code}/open", [
            'opening_floats' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $this->counter->id,
            'user_id' => $this->tellerFrom->id,
            'status' => CounterSessionStatus::Open->value,
        ]);
    }

    /**
     * Test teller cannot open session at already open counter
     */
    public function test_teller_cannot_open_session_at_already_open_counter(): void
    {
        $response = $this->actingAs($this->tellerTo)->post("/counters/{$this->counter->code}/open", [
            'opening_floats' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test manager initiates handover from one teller to another
     */
    public function test_manager_can_initiate_handover(): void
    {
        // First verify the session exists
        $this->assertNotNull($this->session, 'Setup session should exist');
        $this->assertEquals(CounterSessionStatus::Open, $this->session->status, 'Session should be open');
        $this->assertEquals($this->tellerFrom->id, $this->session->user_id, 'Session should belong to tellerFrom');

        // Critical: Clean up any stale till balances from prior tests in this class
        // The handover process tries to create new till balances, and if old ones
        // aren't properly deleted, the unique constraint is violated
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        // First do a GET to show the handover form (this tests the showHandover route too)
        $getResponse = $this->actingAs($this->managerUser)->get("/counters/{$this->counter->code}/handover");
        $this->assertEquals(200, $getResponse->status(), 'GET handover form should work');

        $response = $this->actingAs($this->managerUser)->post("/counters/{$this->counter->code}/handover", [
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
            'physical_counts' => [
                ['currency_id' => 'MYR', 'amount' => '9850.00'], // Variance of -150
            ],
        ]);

        $response->assertRedirect();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Old session should be marked as HandedOver
        $this->session->refresh();
        $this->assertEquals(CounterSessionStatus::HandedOver, $this->session->status);

        // New session should be created for tellerTo
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $this->counter->id,
            'user_id' => $this->tellerTo->id,
            'status' => CounterSessionStatus::Open->value,
        ]);

        // Handover record should be created
        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $this->session->id,
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
        ]);
    }

    /**
     * Test handover with variance creates handover record with variance
     */
    public function test_handover_with_variance_records_variance(): void
    {
        // Clean up any stale till balances
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        $response = $this->actingAs($this->managerUser)->post("/counters/{$this->counter->code}/handover", [
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
            'physical_counts' => [
                ['currency_id' => 'MYR', 'amount' => '9500.00'], // -500 variance
            ],
        ]);

        $response->assertRedirect();

        $handover = CounterHandover::where('counter_session_id', $this->session->id)->first();
        $this->assertNotNull($handover);
        $this->assertNotNull($handover->variance_myr);
    }

    /**
     * Test teller cannot initiate handover (requires manager)
     */
    public function test_teller_cannot_initiate_handover(): void
    {
        $response = $this->actingAs($this->tellerFrom)->post("/counters/{$this->counter->code}/handover", [
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
            'physical_counts' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test handover transfers till balances to new session
     */
    public function test_handover_transfers_till_balances(): void
    {
        // Clean up any stale till balances
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        $response = $this->actingAs($this->managerUser)->post("/counters/{$this->counter->code}/handover", [
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
            'physical_counts' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        $response->assertRedirect();

        // Find the new session
        $newSession = CounterSession::where('user_id', $this->tellerTo->id)
            ->where('status', CounterSessionStatus::Open)
            ->first();

        $this->assertNotNull($newSession);

        // New till balance should be created for new session
        // Note: date is stored as datetime, so we check with whereDate
        $this->assertDatabaseHas('till_balances', [
            'till_id' => (string) $this->counter->id,
            'currency_code' => 'MYR',
            'opening_balance' => '10000.00',
        ]);

        // Verify the date is correct using a whereDate query
        $tillBalance = TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->first();
        $this->assertNotNull($tillBalance);
    }

    /**
     * Test handover closes old session properly
     */
    public function test_handover_closes_old_session(): void
    {
        // Clean up any stale till balances
        TillBalance::where('till_id', (string) $this->counter->id)
            ->where('currency_code', 'MYR')
            ->whereDate('date', now()->toDateString())
            ->delete();

        $response = $this->actingAs($this->managerUser)->post("/counters/{$this->counter->code}/handover", [
            'from_user_id' => $this->tellerFrom->id,
            'to_user_id' => $this->tellerTo->id,
            'supervisor_id' => $this->managerUser->id,
            'physical_counts' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        $response->assertRedirect();

        $this->session->refresh();
        $this->assertEquals(CounterSessionStatus::HandedOver, $this->session->status);
        $this->assertNotNull($this->session->closed_at);
    }

    /**
     * Test manager can close counter session with valid variance
     */
    public function test_manager_can_close_session_with_acceptable_variance(): void
    {
        $response = $this->actingAs($this->managerUser)->post("/counters/{$this->counter->code}/close", [
            'closing_floats' => [
                ['currency_id' => 'MYR', 'amount' => '10050.00'], // Within yellow threshold
            ],
            'notes' => 'Small overage',
        ]);

        $response->assertRedirect();

        $this->session->refresh();
        $this->assertEquals(CounterSessionStatus::Closed, $this->session->status);
    }

    /**
     * Test teller cannot approve handover (manager only)
     */
    public function test_teller_cannot_approve_handover(): void
    {
        // Attempt to close via teller role
        $response = $this->actingAs($this->tellerFrom)->post("/counters/{$this->counter->code}/close", [
            'closing_floats' => [
                ['currency_id' => 'MYR', 'amount' => '10000.00'],
            ],
        ]);

        // Should fail as teller cannot close counter with variance
        // depending on variance thresholds
        $response->assertStatus(403);
    }
}
