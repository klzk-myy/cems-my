<?php

namespace App\Http\Controllers;

use App\Enums\ComplianceFlagType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Transaction\Concerns\TransactionAccounting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use TransactionAccounting;

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
            'amount_foreign' => 'required|numeric|min:0.01|max:9999999999.9999',
            'rate' => 'required|numeric|min:0.0001|max:999999',
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

        // Sanitize text inputs to prevent XSS
        $validated['purpose'] = strip_tags($validated['purpose']);
        $validated['source_of_funds'] = strip_tags($validated['source_of_funds']);

        $tillBalance = TillBalance::where('till_id', $validated['till_id'])
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
            $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorPNG;
            $barcodeData = $barcodeGenerator->getBarcode($barcodeText, $barcodeGenerator::TYPE_CODE_128);
            $barcodeImage = 'data:image/png;base64,'.base64_encode($barcodeData);
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
                    'verify' => url('/verify/transaction/'.$transaction->id),
                ]));
            $qrCodeImage = 'data:image/png;base64,'.base64_encode($qrCodeData);
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
}
