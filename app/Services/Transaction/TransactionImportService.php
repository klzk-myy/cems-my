<?php

namespace App\Services\Transaction;

use App\Enums\TransactionImportStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionImport;
use App\Models\User;
use App\Services\Accounting\CurrencyPositionLockService;
use App\Services\Accounting\CurrencyPositionService;
use App\Services\Branch\TillBalanceManager;
use App\Services\Compliance\ComplianceService;
use App\Services\System\MathService;
use App\Services\ThresholdService;
use App\Services\Traits\TillBalanceTrait;
use App\Support\BcmathHelper;
use Illuminate\Support\Facades\DB;

class TransactionImportService
{
    use TillBalanceTrait;

    protected ?TransactionImport $import = null;

    protected array $errors = [];

    protected int $successCount = 0;

    /** @var array<string, bool> Currency existence cache, keyed by code. */
    protected array $currencyCache = [];

    /** @var array<string, Counter|null> Counter lookup cache, keyed by till id. */
    protected array $counterCache = [];

    public function __construct(
        protected MathService $mathService,
        protected ComplianceService $complianceService,
        protected CurrencyPositionService $positionService,
        protected TransactionMonitoringService $monitoringService,
        protected ThresholdService $thresholdService,
        protected TillBalanceManager $tillBalanceManager,
        protected TransactionCreationService $transactionCreationService,
        protected RateManagementService $rateManagementService,
        protected CurrencyPositionLockService $positionLockService,
    ) {}

    /**
     * Process CSV file
     */
    public function process(TransactionImport $import, string $filePath): void
    {
        $this->import = $import;
        $this->errors = [];
        $this->successCount = 0;
        $this->currencyCache = [];
        $this->counterCache = [];

        $this->import->update([
            'status' => TransactionImportStatus::Processing->value,
            'imported_at' => now(),
        ]);

        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new \Exception("Could not open file: {$filePath}");
        }

