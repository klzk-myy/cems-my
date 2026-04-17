<?php

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Modules\Pos\Services\PosInventoryService;
use App\Modules\Pos\Services\PosRateService;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected PosRateService $rateService,
        protected PosInventoryService $inventoryService,
    ) {}

    public function index(): View
    {
        $todayRates = $this->rateService->getTodayRates();
        $aggregateInventory = $this->inventoryService->getAggregateInventory();
        $lowStockCount = collect($aggregateInventory)->where('status', 'low')->count();

        $todayTransactions = Transaction::whereDate('created_at', today())
            ->where('status', 'Completed')
            ->count();

        $todayVolume = Transaction::whereDate('created_at', today())
            ->where('status', 'Completed')
            ->sum('amount_local');

        return view('pos.dashboard', [
            'todayRates' => $todayRates,
            'currencyCount' => collect($aggregateInventory)->count(),
            'lowStockCount' => $lowStockCount,
            'todayTransactions' => $todayTransactions,
            'todayVolume' => $todayVolume,
        ]);
    }
}
