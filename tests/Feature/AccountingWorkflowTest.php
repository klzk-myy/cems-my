<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Accounting Workflow Tests
 *
 * Tests comprehensive accounting workflows including:
 * - Journal entry creation
 * - Journal entry reversal
 * - Period closing
 */
class AccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected AccountingService $accountingService;

    protected MathService $mathService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
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

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Initialize services
        $this->mathService = new MathService;
        $this->accountingService = new AccountingService($this->mathService);

        // Create chart of accounts
        $this->createChartOfAccounts();

        // Create accounting period
        $this->createAccountingPeriod();
    }

    /**
     * Create chart of accounts for testing
     */
    protected function createChartOfAccounts(): void
    {
        // Asset accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1100'],
            ['account_name' => 'Cash - USD', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Foreign Currency Inventory', 'account_type' => 'Asset', 'is_active' => true]
        );

        // Liability accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '3000'],
            ['account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '3100'],
            ['account_name' => 'Retained Earnings', 'account_type' => 'Liability', 'is_active' => true]
        );

        // Equity accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Opening Balance Equity', 'account_type' => 'Equity', 'is_active' => true]
        );

        // Revenue accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Forex Trading Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5100'],
            ['account_name' => 'Revaluation Gain', 'account_type' => 'Revenue', 'is_active' => true]
        );

        // Expense accounts
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6000'],
            ['account_name' => 'Forex Loss', 'account_type' => 'Expense', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6100'],
            ['account_name' => 'Revaluation Loss', 'account_type' => 'Expense', 'is_active' => true]
        );
    }

    /**
     * Create accounting period for testing
     */
    protected function createAccountingPeriod(): void
    {
        AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);
    }

    /**
     * Test journal entry creation through service
     */
    public function test_journal_entry_creation_through_service(): void
    {
        $this->actingAs($this->managerUser);

        $lines = [
            [
                'account_code' => '1000',
                'debit' => '1000.00',
                'credit' => '0',
                'description' => 'Cash received',
            ],
            [
                'account_code' => '5000',
                'debit' => '0',
                'credit' => '1000.00',
                'description' => 'Service revenue',
            ],
        ];

        $entry = $this->accountingService->createJournalEntry(
            $lines,
            'Manual',
            null,
            'Test journal entry',
            now()->toDateString(),
            $this->managerUser->id
        );

        $this->assertNotNull($entry);
        $this->assertEquals('Posted', $entry->status);
        $this->assertEquals('Test journal entry', $entry->description);
        $this->assertEquals(2, $entry->lines->count());

        // Verify journal lines - using bccomp for decimal comparison
        $debitLine = $entry->lines->where('account_code', '1000')->first();
        $creditLine = $entry->lines->where('account_code', '5000')->first();

        $this->assertTrue(
            bccomp($debitLine->debit, '1000.00', 4) === 0,
            "Expected 1000.00 but got {$debitLine->debit}"
        );
        $this->assertTrue(
            bccomp($debitLine->credit, '0', 4) === 0,
            "Expected 0 but got {$debitLine->credit}"
        );
        $this->assertTrue(
            bccomp($creditLine->debit, '0', 4) === 0,
            "Expected 0 but got {$creditLine->debit}"
        );
        $this->assertTrue(
            bccomp($creditLine->credit, '1000.00', 4) === 0,
            "Expected 1000.00 but got {$creditLine->credit}"
        );

        // Verify system log created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->managerUser->id,
            'action' => 'journal_entry_created',
            'entity_type' => 'JournalEntry',
            'entity_id' => $entry->id,
        ]);
    }

    /**
     * Test journal entry creation via API
     */
    public function test_journal_entry_creation_via_api(): void
    {
        $this->actingAs($this->managerUser);

        $response = $this->post('/accounting/journal', [
            'entry_date' => now()->toDateString(),
            'description' => 'API test journal entry',
            'lines' => [
                [
                    'account_code' => '1000',
                    'debit' => '500.00',
                    'credit' => '0',
                    'description' => 'Cash deposit',
                ],
                [
                    'account_code' => '5000',
                    'debit' => '0',
                    'credit' => '500.00',
                    'description' => 'Revenue',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Journal entry created successfully.');

        $this->assertDatabaseHas('journal_entries', [
            'description' => 'API test journal entry',
            'status' => 'Posted',
        ]);
    }

    /**
     * Test unbalanced journal entry is rejected
     */
    public function test_unbalanced_journal_entry_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry is not balanced');

        $lines = [
            [
                'account_code' => '1000',
                'debit' => '1000.00',
                'credit' => '0',
            ],
            [
                'account_code' => '5000',
                'debit' => '0',
                'credit' => '500.00', // This makes it unbalanced
            ],
        ];

        $this->accountingService->createJournalEntry(
            $lines,
            'Manual',
            null,
            'Unbalanced entry',
            now()->toDateString(),
            $this->managerUser->id
        );
    }

    /**
     * Test journal entry reversal through service
     */
    public function test_journal_entry_reversal_through_service(): void
    {
        $this->actingAs($this->managerUser);

        // Create original entry
        $originalEntry = $this->accountingService->createJournalEntry(
            [
                [
                    'account_code' => '1000',
                    'debit' => '2000.00',
                    'credit' => '0',
                    'description' => 'Original debit',
                ],
                [
                    'account_code' => '5000',
                    'debit' => '0',
                    'credit' => '2000.00',
                    'description' => 'Original credit',
                ],
            ],
            'Manual',
            null,
            'Original entry',
            now()->toDateString(),
            $this->managerUser->id
        );

        // Reverse the entry
        $reversalEntry = $this->accountingService->reverseJournalEntry(
            $originalEntry,
            'Testing reversal',
            $this->managerUser->id
        );

        // Verify reversal entry exists
        $this->assertNotNull($reversalEntry);
        $this->assertEquals('Posted', $reversalEntry->status);
        $this->assertEquals('Reversal', $reversalEntry->reference_type);
        $this->assertEquals($originalEntry->id, $reversalEntry->reference_id);

        // Verify reversal has opposite debits/credits
        $originalDebitLine = $originalEntry->lines->where('account_code', '1000')->first();
        $reversalDebitLine = $reversalEntry->lines->where('account_code', '1000')->first();

        // Original debited 1000, reversal credits 1000 (and vice versa)
        $this->assertEquals($originalDebitLine->debit, $reversalDebitLine->credit);
        $this->assertEquals($originalDebitLine->credit, $reversalDebitLine->debit);

        // Verify original entry is marked as reversed
        $originalEntry->refresh();
        $this->assertEquals('Reversed', $originalEntry->status);
        $this->assertEquals($this->managerUser->id, $originalEntry->reversed_by);
    }

    /**
     * Test journal entry reversal via API
     */
    public function test_journal_entry_reversal_via_api(): void
    {
        $this->actingAs($this->managerUser);

        // Create original entry
        $originalEntry = $this->accountingService->createJournalEntry(
            [
                [
                    'account_code' => '1000',
                    'debit' => '300.00',
                    'credit' => '0',
                ],
                [
                    'account_code' => '5000',
                    'debit' => '0',
                    'credit' => '300.00',
                ],
            ],
            'Manual',
            null,
            'Entry to reverse',
            now()->toDateString(),
            $this->managerUser->id
        );

        // Reverse via API
        $response = $this->post("/accounting/journal/{$originalEntry->id}/reverse", [
            'reason' => 'API reversal test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Entry reversed successfully.');

        // Verify original is marked as reversed
        $originalEntry->refresh();
        $this->assertEquals('Reversed', $originalEntry->status);
    }

    /**
     * Test already reversed entry cannot be reversed again
     */
    public function test_already_reversed_entry_cannot_be_reversed_again(): void
    {
        $this->actingAs($this->managerUser);

        // Create and reverse entry
        $originalEntry = $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '100.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '100.00'],
            ],
            'Manual',
            null,
            'Test entry',
            now()->toDateString(),
            $this->managerUser->id
        );

        $this->accountingService->reverseJournalEntry($originalEntry, 'First reversal');

        // Try to reverse again
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry has already been reversed');

        $this->accountingService->reverseJournalEntry($originalEntry, 'Second reversal');
    }

    /**
     * Test period closing through service
     */
    public function test_period_closing_through_service(): void
    {
        $this->actingAs($this->managerUser);

        $period = AccountingPeriod::where('period_code', now()->format('Y-m'))->first();

        // Create some journal entries with revenue and expenses
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '5000.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '5000.00'],
            ],
            'Manual',
            null,
            'Revenue entry',
            now()->toDateString(),
            $this->managerUser->id
        );

        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '6000', 'debit' => '2000.00', 'credit' => '0'],
                ['account_code' => '1000', 'debit' => '0', 'credit' => '2000.00'],
            ],
            'Manual',
            null,
            'Expense entry',
            now()->toDateString(),
            $this->managerUser->id
        );

        // Close period
        $periodCloseService = new \App\Services\PeriodCloseService(
            $this->accountingService,
            $this->mathService
        );

        $result = $periodCloseService->closePeriod($period, $this->managerUser->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('closed', $period->status);
        $this->assertNotNull($period->closed_at);
        $this->assertEquals($this->managerUser->id, $period->closed_by);

        // Verify system log created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->managerUser->id,
            'action' => 'period_closed',
            'entity_type' => 'AccountingPeriod',
            'entity_id' => $period->id,
        ]);
    }

    /**
     * Test period closing via API
     */
    public function test_period_closing_via_api(): void
    {
        $this->actingAs($this->managerUser);

        $period = AccountingPeriod::where('period_code', now()->format('Y-m'))->first();

        $response = $this->post("/accounting/periods/{$period->id}/close");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $period->refresh();
        $this->assertEquals('closed', $period->status);
    }

    /**
     * Test closed period cannot be closed again
     */
    public function test_closed_period_cannot_be_closed_again(): void
    {
        $this->actingAs($this->managerUser);

        $period = AccountingPeriod::where('period_code', now()->format('Y-m'))->first();
        $period->update(['status' => 'closed', 'closed_at' => now()]);

        $periodCloseService = new \App\Services\PeriodCloseService(
            $this->accountingService,
            $this->mathService
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Period is already closed');

        $periodCloseService->closePeriod($period, $this->managerUser->id);
    }

    /**
     * Test journal entry to closed period is rejected
     */
    public function test_journal_entry_to_closed_period_rejected(): void
    {
        $this->actingAs($this->managerUser);

        // Delete existing period if any and create a new closed one
        AccountingPeriod::where('period_code', now()->format('Y-m'))->delete();

        $period = AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $this->managerUser->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot post to closed period');

        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '100.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '100.00'],
            ],
            'Manual',
            null,
            'Entry to closed period',
            now()->toDateString(),
            $this->managerUser->id
        );
    }

    /**
     * Test teller cannot access accounting routes
     */
    public function test_teller_cannot_access_accounting_routes(): void
    {
        $this->actingAs($this->tellerUser);

        $routes = [
            ['GET', '/accounting'],
            ['GET', '/accounting/journal'],
            ['GET', '/accounting/journal/create'],
            ['POST', '/accounting/journal'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->call($method, $uri);
            $this->assertEquals(403, $response->status());
        }
    }

    /**
     * Test manager can view journal entries
     */
    public function test_manager_can_view_journal_entries(): void
    {
        $this->actingAs($this->managerUser);

        // Create some entries
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '100.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '100.00'],
            ],
            'Manual',
            null,
            'Test entry 1',
            now()->toDateString(),
            $this->managerUser->id
        );

        $response = $this->get('/accounting/journal');
        $response->assertStatus(200);
        $response->assertSee('Test entry 1');
    }

    /**
     * Test journal entry shows with lines
     */
    public function test_journal_entry_show_displays_lines(): void
    {
        $this->actingAs($this->managerUser);

        $entry = $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '250.00', 'credit' => '0', 'description' => 'Line 1'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '250.00', 'description' => 'Line 2'],
            ],
            'Manual',
            null,
            'Entry with line descriptions',
            now()->toDateString(),
            $this->managerUser->id
        );

        $response = $this->get("/accounting/journal/{$entry->id}");
        $response->assertStatus(200);
        $response->assertSee('Line 1');
        $response->assertSee('Line 2');
    }

    /**
     * Test ledger balance updates after journal entry
     */
    public function test_ledger_balance_updates_after_journal_entry(): void
    {
        $this->actingAs($this->managerUser);

        // Create entry
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '1000.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '1000.00'],
            ],
            'Manual',
            null,
            'Balance test',
            now()->toDateString(),
            $this->managerUser->id
        );

        // Check balance
        $balance = $this->accountingService->getAccountBalance('1000');
        $this->assertTrue(
            bccomp($balance, '1000.00', 4) === 0,
            "Expected 1000.00 but got {$balance}"
        );

        // Create another entry
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '500.00', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '500.00'],
            ],
            'Manual',
            null,
            'Balance test 2',
            now()->toDateString(),
            $this->managerUser->id
        );

        // Check updated balance
        $balance = $this->accountingService->getAccountBalance('1000');
        $this->assertTrue(
            bccomp($balance, '1500.00', 4) === 0,
            "Expected 1500.00 but got {$balance}"
        );
    }

    /**
     * Test journal entry validation requires at least 2 lines
     */
    public function test_journal_entry_requires_at_least_two_lines(): void
    {
        $this->actingAs($this->managerUser);

        $response = $this->post('/accounting/journal', [
            'entry_date' => now()->toDateString(),
            'description' => 'Single line entry',
            'lines' => [
                [
                    'account_code' => '1000',
                    'debit' => '100.00',
                    'credit' => '0',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
    }

    /**
     * Test journal entry requires valid account codes
     */
    public function test_journal_entry_requires_valid_account_codes(): void
    {
        $this->actingAs($this->managerUser);

        $response = $this->post('/accounting/journal', [
            'entry_date' => now()->toDateString(),
            'description' => 'Invalid account',
            'lines' => [
                [
                    'account_code' => '9999', // Invalid
                    'debit' => '100.00',
                    'credit' => '0',
                ],
                [
                    'account_code' => '5000',
                    'debit' => '0',
                    'credit' => '100.00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lines.0.account_code');
    }

    /**
     * Test accounting period list loads
     */
    public function test_accounting_period_list_loads(): void
    {
        $this->actingAs($this->managerUser);

        $response = $this->get('/accounting/periods');
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y-m'));
    }
}
