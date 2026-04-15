<?php

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Modules\Pos\Services\PosTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosTransactionController extends Controller
{
    protected PosTransactionService $transactionService;

    public function __construct(PosTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(): View
    {
        $transactions = Transaction::with(['customer', 'counter'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pos.transaction.index', ['transactions' => $transactions]);
    }

    public function create(): View
    {
        $currencies = \App\Models\Currency::where('is_active', true)->get();
        $counters = \App\Models\Counter::where('status', 'active')->get();

        return view('pos.transaction.create', [
            'currencies' => $currencies,
            'counters' => $counters,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:Buy,Sell',
            'currency_code' => 'required|string|size:3',
            'amount_foreign' => 'required|numeric|min:0.01',
            'customer_id' => 'required|exists:customers,id',
            'till_id' => 'required|string|max:50|exists:counters,code',
            'purpose' => 'required|string|max:255',
            'source_of_funds' => 'required|string|max:255',
        ]);

        try {
            $transaction = $this->transactionService->createTransaction($data);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load(['customer', 'counter', 'createdBy']);

        return view('pos.transaction.show', ['transaction' => $transaction]);
    }

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:Buy,Sell',
            'currency_code' => 'required|string|size:3',
            'amount_foreign' => 'required|numeric|min:0.01',
            'customer_id' => 'nullable|exists:customers,id',
            'till_id' => 'nullable|string|max:50',
        ]);

        $result = $this->transactionService->getTransactionQuote($data);

        return response()->json($result);
    }
}
