<?php

namespace App\Modules\Pos\Services;

use App\Models\Counter;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class PosTransactionService
{
    protected TransactionService $transactionService;

    protected ComplianceService $complianceService;

    protected CurrencyPositionService $positionService;

    protected PosRateService $rateService;

    protected MathService $mathService;

    public function __construct(
        TransactionService $transactionService,
        ComplianceService $complianceService,
        CurrencyPositionService $positionService,
        PosRateService $rateService,
        MathService $mathService
    ) {
        $this->transactionService = $transactionService;
        $this->complianceService = $complianceService;
        $this->positionService = $positionService;
        $this->rateService = $rateService;
        $this->mathService = $mathService;
    }

    public function calculateQuote(array $data): array
    {
        $currencyCode = $data['currency_code'];
        $amountForeign = $data['amount_foreign'];
        $type = $data['type'];

        $rate = $this->rateService->getRateForCurrency($currencyCode);

        if ($rate === null) {
            throw new \RuntimeException("No rate set for {$currencyCode} today");
        }

        $rateValue = $type === 'Buy' ? $rate['buy'] : $rate['sell'];
        $amountLocal = $this->mathService->multiply((string) $amountForeign, $rateValue);

        return [
            'amount_local' => $amountLocal,
            'rate' => $rateValue,
            'cdd_level' => $this->determineCddLevel($amountLocal),
            'compliance_flags' => [],
            'warnings' => [],
        ];
    }

    public function validateTransaction(array $data): array
    {
        $errors = [];
        $warnings = [];

        $tillId = $data['till_id'] ?? null;
        $counter = $tillId ? Counter::where('code', $tillId)->first() : null;

        if ($tillId && $counter === null) {
            $errors[] = 'Counter not found';
        } elseif ($counter && $counter->status !== 'active') {
            $errors[] = 'Counter is not active';
        }

        $customerId = $data['customer_id'] ?? null;
        if ($customerId) {
            $customer = Customer::find($customerId);

            if ($customer === null) {
                $errors[] = 'Customer not found';
            } else {
                if ($customer->sanction_match) {
                    $errors[] = 'Customer is on sanctions list';
                }

                if ($customer->risk_rating === 'High') {
                    $warnings[] = 'Customer has high risk rating';
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    public function createTransaction(array $data): Transaction
    {
        $quote = $this->calculateQuote($data);
        $validation = $this->validateTransaction($data);

        if (! empty($validation['errors'])) {
            throw new \RuntimeException(implode(', ', $validation['errors']));
        }

        $transactionData = [
            'type' => $data['type'],
            'currency_code' => $data['currency_code'],
            'amount_foreign' => $data['amount_foreign'],
            'amount_local' => $quote['amount_local'],
            'rate' => $quote['rate'],
            'customer_id' => $data['customer_id'],
            'till_id' => $data['till_id'],
            'purpose' => $data['purpose'],
            'source_of_funds' => $data['source_of_funds'],
            'cdd_level' => $quote['cdd_level'],
            'status' => 'Completed',
            'created_by' => auth()->id(),
        ];

        $transaction = $this->transactionService->createTransaction($transactionData);

        Log::info('POS transaction created', [
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
            'type' => $transaction->type,
            'amount_local' => $transaction->amount_local,
            'currency' => $transaction->currency_code,
        ]);

        return $transaction;
    }

    protected function determineCddLevel(string $amountLocal): string
    {
        $amount = floatval($amountLocal);

        if ($amount < 3000) {
            return 'Simplified';
        } elseif ($amount < 50000) {
            return 'Standard';
        } else {
            return 'Enhanced';
        }
    }

    public function getTransactionQuote(array $data): array
    {
        try {
            $quote = $this->calculateQuote($data);
            $validation = $this->validateTransaction($data);

            return ['success' => true, 'quote' => $quote, 'validation' => $validation];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
