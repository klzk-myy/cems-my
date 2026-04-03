<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\JournalEntry;

class ReconciliationService
{
    /**
     * Import bank statement lines
     */
    public function importStatement(string $accountCode, array $lines, int $userId): array
    {
        $imported = [];

        foreach ($lines as $line) {
            $record = BankReconciliation::create([
                'account_code' => $accountCode,
                'statement_date' => $line['date'],
                'reference' => $line['reference'] ?? null,
                'description' => $line['description'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'status' => 'unmatched',
                'created_by' => $userId,
            ]);

            $imported[] = $record;
        }

        // Auto-match where possible
        $this->autoMatch($accountCode);

        return [
            'imported' => count($imported),
            'unmatched' => BankReconciliation::where('account_code', $accountCode)
                ->where('status', 'unmatched')
                ->count(),
        ];
    }

    /**
     * Auto-match statement lines to journal entries
     */
    protected function autoMatch(string $accountCode): void
    {
        $unmatched = BankReconciliation::where('account_code', $accountCode)
            ->where('status', 'unmatched')
            ->get();

        foreach ($unmatched as $record) {
            // Look for matching journal entry
            $amount = abs($record->getAmount());
            $isDebit = $record->getAmount() > 0;

            $matchingEntry = JournalEntry::where('status', 'Posted')
                ->whereHas('lines', function ($query) use ($accountCode, $amount, $isDebit) {
                    $query->where('account_code', $accountCode)
                        ->where($isDebit ? 'debit' : 'credit', $amount);
                })
                ->whereDate('entry_date', $record->statement_date)
                ->first();

            if ($matchingEntry) {
                $record->update([
                    'status' => 'matched',
                    'matched_to_journal_entry_id' => $matchingEntry->id,
                    'matched_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get reconciliation report
     */
    public function getReconciliationReport(string $accountCode, string $fromDate, string $toDate): array
    {
        $statementBalance = BankReconciliation::where('account_code', $accountCode)
            ->whereBetween('statement_date', [$fromDate, $toDate])
            ->get()
            ->sum(fn ($r) => $r->getAmount());

        $unmatchedItems = BankReconciliation::where('account_code', $accountCode)
            ->where('status', 'unmatched')
            ->whereBetween('statement_date', [$fromDate, $toDate])
            ->get();

        $exceptions = BankReconciliation::where('account_code', $accountCode)
            ->where('status', 'exception')
            ->whereBetween('statement_date', [$fromDate, $toDate])
            ->get();

        return [
            'account_code' => $accountCode,
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'statement_balance' => $statementBalance,
            'unmatched_count' => $unmatchedItems->count(),
            'unmatched_items' => $unmatchedItems,
            'exception_count' => $exceptions->count(),
            'exceptions' => $exceptions,
        ];
    }

    /**
     * Mark as exception with note
     */
    public function markAsException(int $reconciliationId, string $reason, int $userId): BankReconciliation
    {
        $record = BankReconciliation::findOrFail($reconciliationId);

        $record->update([
            'status' => 'exception',
            'notes' => $reason,
        ]);

        return $record;
    }
}
