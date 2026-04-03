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
        // Check if counter is already open today
        $existingSession = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            throw new Exception('Counter is already open today');
        }

        // Check if user is already at another counter
        $userSession = CounterSession::where('user_id', $user->id)
            ->where('session_date', now()->toDateString())
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

        // TODO: Update till balances with opening floats

        return $session;
    }

    /**
     * Close a counter session
     */
    public function closeSession(CounterSession $session, User $user, array $closingFloats, ?string $notes = null): CounterSession
    {
        if (! $session->isOpen()) {
            throw new Exception('Session is not open');
        }

        // Calculate variance
        foreach ($closingFloats as $float) {
            $openingBalance = 10000.00; // TODO: Get from till balances
            $closingBalance = $float['amount'];
            $variance = $this->calculateVariance($openingBalance, $closingBalance);

            if (abs($variance) > self::VARIANCE_THRESHOLD_RED) {
                throw new Exception('Variance exceeds threshold, requires supervisor approval');
            }
        }

        $session->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'status' => 'closed',
            'notes' => $notes,
        ]);

        // TODO: Update till balances with closing floats

        return $session;
    }

    /**
     * Calculate variance between opening and closing
     */
    public function calculateVariance(float $opening, float $closing): float
    {
        return $closing - $opening;
    }

    /**
     * Get counter status
     */
    public function getCounterStatus(Counter $counter): array
    {
        $session = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
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
        $openCounterIds = CounterSession::where('session_date', now()->toDateString())
            ->where('status', 'open')
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
        $totalVariance = 0;
        foreach ($physicalCounts as $count) {
            $expected = 10000.00; // TODO: Get from till balances
            $variance = $count['amount'] - $expected;
            $totalVariance += $variance;
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
            'variance_myr' => $totalVariance,
            'variance_notes' => $totalVariance != 0 ? 'Variance noted' : null,
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

        return ['handover' => $handover, 'new_session' => $newSession];
    }
}
