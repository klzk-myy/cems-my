<?php

namespace App\Services;

use App\Enums\CounterSessionStatus;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Currency;
use App\Models\TillBalance;
use App\Models\User;
use App\Support\BcmathHelper;
use Exception;
use Illuminate\Support\Facades\DB;

class CounterService
{
    private const VARIANCE_THRESHOLD_YELLOW = 100.00;

    private const VARIANCE_THRESHOLD_RED = 500.00;

    /**
     * Open a counter session
     *
     * Wrapped in a transaction with locking to prevent race conditions
     * where two users could open the same counter simultaneously.
     */
    public function openSession(Counter $counter, User $user, array $openingFloats): CounterSession
    {
        $now = now();
        $today = $now->toDateString();

        return DB::transaction(function () use ($counter, $user, $openingFloats, $now, $today) {
            // Lock and check if counter is already open (prevents race condition)
            $existingSession = CounterSession::where('counter_id', $counter->id)
                ->where('status', CounterSessionStatus::Open->value)
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                throw new Exception('Counter is already open today');
            }

            // Lock and check if user is already at another counter
            $userSession = CounterSession::where('user_id', $user->id)
                ->where('status', CounterSessionStatus::Open->value)
                ->lockForUpdate()
                ->first();

            if ($userSession) {
                throw new Exception('User is already at another counter');
            }

            // Pre-fetch all currencies to avoid N+1 queries
            $currencies = $this->resolveCurrencies($openingFloats);

            // Create session
            $session = CounterSession::create([
                'counter_id' => $counter->id,
                'user_id' => $user->id,
                'session_date' => $today,
                'opened_at' => $now,
                'opened_by' => $user->id,
                'status' => CounterSessionStatus::Open,
            ]);

            foreach ($openingFloats as $float) {
                $currencyCode = $currencies[$float['currency_id']] ?? null;

                if ($currencyCode) {
                    TillBalance::create([
                        'till_id' => (string) $counter->id,
                        'currency_code' => $currencyCode,
                        'opening_balance' => $float['amount'],
                        'date' => $today,
                        'opened_by' => $user->id,
                    ]);
                }
            }

            return $session;
        });
    }

    /**
     * Close a counter session
     *
     * Validates variance thresholds, updates till balances, and closes the session
     * atomically within a single database transaction.
     */
    public function closeSession(CounterSession $session, User $user, array $closingFloats, ?string $notes = null, ?User $supervisor = null): CounterSession
    {
        if (! $session->isOpen()) {
            throw new Exception('Session is not open');
        }

        $now = now();

        return DB::transaction(function () use ($session, $user, $closingFloats, $notes, $supervisor, $now) {
            // Pre-fetch all currencies and till balances to avoid N+1
            $currencyIds = collect($closingFloats)->pluck('currency_id')->unique()->toArray();

            // Separate numeric IDs from string codes
            $numericIds = array_filter($currencyIds, 'is_numeric');
            $stringCodes = array_diff($currencyIds, $numericIds);

            // Build proper query to avoid OR condition returning all records
            $currencies = Currency::where(function ($query) use ($stringCodes, $numericIds) {
                if (! empty($stringCodes)) {
                    $query->whereIn('code', $stringCodes);
                }
                if (! empty($numericIds)) {
                    $query->orWhereIn(Currency::getModel()->getKeyName(), $numericIds);
                }
            })->get()->keyBy('code');

            $tillBalances = TillBalance::where('till_id', (string) $session->counter_id)
                ->where('date', $session->session_date)
                ->whereNull('closed_at')
                ->get()
                ->keyBy('currency_code');

            // Single pass: validate variance AND collect update data
            $updates = [];
            foreach ($closingFloats as $float) {
                $currency = $currencies->get($float['currency_id'])
                    ?? $currencies->first(fn ($c) => $c->getKey() == $float['currency_id']);

                if (! $currency) {
                    continue;
                }

                $tillBalance = $tillBalances->get($currency->code);
                $openingBalance = $tillBalance ? $tillBalance->opening_balance : '0';
                $foreignTotal = $tillBalance && $tillBalance->foreign_total !== null
                    ? $tillBalance->foreign_total
                    : '0';
                $expectedBalance = BcmathHelper::add($openingBalance, $foreignTotal);

                $closingBalance = $float['amount'];
                $variance = BcmathHelper::subtract($closingBalance, $expectedBalance);

                // Validate variance thresholds
                if (BcmathHelper::gt(BcmathHelper::abs($variance), (string) self::VARIANCE_THRESHOLD_RED)) {
                    if (! $supervisor || ! $supervisor->isManager()) {
                        throw new Exception('Variance exceeds red threshold, requires supervisor approval');
                    }
                } elseif (BcmathHelper::gt(BcmathHelper::abs($variance), (string) self::VARIANCE_THRESHOLD_YELLOW)) {
                    if (empty($notes)) {
                        throw new Exception('Variance exceeds yellow threshold, requires explanation notes');
                    }
                }

                // Collect update data for the second phase
                if ($tillBalance) {
                    $updates[] = [
                        'tillBalance' => $tillBalance,
                        'closingBalance' => $closingBalance,
                        'variance' => $variance,
                    ];
                }
            }

            // Phase 2: Apply all updates atomically (session + till balances together)
            $session->update([
                'closed_at' => $now,
                'closed_by' => $user->id,
                'status' => CounterSessionStatus::Closed,
                'notes' => $notes,
            ]);

            foreach ($updates as $update) {
                $update['tillBalance']->update([
                    'closing_balance' => $update['closingBalance'],
                    'variance' => $update['variance'],
                    'closed_at' => $now,
                    'closed_by' => $user->id,
                    'notes' => $notes,
                ]);
            }

            return $session;
        });
    }

    /**
     * Calculate variance between expected and actual
     */
    public function calculateVariance(float $expected, float $actual): float
    {
        return $actual - $expected;
    }

    /**
     * Get counter status
     */
    public function getCounterStatus(Counter $counter): array
    {
        $session = CounterSession::where('counter_id', $counter->id)
            ->where('status', CounterSessionStatus::Open->value)
            ->first();

        return [
            'counter' => $counter,
            'status' => $session ? CounterSessionStatus::Open->value : CounterSessionStatus::Closed->value,
            'current_user' => $session ? $session->user : null,
            'session' => $session,
        ];
    }

    /**
     * Get available counters
     */
    public function getAvailableCounters(): array
    {
        $allCounters = Counter::active()->get();
        $openCounterIds = CounterSession::where('status', CounterSessionStatus::Open->value)
            ->pluck('counter_id')
            ->toArray();

        return $allCounters->filter(function ($counter) use ($openCounterIds) {
            return ! in_array($counter->id, $openCounterIds);
        })->values()->all();
    }

    /**
     * Initiate handover between users
     *
     * Closes the current session, creates a handover record, and opens a new
     * session for the receiving user — all atomically within a transaction.
     */
    public function initiateHandover(
        CounterSession $session,
        User $fromUser,
        User $toUser,
        User $supervisor,
        array $physicalCounts
    ): array {
        // Validate supervisor role
        if (! $supervisor->isManager()) {
            throw new Exception('Supervisor must be a manager or admin');
        }

        // Validate session is open
        if (! $session->isOpen()) {
            throw new Exception('Session is not open');
        }

        // Validate fromUser is the session user
        if ($session->user_id !== $fromUser->id) {
            throw new Exception('Session does not belong to the specified user');
        }

        // Validate toUser is not already at another counter
        $existingSession = CounterSession::where('user_id', $toUser->id)
            ->where('status', CounterSessionStatus::Open->value)
            ->first();

        if ($existingSession && $existingSession->id !== $session->id) {
            throw new Exception('User is already at another counter');
        }

        $now = now();
        $today = $now->toDateString();

        return DB::transaction(function () use ($session, $fromUser, $toUser, $supervisor, $physicalCounts, $now, $today) {
            // Pre-fetch all currencies to avoid N+1
            $currencies = $this->resolveCurrenciesForCounts($physicalCounts);

            // Pre-fetch relevant till balances
            $currencyCodes = array_values(array_filter($currencies));
            $tillBalances = TillBalance::where('till_id', (string) $session->counter_id)
                ->where('date', $session->session_date)
                ->whereNull('closed_at')
                ->whereIn('currency_code', $currencyCodes)
                ->get()
                ->keyBy('currency_code');

            // Calculate variance
            $totalVarianceMyr = '0';
            $tillUpdates = [];

            foreach ($physicalCounts as $count) {
                $currencyCode = $currencies[$count['currency_id']] ?? null;
                if (! $currencyCode) {
                    continue;
                }

                $tillBalance = $tillBalances->get($currencyCode);
                $openingBalance = $tillBalance ? $tillBalance->opening_balance : '0';
                $foreignTotal = $tillBalance ? ($tillBalance->foreign_total ?? '0') : '0';
                $expected = BcmathHelper::add($openingBalance, $foreignTotal);
                $variance = BcmathHelper::subtract($count['amount'], $expected);

                // Only sum up MYR variance directly
                if ($currencyCode === 'MYR') {
                    $totalVarianceMyr = BcmathHelper::add($totalVarianceMyr, $variance);
                }

                // Collect till balance updates
                if ($tillBalance) {
                    $tillUpdates[] = [
                        'tillBalance' => $tillBalance,
                        'closingBalance' => $count['amount'],
                        'variance' => $variance,
                        'currencyCode' => $currencyCode,
                    ];
                }
            }

            // Apply till balance closings
            foreach ($tillUpdates as $update) {
                $update['tillBalance']->update([
                    'closing_balance' => $update['closingBalance'],
                    'variance' => $update['variance'],
                    'closed_at' => $now,
                    'closed_by' => $fromUser->id,
                    'notes' => 'Handover',
                ]);
            }

            // Mark old session as handed over
            $session->update([
                'status' => CounterSessionStatus::HandedOver,
                'closed_at' => $now,
                'closed_by' => $fromUser->id,
            ]);

            // Create handover record
            $handover = $session->handovers()->create([
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser->id,
                'supervisor_id' => $supervisor->id,
                'handover_time' => $now,
                'physical_count_verified' => true,
                'variance_myr' => $totalVarianceMyr,
                'variance_notes' => $totalVarianceMyr != 0 ? 'Variance noted during handover' : null,
            ]);

            // Create new session for new user
            $newSession = CounterSession::create([
                'counter_id' => $session->counter_id,
                'user_id' => $toUser->id,
                'session_date' => $today,
                'opened_at' => $now,
                'opened_by' => $supervisor->id,
                'status' => CounterSessionStatus::Open,
            ]);

            // Update existing till balances to transfer to new user (preserve audit trail)
            // The handover details are captured in CounterHandover table (variance_myr, etc.)
            // Note: Due to unique constraint on (till_id, date, currency_code), we cannot
            // create new records - instead we update opened_by to reflect new session owner
            $newTillBalanceIds = [];
            $now = now();
            foreach ($physicalCounts as $count) {
                $currencyCode = $currencies[$count['currency_id']] ?? null;
                if ($currencyCode) {
                    // Check if there's an existing open balance for this till/date/currency
                    $existingBalance = TillBalance::where('till_id', (string) $newSession->counter_id)
                        ->where('currency_code', $currencyCode)
                        ->where('date', $today)
                        ->whereNull('closed_at')
                        ->first();

                    if ($existingBalance) {
                        // Calculate variance for audit (for CounterHandover record)
                        $variance = bcsub((string) $count['amount'], $existingBalance->opening_balance, 4);

                        // Record handover details in notes field before updating
                        $handoverNotes = json_encode([
                            'type' => 'handover',
                            'from_user' => $fromUser->id,
                            'to_user' => $toUser->id,
                            'supervisor' => $supervisor->id,
                            'handover_time' => $now->toIso8601String(),
                            'physical_count' => $count['amount'],
                            'previous_opening' => $existingBalance->opening_balance,
                            'variance' => $variance,
                        ]);

                        // Use lockForUpdate to prevent race conditions
                        $lockedBalance = TillBalance::where('id', $existingBalance->id)
                            ->lockForUpdate()
                            ->first();

                        if ($lockedBalance) {
                            // Single atomic update: close and reopen in one transaction
                            // The CounterHandover table captures the variance and audit details
                            $lockedBalance->closing_balance = $count['amount'];
                            $lockedBalance->variance = $variance;
                            $lockedBalance->closed_at = $now;
                            $lockedBalance->closed_by = $fromUser->id;
                            $lockedBalance->notes = $handoverNotes;
                            $lockedBalance->opened_by = $toUser->id;
                            $lockedBalance->save();

                            // Refresh and reset for new session
                            $lockedBalance->closing_balance = null;
                            $lockedBalance->variance = '0.0000';
                            $lockedBalance->closed_at = null;
                            $lockedBalance->closed_by = null;
                            $lockedBalance->notes = null;
                            $lockedBalance->save();
                        }
                    } else {
                        // No existing balance - check if there's a closed one for today
                        $closedBalance = TillBalance::where('till_id', (string) $newSession->counter_id)
                            ->where('currency_code', $currencyCode)
                            ->whereDate('date', $today)
                            ->first();

                        if ($closedBalance) {
                            // Reopen the closed balance with new user
                            $closedBalance->opened_by = $toUser->id;
                            $closedBalance->opening_balance = $count['amount'];
                            $closedBalance->closing_balance = null;
                            $closedBalance->variance = '0.0000';
                            $closedBalance->closed_at = null;
                            $closedBalance->closed_by = null;
                            $closedBalance->notes = null;
                            $closedBalance->save();
                        } else {
                            // Create new balance record
                            TillBalance::create([
                                'till_id' => (string) $newSession->counter_id,
                                'currency_code' => $currencyCode,
                                'opening_balance' => $count['amount'],
                                'date' => $today,
                                'opened_by' => $toUser->id,
                            ]);
                        }
                    }
                    $newTillBalanceIds[] = $currencyCode;
                }
            }

            return ['handover' => $handover, 'new_session' => $newSession];
        });
    }

    /**
     * Resolve currency codes from opening floats, handling both numeric IDs and string codes.
     * Returns a map of [input_id => currency_code].
     */
    private function resolveCurrencies(array $floats): array
    {
        $ids = collect($floats)->pluck('currency_id')->unique()->toArray();

        $numericIds = array_filter($ids, 'is_numeric');
        $stringCodes = array_filter($ids, fn ($id) => ! is_numeric($id));

        $resolved = [];

        // Map string codes directly
        foreach ($stringCodes as $code) {
            $resolved[$code] = $code;
        }

        // Look up numeric IDs
        if (! empty($numericIds)) {
            $currencies = Currency::whereIn('id', $numericIds)->pluck('code', 'id');
            foreach ($currencies as $id => $code) {
                $resolved[$id] = $code;
            }
        }

        return $resolved;
    }

    /**
     * Resolve currency codes from physical counts array.
     * Returns a map of [input_id => currency_code].
     */
    private function resolveCurrenciesForCounts(array $counts): array
    {
        $ids = collect($counts)->pluck('currency_id')->unique()->toArray();

        // Since Currency uses 'code' as PK, Currency::find() with a code returns the model
        $currencies = Currency::whereIn('code', $ids)->pluck('code', 'code');

        $resolved = [];
        foreach ($ids as $id) {
            $resolved[$id] = $currencies->get($id);
        }

        return $resolved;
    }
}
