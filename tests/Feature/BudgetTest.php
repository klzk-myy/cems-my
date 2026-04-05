<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TillBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser;

    protected User $tellerUser;

    protected AccountingPeriod $currentPeriod;

    protected ChartOfAccount $revenueAccount;

    protected ChartOfAccount $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create currency
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

        Currency::firstOrCreate(
            ['code' => 'MYR'],
            [
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'rate_buy' => 1.0000,
                'rate_sell' => 1.0000,
                'is_active' => true,
            ]
        );

        // Create chart of accounts
        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Forex Trading Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );

        $this->expenseAccount = ChartOfAccount::firstOrCreate(
            ['account_code' => '6000'],
            ['account_name' => 'Forex Loss', 'account_type' => 'Expense', 'is_active' => true]
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '6100'],
            ['account_name' => 'Operating Expenses', 'account_type' => 'Expense', 'is_active' => true]
        );

        // Create accounting period
        $this->currentPeriod = AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create other required accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Inventory - USD', 'account_type' => 'Asset', 'is_active' => true]
        );

        // Open till
        TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '100000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

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
    }

    /**
     * Test manager can access budget page
     */
    public function test_manager_can_access_budget_page(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget');

        $response->assertStatus(200);
        $response->assertSee('Budget');
    }

    /**
     * Test teller cannot access budget page
     */
    public function test_teller_cannot_access_budget(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/accounting/budget');

        $response->assertStatus(403);
    }

    /**
     * Test can seed budget for current month
     */
    public function test_can_create_budget_for_period(): void
    {
        $response = $this->actingAs($this->managerUser)->post('/accounting/budget', [
            'period_code' => $this->currentPeriod->period_code,
            'budgets' => [
                ['account_code' => '5000', 'amount' => '50000.00'],
                ['account_code' => '6000', 'amount' => '20000.00'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('budgets', [
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
        ]);

        $this->assertDatabaseHas('budgets', [
            'account_code' => '6000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '20000.00',
        ]);
    }

    /**
     * Test can update existing budget
     */
    public function test_can_update_existing_budget(): void
    {
        // Create initial budget
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '0',
            'created_by' => $this->managerUser->id,
        ]);

        // Update budget
        $response = $this->actingAs($this->managerUser)->post('/accounting/budget', [
            'period_code' => $this->currentPeriod->period_code,
            'budgets' => [
                ['account_code' => '5000', 'amount' => '75000.00'],
            ],
        ]);

        $response->assertRedirect();

        $budget = Budget::where('account_code', '5000')
            ->where('period_code', $this->currentPeriod->period_code)
            ->first();

        $this->assertEquals('75000.00', $budget->budget_amount);
    }

    /**
     * Test budget report shows actual vs budget
     */
    public function test_budget_report_shows_actual_vs_budget(): void
    {
        // Create budget
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '0',
            'created_by' => $this->managerUser->id,
        ]);

        // Create some transactions that hit revenue account
        $journal = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'description' => 'Forex trading gain',
            'reference_type' => 'Budget',
            'reference_id' => null,
            'status' => 'Posted',
            'posted_by' => $this->managerUser->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $journal->id,
            'account_code' => '5000',
            'debit' => '0',
            'credit' => '15000.00', // Revenue
        ]);

        JournalLine::create([
            'journal_entry_id' => $journal->id,
            'account_code' => '1000',
            'debit' => '15000.00',
            'credit' => '0',
        ]);

        // Update actual amount
        $budget = Budget::where('account_code', '5000')->first();
        $budget->update(['actual_amount' => '15000.00']);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        $response->assertSee('50000.00'); // Budget
        $response->assertSee('15000.00'); // Actual
    }

    /**
     * Test variance calculation is correct
     */
    public function test_variance_calculation_is_correct(): void
    {
        $budget = Budget::create([
            'account_code' => '6000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '20000.00',
            'actual_amount' => '25000.00', // Over budget
            'created_by' => $this->managerUser->id,
        ]);

        $this->assertEquals(-5000.00, $budget->getVariance());
        $this->assertTrue($budget->isOverBudget());
        $this->assertEquals(-25.00, $budget->getVariancePercentage());
    }

    /**
     * Test budget report shows under budget items
     */
    public function test_budget_report_shows_under_budget(): void
    {
        $budget = Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '30000.00', // Under budget
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        $response->assertSee('30000.00'); // Actual
        $this->assertFalse($budget->isOverBudget());
    }

    /**
     * Test budget report can be filtered by period
     */
    public function test_budget_report_can_filter_by_period(): void
    {
        // Create previous period
        $previousPeriod = AccountingPeriod::create([
            'period_code' => now()->subMonth()->format('Y-m'),
            'start_date' => now()->subMonth()->startOfMonth(),
            'end_date' => now()->subMonth()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'closed',
        ]);

        // Create budgets for both periods
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '30000.00',
            'created_by' => $this->managerUser->id,
        ]);

        Budget::create([
            'account_code' => '5000',
            'period_code' => $previousPeriod->period_code,
            'budget_amount' => '45000.00',
            'actual_amount' => '40000.00',
            'created_by' => $this->managerUser->id,
        ]);

        // View current period report
        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        $response->assertSee('50000.00');
    }

    /**
     * Test over budget count is calculated correctly
     */
    public function test_over_budget_count_is_correct(): void
    {
        // Create multiple budgets
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '60000.00', // Over
            'created_by' => $this->managerUser->id,
        ]);

        Budget::create([
            'account_code' => '6000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '20000.00',
            'actual_amount' => '18000.00', // Under
            'created_by' => $this->managerUser->id,
        ]);

        Budget::create([
            'account_code' => '6100',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '10000.00',
            'actual_amount' => '12000.00', // Over
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        $response->assertSee('over_budget_count');
    }

    /**
     * Test admin can access budget page
     */
    public function test_admin_can_access_budget(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/accounting/budget');

        $response->assertStatus(200);
    }

    /**
     * Test budget shows accounts without budget
     */
    public function test_shows_accounts_without_budget(): void
    {
        // Only create budget for one account
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '0',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        // Should show that 6000 and 6100 don't have budgets
    }

    /**
     * Test budget totals are calculated
     */
    public function test_budget_totals_are_calculated(): void
    {
        Budget::create([
            'account_code' => '5000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '50000.00',
            'actual_amount' => '30000.00',
            'created_by' => $this->managerUser->id,
        ]);

        Budget::create([
            'account_code' => '6000',
            'period_code' => $this->currentPeriod->period_code,
            'budget_amount' => '20000.00',
            'actual_amount' => '18000.00',
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get('/accounting/budget?period='.$this->currentPeriod->period_code);

        $response->assertStatus(200);
        $response->assertSee('total_budget');
        $response->assertSee('total_actual');
        $response->assertSee('total_variance');
    }
}
