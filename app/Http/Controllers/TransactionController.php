<?php

namespace App\Http\Controllers;

use App\Enums\AccountCode;
use App\Enums\ComplianceFlagType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\TransactionConfirmation;
use App\Models\TransactionImport;
use App\Services\AccountingService;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionImportService;
use App\Services\TransactionMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function __construct(
        protected CurrencyPositionService $positionService,
        protected ComplianceService $complianceService,
        protected TransactionMonitoringService $monitoringService,
        protected MathService $mathService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Display list of transactions
     */
    public function index()
    {
        $transactions = Transaction::with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show form to create new transaction
     */
    public function create()
    {
        $currencies = Currency::where('is_active', true)->get();
        $customers = Customer::all();
        $tillBalances = TillBalance::where('date', today())
            ->whereNull('closed_at')
            ->with('currency')
            ->get();

        return view('transactions.create', compact('currencies', 'customers', 'tillBalances'));
    }

    /**
     * Store new transaction
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => ['required', 'in:'.TransactionType::Buy->value.','.TransactionType::Sell->value],
            'currency_code' => 'required|exists:currencies,code',
            'amount_foreign' => 'required|numeric|min:0.01',
            'rate' => 'required|numeric|min:0.0001',
            'purpose' => 'required|string|max:255',
            'source_of_funds' => 'required|string|max:255',
            'till_id' => 'required|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        // Duplicate transaction prevention: Check for recent similar transaction
        // This prevents double-submit and network retry issues
        $recentWindow = now()->subSeconds(30);
        $duplicateQuery = Transaction::where('user_id', auth()->id())
            ->where('customer_id', $validated['customer_id'])
            ->where('type', $validated['type'])
            ->where('currency_code', $validated['currency_code'])
            ->where('created_at', '>=', $recentWindow);

        // If idempotency key provided, check that specifically
        if (! empty($validated['idempotency_key'])) {
            $existingByKey = Transaction::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existingByKey) {
                return redirect()->route('transactions.show', $existingByKey)
                    ->with('info', 'Transaction already processed.');
            }
        }

        // Check for similar transactions (same amount within recent window)
        $recentAmount = Transaction::where('user_id', auth()->id())
            ->where('created_at', '>=', $recentWindow)
            ->where('amount_foreign', $validated['amount_foreign'])
            ->where('currency_code', $validated['currency_code'])
            ->first();

        if ($recentAmount) {
            // Log potential duplicate for audit
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'potential_duplicate_detected',
                'entity_type' => 'Transaction',
                'description' => "Similar transaction {$recentAmount->id} found within 30 seconds",
                'ip_address' => $request->ip(),
            ]);
        }

        $tillBalance = TillBalance::where('till_id', $validated['till_id'])
            ->where('currency_code', $validated['currency_code'])
            ->whereDate('date', today())
            ->whereNull('closed_at')
            ->first();

        if (! $tillBalance) {
            return back()->with('error', 'Till is not open for this currency. Please open the till first.')
                ->withInput();
        }

        $customer = Customer::find($validated['customer_id']);
        $amountForeign = (string) $validated['amount_foreign'];
        $rate = (string) $validated['rate'];
        $amountLocal = $this->mathService->multiply($amountForeign, $rate);

        // Use string amounts for precision (BCMath)
        $cddLevel = $this->complianceService->determineCDDLevel($amountLocal, $customer);
        $holdCheck = $this->complianceService->requiresHold($amountLocal, $customer);

        // Log CDD decision for compliance audit trail
        $cddTriggers = [];
        if ($customer->pep_status) {
            $cddTriggers[] = 'PEP customer';
        }
        if ($this->mathService->compare($amountLocal, '50000') >= 0) {
            $cddTriggers[] = 'Large amount >= RM 50,000';
        } elseif ($this->mathService->compare($amountLocal, '3000') >= 0) {
            $cddTriggers[] = 'Standard amount >= RM 3,000';
        }
        if ($customer->risk_rating === 'High') {
            $cddTriggers[] = 'High risk customer';
        }

        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'cdd_decision',
            'entity_type' => 'Transaction',
            'entity_id' => null, // Will be updated after creation
            'new_values' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'cdd_level' => $cddLevel->value,
                'triggers' => $cddTriggers,
                'amount_local' => $amountLocal,
            ],
            'ip_address' => $request->ip(),
        ]);

        $status = TransactionStatus::Completed;
        $holdReason = null;
        $approvedBy = null;

        if ($holdCheck['requires_hold']) {
            // Check if amount >= 50,000 using BCMath for precision
            if ($this->mathService->compare($amountLocal, '50000') >= 0) {
                $status = TransactionStatus::Pending;
                $holdReason = ComplianceFlagType::EddRequired->label().': '.implode(', ', $holdCheck['reasons']);
            } else {
                $status = TransactionStatus::OnHold;
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        if ($validated['type'] === TransactionType::Sell->value) {
            $position = $this->positionService->getPosition($validated['currency_code'], $validated['till_id']);
            if (! $position || $this->mathService->compare($position->balance, $amountForeign) < 0) {
                $availableBalance = $position ? $position->balance : '0';

                return back()->with('error', "Insufficient stock. Available: {$availableBalance} {$validated['currency_code']}")
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'till_id' => $validated['till_id'],
                'type' => $validated['type'],
                'currency_code' => $validated['currency_code'],
                'amount_foreign' => $amountForeign,
                'amount_local' => $amountLocal,
                'rate' => $rate,
                'purpose' => $validated['purpose'],
                'source_of_funds' => $validated['source_of_funds'],
                'status' => $status,
                'hold_reason' => $holdReason,
                'approved_by' => $approvedBy,
                'cdd_level' => $cddLevel,
                'idempotency_key' => $validated['idempotency_key'] ?? null,
                'version' => 0,
            ]);

            if ($status === TransactionStatus::Completed) {
                $this->positionService->updatePosition(
                    $validated['currency_code'],
                    $amountForeign,
                    $rate,
                    $validated['type'],
                    $validated['till_id']
                );
                $this->updateTillBalance($tillBalance, $validated['type'], $amountLocal, $amountForeign);
                $this->createAccountingEntries($transaction);
            }

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_created',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => [
                    'type' => $transaction->type,
                    'amount_local' => $transaction->amount_local,
                    'amount_foreign' => $transaction->amount_foreign,
                    'currency' => $transaction->currency_code,
                    'status' => $transaction->status,
                    'cdd_level' => $cddLevel,
                ],
                'ip_address' => $request->ip(),
            ]);

            // Transaction monitoring is handled via TransactionCreated event
            // to avoid duplicate processing

            DB::commit();

            // Dispatch event for async processing
            \App\Events\TransactionCreated::dispatch($transaction);

            if ($status === TransactionStatus::Pending) {
                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction created and pending manager approval (≥ RM 50,000).');
            } elseif ($status === TransactionStatus::OnHold) {
                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction on hold: '.$holdReason);
            }

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction completed successfully. Receipt #'.$transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_failed',
                'description' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return back()->with('error', 'Transaction failed: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Display single transaction
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['customer', 'user', 'approver', 'flags']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Approve pending transaction
     */
    public function approve(Request $request, Transaction $transaction)
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager approval required.');
        }

        if (! $transaction->status->isPending()) {
            return back()->with('error', 'Transaction is not pending approval.');
        }

        DB::beginTransaction();
        try {
            // Re-evaluate AML rules before approval
            // If high-priority flags are generated, keep transaction pending
            $amlResult = $this->monitoringService->monitorTransaction($transaction);
            $highPriorityFlags = array_filter($amlResult['flags'], function ($flag) {
                return $flag->flag_type->isHighPriority();
            });

            if (! empty($highPriorityFlags)) {
                DB::rollBack();
                $flagTypes = implode(', ', array_map(fn ($f) => $f->flag_type->label(), $highPriorityFlags));

                return back()->with('error', "Approval blocked: High-priority AML flags generated ({$flagTypes}). Transaction remains pending for compliance review.");
            }

            // Optimistic locking: Use version to prevent race conditions
            // If another manager approved between the status check and now, this will fail
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', TransactionStatus::Pending)
                ->where('version', $transaction->version)
                ->update([
                    'status' => TransactionStatus::Completed,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'version' => DB::raw('version + 1'),
                ]);

            if (! $updated) {
                DB::rollBack();

                return back()->with('error', 'Transaction was already processed or modified by another user.');
            }

            // Refresh the model to get the updated version
            $transaction->refresh();

            $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                ->where('currency_code', $transaction->currency_code)
                ->whereDate('date', today())
                ->whereNull('closed_at')
                ->first();

            if ($tillBalance) {
                $this->positionService->updatePosition(
                    $transaction->currency_code,
                    (string) $transaction->amount_foreign,
                    (string) $transaction->rate,
                    $transaction->type->value,
                    $transaction->till_id ?? 'MAIN'
                );
                $this->updateTillBalance($tillBalance, $transaction->type->value,
                    (string) $transaction->amount_local,
                    (string) $transaction->amount_foreign
                );
            }

            $this->createAccountingEntries($transaction);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_approved',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => ['approved_by' => auth()->id()],
                'ip_address' => $request->ip(),
            ]);

            // Transaction monitoring is handled via TransactionCreated event
            DB::commit();

            // Dispatch event for async processing of monitoring
            \App\Events\TransactionCreated::dispatch($transaction);

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction approved and completed.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Approval failed: '.$e->getMessage());
        }
    }

    /**
     * Update till balance
     */
    protected function updateTillBalance(TillBalance $tillBalance, string $type, string $amountLocal, string $amountForeign): void
    {
        $currentTotal = $tillBalance->transaction_total ?? '0';
        $foreignTotal = $tillBalance->foreign_total ?? '0';

        if ($type === TransactionType::Buy->value) {
            $tillBalance->update([
                'transaction_total' => $this->mathService->add($currentTotal, $amountLocal),
                'foreign_total' => $this->mathService->add($foreignTotal, $amountForeign),
            ]);
        } else {
            $tillBalance->update([
                'transaction_total' => $this->mathService->add($currentTotal, $amountLocal),
                'foreign_total' => $this->mathService->subtract($foreignTotal, $amountForeign),
            ]);
        }
    }

    /**
     * Create accounting journal entries
     */
    protected function createAccountingEntries(Transaction $transaction): void
    {
        $entries = [];

        if ($transaction->type->isBuy()) {
            $entries = [
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Buy {$transaction->amount_foreign} {$transaction->currency_code} @ {$transaction->rate}",
                ],
                [
                    'account_code' => AccountCode::CASH_MYR->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Payment for {$transaction->currency_code} purchase",
                ],
            ];
        } else {
            $position = $this->positionService->getPosition($transaction->currency_code);
            $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
            $costBasis = $this->mathService->multiply((string) $transaction->amount_foreign, $avgCost);
            $revenue = $this->mathService->subtract((string) $transaction->amount_local, $costBasis);
            $isGain = $this->mathService->compare($revenue, '0') >= 0;

            $entries = [
                [
                    'account_code' => AccountCode::CASH_MYR->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Sale of {$transaction->amount_foreign} {$transaction->currency_code}",
                ],
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => '0',
                    'credit' => $costBasis,
                    'description' => "Cost of {$transaction->currency_code} sold",
                ],
            ];

            if ($isGain) {
                $entries[] = [
                    'account_code' => AccountCode::FOREX_TRADING_REVENUE->value,
                    'debit' => '0',
                    'credit' => $revenue,
                    'description' => "Gain on {$transaction->currency_code} sale",
                ];
            } else {
                $entries[] = [
                    'account_code' => AccountCode::FOREX_LOSS->value,
                    'debit' => $this->mathService->multiply($revenue, '-1'),
                    'credit' => '0',
                    'description' => "Loss on {$transaction->currency_code} sale",
                ];
            }
        }

        $this->accountingService->createJournalEntry(
            $entries,
            'Transaction',
            $transaction->id,
            "Transaction #{$transaction->id} - {$transaction->type->value} {$transaction->currency_code}"
        );
    }

    /**
     * Display customer's transaction history
     */
    public function customerTransactions(Customer $customer)
    {
        $transactions = Transaction::where('customer_id', $customer->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('transactions.customer', compact('transactions', 'customer'));
    }

    /**
     * Generate PDF receipt
     */
    public function receipt(Transaction $transaction)
    {
        if (! $transaction->status->isCompleted()) {
            return back()->with('error', 'Receipts can only be generated for completed transactions.');
        }

        $transaction->load(['customer', 'user', 'approver']);

        // Generate barcode (Code128) for transaction reference number
        $barcodeImage = null;
        $barcodeText = str_pad($transaction->id, 10, '0', STR_PAD_LEFT);
        try {
            $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcodeData = $barcodeGenerator->getBarcode($barcodeText, $barcodeGenerator::TYPE_CODE_128);
            $barcodeImage = 'data:image/png;base64,' . base64_encode($barcodeData);
        } catch (\Exception $e) {
            // Graceful fallback if barcode generation fails
            $barcodeImage = null;
        }

        // Generate QR code with transaction verification data
        $qrCodeImage = null;
        try {
            $qrCodeData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(150)
                ->generate(json_encode([
                    'id' => $transaction->id,
                    'amount' => $transaction->amount_local,
                    'currency' => $transaction->currency_code,
                    'date' => $transaction->created_at->toIso8601String(),
                    'customer_id' => $transaction->customer_id,
                    'type' => $transaction->type->value,
                    'verify' => url('/verify/transaction/' . $transaction->id),
                ]));
            $qrCodeImage = 'data:image/png;base64,' . base64_encode($qrCodeData);
        } catch (\Exception $e) {
            // Graceful fallback if QR code generation fails
            $qrCodeImage = null;
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('transactions.receipt', compact('transaction', 'barcodeImage', 'qrCodeImage', 'barcodeText'));
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');
        $filename = 'receipt_'.str_pad($transaction->id, 8, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Display comprehensive customer transaction history
     */
    public function customerHistory(Customer $customer)
    {
        $allTransactions = Transaction::where('customer_id', $customer->id)->get();

        $stats = [
            'total_count' => $allTransactions->count(),
            'buy_volume' => $allTransactions->where('type', TransactionType::Buy)->sum('amount_local'),
            'sell_volume' => $allTransactions->where('type', TransactionType::Sell)->sum('amount_local'),
            'total_volume' => $allTransactions->sum('amount_local'),
            'avg_transaction' => $allTransactions->count() > 0
                ? $this->mathService->divide(
                    (string) $allTransactions->sum('amount_local'),
                    (string) $allTransactions->count()
                )
                : '0',
            'first_transaction' => $allTransactions->min('created_at'),
            'last_transaction' => $allTransactions->max('created_at'),
        ];

        $transactions = Transaction::where('customer_id', $customer->id)
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $dbDriver = DB::getDriverName();
        $dateFormat = $dbDriver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyData = Transaction::where('customer_id', $customer->id)
            ->selectRaw("{$dateFormat} as month, type, SUM(amount_local) as total")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        $chartLabels = [];
        $chartBuyData = [];
        $chartSellData = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->format('M Y');
            $chartLabels[] = $monthLabel;

            $monthData = $monthlyData->get($monthKey, collect());
            $chartBuyData[] = (float) ($monthData->where('type', TransactionType::Buy)->first()?->total ?? 0);
            $chartSellData[] = (float) ($monthData->where('type', TransactionType::Sell)->first()?->total ?? 0);
        }

        return view('customers.history', compact(
            'customer', 'transactions', 'stats', 'chartLabels', 'chartBuyData', 'chartSellData'
        ));
    }

    /**
     * Export customer transaction history to CSV
     */
    public function exportCustomerHistory(Customer $customer)
    {
        $transactions = Transaction::where('customer_id', $customer->id)
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_'.$customer->id.'_history.csv"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Type', 'Currency', 'Amount Foreign', 'Amount Local', 'Rate', 'User', 'Status']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->type->value,
                    $transaction->currency_code,
                    $transaction->amount_foreign,
                    $transaction->amount_local,
                    $transaction->rate,
                    $transaction->user->username ?? 'N/A',
                    $transaction->status->value,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show cancellation confirmation form
     */
    public function showCancel(Transaction $transaction)
    {
        if (! $this->canCancel(auth()->user(), $transaction)) {
            abort(403, 'Unauthorized to cancel this transaction.');
        }

        if (! $transaction->isRefundable()) {
            return back()->with('error', 'This transaction cannot be cancelled.');
        }

        return view('transactions.cancel', compact('transaction'));
    }

    /**
     * Process transaction cancellation
     */
    public function cancel(Request $request, Transaction $transaction)
    {
        if (! $this->canCancel(auth()->user(), $transaction)) {
            abort(403, 'Unauthorized to cancel this transaction.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:1000',
            'confirm_understanding' => 'required|accepted',
        ]);

        if (! $transaction->isRefundable()) {
            return back()->with('error', 'This transaction cannot be cancelled.');
        }

        DB::beginTransaction();
        try {
            $originalTillId = $transaction->till_id ?? 'MAIN';
            $refundTransaction = $this->createRefundTransaction($transaction);

            $transaction->update([
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $validated['cancellation_reason'],
            ]);

            $this->reverseStockPosition($transaction, $originalTillId);
            $this->createReversingJournalEntries($transaction);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_cancelled',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'old_values' => ['status' => TransactionStatus::Completed->value],
                'new_values' => [
                    'status' => TransactionStatus::Cancelled->value,
                    'refund_transaction_id' => $refundTransaction->id,
                    'reason' => $validated['cancellation_reason'],
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction cancelled successfully. Refund transaction created.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Cancellation failed: '.$e->getMessage());
        }
    }

    /**
     * Show batch upload form
     */
    public function showBatchUpload()
    {
        $recentImports = TransactionImport::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('transactions.batch-upload', compact('recentImports'));
    }

    /**
     * Process batch upload
     */
    public function processBatchUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');

        // Store file
        $path = $file->store('imports');

        // Get the full file path - use actual file path for testing, Storage::path otherwise
        $fullPath = Storage::exists($path) ? Storage::path($path) : $file->getRealPath();

        // If file still doesn't exist at Storage path, fall back to temp path
        if (! file_exists($fullPath)) {
            $fullPath = $file->getRealPath();
        }

        // Count total rows first
        $handle = fopen($fullPath, 'r');
        if (! $handle) {
            return back()->with('error', 'Could not read uploaded file.')->withInput();
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        while (fgetcsv($handle) !== false) {
            $rowCount++;
        }
        fclose($handle);

        // Create import record with total_rows
        $import = TransactionImport::create([
            'user_id' => auth()->id(),
            'filename' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'total_rows' => $rowCount,
            'status' => 'pending',
        ]);

        try {
            // Process import
            $service = new TransactionImportService(
                $import,
                $this->mathService,
                $this->complianceService,
                $this->positionService,
                $this->accountingService,
                $this->monitoringService
            );
            $service->process($fullPath);

            return redirect()->route('transactions.batch-upload.show', $import)
                ->with('success', "Import completed. {$import->success_count} transactions imported, {$import->error_count} errors.");
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Show import results
     */
    public function showImportResults(TransactionImport $import)
    {
        // Authorization check - only owner can view (managers can only view their own imports)
        if ($import->user_id !== auth()->id()) {
            abort(403, 'Unauthorized. You can only view your own import results.');
        }

        return view('transactions.import-results', compact('import'));
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transaction_template.csv"',
        ];

        $template = "customer_id,type,currency_code,amount_foreign,rate,purpose,source_of_funds,till_id\n";
        $template .= "1,Buy,USD,1000,4.72,Business Travel,Salary,MAIN\n";
        $template .= "1,Sell,USD,500,4.75,Personal Use,Savings,TILL-001\n";

        return response($template, 200, $headers);
    }

    /**
     * Check if user can cancel transaction
     *
     * All transaction cancellations require manager or admin approval.
     * This enforces segregation of duties - no user should be able to
     * cancel their own transactions without supervisory approval.
     */
    protected function canCancel($user, Transaction $transaction): bool
    {
        // Managers and admins can cancel any transaction
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Tellers cannot cancel any transactions - requires manager approval
        // This enforces segregation of duties
        return false;
    }

    /**
     * Create refund transaction
     */
    protected function createRefundTransaction(Transaction $original): Transaction
    {
        $refundType = $original->type->opposite();
        $customer = Customer::find($original->customer_id);
        $amountLocal = $this->mathService->multiply(
            (string) $original->amount_foreign,
            (string) $original->rate
        );

        // Evaluate compliance for refund transaction
        $holdCheck = $this->complianceService->requiresHold($amountLocal, $customer);

        $status = TransactionStatus::Completed;
        $holdReason = null;

        if ($holdCheck['requires_hold']) {
            if ($this->mathService->compare($amountLocal, '50000') >= 0) {
                $status = TransactionStatus::Pending;
                $holdReason = implode(', ', $holdCheck['reasons']);
            } else {
                $status = TransactionStatus::OnHold;
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        // Log compliance decision for refund audit trail
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'refund_compliance_check',
            'entity_type' => 'Transaction',
            'entity_id' => null,
            'new_values' => [
                'original_transaction_id' => $original->id,
                'amount_local' => $amountLocal,
                'status' => $status->value,
                'hold_reason' => $holdReason,
                'compliance_reasons' => $holdCheck['reasons'],
            ],
        ]);

        return Transaction::create([
            'customer_id' => $original->customer_id,
            'user_id' => auth()->id(),
            'till_id' => $original->till_id,
            'type' => $refundType,
            'currency_code' => $original->currency_code,
            'amount_foreign' => $original->amount_foreign,
            'amount_local' => $amountLocal,
            'rate' => $original->rate,
            'purpose' => 'Refund: '.$original->purpose,
            'source_of_funds' => 'Refund',
            'status' => $status,
            'cdd_level' => $original->cdd_level,
            'original_transaction_id' => $original->id,
            'is_refund' => true,
        ]);
    }

    /**
     * Reverse stock position
     */
    protected function reverseStockPosition(Transaction $transaction, ?string $tillId = null): void
    {
        $reverseType = $transaction->type->opposite();
        $this->positionService->updatePosition(
            $transaction->currency_code,
            (string) $transaction->amount_foreign,
            (string) $transaction->rate,
            $reverseType->value,
            $tillId ?? $transaction->till_id ?? 'MAIN'
        );
    }

    /**
     * Create reversing journal entries
     */
    protected function createReversingJournalEntries(Transaction $transaction): void
    {
        $entries = [];
        if ($transaction->type->isBuy()) {
            $entries = [
                [
                    'account_code' => AccountCode::CASH_MYR->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Reversal: {$transaction->currency_code} refund",
                ],
            ];
        } else {
            $entries = [
                [
                    'account_code' => AccountCode::FOREIGN_CURRENCY_INVENTORY->value,
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Refund for cancelled transaction #{$transaction->id}",
                ],
                [
                    'account_code' => AccountCode::CASH_MYR->value,
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Reversal: {$transaction->currency_code} refund",
                ],
            ];
        }

        $this->accountingService->createJournalEntry(
            $entries,
            'TransactionCancellation',
            $transaction->id,
            "Cancellation of Transaction #{$transaction->id}"
        );
    }

    /**
     * Show confirmation page for large transactions (>= RM 50,000)
     */
    public function showConfirm(Transaction $transaction)
    {
        // Check if transaction requires confirmation (>= RM 50,000)
        if (! $this->requiresConfirmation($transaction)) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction does not require confirmation.');
        }

        // Check if there's already a pending confirmation
        $confirmation = TransactionConfirmation::where('transaction_id', $transaction->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (! $confirmation) {
            // Create a new confirmation request
            $confirmationToken = bin2hex(random_bytes(32));
            $confirmation = TransactionConfirmation::create([
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'confirmation_token' => $confirmationToken,
                'expires_at' => now()->addMinutes(30),
            ]);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'confirmation_requested',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => [
                    'confirmation_id' => $confirmation->id,
                    'amount_local' => $transaction->amount_local,
                ],
                'ip_address' => request()->ip(),
            ]);
        }

        $transaction->load(['customer', 'user']);

        return view('transactions.confirm', compact('transaction', 'confirmation'));
    }

    /**
     * Process transaction confirmation (manager approves large transaction)
     */
    public function confirm(Request $request, Transaction $transaction)
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager approval required for confirmation.');
        }

        if (! $this->requiresConfirmation($transaction)) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction does not require confirmation.');
        }

        $confirmation = TransactionConfirmation::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->first();

        if (! $confirmation) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'No pending confirmation found.');
        }

        if ($confirmation->isExpired()) {
            $confirmation->markExpired();

            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Confirmation has expired. Please request a new confirmation.');
        }

        $validated = $request->validate([
            'confirmation_action' => 'required|in:confirm,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            if ($validated['confirmation_action'] === 'confirm') {
                $confirmation->markConfirmed(auth()->id(), $validated['notes'] ?? null);

                // Complete the transaction
                $updated = Transaction::where('id', $transaction->id)
                    ->whereIn('status', [TransactionStatus::Pending, TransactionStatus::OnHold])
                    ->update([
                        'status' => TransactionStatus::Completed,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                if (! $updated) {
                    DB::rollBack();

                    return back()->with('error', 'Transaction could not be completed. Status may have changed.');
                }

                $transaction->refresh();

                // Update positions and create accounting entries
                $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                    ->where('currency_code', $transaction->currency_code)
                    ->whereDate('date', today())
                    ->whereNull('closed_at')
                    ->first();

                if ($tillBalance) {
                    $this->positionService->updatePosition(
                        $transaction->currency_code,
                        (string) $transaction->amount_foreign,
                        (string) $transaction->rate,
                        $transaction->type->value,
                        $transaction->till_id ?? 'MAIN'
                    );
                    $this->updateTillBalance($tillBalance, $transaction->type->value,
                        (string) $transaction->amount_local,
                        (string) $transaction->amount_foreign
                    );
                }

                $this->createAccountingEntries($transaction);

                SystemLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'transaction_confirmed',
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'confirmation_id' => $confirmation->id,
                        'confirmed_by' => auth()->id(),
                    ],
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();

                // Dispatch event for async processing
                \App\Events\TransactionCreated::dispatch($transaction);

                return redirect()->route('transactions.show', $transaction)
                    ->with('success', 'Transaction confirmed and completed successfully.');

            } else {
                // Reject the transaction
                $confirmation->markRejected(auth()->id(), $validated['notes'] ?? null);

                $transaction->update([
                    'status' => TransactionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => 'Rejected during confirmation: '.($validated['notes'] ?? 'No reason provided'),
                ]);

                SystemLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'transaction_rejected',
                    'entity_type' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'new_values' => [
                        'confirmation_id' => $confirmation->id,
                        'rejected_by' => auth()->id(),
                        'reason' => $validated['notes'] ?? 'No reason provided',
                    ],
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();

                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction has been rejected.');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Confirmation failed: '.$e->getMessage());
        }
    }

    /**
     * Check if transaction requires manager confirmation (>= RM 50,000)
     */
    protected function requiresConfirmation(Transaction $transaction): bool
    {
        $threshold = config('cems.thresholds.str', '50000');

        return $this->mathService->compare($transaction->amount_local, $threshold) >= 0;
    }
}
