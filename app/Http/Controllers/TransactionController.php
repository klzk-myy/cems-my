<?php

namespace App\Http\Controllers;

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
    protected CurrencyPositionService $positionService;
    protected ComplianceService $complianceService;
    protected TransactionMonitoringService $monitoringService;
    protected MathService $mathService;

    public function __construct(
        CurrencyPositionService $positionService,
        ComplianceService $complianceService,
        TransactionMonitoringService $monitoringService,
        MathService $mathService
    ) {
        $this->positionService = $positionService;
        $this->complianceService = $complianceService;
        $this->monitoringService = $monitoringService;
        $this->mathService = $mathService;
    }

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
            'type' => 'required|in:Buy,Sell',
            'currency_code' => 'required|exists:currencies,code',
            'amount_foreign' => 'required|numeric|min:0.01',
            'rate' => 'required|numeric|min:0.0001',
            'purpose' => 'required|string|max:255',
            'source_of_funds' => 'required|string|max:255',
            'till_id' => 'required|string',
        ]);

        // Check if till is open
        $tillBalance = TillBalance::where('till_id', $validated['till_id'])
            ->where('currency_code', $validated['currency_code'])
            ->whereDate('date', today())
            ->whereNull('closed_at')
            ->first();

        if (!$tillBalance) {
            return back()->with('error', 'Till is not open for this currency. Please open the till first.')
                ->withInput();
        }

        $customer = Customer::find($validated['customer_id']);

        // Calculate local amount
        $amountForeign = (string) $validated['amount_foreign'];
        $rate = (string) $validated['rate'];
        $amountLocal = $this->mathService->multiply($amountForeign, $rate);

        // Compliance checks
        $cddLevel = $this->complianceService->determineCDDLevel(
            (float) $amountLocal,
            $customer
        );

        // Check if requires hold/approval
        $holdCheck = $this->complianceService->requiresHold(
            (float) $amountLocal,
            $customer
        );

        // Determine initial status
        $status = 'Completed';
        $holdReason = null;
        $approvedBy = null;

        if ($holdCheck['requires_hold']) {
            if ((float) $amountLocal >= 50000) {
                // Large transaction needs manager approval
                $status = 'Pending';
                $holdReason = 'EDD_Required: ' . implode(', ', $holdCheck['reasons']);
            } else {
                $status = 'OnHold';
                $holdReason = implode(', ', $holdCheck['reasons']);
            }
        }

        // For sell transactions, check stock availability
        if ($validated['type'] === 'Sell') {
            try {
                $position = $this->positionService->getPosition(
                    $validated['currency_code'],
                    $validated['till_id']
                );

                if (!$position || $this->mathService->compare($position->balance, $amountForeign) < 0) {
                    $availableBalance = $position ? $position->balance : '0';
                    return back()->with('error', "Insufficient stock. Available: {$availableBalance} {$validated['currency_code']}")
                        ->withInput();
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Stock validation error: ' . $e->getMessage())
                    ->withInput();
            }
        }

        // Create transaction within database transaction
        DB::beginTransaction();

        try {
            // Create transaction record
            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
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
            ]);

            // Update currency position (if not pending approval)
            if ($status === 'Completed') {
                $this->positionService->updatePosition(
                    $validated['currency_code'],
                    $amountForeign,
                    $rate,
                    $validated['type'],
                    $validated['till_id']
                );

                // Update till balance (cash)
                $this->updateTillBalance($tillBalance, $validated['type'], $amountLocal, $amountForeign);

                // Create accounting entries
                $this->createAccountingEntries($transaction);
            }

            // Log transaction creation
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

            // Run compliance monitoring
            if ($status === 'Completed') {
                $this->monitoringService->monitorTransaction($transaction);
            }

            DB::commit();

            if ($status === 'Pending') {
                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction created and pending manager approval (≥ RM 50,000).');
            } elseif ($status === 'OnHold') {
                return redirect()->route('transactions.show', $transaction)
                    ->with('warning', 'Transaction on hold: ' . $holdReason);
            }

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction completed successfully. Receipt #' . $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_failed',
                'description' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return back()->with('error', 'Transaction failed: ' . $e->getMessage())
                ->withInput();
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
     * Approve pending transaction (Manager/Admin only)
     */
    public function approve(Request $request, Transaction $transaction)
    {
        // Check if user can approve
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager approval required.');
        }

        if ($transaction->status !== 'Pending') {
            return back()->with('error', 'Transaction is not pending approval.');
        }

        DB::beginTransaction();

        try {
            $transaction->update([
                'status' => 'Completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Get till balance
            $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                ->where('currency_code', $transaction->currency_code)
                ->whereDate('date', today())
                ->whereNull('closed_at')
                ->first();

            if ($tillBalance) {
                // Update currency position
                $this->positionService->updatePosition(
                    $transaction->currency_code,
                    (string) $transaction->amount_foreign,
                    (string) $transaction->rate,
                    $transaction->type,
                    $transaction->till_id ?? 'MAIN'
                );

                // Update till balance
                $this->updateTillBalance(
                    $tillBalance,
                    $transaction->type,
                    (string) $transaction->amount_local,
                    (string) $transaction->amount_foreign
                );
            }

            // Create accounting entries
            $this->createAccountingEntries($transaction);

            // Log approval
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_approved',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => ['approved_by' => auth()->id()],
                'ip_address' => $request->ip(),
            ]);

            // Run compliance monitoring
            $this->monitoringService->monitorTransaction($transaction);

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction approved and completed.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Update till balance for transaction
     */
    protected function updateTillBalance(TillBalance $tillBalance, string $type, string $amountLocal, string $amountForeign): void
    {
        // Note: Till balance tracks foreign currency stock, not cash
        // The actual cash (MYR) is tracked separately
        // For now, we update a running total of transactions

        $currentTotal = $tillBalance->transaction_total ?? '0';
        $foreignTotal = $tillBalance->foreign_total ?? '0';

        if ($type === 'Buy') {
            // Buying foreign: stock increases
            $tillBalance->update([
                'transaction_total' => $this->mathService->add($currentTotal, $amountLocal),
                'foreign_total' => $this->mathService->add($foreignTotal, $amountForeign),
            ]);
        } else {
            // Selling foreign: stock decreases
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

        if ($transaction->type === 'Buy') {
            // Buy: Dr Foreign Currency Inventory, Cr Cash - MYR
            $entries = [
                [
                    'account_code' => '2000', // Foreign Currency Inventory
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Buy {$transaction->amount_foreign} {$transaction->currency_code} @ {$transaction->rate}",
                ],
                [
                    'account_code' => '1000', // Cash - MYR
                    'debit' => '0',
                    'credit' => $transaction->amount_local,
                    'description' => "Payment for {$transaction->currency_code} purchase",
                ],
            ];
        } else {
            // Sell: Calculate gain/loss
            $position = $this->positionService->getPosition($transaction->currency_code);
            $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
            $costBasis = $this->mathService->multiply(
                (string) $transaction->amount_foreign,
                $avgCost
            );
            $revenue = $this->mathService->subtract(
                (string) $transaction->amount_local,
                $costBasis
            );

            $isGain = $this->mathService->compare($revenue, '0') >= 0;

            $entries = [
                [
                    'account_code' => '1000', // Cash - MYR
                    'debit' => $transaction->amount_local,
                    'credit' => '0',
                    'description' => "Sale of {$transaction->amount_foreign} {$transaction->currency_code}",
                ],
                [
                    'account_code' => '2000', // Foreign Currency Inventory
                    'debit' => '0',
                    'credit' => $costBasis,
                    'description' => "Cost of {$transaction->currency_code} sold",
                ],
            ];

        if ($isGain) {
            $entries[] = [
                'account_code' => '5000', // Revenue - Forex Trading
                'debit' => '0',
                'credit' => $revenue,
                'description' => "Gain on {$transaction->currency_code} sale",
            ];
        } else {
            $entries[] = [
                'account_code' => '6000', // Expense - Forex Loss
                'debit' => $this->mathService->multiply($revenue, '-1'),
                'credit' => '0',
                'description' => "Loss on {$transaction->currency_code} sale",
            ];
        }
    }

    $accountingService = app(AccountingService::class);
    $accountingService->createJournalEntry(
        $entries,
        'Transaction',
        $transaction->id,
        "Transaction #{$transaction->id} - {$transaction->type} {$transaction->currency_code}"
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
}
