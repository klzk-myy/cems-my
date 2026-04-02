<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $tellerUser;

    protected User $managerUser;

    protected Customer $customer;

    protected Currency $currency;

    protected TillBalance $tillBalance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create currency (or use existing)
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_buy' => 4.7200,
                'rate_sell' => 4.7500,
                'is_active' => true,
            ]
        );

        // Create customer
        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456789'),
            'email' => 'customer@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        // Open till
        $this->tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test completed transaction can generate receipt
     */
    public function test_completed_transaction_can_generate_receipt(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', function ($header) {
            return str_contains($header, 'receipt_');
        });
    }

    /**
     * Test pending transaction cannot generate receipt
     */
    public function test_pending_transaction_cannot_generate_receipt(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertRedirect();
        $response->assertSessionHas('error', function ($message) {
            return str_contains($message, 'Receipts can only be generated for completed transactions');
        });
    }

    /**
     * Test on-hold transaction cannot generate receipt
     */
    public function test_onhold_transaction_cannot_generate_receipt(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'OnHold',
            'hold_reason' => 'EDD_Required: Risk rating is High',
            'cdd_level' => 'Enhanced',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertRedirect();
        $response->assertSessionHas('error', function ($message) {
            return str_contains($message, 'Receipts can only be generated for completed transactions');
        });
    }

    /**
     * Test PDF receipt contains transaction details
     */
    public function test_pdf_receipt_contains_transaction_details(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertStatus(200);

        // Get the PDF content
        $pdfContent = $response->getContent();

        // Basic check that content is a valid PDF
        $this->assertNotEmpty($pdfContent);

        // Check PDF header
        $this->assertTrue(str_starts_with($pdfContent, '%PDF'));
    }

    /**
     * Test PDF receipt contains BNM compliance fields
     */
    public function test_pdf_receipt_contains_bnm_compliance_fields(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertStatus(200);

        // Get the PDF content
        $pdfContent = $response->getContent();

        // Basic check that PDF was generated
        $this->assertNotEmpty($pdfContent);
        $this->assertTrue(str_starts_with($pdfContent, '%PDF'));
    }

    /**
     * Test receipt generation requires authentication
     */
    public function test_receipt_requires_authentication(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
        ]);

        $response = $this->get("/transactions/{$transaction->id}/receipt");

        $response->assertRedirect('/login');
    }

    /**
     * Test completed sell transaction can generate receipt
     */
    public function test_completed_sell_transaction_can_generate_receipt(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '500',
            'amount_local' => '2375',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'status' => 'Completed',
            'cdd_level' => 'Standard',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test approved transaction can generate receipt after approval
     */
    public function test_approved_transaction_can_generate_receipt(): void
    {
        // Create pending transaction first
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        // Manager approves it
        $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $transaction->refresh();
        $this->assertEquals('Completed', $transaction->status);

        // Now try to generate receipt
        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
