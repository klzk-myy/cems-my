<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected User $manager;

    protected Currency $currency;

    protected Counter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teller = User::factory()->create(['role' => 'teller', 'is_active' => true]);
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        $this->currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );

        $this->counter = Counter::factory()->create(['status' => 'active']);
    }

    public function test_user_can_view_counters_list(): void
    {
        $response = $this->actingAs($this->teller)
            ->get(route('counters.index'));

        $response->assertStatus(200)
            ->assertViewIs('counters.index')
            ->assertViewHas(['counters', 'stats']);
    }

    public function test_user_can_open_counter_form(): void
    {
        $response = $this->actingAs($this->teller)
            ->get(route('counters.open.show', $this->counter));

        $response->assertStatus(200)
            ->assertViewIs('counters.open')
            ->assertViewHas(['counter', 'currencies']);
    }

    public function test_user_can_open_counter(): void
    {
        $response = $this->actingAs($this->teller)
            ->post(route('counters.open', $this->counter), [
                'opening_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 10000.00],
                ],
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $this->counter->id,
            'user_id' => $this->teller->id,
            'status' => 'open',
        ]);
    }

    public function test_user_can_close_counter(): void
    {
        // First open the counter
        $this->actingAs($this->teller)
            ->post(route('counters.open', $this->counter), [
                'opening_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 10000.00],
                ],
            ]);

        // Now close it with exact same amount
        $response = $this->actingAs($this->teller)
            ->post(route('counters.close', $this->counter), [
                'closing_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 10000.00],
                ],
                'notes' => 'Test close',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $this->counter->id,
            'status' => 'closed',
        ]);
    }

    public function test_user_can_view_counter_history(): void
    {
        $response = $this->actingAs($this->teller)
            ->get(route('counters.history', $this->counter));

        $response->assertStatus(200)
            ->assertViewIs('counters.history')
            ->assertViewHas(['counter', 'sessions']);
    }

    public function test_user_can_view_handover_form(): void
    {
        // Need a second user for handover
        User::factory()->create(['role' => 'teller', 'is_active' => true]);

        // First open the counter
        $this->actingAs($this->teller)
            ->post(route('counters.open', $this->counter), [
                'opening_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 10000.00],
                ],
            ]);

        $response = $this->actingAs($this->teller)
            ->get(route('counters.handover.show', $this->counter));

        // Should return 200 with the view
        $response->assertStatus(200)
            ->assertViewIs('counters.handover')
            ->assertViewHas(['counter', 'session', 'availableUsers']);
    }

    public function test_user_cannot_open_already_open_counter(): void
    {
        // First open the counter
        $response1 = $this->actingAs($this->teller)
            ->post(route('counters.open', $this->counter), [
                'opening_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 10000.00],
                ],
            ]);

        $response1->assertRedirect();

        // Try to open again with same user - should fail
        $response2 = $this->actingAs($this->teller)
            ->post(route('counters.open', $this->counter), [
                'opening_floats' => [
                    ['currency_id' => $this->currency->code, 'amount' => 5000.00],
                ],
            ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('error');
    }

    public function test_counter_api_returns_status(): void
    {
        $response = $this->actingAs($this->teller)
            ->getJson(route('counters.status', $this->counter));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
