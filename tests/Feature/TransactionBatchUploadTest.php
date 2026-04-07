<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\TransactionImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionBatchUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected Customer $customer;

    protected Currency $currency;

    protected TillBalance $tillBalance;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Create users
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

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

        // Create currency (or get existing)
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
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);
    }

    /**
     * @group skip
     * Test manager can access batch upload form
     */
    public function test_manager_can_access_batch_upload_form(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get(route('transactions.batch-upload'));

        $response->assertStatus(200);
        $response->assertSee('Batch Transaction Upload');
        $response->assertSee('CSV Format Requirements');
    }
    /**
     * @group skip
     * Test teller cannot access batch upload
     */
    public function test_teller_cannot_access_batch_upload(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get(route('transactions.batch-upload'));

        $response->assertStatus(403);
    }

    /**
     * Test manager can upload and process valid CSV
     */
    public function test_manager_can_upload_csv(): void
    {
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Buy,USD,1000,4.72,Travel,Savings,MAIN\n";
        $csvContent .= "{$this->customer->id},Buy,USD,500,4.73,Business,Business Income,MAIN\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        // Assert import record created
        $this->assertDatabaseHas('transaction_imports', [
            'user_id' => $this->managerUser->id,
            'original_filename' => 'transactions.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'success_count' => 2,
            'error_count' => 0,
        ]);

        // Assert transactions created
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000.0000',
            'status' => 'Completed',
        ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'amount_foreign' => '500.0000',
        ]);
    }

    /**
     * Test CSV with errors shows error report
     */
    public function test_csv_with_errors_shows_error_report(): void
    {
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Buy,USD,1000,4.72,Travel,Savings,MAIN\n";
        $csvContent .= "99999,Buy,USD,500,4.73,Business,Business Income,MAIN\n"; // Invalid customer
        $csvContent .= "{$this->customer->id},Buy,XXX,500,4.73,Business,Business Income,MAIN\n"; // Invalid currency

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        // Find the import record
        $import = TransactionImport::where('user_id', $this->managerUser->id)->first();
        $this->assertNotNull($import);
        $this->assertEquals(3, $import->total_rows);
        $this->assertEquals(1, $import->success_count);
        $this->assertEquals(2, $import->error_count);
        $this->assertTrue($import->hasErrors());

        // Check errors are stored
        $errors = $import->getErrors();
        $this->assertCount(2, $errors);
    }

    /**
     * Test manager can view import results
     */
    public function test_manager_can_view_import_results(): void
    {
        $import = TransactionImport::create([
            'user_id' => $this->managerUser->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'total_rows' => 5,
            'success_count' => 3,
            'error_count' => 2,
            'status' => 'completed',
            'errors' => [
                ['row' => 3, 'data' => ['1', 'Buy', 'USD', '100'], 'error' => 'Invalid customer'],
            ],
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('transactions.batch-upload.show', $import));

        $response->assertStatus(200);
        $response->assertSee('Import Results');
        $response->assertSee('test.csv');
        $response->assertSee('5'); // Total rows
        $response->assertSee('3'); // Success count
        $response->assertSee('2'); // Error count
    }

    /**
     * Test user can only view their own import results
     */
    public function test_user_can_only_view_own_import_results(): void
    {
        $otherManager = User::create([
            'username' => 'manager2',
            'email' => 'manager2@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $import = TransactionImport::create([
            'user_id' => $this->managerUser->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'total_rows' => 5,
            'success_count' => 5,
            'error_count' => 0,
            'status' => 'completed',
        ]);

        // Other manager should get 403
        $response = $this->actingAs($otherManager)
            ->get(route('transactions.batch-upload.show', $import));

        $response->assertStatus(403);
    }

    /**
     * @group skip
     * Test template download
     */
    public function test_template_download_works(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get(route('transactions.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="transaction_template.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('customer_id,type,currency_code', $content);
        $this->assertStringContainsString('Buy,USD,1000,4.72', $content);
        $this->assertStringContainsString('Sell,USD,500,4.75', $content);
    }

    /**
     * Test empty CSV validation
     */
    public function test_empty_csv_shows_error(): void
    {
        // Create a CSV file with only header, no data rows
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $file = UploadedFile::fake()->createWithContent('empty.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        // Assert import record created with 0 successful transactions
        $this->assertDatabaseHas('transaction_imports', [
            'user_id' => $this->managerUser->id,
            'original_filename' => 'empty.csv',
            'status' => 'completed',
            'total_rows' => 0,
            'success_count' => 0,
            'error_count' => 0,
        ]);
    }

    /**
     * Test only managers can upload
     */
    public function test_only_managers_can_upload(): void
    {
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Buy,USD,100,4.72,Travel,Savings,MAIN\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->tellerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test CSV with insufficient stock for sell
     */
    public function test_sell_fails_with_insufficient_stock(): void
    {
        // No stock setup - trying to sell should fail
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Sell,USD,1000,4.75,Travel,Savings,MAIN\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        $import = TransactionImport::first();
        $this->assertEquals(0, $import->success_count);
        $this->assertEquals(1, $import->error_count);

        $errors = $import->getErrors();
        $this->assertStringContainsString('Insufficient stock', $errors[0]['error']);
    }

    /**
     * Test CSV with closed till
     */
    public function test_fails_with_closed_till(): void
    {
        // Close the till
        $this->tillBalance->update(['closed_at' => now()]);

        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Buy,USD,100,4.72,Travel,Savings,MAIN\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        $import = TransactionImport::first();
        $this->assertEquals(0, $import->success_count);
        $this->assertEquals(1, $import->error_count);
    }

    /**
     * Test CSV with invalid transaction type
     */
    public function test_fails_with_invalid_transaction_type(): void
    {
        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Invalid,USD,100,4.72,Travel,Savings,MAIN\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        $import = TransactionImport::first();
        $this->assertEquals(0, $import->success_count);
        $this->assertEquals(1, $import->error_count);
    }

    /**
     * Test multiple transactions with mixed success/failure
     */
    public function test_mixed_success_and_failure(): void
    {
        // Setup stock for sell
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        $csvContent = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $csvContent .= "{$this->customer->id},Buy,USD,100,4.72,Travel,Savings,MAIN\n"; // Success
        $csvContent .= "{$this->customer->id},Sell,USD,200,4.75,Business,Business,MAIN\n"; // Success
        $csvContent .= "99999,Buy,USD,100,4.72,Travel,Savings,MAIN\n"; // Fail - invalid customer
        $csvContent .= "{$this->customer->id},Buy,USD,-50,4.72,Travel,Savings,MAIN\n"; // Fail - negative amount

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();

        $import = TransactionImport::first();
        $this->assertEquals(2, $import->success_count);
        $this->assertEquals(2, $import->error_count);

        // Verify 2 transactions were created
        $this->assertEquals(2, Transaction::count());
    }

    /**
     * Test CSV file size validation
     */
    public function test_file_size_validation(): void
    {
        // Create file larger than 2MB (2048 KB = 2097152 bytes)
        $file = UploadedFile::fake()->create('large.csv', 2049);

        $response = $this->actingAs($this->managerUser)
            ->post(route('transactions.batch-upload'), [
                'csv_file' => $file,
            ]);

        $response->assertSessionHasErrors('csv_file');
    }

    /**
     * @group skip
     * Test admin can also access batch upload
     */
    public function test_admin_can_access_batch_upload(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('transactions.batch-upload'));

        $response->assertStatus(200);
        $response->assertSee('Batch Transaction Upload');
    }
}
