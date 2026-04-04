<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\CurrencyPosition;
use App\Models\RevaluationEntry;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RevaluationService
{
    public function __construct(
        protected MathService $mathService,
        protected RateApiService $rateApiService,
        protected AccountingService $accountingService
    ) {}

    public function runRevaluation(int $postedBy, ?string $tillId = null): array
    {
        $tillId = $tillId ?? 'MAIN';
        $revaluationDate = now()->toDateString();
        $results = [];

        $positions = CurrencyPosition::where('till_id', $tillId)
            ->where('balance', '!=', 0)
            ->get();

        foreach ($positions as $position) {
            $result = $this->revaluePosition($position, $revaluationDate, $postedBy);
            if ($result) {
                $results[] = $result;
            }
        }

        return [
            'date' => $revaluationDate,
            'till_id' => $tillId,
            'positions_revalued' => count($results),
            'entries' => $results,
        ];
    }

    protected function revaluePosition(CurrencyPosition $position, string $date, int $postedBy): ?array
    {
        $newRate = $this->getCurrentRate($position->currency_code);
        if (! $newRate) {
            return null;
        }

        $oldRate = $position->last_valuation_rate ?? $position->avg_cost_rate;
        $gainLoss = $this->mathService->calculateRevaluationPnl(
            $position->balance,
            $oldRate,
            $newRate
        );

        return DB::transaction(function () use ($position, $oldRate, $newRate, $gainLoss, $date, $postedBy) {
            // Create revaluation entry
            $entry = RevaluationEntry::create([
                'currency_code' => $position->currency_code,
                'till_id' => $position->till_id,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'position_amount' => $position->balance,
                'gain_loss_amount' => $gainLoss,
                'revaluation_date' => $date,
                'posted_by' => $postedBy,
            ]);

            // Update position
            $cumulativePnl = $this->mathService->add(
                $position->unrealized_pnl ?? '0',
                $gainLoss
            );
            $position->update([
                'last_valuation_rate' => $newRate,
                'unrealized_pnl' => $cumulativePnl,
                'last_valuation_at' => now(),
            ]);

            return [
                'entry_id' => $entry->id,
                'currency' => $position->currency_code,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'gain_loss' => $gainLoss,
            ];
        });
    }

    protected function getCurrentRate(string $currencyCode): ?string
    {
        $rate = $this->rateApiService->getRateForCurrency($currencyCode);
        if (! $rate) {
            return null;
        }

        // Use mid rate for revaluation
        return (string) $rate['mid'];
    }

    public function getRevaluationReport(string $date): array
    {
        $entries = RevaluationEntry::where('revaluation_date', $date)
            ->with(['currency', 'postedBy'])
            ->get();

        $totalGain = '0';
        $totalLoss = '0';

        foreach ($entries as $entry) {
            $amount = $entry->gain_loss_amount;
            if ($this->mathService->compare($amount, '0') >= 0) {
                $totalGain = $this->mathService->add($totalGain, $amount);
            } else {
                $totalLoss = $this->mathService->add($totalLoss, $amount);
            }
        }

        return [
            'date' => $date,
            'entries' => $entries,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
        ];
    }

    public function runRevaluationWithJournal(?string $date = null, ?int $postedBy = null): array
    {
        $date = $date ?? now()->toDateString();
        $postedBy = $postedBy ?? auth()->id() ?? 1;

        // FAULT #5 FIX: Validate period before processing
        $this->validatePeriodForDate($date);

        $positions = CurrencyPosition::all();
        $results = [];
        $totalGain = '0';
        $totalLoss = '0';
        $errors = [];

        // FAULT #6 FIX: Process each currency independently in its own transaction
        foreach ($positions as $position) {
            if ($this->mathService->compare($position->balance, '0') <= 0) {
                continue;
            }

            $oldRate = $position->last_valuation_rate ?? $position->avg_cost_rate;
            $newRate = $this->getCurrentRate($position->currency_code) ?? $oldRate;

            if (! $newRate) {
                continue;
            }

            $gainLoss = $this->mathService->calculateRevaluationPnl(
                $position->balance,
                $oldRate,
                $newRate
            );

            if ($this->mathService->compare($gainLoss, '0') === 0) {
                continue;
            }

            // Process each currency in its own transaction
            try {
                DB::transaction(function () use ($position, $oldRate, $newRate, $gainLoss, $date, $postedBy) {
                    $revaluationEntry = RevaluationEntry::create([
                        'currency_code' => $position->currency_code,
                        'till_id' => $position->till_id,
                        'old_rate' => $oldRate,
                        'new_rate' => $newRate,
                        'position_amount' => $position->balance,
                        'gain_loss_amount' => $gainLoss,
                        'revaluation_date' => $date,
                        'posted_by' => $postedBy,
                    ]);

                    // Validate and get configured account codes
                    $forexPositionAccount = $this->getValidatedAccountCode('accounting.forex_position_account', '2000');
                    $gainAccount = $this->getValidatedAccountCode('accounting.revaluation_gain_account', '5100');
                    $lossAccount = $this->getValidatedAccountCode('accounting.revaluation_loss_account', '6100');

                    $isGain = $this->mathService->compare($gainLoss, '0') > 0;
                    $lines = [
                        [
                            'account_code' => $forexPositionAccount,
                            'debit' => $isGain ? $gainLoss : '0',
                            'credit' => $isGain ? '0' : $this->mathService->multiply($gainLoss, '-1'),
                            'description' => "Revaluation for {$position->currency_code} @ {$newRate}",
                        ],
                        [
                            'account_code' => $isGain ? $gainAccount : $lossAccount,
                            'debit' => $isGain ? '0' : $this->mathService->multiply($gainLoss, '-1'),
                            'credit' => $isGain ? $gainLoss : '0',
                            'description' => "Revaluation gain/loss for {$position->currency_code}",
                        ],
                    ];

                    // FAULT #5 FIX: AccountingService will assign period_id to journal entry
                    $this->accountingService->createJournalEntry(
                        $lines,
                        'Revaluation',
                        $revaluationEntry->id,
                        "Month-end revaluation: {$position->currency_code}",
                        $date,
                        $postedBy
                    );

                    $position->update([
                        'unrealized_pnl' => $this->mathService->add($position->unrealized_pnl ?? '0', $gainLoss),
                        'last_valuation_rate' => $newRate,
                        'last_valuation_at' => now(),
                    ]);

                    return [
                        'currency_code' => $position->currency_code,
                        'gain_loss' => $gainLoss,
                        'is_gain' => $isGain,
                    ];
                });

                $isGain = $this->mathService->compare($gainLoss, '0') > 0;

                if ($isGain) {
                    $totalGain = $this->mathService->add($totalGain, $gainLoss);
                } else {
                    $totalLoss = $this->mathService->add($totalLoss, $gainLoss);
                }

                $results[] = [
                    'currency_code' => $position->currency_code,
                    'gain_loss' => $gainLoss,
                    'is_gain' => $isGain,
                ];
            } catch (\Exception $e) {
                // FAULT #6 FIX: Log error and continue processing other currencies
                $errorMessage = "Revaluation failed for {$position->currency_code}: {$e->getMessage()}";
                Log::error($errorMessage);
                $errors[] = [
                    'currency_code' => $position->currency_code,
                    'error' => $errorMessage,
                ];
            }
        }

        // If all currencies failed, throw exception
        if (empty($results) && ! empty($errors)) {
            throw new \RuntimeException('All revaluations failed: '.implode(', ', array_column($errors, 'error')));
        }

        return [
            'date' => $date,
            'positions_updated' => count($results),
            'results' => $results,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
            'report_path' => null,
            'errors' => $errors,
        ];
    }

    /**
     * FAULT #5 FIX: Validate that the posting date falls within an open period.
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePeriodForDate(string $date): void
    {
        // Find the accounting period for this entry date
        $period = AccountingPeriod::forDate($date)->first();

        // If no period exists for the date, throw exception
        if (! $period) {
            throw new \InvalidArgumentException(
                "No accounting period found for date {$date}. Please create a period for this date or use a different date."
            );
        }

        // Validate that the period is open
        if (! $period->isOpen()) {
            throw new \InvalidArgumentException(
                "Cannot post to closed period {$period->period_code}. Please use an open period or contact administrator."
            );
        }
    }

    public function scheduleRevaluation(): void
    {
        Log::info('Revaluation scheduled for month-end');
    }

    public function getRevaluationStatus(string $month): array
    {
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = now()->parse($month)->endOfMonth();

        $entries = RevaluationEntry::whereBetween('revaluation_date', [$startDate, $endDate])
            ->get();

        return [
            'month' => $month,
            'has_run' => $entries->count() > 0,
            'entries_count' => $entries->count(),
            'currencies' => $entries->pluck('currency_code')->toArray(),
        ];
    }

    public function sendRevaluationNotification(array $results): void
    {
        $recipients = $this->getNotificationRecipients();

        foreach ($recipients as $recipient) {
            try {
                Mail::raw("Revaluation Complete\n\nDate: {$results['date']}\nPositions Updated: {$results['positions_updated']}\nNet P&L: {$results['net_pnl']}", function ($message) use ($recipient, $results) {
                    $message->to($recipient['email'])
                        ->subject('Monthly Revaluation Complete - '.now()->format('F Y'));

                    if (! empty($results['report_path'])) {
                        $message->attach($results['report_path']);
                    }
                });
            } catch (\Exception $e) {
                Log::error('Failed to send revaluation notification', [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function getNotificationRecipients(): array
    {
        return User::where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Get validated account code from config.
     * Throws exception if account doesn't exist or is inactive (when validation is enabled).
     */
    protected function getValidatedAccountCode(string $configKey, string $defaultCode): string
    {
        $code = \Illuminate\Support\Facades\Config::get($configKey, $defaultCode);

        if (\Illuminate\Support\Facades\Config::get('accounting.validate_accounts', true)) {
            $account = ChartOfAccount::where('account_code', $code)->first();

            if (! $account) {
                throw new \InvalidArgumentException("Configured account '{$configKey}' with code '{$code}' does not exist in chart of accounts");
            }

            if (! $account->is_active) {
                throw new \InvalidArgumentException("Configured account '{$configKey}' with code '{$code}' is not active");
            }
        }

        return $code;
    }
}