        try {
            $header = fgetcsv($handle);

            if (! $header) {
                throw new \Exception('CSV file is empty');
            }

            // Validate header
            $expectedHeader = ['customer_id', 'type', 'currency_code', 'amount_foreign', 'rate', 'purpose', 'source_of_funds', 'till_id'];
            $headerLower = array_map('strtolower', $header);
            if (count(array_diff($expectedHeader, $headerLower)) > 0) {
                throw new \Exception('Invalid CSV header. Expected columns: '.implode(', ', $expectedHeader));
            }

            $threshold = $this->thresholdService->getAutoApproveThreshold();
            $rowNumber = 1;

            // Resolve the importing user once instead of once per row.
            $importUser = User::find($import->imported_by);
            if (! $importUser) {
                throw new \Exception("Import user ID {$import->imported_by} not found");
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $this->processRow($import, $row, $rowNumber, $threshold, $importUser);
            }

            $this->import->update([
                'status' => count($this->errors) > 0 ? TransactionImportStatus::CompletedWithErrors->value : TransactionImportStatus::Completed->value,
                'success_count' => $this->successCount,
                'error_count' => count($this->errors),
                'error_details' => $this->errors,
                'completed_at' => now(),
            ]);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Process single row
     */
    protected function processRow(TransactionImport $import, array $row, int $rowNumber, string $threshold, User $importUser): void
    {
        try {
            DB::transaction(function () use ($row, $threshold, $importUser) {
                // Pad short rows so malformed CSVs produce clean per-row errors instead
                // of PHP undefined-array-key warnings on every column access.
                $row = array_pad($row, 8, '');

                // Expected columns: customer_id, type, currency_code, amount_foreign, rate, purpose, source_of_funds, till_id
                $data = [
                    'customer_id' => trim($row[0]),
                    'type' => trim($row[1]), // Buy or Sell
                    'currency_code' => strtoupper(trim($row[2])),
                    'amount_foreign' => trim($row[3]),
                    'rate' => trim($row[4]),
                    'purpose' => trim($row[5]),
                    'source_of_funds' => trim($row[6]),
                    'till_id' => isset($row[7]) && ! empty(trim($row[7])) ? trim($row[7]) : 'MAIN',
                ];

                $data['idempotency_key'] = hash('sha256', json_encode($data));

                // Validate required fields
                if (empty($data['customer_id']) || empty($data['type']) || empty($data['currency_code']) ||
                    empty($data['amount_foreign']) || empty($data['rate']) || empty($data['purpose']) ||
                    empty($data['source_of_funds'])) {
                    throw new \Exception('Missing required fields');
                }

                // Validate customer exists
                $customer = Customer::find($data['customer_id']);
                if (! $customer) {
                    throw new \Exception("Customer ID {$data['customer_id']} not found");
                }

                // Validate currency exists (cached per import - avoids one query per row)
                $currencyCode = $data['currency_code'];
                if (! isset($this->currencyCache[$currencyCode])) {
                    $this->currencyCache[$currencyCode] = Currency::where('code', $currencyCode)->exists();
                }
                if (! $this->currencyCache[$currencyCode]) {
                    throw new \Exception("Currency {$currencyCode} not found");
                }

                // Validate transaction type
                if (TransactionType::tryFrom($data['type']) === null) {
                    throw new \Exception("Invalid transaction type: {$data['type']}. Must be '".TransactionType::Buy->value."' or '".TransactionType::Sell->value."'");
                }

                // Validate numeric amounts
                if (! is_numeric($data['amount_foreign']) || BcmathHelper::lte($data['amount_foreign'], '0')) {
                    throw new \Exception("Invalid amount_foreign: {$data['amount_foreign']}");
                }

                if (! is_numeric($data['rate']) || BcmathHelper::lte($data['rate'], '0')) {
                    throw new \Exception("Invalid rate: {$data['rate']}");
                }

                // Upper bounds so a single malformed row cannot create unbounded entries.
                $maxAmountForeign = (string) config('transactions.import.max_amount_foreign');
                if (BcmathHelper::gt($data['amount_foreign'], $maxAmountForeign)) {
                    throw new \Exception("amount_foreign {$data['amount_foreign']} exceeds maximum allowed ({$maxAmountForeign})");
                }

                $maxRate = (string) config('transactions.import.max_rate');
                if (BcmathHelper::gt($data['rate'], $maxRate)) {
                    throw new \Exception("rate {$data['rate']} exceeds maximum allowed ({$maxRate})");
                }

                // Validate till is open (cached per import - avoids one query per row)
                $tillKey = (string) $data['till_id'];
                if (! array_key_exists($tillKey, $this->counterCache)) {
                    $this->counterCache[$tillKey] = Counter::where('code', $data['till_id'])
                        ->orWhere('id', $data['till_id'])
                        ->first();
                }
                $counter = $this->counterCache[$tillKey];

                if (! $counter) {
                    throw new \Exception("Till {$data['till_id']} is not open for {$data['currency_code']}");
                }

                $tillBalance = $this->tillBalanceManager->currentBalance($counter, $data['currency_code']);

                if (! $tillBalance) {
                    throw new \Exception("Till {$data['till_id']} is not open for {$data['currency_code']}");
                }

                // Validate the rate against the current market rate so bulk imports
                // cannot book trades at aberrant rates (same guard the interactive
                // wizard applies). Skips rows where no market rate is configured.
                $rateCheck = $this->rateManagementService->validateTransactionRate(
                    (string) $data['rate'],
                    $data['currency_code'],
                    strtolower($data['type']),
                    $counter->branch_id
                );

                if (! $rateCheck['valid']) {
                    throw new \Exception($rateCheck['reason'] ?? 'Rate deviation exceeds maximum allowed');
                }

                // Calculate local amount
                $amountForeign = (string) $data['amount_foreign'];
                $rate = (string) $data['rate'];
                $amountLocal = $this->mathService->multiply($amountForeign, $rate);

                // Compliance checks
                $cddLevel = $this->complianceService->determineCDDLevel(
                    $amountLocal,
                    $customer
                );

                // Check if requires hold/approval
                $holdCheck = $this->complianceService->requiresHold(
                    $amountLocal,
                    $customer
                );

                // Determine initial status
                $status = TransactionStatus::Completed->value;
                $holdReason = null;
                $approvedBy = null;

                if ($holdCheck->requiresHold) {
                    $status = TransactionStatus::PendingApproval->value;
                    $holdReason = implode(', ', $holdCheck->reasons);
                }

                // Enforce auto-approve threshold: if amount exceeds threshold, require approval
                if ($this->mathService->compare($amountLocal, $threshold) >= 0) {
                    $status = TransactionStatus::PendingApproval->value;
                    $thresholdReason = 'Transaction amount exceeds auto-approve threshold';
                    $holdReason = $holdReason ? "{$holdReason}; {$thresholdReason}" : $thresholdReason;
                }

                // Create transaction using TransactionCreationService to avoid duplicate logic.
                // The importing user is resolved once in process() and passed in.
                $transaction = $this->transactionCreationService->createForImport(
                    data: $data,
                    customer: $customer,
                    tillBalance: $tillBalance,
                    cddLevel: $cddLevel,
                    status: TransactionStatus::from($status),
                    amountLocal: $amountLocal,
                    user: $importUser,
                    holdReason: $holdReason,
                );

                // Run compliance monitoring BEFORE commit (moved before commit)
                if ($status === TransactionStatus::Completed->value) {
                    $this->monitoringService->monitorTransaction($transaction);
                }

                $this->successCount++;
            });
        } catch (\Exception $e) {
            $this->errors[] = [
                'row' => $rowNumber,
                'data' => $row,
                'error' => $e->getMessage(),
            ];
        }
    }
}
