<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Compliance\ComplianceCase;
use App\Services\CaseManagementService;
use App\Services\Compliance\CaseManagementService as ComplianceCaseManagementService;
use Illuminate\Http\Request;

class CaseManagementController extends Controller
{
    public function __construct(
        protected CaseManagementService $caseManagementService,
        protected ComplianceCaseManagementService $complianceCaseManagementService
    ) {}

    public function index(Request $request)
    {
        $query = ComplianceCase::with(['customer', 'assignedTo', 'alerts'])
            ->open();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $cases = $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('sla_deadline')
            ->paginate(25);

        $summary = $this->caseManagementService->getCaseSummary();

        return view('compliance.cases.index', compact('cases', 'summary'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'exists:alerts,id',
        ]);

        $case = $this->caseManagementService->createFromAlerts(
            $request->alert_ids,
            auth()->id()
        );

        return redirect()->route('compliance.cases.show', $case->id)
            ->with('success', 'Case created successfully');
    }

    public function show(ComplianceCase $case)
    {
        $case->load(['customer', 'assignedTo', 'openedBy', 'alerts', 'alerts.flaggedTransaction']);

        return view('compliance.cases.show', compact('case'));
    }

    public function update(Request $request, ComplianceCase $case)
    {
        $request->validate([
            'status' => 'nullable|in:open,in_progress,pending_review,resolved,closed',
            'notes' => 'nullable|string',
        ]);

        if ($request->has('status')) {
            $case = $this->caseManagementService->updateStatus($case, $request->status);
        }

        if ($request->has('notes')) {
            $case->update(['notes' => $request->notes]);
        }

        return redirect()->back()->with('success', 'Case updated successfully');
    }

    public function merge(Request $request, ComplianceCase $case)
    {
        $request->validate([
            'target_case_id' => 'required|exists:compliance_cases,id',
        ]);

        $targetCase = ComplianceCase::findOrFail($request->target_case_id);

        $mergedCase = $this->caseManagementService->mergeCases($case, $targetCase);

        return redirect()->route('compliance.cases.show', $mergedCase->id)
            ->with('success', 'Cases merged successfully');
    }

    public function linkAlert(Request $request, ComplianceCase $case)
    {
        $request->validate([
            'alert_id' => 'required|exists:alerts,id',
        ]);

        $alert = Alert::findOrFail($request->alert_id);

        $this->caseManagementService->linkAlertToCase($alert, $case);

        return redirect()->back()->with('success', 'Alert linked to case');
    }

    public function escalate(Request $request, ComplianceCase $case)
    {
        $this->complianceCaseManagementService->escalateCase($case);

        return redirect()->back()->with('success', 'Case escalated successfully');
    }
}