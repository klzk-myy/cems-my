<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountingService(new MathService);

        // Seed chart of accounts
        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        // Assets - use firstOrCreate to avoid duplicates
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset']
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Revenue', 'account_type' => 'Revenue']
        );
    }

    public function test_can_create_journal_entry()
    {
        $user = User::factory()->create();

        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $entry = $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Test transaction',
            now()->toDateString(),
            $user->id
        );

        $this->assertInstanceOf(JournalEntry::class, $entry);
        // Entries are now created as Draft and must be approved
        $this->assertEquals('Draft', $entry->status);
        $this->assertNull($entry->posted_by);

        // Submit for approval then approve (submitForApproval returns fresh model)
        $entry = $this->service->submitForApproval($entry);
        $this->assertEquals('Pending', $entry->status);

        $this->service->approveEntry($entry, $user->id);
        $this->assertEquals('Posted', $entry->status);
        $this->assertEquals($user->id, $entry->posted_by);

        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $entry->id, 'account_code' => '1000', 'debit' => 1000.00]);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $entry->id, 'account_code' => '4000', 'credit' => 1000.00]);
    }

    public function test_journal_entry_must_be_balanced()
    {
        $user = User::factory()->create();

        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 500.00],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry is not balanced');

        $this->service->createJournalEntry(
            $lines,
            'Test',
            1,
            'Unbalanced entry',
            now()->toDateString(),
            $user->id
        );
    }

    public function test_can_reverse_journal_entry()
    {
        $user = User::factory()->create();

        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $entry = $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Original entry',
            now()->toDateString(),
            $user->id
        );

        // Must submit and approve before reversing (submitForApproval returns fresh model)
        $entry = $this->service->submitForApproval($entry);
        $this->service->approveEntry($entry, $user->id);
        // Refresh to get the Posted status
        $entry->refresh();

        $reversal = $this->service->reverseJournalEntry($entry, 'Correction', $user->id);

        $this->assertInstanceOf(JournalEntry::class, $reversal);
        $this->assertEquals('Posted', $reversal->status);
        $this->assertStringContainsString('Reversal', $reversal->description);
        $this->assertEquals('Reversed', $entry->fresh()->status);
    }

    public function test_validate_balanced_returns_true_for_balanced_entry()
    {
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $this->assertTrue($this->service->validateBalanced($lines));
    }

    public function test_validate_balanced_returns_false_for_unbalanced_entry()
    {
        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 500.00],
        ];

        $this->assertFalse($this->service->validateBalanced($lines));
    }

    public function test_get_account_balance_returns_correct_balance()
    {
        $user = User::factory()->create();

        $lines = [
            ['account_code' => '1000', 'debit' => 1000.00, 'credit' => 0],
            ['account_code' => '4000', 'debit' => 0, 'credit' => 1000.00],
        ];

        $entry = $this->service->createJournalEntry(
            $lines,
            'Transaction',
            1,
            'Test',
            now()->toDateString(),
            $user->id
        );

        // Must submit and approve for ledger to be updated
        $this->service->submitForApproval($entry);
        $this->service->approveEntry($entry, $user->id);

        $cashBalance = $this->service->getAccountBalance('1000');
        $this->assertEqualsWithDelta(1000.00, (float) $cashBalance, 0.01);
    }

    public function test_get_account_balance_returns_zero_for_no_entries()
    {
        $balance = $this->service->getAccountBalance('1000');
        $this->assertEquals('0', $balance);
    }
}
