<?php

namespace App\Services;

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

    public function determineCDDLevel(float $amount, Customer $customer): string
    {
        // Enhanced Due Diligence triggers
        if ($customer->pep_status || $this->checkSanctionMatch($customer)) {
            return 'Enhanced';
        }

        if ($amount >= 50000 || $customer->risk_rating === 'High') {
            return 'Enhanced';
        }

        if ($amount >= 3000) {
            return 'Standard';
        }

        return 'Simplified';
    }

    public function checkSanctionMatch(Customer $customer): bool
    {
        // Query sanction_entries for fuzzy match
        $matches = DB::table('sanction_entries')
            ->whereRaw('LOWER(entity_name) LIKE ?', ['%' . strtolower($customer->full_name) . '%'])
            ->orWhereRaw('LOWER(aliases) LIKE ?', ['%' . strtolower($customer->full_name) . '%'])
            ->count();

        return $matches > 0;
    }

    public function checkVelocity(int $customerId, float $newAmount): array
    {
        $startTime = now()->subHours(24);
        $velocity = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $startTime)
            ->sum('amount_local');

        $total = $velocity + $newAmount;

        return [
            'amount_24h' => (float) $velocity,
            'with_new_transaction' => $total,
            'threshold_exceeded' => $total > 50000,
            'threshold_amount' => 50000,
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

    public function requiresHold(float $amount, Customer $customer): array
    {
        $reasons = [];

        if ($amount >= 50000) {
            $reasons[] = 'EDD_Required';
        }

        if ($customer->pep_status) {
            $reasons[] = 'PEP_Status';
        }

        if ($this->checkSanctionMatch($customer)) {
            $reasons[] = 'Sanction_Match';
        }

        if ($customer->risk_rating === 'High') {
            $reasons[] = 'High_Risk_Customer';
        }

        return [
            'requires_hold' => !empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
