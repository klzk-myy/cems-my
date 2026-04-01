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
            'total_transactions' => Transaction::whereDate('created_at', today())->count(),
            'buy_volume' => Transaction::whereDate('created_at', today())->where('type', 'Buy')->sum('amount_local'),
            'sell_volume' => Transaction::whereDate('created_at', today())->where('type', 'Sell')->sum('amount_local'),
            'flagged' => FlaggedTransaction::where('status', 'Open')->count(),
            'active_customers' => \App\Models\Customer::count(),
        ];

        $recent_transactions = Transaction::with('customer')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('stats', 'recent_transactions'));
    }

    public function compliance(Request $request)
    {
        // Only Compliance Officers and Admins can access
        if (!auth()->user()->isComplianceOfficer()) {
            abort(403, 'Unauthorized. Compliance Officer access required.');
        }

        // Build query with filters
        $query = FlaggedTransaction::with(['transaction.customer', 'assignedTo', 'reviewer']);

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply flag_type filter
        if ($request->filled('flag_type') && $request->flag_type !== 'all') {
            $query->where('flag_type', $request->flag_type);
        }

        // Get paginated flags
        $flags = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate stats
        $stats = [
            'open' => FlaggedTransaction::where('status', 'Open')->count(),
            'under_review' => FlaggedTransaction::where('status', 'Under_Review')->count(),
            'resolved_today' => FlaggedTransaction::where('status', 'Resolved')
                ->whereDate('resolved_at', today())
                ->count(),
            'high_priority' => FlaggedTransaction::whereIn('flag_type', ['Sanction_Match', 'Structuring'])
                ->where('status', '!=', 'Resolved')
                ->count(),
        ];

        return view('compliance', compact('flags', 'stats'));
    }

    public function assignFlag(Request $request, FlaggedTransaction $flaggedTransaction)
    {
        if (!auth()->user()->isComplianceOfficer()) {
            abort(403);
        }

        $flaggedTransaction->update([
            'assigned_to' => auth()->id(),
            'status' => 'Under_Review',
        ]);

        return back()->with('success', 'Flag assigned to you for review.');
    }

    public function resolveFlag(Request $request, FlaggedTransaction $flaggedTransaction)
    {
        if (!auth()->user()->isComplianceOfficer()) {
            abort(403);
        }

        $flaggedTransaction->update([
            'status' => 'Resolved',
            'reviewed_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Flag marked as resolved.');
    }

    public function accounting()
    {
        // Only Managers and Admins can access
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager access required.');
        }

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
