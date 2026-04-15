<?php

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Services\PosInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosInventoryController extends Controller
{
    protected PosInventoryService $inventoryService;

    public function __construct(PosInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(): View
    {
        $aggregateInventory = $this->inventoryService->getAggregateInventory();
        $lowStockCurrencies = $this->inventoryService->getLowStockCurrencies();

        return view('pos.inventory.index', [
            'aggregateInventory' => $aggregateInventory,
            'lowStockCurrencies' => $lowStockCurrencies,
        ]);
    }

    public function counter(Request $request): JsonResponse
    {
        $counterId = $request->input('counter_id');

        if (! $counterId) {
            return response()->json(['success' => false, 'message' => 'Counter ID required'], 400);
        }

        return response()->json([
            'success' => true,
            'inventory' => $this->inventoryService->getInventoryByCounter($counterId),
        ]);
    }

    public function aggregate(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'inventory' => $this->inventoryService->getAggregateInventory(),
        ]);
    }

    public function lowStock(): JsonResponse
    {
        $threshold = (float) request()->input('threshold', 10000);

        return response()->json([
            'success' => true,
            'low_stock' => $this->inventoryService->getLowStockCurrencies($threshold),
        ]);
    }

    public function eod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'counter_id' => 'required|string|exists:counters,code',
            'physical_counts' => 'required|array',
            'physical_counts.*.currency_code' => 'required|string|size:3',
            'physical_counts.*.amount' => 'required|numeric|min:0',
        ]);

        $physicalCounts = array_map(function ($count) use ($data) {
            return [
                'currency_code' => $count['currency_code'],
                'amount' => $count['amount'],
                'counter_id' => $data['counter_id'],
            ];
        }, $data['physical_counts']);

        $variances = $this->inventoryService->calculateEodVariance($physicalCounts);

        $hasRedVariance = collect($variances)->contains('status', 'red');
        $hasYellowVariance = collect($variances)->contains('status', 'yellow');

        return response()->json([
            'success' => true,
            'variances' => $variances,
            'requires_manager_approval' => $hasRedVariance,
            'requires_notes' => $hasYellowVariance,
        ]);
    }
}
