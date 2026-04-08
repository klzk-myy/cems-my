<?php

namespace App\Services;

use App\Enums\AlertPriority;
use App\Enums\CaseStatus;
use App\Events\CaseOpened;
use App\Models\Alert;
use App\Models\Compliance\ComplianceCase;
use Illuminate\Support\Facades\DB;

class CaseManagementService
{
    public function __construct(
        protected AlertTriageService $alertTriageService,
    ) {}

    /**
     * Create a case from one or more alerts.
     */
    public function createFromAlerts(array $alertIds, int $openedBy): ComplianceCase
    {
        return DB::transaction(function () use ($alertIds, $openedBy) {
            $alerts = Alert::whereIn('id', $alertIds)->get();

            if ($alerts->isEmpty()) {
                throw new \InvalidArgumentException('No alerts provided');
            }

            $customerId = $alerts->first()->customer_id;
            $maxRiskScore = $alerts->max('risk_score');
            $priority = AlertPriority::fromRiskScore($maxRiskScore);

            $case = ComplianceCase::create([
                'case_number' => ComplianceCase::generateCaseNumber(),
                'customer_id' => $customerId,
                'status' => CaseStatus::Open,
                'priority' => $priority,
                'assigned_to' => null,
                'opened_by' => $openedBy,
                'sla_deadline' => $this->calculateSlaDeadline($priority),
            ]);

            foreach ($alerts as $alert) {
                $alert->update(['case_id' => $case->id]);
            }

            event(new CaseOpened($case));

            return $case->load('alerts');
        });
    }

    /**
     * Link an alert to an existing case.
     */
    public function linkAlertToCase(Alert $alert, ComplianceCase $case): Alert
    {
        if ($alert->case_id && $alert->case_id !== $case->id) {
            throw new \InvalidArgumentException('Alert already linked to another case');
        }

        $alert->update(['case_id' => $case->id]);

        $this->recalculateCasePriority($case);
        $this->recalculateCaseSla($case);

        return $alert->fresh();
    }

    /**
     * Merge two cases together.
     */
    public function mergeCases(ComplianceCase $sourceCase, ComplianceCase $targetCase): ComplianceCase
    {
        return DB::transaction(function () use ($sourceCase, $targetCase) {
            Alert::where('case_id', $sourceCase->id)
                ->update(['case_id' => $targetCase->id]);

            $sourceCase->strDrafts()->update(['case_id' => $targetCase->id]);

            $sourceCase->update(['status' => CaseStatus::Closed]);

            $this->recalculateCasePriority($targetCase);
            $this->recalculateCaseSla($targetCase);

            return $targetCase->fresh()->load('alerts');
        });
    }

    /**
     * Update case status.
     */
    public function updateStatus(ComplianceCase $case, CaseStatus $status): ComplianceCase
    {
        $case->update(['status' => $status]);

        if ($status === CaseStatus::Resolved || $status === CaseStatus::Closed) {
            $case->update(['resolved_at' => now()]);
        }

        return $case->fresh();
    }

    /**
     * Assign case to an officer.
     */
    public function assignToOfficer(ComplianceCase $case, int $userId): ComplianceCase
    {
        $case->update(['assigned_to' => $userId]);

        if ($case->status === CaseStatus::Open) {
            $case->update(['status' => CaseStatus::InProgress]);
        }

        return $case->fresh();
    }

    /**
     * Resolve a case (requires all alerts to be resolved).
     */
    public function resolveCase(ComplianceCase $case, int $resolvedBy, ?string $notes = null): ComplianceCase
    {
        if (!$case->canBeResolved()) {
            throw new \RuntimeException('Cannot resolve case: not all alerts are linked');
        }

        $case->update([
            'status' => CaseStatus::Resolved,
            'resolved_at' => now(),
            'notes' => $notes,
        ]);

        return $case->fresh();
    }

    /**
     * Calculate SLA deadline based on priority.
     */
    protected function calculateSlaDeadline(AlertPriority $priority): \DateTime
    {
        $hours = match ($priority) {
            AlertPriority::Critical => 4,
            AlertPriority::High => 8,
            AlertPriority::Medium => 24,
            AlertPriority::Low => 72,
        };

        return now()->addHours($hours);
    }

    /**
     * Recalculate case priority based on linked alerts.
     */
    protected function recalculateCasePriority(ComplianceCase $case): void
    {
        $priority = $case->derivePriorityFromAlerts();
        $case->update(['priority' => $priority]);
    }

    /**
     * Recalculate case SLA based on priority.
     */
    protected function recalculateCaseSla(ComplianceCase $case): void
    {
        $slaDeadline = $this->calculateSlaDeadline($case->priority);
        $case->update(['sla_deadline' => $slaDeadline]);
    }

    /**
     * Get open cases ordered by priority.
     */
    public function getOpenCases(): \Illuminate\Database\Eloquent\Collection
    {
        return ComplianceCase::with(['customer', 'assignedTo', 'alerts'])
            ->open()
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('sla_deadline')
            ->get();
    }

    /**
     * Get case summary statistics.
     */
    public function getCaseSummary(): array
    {
        return [
            'total_open' => ComplianceCase::open()->count(),
            'critical' => ComplianceCase::open()
                ->where('priority', AlertPriority::Critical)->count(),
            'high' => ComplianceCase::open()
                ->where('priority', AlertPriority::High)->count(),
            'medium' => ComplianceCase::open()
                ->where('priority', AlertPriority::Medium)->count(),
            'low' => ComplianceCase::open()
                ->where('priority', AlertPriority::Low)->count(),
            'overdue' => ComplianceCase::open()
                ->where('sla_deadline', '<', now())->count(),
            'pending_review' => ComplianceCase::where('status', CaseStatus::PendingReview)->count(),
        ];
    }

    /**
     * Find potential duplicate cases for a customer.
     */
    public function findPotentialDuplicates(int $customerId, ?int $excludeCaseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ComplianceCase::where('customer_id', $customerId)
            ->open()
            ->where('created_at', '>=', now()->subDays(7));

        if ($excludeCaseId) {
            $query->where('id', '!=', $excludeCaseId);
        }

        return $query->get();
    }
}