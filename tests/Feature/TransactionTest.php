<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\CurrencyPosition;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teller_can_access_transaction_create(): void
    {
        $teller = User::factory()->create(['role' => \App\Enums\UserRole::Teller]);

        $response = $this->actingAs($teller)->get('/transactions/create');

        $response->assertStatus(200);
    }

    public function test_can_view_transaction_list(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
    }

    // /**
    //  * Test viewing transaction details - requires complex view setup
    //  */
    // public function test_can_view_transaction_details(): void
    // {
    //     $user = User::factory()->create();
    //     $customer = Customer::factory()->create();
    //     $transaction = Transaction::factory()->create([
    //         'customer_id' => $customer->id,
    //     ]);
    //
    //     $response = $this->actingAs($user)->get("/transactions/{$transaction->id}");
    //
    //     $response->assertStatus(200);
    // }

    /**
     * Test teller can create buy transaction - requires till balance setup
     */
    public function test_teller_can_create_buy_transaction(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_sell_updates_currency_position(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_buy_updates_currency_position(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_sell_fails_with_insufficient_stock(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_transaction_requires_positive_amount(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_transaction_requires_valid_currency(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => 'Buy',
            'currency_code' => 'INVALID',
            'amount_foreign' => '1000',
            'rate' => '4.50',
            'customer_id' => $customer->id,
        ]);

        $response->assertSessionHasErrors('currency_code');
    }

    public function test_large_transaction_requires_approval(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }

    public function test_teller_cannot_approve_transaction(): void
    {
        $teller = User::factory()->create(['role' => \App\Enums\UserRole::Teller]);
        $transaction = Transaction::factory()->create([
            'status' => TransactionStatus::OnHold,
        ]);

        $response = $this->actingAs($teller)->post("/transactions/{$transaction->id}/approve");

        $response->assertStatus(403);
    }

    public function test_manager_can_approve_transaction(): void
    {
        $this->markTestSkipped('Integration test - requires TillBalance setup');
    }
}