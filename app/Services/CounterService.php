<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use Exception;

class CounterService
{
    private const VARIANCE_THRESHOLD_YELLOW = 100.00;

    private const VARIANCE_THRESHOLD_RED = 500.00;

    /**
     * Open a counter session
     */
    public function openSession(Counter $counter, User $user, array $openingFloats): CounterSession
    {
        // Check if counter is already open
        $existingSession = CounterSession::where('counter_id', $counter->id)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            throw new Exception('Counter is already open today');
        }

        // Check if user is already at another counter
        $userSession = CounterSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($userSession) {
            throw new Exception('User is already at another counter');
        }

        // Create session
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open',
        ]);

        foreach ($openingFloats as $float) {
            $currency = \App\Models\Currency::find($float['currency_id']);
            if ($currency) {
                \App\Models\TillBalance::create([
                    'till_id' => $counter->id,
                    'currency_code' => $currency->code,
                    'opening_balance' => $float['amount'],
                    'date' => now()->toDateString(),
                    'opened_by' => $user->id,
                ]);
            }
        }

        return $session;
    }

    /**
     * Close a counter session
     */
    public function closeSession(CounterSession $session, User $user, array $closingFloats, ?string $notes = null, ?User $supervisor = null): CounterSession
    {
        if (! $session->isOpen()) {
            throw new Exception('Session is not open');
        }

        foreach ($closingFloats as $float) {
            $currency = \App\Models\Currency::find($float['currency_id']);
            $tillBalance = $currency ? \App\Models\TillBalance::where('till_id', $session->counter_id)
                ->where('currency_code', $currency->code)
                ->where('date', $session->session_date)
                ->whereNull('closed_at')
                ->first() : null;

            $openingBalance = $tillBalance ? (float) $tillBalance->opening_balance : 0.00;
            $foreignTotal = $tillBalance ? (float) ($tillBalance->foreign_total ?? 0.00) : 0.00;
            $expectedBalance = $openingBalance + $foreignTotal;
            
            $closingBalance = $float['amount'];
            $variance = $this->calculateVariance($expectedBalance, $closingBalance);

            if (abs($variance) > self::VARIANCE_THRESHOLD_RED) {
                if (! $supervisor || ! $supervisor->isManager()) {
                    throw new Exception('Variance exceeds red threshold, requires supervisor approval');
                }
            } elseif (abs($variance) > self::VARIANCE_THRESHOLD_YELLOW) {
                if (empty($notes)) {
                    throw new Exception('Variance exceeds yellow threshold, requires explanation notes');
                }
            }
        }

        $session->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'status' => 'closed',
            'notes' => $notes,
        ]);

        foreach ($closingFloats as $float) {
            $currency = \App\Models\Currency::find($float['currency_id']);
            if ($currency) {
                $tillBalance = \App\Models\TillBalance::where('till_id', $session->counter_id)
                    ->where('currency_code', $currency->code)
                    ->where('date', $session->session_date)
                    ->whereNull('closed_at')
                    ->first();
                
                if ($tillBalance) {
                    $expectedBalance = $tillBalance->opening_balance + ($tillBalance->foreign_total ?? 0.00);
                    $tillBalance->update([
                        'closing_balance' => $float['amount'],
                        'variance' => $float['amount'] - $expectedBalance,
                        'closed_at' => now(),
                        'closed_by' => $user->id,
                        'notes' => $notes,
                    ]);
                }
            }
        }

        return $session;
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
            ->where('status', 'open')
            ->first();

        return [
            'counter' => $counter,
            'status' => $session ? 'open' : 'closed',
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
        $openCounterIds = CounterSession::where('status', 'open')
            ->pluck('counter_id')
            ->toArray();

        return $allCounters->filter(function ($counter) use ($openCounterIds) {
            return ! in_array($counter->id, $openCounterIds);
        })->values()->all();
    }

    /**
     * Initiate handover between users
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

        // Calculate variance
        $totalVarianceMyr = 0;
        foreach ($physicalCounts as $count) {
            $currency = \App\Models\Currency::find($count['currency_id']);
            $tillBalance = $currency ? \App\Models\TillBalance::where('till_id', $session->counter_id)
                ->where('currency_code', $currency->code)
                ->where('date', $session->session_date)
                ->whereNull('closed_at')
                ->first() : null;
                
            $openingBalance = $tillBalance ? (float) $tillBalance->opening_balance : 0.00;
            $foreignTotal = $tillBalance ? (float) ($tillBalance->foreign_total ?? 0.00) : 0.00;
            $expected = $openingBalance + $foreignTotal;
            $variance = $count['amount'] - $expected;
            
            // Only sum up MYR variance directly
            if ($currency && $currency->code === 'MYR') {
                $totalVarianceMyr += $variance;
            }
            
            // Close the old till balance
            if ($tillBalance) {
                $tillBalance->update([
                    'closing_balance' => $count['amount'],
                    'variance' => $variance,
                    'closed_at' => now(),
                    'closed_by' => $fromUser->id,
                    'notes' => 'Handover',
                ]);
            }
        }

        // Mark old session as handed over
        $session->update(['status' => 'handed_over']);

        // Create handover record
        $handover = $session->handovers()->create([
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'handover_time' => now(),
            'physical_count_verified' => true,
            'variance_myr' => $totalVarianceMyr,
            'variance_notes' => $totalVarianceMyr != 0 ? 'Variance noted during handover' : null,
        ]);

        // Create new session for new user
        $newSession = CounterSession::create([
            'counter_id' => $session->counter_id,
            'user_id' => $toUser->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $supervisor->id,
            'status' => 'open',
        ]);
        
        // Open new till balances based on physical counts
        foreach ($physicalCounts as $count) {
            $currency = \App\Models\Currency::find($count['currency_id']);
            if ($currency) {
                \App\Models\TillBalance::create([
                    'till_id' => $newSession->counter_id,
                    'currency_code' => $currency->code,
                    'opening_balance' => $count['amount'],
                    'date' => now()->toDateString(),
                    'opened_by' => $toUser->id,
                ]);
            }
        }

        return ['handover' => $handover, 'new_session' => $newSession];
    }
}
