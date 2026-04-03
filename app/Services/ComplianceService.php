<?php

namespace App\Services;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    protected EncryptionService $encryptionService;

    protected MathService $mathService;

    public function __construct(
        EncryptionService $encryptionService,
        MathService $mathService
    ) {
        $this->encryptionService = $encryptionService;
        $this->mathService = $mathService;
    }

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

    public function checkSanctionMatch(Customer $customer): bool
    {
        // Query sanction_entries for fuzzy match
        $matches = DB::table('sanction_entries')
            ->whereRaw('LOWER(entity_name) LIKE ?', ['%'.strtolower($customer->full_name).'%'])
            ->orWhereRaw('LOWER(aliases) LIKE ?', ['%'.strtolower($customer->full_name).'%'])
            ->count();

        return $matches > 0;
    }

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

    public function checkStructuring(int $customerId): bool
    {
        $oneHourAgo = now()->subHour();
        $smallTransactions = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $oneHourAgo)
            ->where('amount_local', '<', 3000)
            ->count();

        return $smallTransactions >= 3;
    }

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
