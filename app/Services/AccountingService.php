<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

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

    public function reverseJournalEntry(
        JournalEntry $originalEntry,
        string $reason = '',
        ?int $reversedBy = null
    ): JournalEntry {
        $reversedBy = $reversedBy ?? auth()->id();

        return DB::transaction(function () use ($originalEntry, $reason, $reversedBy) {
            $originalEntry->update([
                'status' => 'Reversed',
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
            ]);

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

            return $entry;
        });
    }

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

    protected function isDebitAccount(string $accountCode): bool
    {
        $account = ChartOfAccount::find($accountCode);
        if (! $account) {
            throw new \InvalidArgumentException("Account not found: {$accountCode}");
        }

        return in_array($account->account_type, ['Asset', 'Expense']);
    }

    public function getAccountBalance(string $accountCode, ?string $asOfDate = null): string
    {
        $query = AccountLedger::where('account_code', $accountCode);

        if ($asOfDate) {
            $query->where('entry_date', '<=', $asOfDate);
        }

        $lastEntry = $query->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? (string) $lastEntry->running_balance : '0';
    }
}
