<?php

namespace App\Services\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\CaseResolution;
use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceCaseNote;
use App\Models\Compliance\ComplianceFinding;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing compliance cases and their lifecycle.
 * Handles case creation, assignment, notes, and resolution.
 */
class CaseManagementService
{
    /**
     * Create a compliance case from a finding.
     */
    public function createCaseFromFinding(
        ComplianceFinding $finding,
        ComplianceCaseType $caseType,
        int $assignedTo,
        ?string $summary = null
    ): ComplianceCase {
        return DB::transaction(function () use ($finding, $caseType, $assignedTo, $summary) {
            $case = ComplianceCase::create([
                'case_type' => $caseType,
                'status' => ComplianceCaseStatus::Open,
                'severity' => $finding->severity,
                'priority' => $this->severityToPriority($finding->severity),
                'customer_id' => class_basename($finding->subject_type) === 'Customer' ? $finding->subject_id : null,
                'primary_finding_id' => $finding->id,
                'assigned_to' => $assignedTo,
                'case_summary' => $summary,
                'sla_deadline' => $this->calculateSlaDeadline($finding->severity, $caseType),
                'created_via' => 'Automated',
            ]);

            $finding->markCaseCreated();

            return $case;
        });
    }

    /**
     * Create a manual compliance case.
     */
    public function createManualCase(
        ComplianceCaseType $caseType,
        int $customerId,
        int $assignedTo,
        FindingSeverity $severity,
        ?string $summary = null,
        ?int $primaryFlagId = null
    ): ComplianceCase {
        return ComplianceCase::create([
            'case_type' => $caseType,
            'status' => ComplianceCaseStatus::Open,
            'severity' => $severity,
            'priority' => $this->severityToPriority($severity),
            'customer_id' => $customerId,
            'primary_flag_id' => $primaryFlagId,
            'assigned_to' => $assignedTo,
            'case_summary' => $summary,
            'sla_deadline' => $this->calculateSlaDeadline($severity, $caseType),
            'created_via' => 'Manual',
        ]);
    }

    /**
     * Add a note to a case.
     */
    public function addNote(
        ComplianceCase $case,
        int $authorId,
        CaseNoteType $noteType,
        string $content,
        bool $isInternal = true
    ): ComplianceCaseNote {
        return ComplianceCaseNote::create([
            'case_id' => $case->id,
            'author_id' => $authorId,
            'note_type' => $noteType,
            'content' => $content,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Assign a case to an officer.
     */
    public function assignCase(ComplianceCase $case, int $officerId): ComplianceCase
    {
        $case->assignTo($officerId);

        return $case->fresh();
    }

    /**
     * Close a case.
     */
    public function closeCase(
        ComplianceCase $case,
        CaseResolution $resolution,
        ?string $notes = null
    ): ComplianceCase {
        $case->close($resolution, $notes);

        return $case->fresh();
    }

    /**
     * Escalate a case.
     */
    public function escalateCase(ComplianceCase $case): ComplianceCase
    {
        $case->escalate();

        return $case->fresh();
    }

    /**
     * Calculate SLA deadline based on severity and case type.
     */
    protected function calculateSlaDeadline(FindingSeverity $severity, ComplianceCaseType $caseType): Carbon
    {
        $hours = match ($severity) {
            FindingSeverity::Critical => 24,
            FindingSeverity::High => 48,
            FindingSeverity::Medium => 120,
            FindingSeverity::Low => 240,
        };

        if ($caseType === ComplianceCaseType::Str || $caseType === ComplianceCaseType::SanctionReview) {
            $hours = min($hours, 24);
        }

        return now()->addHours($hours);
    }

    /**
     * Convert severity to priority.
     */
    protected function severityToPriority(FindingSeverity $severity): ComplianceCasePriority
    {
        return match ($severity) {
            FindingSeverity::Critical => ComplianceCasePriority::Critical,
            FindingSeverity::High => ComplianceCasePriority::High,
            FindingSeverity::Medium => ComplianceCasePriority::Medium,
            FindingSeverity::Low => ComplianceCasePriority::Low,
        };
    }

    /**
     * Generate next case number.
     */
    public function generateCaseNumber(): string
    {
        $year = now()->year;
        $prefix = "CASE-{$year}-";

        // Use lock to prevent race conditions
        $lastCase = DB::table('compliance_cases')
            ->where('case_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderBy('case_number', 'desc')
            ->first();

        $newNumber = $lastCase ? ((int) substr($lastCase->case_number, -5)) + 1 : 1;

        return $prefix.str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
