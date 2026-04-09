<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $stockTransferService,
    ) {}

    public function index(Request $request)
    {
        $query = StockTransfer::with(['items', 'requestedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source_branch')) {
            $query->where('source_branch_name', $request->source_branch);
        }

        if ($request->has('destination_branch')) {
            $query->where('destination_branch_name', $request->destination_branch);
        }

        $transfers = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        return view('stock-transfers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_branch_name' => 'required|string',
            'destination_branch_name' => 'required|string|different:source_branch_name',
            'type' => 'required|in:Standard,Emergency,Scheduled,Return',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.currency_code' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.value_myr' => 'required|numeric|min:0',
        ]);

        $transfer = $this->stockTransferService->createRequest($request->validated());

        return redirect()->route('stock-transfers.show', $transfer->id)
            ->with('success', 'Transfer request created');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items', 'requestedBy', 'branchManagerApprovedBy', 'hqApprovedBy']);

        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function approveBm(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->approveByBranchManager($stockTransfer);

        return redirect()->back()->with('success', 'Transfer approved by branch manager');
    }

    public function approveHq(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->approveByHQ($stockTransfer);

        return redirect()->back()->with('success', 'Transfer approved by HQ');
    }

    public function dispatch(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->dispatch($stockTransfer);

        return redirect()->back()->with('success', 'Transfer dispatched');
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        $this->stockTransferService->receiveItems($stockTransfer, $request->items);

        return redirect()->back()->with('success', 'Items received');
    }

    public function complete(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->complete($stockTransfer);

        return redirect()->back()->with('success', 'Transfer completed');
    }

    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->stockTransferService->cancel($stockTransfer, $request->reason);

        return redirect()->back()->with('success', 'Transfer cancelled');
    }
}