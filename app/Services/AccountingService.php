<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

/**
 * Accounting Service
 *
 * Handles core accounting operations including journal entry creation,
 * validation, reversal, and account balance/activity queries.
 *
 * Ensures double-entry bookkeeping integrity and maintains ledger consistency.
 */
class AccountingService
{
    /**
     * Math service for high-precision calculations.
     */
    protected MathService $mathService;

    /**
     * Create a new AccountingService instance.
     *
     * @param  MathService  $mathService  Math service for precise calculations
     */
    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Create a new journal entry with validation.
     *
     * Validates that the entry is balanced (debits equal credits) and posts
     * to an open accounting period. Creates journal lines and updates ledger.
     *
     * @param  array  $lines  Array of journal line items with keys:
     *                        - account_code: string Account code
     *                        - debit?: float|int|string Debit amount (default: 0)
     *                        - credit?: float|int|string Credit amount (default: 0)
     *                        - description?: string Line description (optional)
     * @param  string  $referenceType  Type of reference (e.g., 'Invoice', 'Payment')
     * @param  int|null  $referenceId  Reference document ID (optional)
     * @param  string  $description  Entry description
     * @param  string|null  $entryDate  Entry date in YYYY-MM-DD format (default: today)
     * @param  int|null  $postedBy  User ID posting the entry (default: authenticated user)
     * @return JournalEntry Created journal entry with loaded lines
     *
     * @throws \InvalidArgumentException If entry is not balanced or period is closed
     */
    public function createJournalEntry(
        array $lines,
        string $referenceType,
        ?int $referenceId = null,
        string $description = '',
        ?string $entryDate = null,
        ?int $postedBy = null
    ): JournalEntry {
        $postedBy = $postedBy ?? auth()->id();
        $entryDate = $entryDate ?? now()->toDateString();

        return DB::transaction(function () use ($lines, $referenceType, $referenceId, $description, $entryDate, $postedBy) {
            if (! $this->validateBalanced($lines)) {
                throw new \InvalidArgumentException('Journal entry is not balanced: debits do not equal credits');
            }

            // Find the accounting period for this entry date
            $period = AccountingPeriod::forDate($entryDate)->first();

            // Validate that the period is open (if period exists)
            if ($period && ! $period->isOpen()) {
                throw new \InvalidArgumentException(
                    "Cannot post to closed period {$period->period_code}. Please use an open period or contact administrator."
                );
            }

            $entry = JournalEntry::create([
                'entry_date' => $entryDate,
                'period_id' => $period?->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => 'Posted',
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code' => $line['account_code'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            $this->updateLedger($entry);

            SystemLog::create([
                'user_id' => $postedBy,
                'action' => 'journal_entry_created',
                'entity_type' => 'JournalEntry',
                'entity_id' => $entry->id,
                'new_values' => [
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'description' => $description,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $entry->fresh()->load('lines');
        });
    }

    /**
     * Validate that journal entry lines are balanced.
     *
     * Calculates total debits and credits using high-precision arithmetic
     * and verifies they are equal.
     *
     * @param  array  $lines  Array of journal line items with keys:
     *                        - debit?: float|int|string Debit amount (default: 0)
     *                        - credit?: float|int|string Credit amount (default: 0)
     * @return bool True if debits equal credits, false otherwise
     */
    public function validateBalanced(array $lines): bool
    {
        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);
            $totalDebits = $this->mathService->add($totalDebits, $debit);
            $totalCredits = $this->mathService->add($totalCredits, $credit);
        }

        return $this->mathService->compare($totalDebits, $totalCredits) === 0;
    }

    /**
     * Reverse an existing journal entry.
     *
     * Creates a new reversal entry that swaps debits and credits from the
     * original entry. Updates original entry status to 'Reversed'.
     *
     * @param  JournalEntry  $originalEntry  The entry to reverse
     * @param  string  $reason  Reason for the reversal
     * @param  int|null  $reversedBy  User ID performing the reversal (default: authenticated user)
     * @return JournalEntry The newly created reversal entry
     *
     * @throws \InvalidArgumentException If entry is already reversed or not posted
     */
    public function reverseJournalEntry(
        JournalEntry $originalEntry,
        string $reason = '',
        ?int $reversedBy = null
    ): JournalEntry {
        $reversedBy = $reversedBy ?? auth()->id();

        return DB::transaction(function () use ($originalEntry, $reason, $reversedBy) {
            // Validation 1: Check if entry is already reversed
            if ($originalEntry->isReversed()) {
                throw new \InvalidArgumentException('Entry has already been reversed');
            }

            // Validation 2: Check if entry is posted (can only reverse posted entries)
            if (! $originalEntry->isPosted()) {
                throw new \InvalidArgumentException('Entry must be Posted to be reversed');
            }

            // Load lines if not already loaded
            if (! $originalEntry->relationLoaded('lines')) {
                $originalEntry->load('lines');
            }

            // Create reversal entry FIRST (so we can link to it)
            $lines = [];
            foreach ($originalEntry->lines as $line) {
                $lines[] = [
                    'account_code' => $line->account_code,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: '.$line->description,
                ];
            }

            $entry = $this->createJournalEntry(
                $lines,
                'Reversal',
                $originalEntry->id,
                "Reversal of entry {$originalEntry->id}: {$reason}",
                now()->toDateString(),
                $reversedBy
            );

            // Update original entry status and create explicit link via reversal_id
            $originalEntry->update([
                'status' => 'Reversed',
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
            ]);

            return $entry;
        });
    }

    /**
     * Update the account ledger with journal entry lines.
     *
     * @param  JournalEntry  $entry  The journal entry to process
     */
    protected function updateLedger(JournalEntry $entry): void
    {
        foreach ($entry->lines as $line) {
            $currentBalance = $this->getAccountBalance($line->account_code);

            if ($this->isDebitAccount($line->account_code)) {
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->debit),
                    $this->mathService->multiply((string) $line->credit, '-1')
                );
            } else {
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->credit),
                    $this->mathService->multiply((string) $line->debit, '-1')
                );
            }

            AccountLedger::create([
                'account_code' => $line->account_code,
                'entry_date' => $entry->entry_date,
                'journal_entry_id' => $entry->id,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'running_balance' => $newBalance,
            ]);
        }
    }

    /**
     * Determine if an account is a debit-balance account.
     *
     * @param  string  $accountCode  The account code to check
     * @return bool True if account type is Asset or Expense
     *
     * @throws \InvalidArgumentException If account is not found
     */
    protected function isDebitAccount(string $accountCode): bool
    {
        $account = ChartOfAccount::find($accountCode);
        if (! $account) {
            throw new \InvalidArgumentException("Account not found: {$accountCode}");
        }

        return in_array($account->account_type, ['Asset', 'Expense']);
    }

    /**
     * Get the current balance for an account.
     *
     * Retrieves the running balance from the most recent ledger entry,
     * optionally filtered by an as-of date.
     *
     * @param  string  $accountCode  The account code to query
     * @param  string|null  $asOfDate  Date in YYYY-MM-DD format (default: current date)
     * @return string Account balance as a string for precision
     */
    public function getAccountBalance(string $accountCode, ?string $asOfDate = null): string
    {
        $query = AccountLedger::where('account_code', $accountCode);

        if ($asOfDate) {
            // Use date function for cross-database compatibility
            // This ensures proper comparison regardless of datetime vs date storage
            $query->whereRaw('DATE(entry_date) <= ?', [$asOfDate]);
        }

        $lastEntry = $query->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry ? (string) $lastEntry->running_balance : '0';
    }

    /**
     * Get net account activity (change in balance) within a date range.
     *
     * Calculates the net movement of an account between two dates.
     * For expense accounts, this returns total debits minus credits.
     *
     * @param  string  $accountCode  The account code to query
     * @param  string  $startDate  Start date in YYYY-MM-DD format (inclusive)
     * @param  string  $endDate  End date in YYYY-MM-DD format (inclusive)
     * @return string Net activity amount as a string (positive = net debit, negative = net credit)
     */
    public function getAccountActivity(string $accountCode, string $startDate, string $endDate): string
    {
        $entries = AccountLedger::where('account_code', $accountCode)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($entries as $entry) {
            $totalDebits = $this->mathService->add($totalDebits, (string) $entry->debit);
            $totalCredits = $this->mathService->add($totalCredits, (string) $entry->credit);
        }

        // For expense accounts, activity is typically the net amount (debits - credits)
        // This gives us the actual spending in the period
        return $this->mathService->subtract($totalDebits, $totalCredits);
    }
}
