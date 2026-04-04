<?php

namespace App\Services;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Compliance Service
 *
 * Provides compliance-related operations for money changing transactions.
 * Handles Customer Due Diligence (CDD) level determination, sanctions screening,
 * velocity checks, structuring detection, and transaction hold decisions.
 *
 * This service ensures compliance with BNM regulations and AML/CFT requirements.
 */
class ComplianceService
{
    /**
     * Encryption service for sensitive data operations.
     */
    protected EncryptionService $encryptionService;

    /**
     * Math service for precise financial calculations.
     */
    protected MathService $mathService;

    /**
     * Create a new ComplianceService instance.
     *
     * @param  EncryptionService  $encryptionService  Service for data encryption
     * @param  MathService  $mathService  Service for high-precision calculations
     */
    public function __construct(
        EncryptionService $encryptionService,
        MathService $mathService
    ) {
        $this->encryptionService = $encryptionService;
        $this->mathService = $mathService;
    }

    /**
     * Determine the Customer Due Diligence (CDD) level for a transaction.
     *
     * Evaluates transaction amount and customer risk profile to assign
     * appropriate due diligence level:
     * - Enhanced: Amount ≥ RM 50,000, PEP status, sanctions match, or high risk rating
     * - Standard: Amount ≥ RM 3,000
     * - Simplified: Amount < RM 3,000 with no risk factors
     *
     * @param  string  $amount  Transaction amount in MYR (as string for precision)
     * @param  Customer  $customer  The customer initiating the transaction
     * @return CddLevel The determined CDD level (Simplified, Standard, or Enhanced)
     */
    public function determineCDDLevel(string $amount, Customer $customer): CddLevel
    {
        // Enhanced Due Diligence triggers
        if ($customer->pep_status || $this->checkSanctionMatch($customer)) {
            return CddLevel::Enhanced;
        }

        if ($this->mathService->compare($amount, '50000') >= 0 || $customer->risk_rating === 'High') {
            return CddLevel::Enhanced;
        }

        if ($this->mathService->compare($amount, '3000') >= 0) {
            return CddLevel::Standard;
        }

        return CddLevel::Simplified;
    }

    /**
     * Check if customer matches any sanctions list entries.
     *
     * Performs a case-insensitive fuzzy search on entity_name and aliases
     * fields in the sanction_entries database table.
     *
     * @param  Customer  $customer  The customer to screen against sanctions lists
     * @return bool True if customer matches any sanctions entry, false otherwise
     */
    public function checkSanctionMatch(Customer $customer): bool
    {
        // Query sanction_entries for fuzzy match
        $matches = DB::table('sanction_entries')
            ->whereRaw('LOWER(entity_name) LIKE ?', ['%'.strtolower($customer->full_name).'%'])
            ->orWhereRaw('LOWER(aliases) LIKE ?', ['%'.strtolower($customer->full_name).'%'])
            ->count();

        return $matches > 0;
    }

    /**
     * Check transaction velocity for a customer over the last 24 hours.
     *
     * Calculates the total transaction amount within the past 24 hours
     * and determines if adding the new transaction would exceed the threshold.
     *
     * @param  int  $customerId  The ID of the customer to check
     * @param  string  $newAmount  The amount of the new transaction (as string for precision)
     * @return array<string, mixed> Velocity check results containing:
     *                              - amount_24h: string Total amount transacted in last 24 hours
     *                              - with_new_transaction: string Projected total including new transaction
     *                              - threshold_exceeded: bool Whether threshold of RM 50,000 would be exceeded
     *                              - threshold_amount: string The threshold amount (RM 50,000)
     */
    public function checkVelocity(int $customerId, string $newAmount): array
    {
        $startTime = now()->subHours(24);
        $velocity = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $startTime)
            ->sum('amount_local');

        $total = $this->mathService->add((string) $velocity, $newAmount);

        return [
            'amount_24h' => (string) $velocity,
            'with_new_transaction' => $total,
            'threshold_exceeded' => $this->mathService->compare($total, '50000') > 0,
            'threshold_amount' => '50000',
        ];
    }

    /**
     * Detect potential structuring behavior for a customer.
     *
     * Structuring (or smurfing) is the practice of breaking up large transactions
     * into smaller amounts to avoid reporting thresholds. This method checks
     * for 3 or more transactions under RM 3,000 within the last hour.
     *
     * @param  int  $customerId  The ID of the customer to check
     * @return bool True if structuring behavior is detected (3+ small transactions in 1 hour)
     */
    public function checkStructuring(int $customerId): bool
    {
        $oneHourAgo = now()->subHour();
        $smallTransactions = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $oneHourAgo)
            ->where('amount_local', '<', 3000)
            ->count();

        return $smallTransactions >= 3;
    }

    /**
     * Determine if a transaction requires a compliance hold.
     *
     * Evaluates transaction and customer risk factors to decide if the
     * transaction should be held pending compliance review. Returns a list
     * of reasons for the hold decision.
     *
     * Hold triggers include:
     * - Large amounts (≥ RM 50,000)
     * - PEP status
     * - Sanctions list match
     * - High risk customer rating
     *
     * @param  string  $amount  Transaction amount in MYR (as string for precision)
     * @param  Customer  $customer  The customer initiating the transaction
     * @return array<string, mixed> Hold decision containing:
     *                              - requires_hold: bool Whether the transaction must be held
     *                              - reasons: array<string> List of ComplianceFlagType values as strings
     */
    public function requiresHold(string $amount, Customer $customer): array
    {
        $reasons = [];

        if ($this->mathService->compare($amount, '50000') >= 0) {
            $reasons[] = ComplianceFlagType::EddRequired->value;
        }

        if ($customer->pep_status) {
            $reasons[] = ComplianceFlagType::PepStatus->value;
        }

        if ($this->checkSanctionMatch($customer)) {
            $reasons[] = ComplianceFlagType::SanctionMatch->value;
        }

        if ($customer->risk_rating === 'High') {
            $reasons[] = ComplianceFlagType::HighRiskCustomer->value;
        }

        return [
            'requires_hold' => ! empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
