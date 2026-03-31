<?php

namespace App\Http\Controllers;

use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use App\Services\CurrencyPositionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'today_transactions' => Transaction::whereDate('created_at', today())->count(),
            'today_volume' => Transaction::whereDate('created_at', today())->sum('amount_local'),
            'open_flags' => FlaggedTransaction::where('status', 'Open')->count(),
        ];

        return view('dashboard', compact('stats'));
    }

    public function compliance()
    {
        $flags = FlaggedTransaction::where('status', 'Open')
            ->with(['transaction.customer'])
            ->paginate(20);

        return view('compliance', compact('flags'));
    }

    public function accounting()
    {
        $service = new CurrencyPositionService(new \App\Services\MathService());
        $positions = $service->getAllPositions();
        $totalPnl = $service->getTotalPnl();

        return view('accounting', compact('positions', 'totalPnl'));
    }

    public function reports()
    {
        return view('reports');
    }
}
