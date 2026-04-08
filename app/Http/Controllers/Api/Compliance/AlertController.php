<?php

namespace App\Http\Controllers\Api\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertTriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        protected AlertTriageService $alertTriageService
    ) {}

    /**
     * List alerts for triage.
     *
     * GET /api/compliance/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Alert::with(['customer', 'flaggedTransaction', 'assignedTo'])
            ->whereNull('case_id');

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned')) {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->whereNotNull('assigned_to');
            }
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('risk_score')
            ->paginate(50);

        return response()->json($alerts);
    }

    /**
     * Get a single alert.
     *
     * GET /api/compliance/alerts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $alert = Alert::with(['customer', 'flaggedTransaction', 'flaggedTransaction.transaction', 'assignedTo', 'case'])
            ->findOrFail($id);

        return response()->json($alert);
    }

    /**
     * Bulk assign alerts to a compliance officer.
     *
     * POST /api/compliance/alerts/bulk-assign
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'integer',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $results = $this->alertTriageService->bulkAssign(
            $request->alert_ids,
            $request->user_id
        );

        return response()->json([
            'message' => "Bulk assign completed: {$results['success']} succeeded, {$results['failed']} failed",
            'results' => $results,
        ], $results['failed'] > 0 ? 207 : 200);
    }

    /**
     * Bulk resolve multiple alerts.
     *
     * POST /api/compliance/alerts/bulk-resolve
     */
    public function bulkResolve(Request $request): JsonResponse
    {
        $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $results = $this->alertTriageService->bulkResolve(
            $request->alert_ids,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'message' => "Bulk resolve completed: {$results['success']} succeeded, {$results['failed']} failed",
            'results' => $results,
        ], $results['failed'] > 0 ? 207 : 200);
    }

    /**
     * Get alert queue summary.
     *
     * GET /api/compliance/alerts/summary
     */
    public function summary(): JsonResponse
    {
        $summary = $this->alertTriageService->getQueueSummary();

        return response()->json($summary);
    }

    /**
     * Get overdue alerts.
     *
     * GET /api/compliance/alerts/overdue
     */
    public function overdue(): JsonResponse
    {
        $alerts = Alert::with(['customer', 'flaggedTransaction'])
            ->whereNull('case_id')
            ->get()
            ->filter(fn($alert) => $alert->isOverdue())
            ->values();

        return response()->json([
            'count' => $alerts->count(),
            'alerts' => $alerts,
        ]);
    }

    /**
     * Auto-assign alerts to available officers.
     *
     * POST /api/compliance/alerts/auto-assign
     */
    public function autoAssign(): JsonResponse
    {
        $assigned = $this->alertTriageService->autoAssignAlerts();

        return response()->json([
            'message' => 'Auto-assignment completed',
            'assigned_count' => count($assigned),
        ]);
    }
}
