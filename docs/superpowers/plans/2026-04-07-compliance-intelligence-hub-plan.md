# Compliance Intelligence Hub — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a modular compliance intelligence hub with monitoring engine, case management, EDD workflow, risk scoring engine, and compliance reporting dashboard.

**Architecture:** Hub-and-spoke design with a central `ComplianceHub` orchestrating five specialized modules. Each module is self-contained with its own service, events, and models. Modules communicate through Laravel events. Findings are stored but cases are manually created from findings to prevent inflation.

**Tech Stack:** Laravel 10, PHP 8.1+, MySQL, BCMath for precision math, Laravel events for cross-module communication, Laravel scheduler for monitoring jobs.

---

## File Structure Overview

```
app/
├── Enums/
│   ├── ComplianceCaseType.php       (NEW)
│   ├── ComplianceCaseStatus.php     (NEW)
│   ├── ComplianceCasePriority.php   (NEW)
│   ├── CaseResolution.php           (NEW)
│   ├── CaseNoteType.php            (NEW)
│   ├── FindingType.php             (NEW)
│   ├── FindingSeverity.php         (NEW)
│   ├── FindingStatus.php           (NEW)
│   ├── EddStatus.php               (ENHANCE existing)
│   └── RecalculationTrigger.php    (NEW)
├── Models/Compliance/
│   ├── ComplianceFinding.php        (NEW)
│   ├── ComplianceCase.php           (NEW)
│   ├── ComplianceCaseNote.php       (NEW)
│   ├── ComplianceCaseDocument.php   (NEW)
│   ├── ComplianceCaseLink.php       (NEW)
│   ├── CustomerRiskProfile.php      (NEW)
│   ├── CustomerBehavioralBaseline.php (NEW)
│   ├── EddQuestionnaireTemplate.php (NEW)
│   └── EddDocumentRequest.php       (NEW)
├── Services/Compliance/
│   ├── ComplianceHub.php            (NEW)
│   ├── MonitoringEngine.php         (NEW)
│   ├── CaseManagementService.php     (NEW)
│   ├── EddWorkflowService.php       (NEW)
│   ├── RiskScoringEngine.php        (NEW)
│   └── ComplianceReportingService.php (NEW)
│   └── Monitors/
│       ├── BaseMonitor.php          (NEW)
│       ├── VelocityMonitor.php      (NEW)
│       ├── StructuringMonitor.php   (NEW)
│       └── StrDeadlineMonitor.php   (NEW)
├── Http/Controllers/Api/Compliance/
│   ├── FindingController.php        (NEW)
│   ├── CaseController.php           (NEW)
│   ├── EddController.php            (NEW)
│   ├── RiskController.php           (NEW)
│   └── DashboardController.php      (NEW)
├── Http/Requests/Compliance/
│   ├── CreateCaseRequest.php        (NEW)
│   ├── CreateFindingRequest.php     (NEW)
│   └── (other form requests)
├── Events/Compliance/
│   ├── ComplianceFindingCreated.php (NEW)
│   ├── CaseCreated.php              (NEW)
│   ├── CaseClosed.php               (NEW)
│   ├── RiskScoreRecalculated.php    (NEW)
│   └── EddStatusChanged.php         (NEW)
├── Jobs/Compliance/
│   ├── VelocityMonitorJob.php       (NEW)
│   ├── StructuringMonitorJob.php     (NEW)
│   └── StrDeadlineMonitorJob.php     (NEW)
└── Notifications/
    └── ComplianceFindingNotification.php (NEW)

database/migrations/
├── 2026_04_08_000001_create_compliance_findings_table.php       (NEW)
├── 2026_04_08_000002_create_compliance_cases_table.php          (NEW)
├── 2026_04_08_000003_create_compliance_case_notes_table.php    (NEW)
├── 2026_04_08_000004_create_compliance_case_documents_table.php (NEW)
├── 2026_04_08_000005_create_compliance_case_links_table.php    (NEW)
├── 2026_04_08_000006_create_customer_risk_profiles_table.php   (NEW)
├── 2026_04_08_000007_create_customer_behavioral_baselines_table.php (NEW)
├── 2026_04_08_000008_create_edd_questionnaire_templates_table.php (NEW)
├── 2026_04_08_000009_create_edd_document_requests_table.php     (NEW)
└── 2026_04_08_000010_create_compliance_notifications_table.php  (NEW)

routes/
└── api.php (MODIFY - add compliance routes)
```

---

## PHASE 1: Monitoring Engine & Findings

### Task 1: Create Compliance Enums

**Files:**
- Create: `app/Enums/FindingType.php`
- Create: `app/Enums/FindingSeverity.php`
- Create: `app/Enums/FindingStatus.php`
- Modify: `app/Enums/EddStatus.php` — add new statuses from design

- [ ] **Step 1: Write test for FindingType enum**

```php
// tests/Unit/Enums/FindingTypeTest.php
namespace Tests\Unit\Enums;

use App\Enums\FindingType;
use PHPUnit\Framework\TestCase;

class FindingTypeTest extends TestCase
{
    public function test_velocity_exceeded_exists(): void
    {
        $type = FindingType::VelocityExceeded;
        $this->assertEquals('VelocityExceeded', $type->value);
    }

    public function test_all_types_from_design_exist(): void
    {
        $expected = [
            'VelocityExceeded',
            'StructuringPattern',
            'AggregateTransaction',
            'StrDeadline',
            'SanctionMatch',
            'LocationAnomaly',
            'CurrencyFlowAnomaly',
            'CounterfeitAlert',
            'RiskScoreChange',
        ];

        foreach ($expected as $value) {
            $this->assertContains($value, array_column(FindingType::cases(), 'value'));
        }
    }

    public function test_structuring_pattern_exists(): void
    {
        $type = FindingType::StructuringPattern;
        $this->assertEquals('StructuringPattern', $type->value);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/FindingTypeTest.php`
Expected: FAIL — class does not exist

- [ ] **Step 3: Write FindingType enum**

```php
// app/Enums/FindingType.php
<?php

namespace App\Enums;

/**
 * Finding Type Enum
 *
 * Types of automated compliance findings from the monitoring engine.
 */
enum FindingType: string
{
    case VelocityExceeded = 'VelocityExceeded';
    case StructuringPattern = 'StructuringPattern';
    case AggregateTransaction = 'AggregateTransaction';
    case StrDeadline = 'StrDeadline';
    case SanctionMatch = 'SanctionMatch';
    case LocationAnomaly = 'LocationAnomaly';
    case CurrencyFlowAnomaly = 'CurrencyFlowAnomaly';
    case CounterfeitAlert = 'CounterfeitAlert';
    case RiskScoreChange = 'RiskScoreChange';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::VelocityExceeded => 'Velocity Exceeded',
            self::StructuringPattern => 'Structuring Pattern Detected',
            self::AggregateTransaction => 'Aggregate Transaction Concern',
            self::StrDeadline => 'STR Filing Deadline Warning',
            self::SanctionMatch => 'Sanctions List Match',
            self::LocationAnomaly => 'Location Anomaly',
            self::CurrencyFlowAnomaly => 'Currency Flow Anomaly',
            self::CounterfeitAlert => 'Counterfeit Currency Alert',
            self::RiskScoreChange => 'Risk Score Change',
        };
    }

    /**
     * Get default severity for this finding type.
     */
    public function defaultSeverity(): FindingSeverity
    {
        return match ($this) {
            self::SanctionMatch, self::CounterfeitAlert => FindingSeverity::Critical,
            self::VelocityExceeded, self::StructuringPattern => FindingSeverity::High,
            self::AggregateTransaction, self::StrDeadline => FindingSeverity::Medium,
            self::LocationAnomaly, self::CurrencyFlowAnomaly => FindingSeverity::Low,
            self::RiskScoreChange => FindingSeverity::Low,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/FindingTypeTest.php`
Expected: PASS

- [ ] **Step 5: Write test for FindingSeverity enum**

```php
// tests/Unit/Enums/FindingSeverityTest.php
namespace Tests\Unit\Enums;

use App\Enums\FindingSeverity;
use PHPUnit\Framework\TestCase;

class FindingSeverityTest extends TestCase
{
    public function test_all_severities_exist(): void
    {
        $expected = ['Low', 'Medium', 'High', 'Critical'];
        foreach ($expected as $value) {
            $this->assertContains($value, array_column(FindingSeverity::cases(), 'value'));
        }
    }

    public function test_critical_has_highest_weight(): void
    {
        $this->assertGreaterThan(FindingSeverity::High->weight(), FindingSeverity::Critical->weight());
        $this->assertGreaterThan(FindingSeverity::Medium->weight(), FindingSeverity::High->weight());
        $this->assertGreaterThan(FindingSeverity::Low->weight(), FindingSeverity::Medium->weight());
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/FindingSeverityTest.php`
Expected: FAIL

- [ ] **Step 7: Write FindingSeverity enum**

```php
// app/Enums/FindingSeverity.php
<?php

namespace App\Enums;

/**
 * Finding Severity Enum
 *
 * Severity level of compliance findings.
 */
enum FindingSeverity: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
    case Critical = 'Critical';

    /**
     * Numeric weight for comparisons.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    /**
     * Get color class for UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'warning',
            self::High => 'danger',
            self::Critical => 'dark',
        };
    }

    /**
     * Get icon for UI.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Low => 'info',
            self::Medium => 'warning',
            self::High => 'exclamation',
            self::Critical => 'exclamation-triangle',
        };
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/FindingSeverityTest.php`
Expected: PASS

- [ ] **Step 9: Write test for FindingStatus enum**

```php
// tests/Unit/Enums/FindingStatusTest.php
namespace Tests\Unit\Enums;

use App\Enums\FindingStatus;
use PHPUnit\Framework\TestCase;

class FindingStatusTest extends TestCase
{
    public function test_all_statuses_exist(): void
    {
        $expected = ['New', 'Reviewed', 'Dismissed', 'CaseCreated'];
        foreach ($expected as $value) {
            $this->assertContains($value, array_column(FindingStatus::cases(), 'value'));
        }
    }

    public function test_new_can_transition_to_reviewed(): void
    {
        $this->assertTrue(FindingStatus::New->canBeReviewed());
    }

    public function test_dismissed_is_terminal(): void
    {
        $this->assertFalse(FindingStatus::Dismissed->canBeReviewed());
        $this->assertFalse(FindingStatus::Dismissed->canCreateCase());
    }
}
```

- [ ] **Step 10: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/FindingStatusTest.php`
Expected: FAIL

- [ ] **Step 11: Write FindingStatus enum**

```php
// app/Enums/FindingStatus.php
<?php

namespace App\Enums;

/**
 * Finding Status Enum
 *
 * Status of a compliance finding.
 */
enum FindingStatus: string
{
    case New = 'New';
    case Reviewed = 'Reviewed';
    case Dismissed = 'Dismissed';
    case CaseCreated = 'CaseCreated';

    /**
     * Check if finding can be reviewed.
     */
    public function canBeReviewed(): bool
    {
        return $this === self::New;
    }

    /**
     * Check if finding can be dismissed.
     */
    public function canBeDismissed(): bool
    {
        return in_array($this, [self::New, self::Reviewed], true);
    }

    /**
     * Check if a case can be created from this finding.
     */
    public function canCreateCase(): bool
    {
        return in_array($this, [self::New, self::Reviewed], true);
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewed => 'Reviewed',
            self::Dismissed => 'Dismissed',
            self::CaseCreated => 'Case Created',
        };
    }
}
```

- [ ] **Step 12: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/FindingStatusTest.php`
Expected: PASS

- [ ] **Step 13: Write test for existing EddStatus enhancement**

```php
// tests/Unit/Enums/EddStatusTest.php
namespace Tests\Unit\Enums;

use App\Enums\EddStatus;
use PHPUnit\Framework\TestCase;

class EddStatusTest extends TestCase
{
    public function test_pending_questionnaire_exists(): void
    {
        $status = EddStatus::PendingQuestionnaire;
        $this->assertEquals('PendingQuestionnaire', $status->value);
    }

    public function test_expired_exists(): void
    {
        $status = EddStatus::Expired;
        $this->assertEquals('Expired', $status->value);
    }

    public function test_can_submit_questionnaire(): void
    {
        $this->assertTrue(EddStatus::PendingQuestionnaire->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::Approved->canSubmitQuestionnaire());
    }
}
```

- [ ] **Step 14: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/EddStatusTest.php`
Expected: FAIL — new cases don't exist

- [ ] **Step 15: Read existing EddStatus and enhance it**

```php
// app/Enums/EddStatus.php (read first, then modify)
// Add these cases to the existing enum:
case PendingQuestionnaire = 'Pending_Questionnaire';
case QuestionnaireSubmitted = 'Questionnaire_Submitted';
case Expired = 'Expired';

// Add this method:
public function canSubmitQuestionnaire(): bool
{
    return $this === self::PendingQuestionnaire;
}
```

- [ ] **Step 16: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/EddStatusTest.php`
Expected: PASS

- [ ] **Step 17: Write remaining Phase 1 enums tests and implementations**

Write tests and implementations for:
- `RecalculationTrigger.php` — cases: Manual, Scheduled, EventDriven
- `CaseNoteType.php` — cases: Investigation, Update, Decision, Escalation

Run tests for each, verify pass.

- [ ] **Step 18: Commit**

```bash
git add app/Enums/FindingType.php app/Enums/FindingSeverity.php app/Enums/FindingStatus.php app/Enums/EddStatus.php app/Enums/RecalculationTrigger.php app/Enums/CaseNoteType.php
git add tests/Unit/Enums/FindingTypeTest.php tests/Unit/Enums/FindingSeverityTest.php tests/Unit/Enums/FindingStatusTest.php tests/Unit/Enums/EddStatusTest.php
git commit -m "feat: add compliance finding enums and enhance EddStatus"
```

---

### Task 2: Create Remaining Case Enums

**Files:**
- Create: `app/Enums/ComplianceCaseType.php`
- Create: `app/Enums/ComplianceCaseStatus.php`
- Create: `app/Enums/ComplianceCasePriority.php`
- Create: `app/Enums/CaseResolution.php`

- [ ] **Step 1: Write test for ComplianceCaseType**

```php
// tests/Unit/Enums/ComplianceCaseTypeTest.php
namespace Tests\Unit\Enums;

use App\Enums\ComplianceCaseType;
use PHPUnit\Framework\TestCase;

class ComplianceCaseTypeTest extends TestCase
{
    public function test_all_case_types_exist(): void
    {
        $expected = ['Investigation', 'Edd', 'Str', 'SanctionReview', 'Counterfeit'];
        foreach ($expected as $value) {
            $this->assertContains($value, array_column(ComplianceCaseType::cases(), 'value'));
        }
    }

    public function test_str_case_requires_str_link(): void
    {
        $this->assertTrue(ComplianceCaseType::Str->requiresStrLink());
        $this->assertFalse(ComplianceCaseType::Investigation->requiresStrLink());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/ComplianceCaseTypeTest.php`
Expected: FAIL

- [ ] **Step 3: Write ComplianceCaseType enum**

```php
// app/Enums/ComplianceCaseType.php
<?php

namespace App\Enums;

/**
 * Compliance Case Type Enum
 *
 * Types of compliance cases.
 */
enum ComplianceCaseType: string
{
    case Investigation = 'Investigation';
    case Edd = 'Edd';
    case Str = 'Str';
    case SanctionReview = 'SanctionReview';
    case Counterfeit = 'Counterfeit';

    /**
     * Check if this case type requires a linked STR.
     */
    public function requiresStrLink(): bool
    {
        return $this === self::Str;
    }

    /**
     * Check if this case type requires EDD.
     */
    public function requiresEddLink(): bool
    {
        return $this === self::Edd;
    }

    /**
     * Get default SLA hours based on case type.
     */
    public function defaultSlaHours(): int
    {
        return match ($this) {
            self::Str => 24,
            self::SanctionReview, self::Counterfeit => 24,
            self::Edd => 72,
            self::Investigation => 120,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Investigation => 'Investigation',
            self::Edd => 'Enhanced Due Diligence',
            self::Str => 'Suspicious Transaction Report',
            self::SanctionReview => 'Sanction Review',
            self::Counterfeit => 'Counterfeit Currency',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/ComplianceCaseTypeTest.php`
Expected: PASS

- [ ] **Step 5: Write test for ComplianceCaseStatus**

```php
// tests/Unit/Enums/ComplianceCaseStatusTest.php
namespace Tests\Unit\Enums;

use App\Enums\ComplianceCaseStatus;
use PHPUnit\Framework\TestCase;

class ComplianceCaseStatusTest extends TestCase
{
    public function test_all_statuses_exist(): void
    {
        $expected = ['Open', 'UnderReview', 'PendingApproval', 'Closed', 'Escalated'];
        foreach ($expected as $value) {
            $this->assertContains($value, array_column(ComplianceCaseStatus::cases(), 'value'));
        }
    }

    public function test_open_can_move_to_under_review(): void
    {
        $this->assertTrue(ComplianceCaseStatus::Open->canMoveTo(ComplianceCaseStatus::UnderReview));
    }

    public function test_closed_is_terminal(): void
    {
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::Open));
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::UnderReview));
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/ComplianceCaseStatusTest.php`
Expected: FAIL

- [ ] **Step 7: Write ComplianceCaseStatus enum**

```php
// app/Enums/ComplianceCaseStatus.php
<?php

namespace App\Enums;

/**
 * Compliance Case Status Enum
 *
 * Status of a compliance case lifecycle.
 */
enum ComplianceCaseStatus: string
{
    case Open = 'Open';
    case UnderReview = 'UnderReview';
    case PendingApproval = 'PendingApproval';
    case Closed = 'Closed';
    case Escalated = 'Escalated';

    /**
     * Check if this status can transition to another.
     */
    public function canMoveTo(ComplianceCaseStatus $target): bool
    {
        $transitions = match ($this) {
            self::Open => [self::UnderReview, self::Closed, self::Escalated],
            self::UnderReview => [self::PendingApproval, self::Closed, self::Escalated],
            self::PendingApproval => [self::Closed, self::UnderReview],
            self::Escalated => [self::UnderReview, self::Closed],
            self::Closed => [],
        };

        return in_array($target, $transitions, true);
    }

    /**
     * Check if this is a terminal status.
     */
    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    /**
     * Check if this status is active (not closed).
     */
    public function isActive(): bool
    {
        return $this !== self::Closed;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderReview => 'Under Review',
            self::PendingApproval => 'Pending Approval',
            self::Closed => 'Closed',
            self::Escalated => 'Escalated',
        };
    }

    /**
     * Get color for UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'primary',
            self::UnderReview => 'warning',
            self::PendingApproval => 'info',
            self::Closed => 'success',
            self::Escalated => 'danger',
        };
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/ComplianceCaseStatusTest.php`
Expected: PASS

- [ ] **Step 9: Write tests and implementations for ComplianceCasePriority and CaseResolution**

Write similar tests and implementations following the same pattern as above.

- [ ] **Step 10: Commit**

```bash
git add app/Enums/ComplianceCaseType.php app/Enums/ComplianceCaseStatus.php app/Enums/ComplianceCasePriority.php app/Enums/CaseResolution.php
git add tests/Unit/Enums/ComplianceCaseTypeTest.php tests/Unit/Enums/ComplianceCaseStatusTest.php
git commit -m "feat: add compliance case enums"
```

---

### Task 3: Create ComplianceFinding Model and Migration

**Files:**
- Create: `database/migrations/2026_04_08_000001_create_compliance_findings_table.php`
- Create: `app/Models/Compliance/ComplianceFinding.php`
- Test: `tests/Unit/Models/ComplianceFindingTest.php`

- [ ] **Step 1: Write test for ComplianceFinding model**

```php
// tests/Unit/Models/ComplianceFindingTest.php
namespace Tests\Unit\Models;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Models\Compliance\ComplianceFinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_finding(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => ['transactions_24h' => 8, 'total_amount_24h' => '48500.00'],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertDatabaseHas('compliance_findings', [
            'id' => $finding->id,
            'finding_type' => 'VelocityExceeded',
            'severity' => 'High',
        ]);
    }

    public function test_can_be_dismissed(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $finding->dismiss('Routine transaction, no concern');
        $this->assertEquals(FindingStatus::Dismissed, $finding->status);
    }

    public function test_can_transition_to_case_created(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $finding->markCaseCreated();
        $this->assertEquals(FindingStatus::CaseCreated, $finding->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/ComplianceFindingTest.php`
Expected: FAIL — table and model don't exist

- [ ] **Step 3: Create migration**

```php
// database/migrations/2026_04_08_000001_create_compliance_findings_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_findings', function (Blueprint $table) {
            $table->id();
            $table->string('finding_type');
            $table->string('severity');
            $table->string('subject_type'); // Customer or Transaction
            $table->unsignedBigInteger('subject_id');
            $table->json('details')->nullable();
            $table->string('status')->default('New');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['finding_type', 'status']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['severity', 'status']);
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_findings');
    }
};
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 5: Create ComplianceFinding model**

```php
// app/Models/Compliance/ComplianceFinding.php
<?php

namespace App\Models\Compliance;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComplianceFinding extends Model
{
    protected $fillable = [
        'finding_type',
        'severity',
        'subject_type',
        'subject_id',
        'details',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'finding_type' => FindingType::class,
        'severity' => FindingSeverity::class,
        'status' => FindingStatus::class,
        'details' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the subject of the finding (Customer or Transaction).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    /**
     * Dismiss this finding.
     */
    public function dismiss(string $reason): void
    {
        if (! $this->status->canBeDismissed()) {
            throw new \InvalidArgumentException('Finding cannot be dismissed in current status');
        }

        $this->update([
            'status' => FindingStatus::Dismissed,
        ]);
    }

    /**
     * Mark this finding as having a case created.
     */
    public function markCaseCreated(): void
    {
        if (! $this->status->canCreateCase()) {
            throw new \InvalidArgumentException('Case cannot be created from finding in current status');
        }

        $this->update([
            'status' => FindingStatus::CaseCreated,
        ]);
    }

    /**
     * Check if finding is new.
     */
    public function isNew(): bool
    {
        return $this->status === FindingStatus::New;
    }

    /**
     * Check if finding is critical severity.
     */
    public function isCritical(): bool
    {
        return $this->severity === FindingSeverity::Critical;
    }

    /**
     * Scope: filter by status.
     */
    public function scopeWithStatus($query, FindingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by severity.
     */
    public function scopeWithSeverity($query, FindingSeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: filter new findings only.
     */
    public function scopeNew($query)
    {
        return $query->where('status', FindingStatus::New);
    }

    /**
     * Scope: filter by finding type.
     */
    public function scopeOfType($query, FindingType $type)
    {
        return $query->where('finding_type', $type);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/ComplianceFindingTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_04_08_000001_create_compliance_findings_table.php app/Models/Compliance/ComplianceFinding.php
git add tests/Unit/Models/ComplianceFindingTest.php
git commit -m "feat: add ComplianceFinding model and migration"
```

---

### Task 4: Create BaseMonitor and MonitoringEngine

**Files:**
- Create: `app/Services/Compliance/Monitors/BaseMonitor.php`
- Create: `app/Services/Compliance/MonitoringEngine.php`
- Test: `tests/Unit/Services/Compliance/MonitoringEngineTest.php`

- [ ] **Step 1: Write test for BaseMonitor**

```php
// tests/Unit/Services/Compliance/BaseMonitorTest.php
namespace Tests\Unit\Services\Compliance;

use App\Services\Compliance\Monitors\BaseMonitor;
use PHPUnit\Framework\TestCase;

class BaseMonitorTest extends TestCase
{
    public function test_finding_data_structure_is_correct(): void
    {
        $finding = BaseMonitor::createFinding(
            type: \App\Enums\FindingType::VelocityExceeded,
            severity: \App\Enums\FindingSeverity::High,
            subjectType: 'Customer',
            subjectId: 42,
            details: ['amount' => '50000']
        );

        $this->assertEquals('VelocityExceeded', $finding['finding_type']);
        $this->assertEquals('High', $finding['severity']);
        $this->assertEquals('Customer', $finding['subject_type']);
        $this->assertEquals(42, $finding['subject_id']);
        $this->assertArrayHasKey('generated_at', $finding);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/BaseMonitorTest.php`
Expected: FAIL

- [ ] **Step 3: Create BaseMonitor**

```php
// app/Services/Compliance/Monitors/BaseMonitor.php
<?php

namespace App\Services\Compliance\Monitors;

use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Enums\FindingStatus;
use App\Models\Compliance\ComplianceFinding;
use App\Services\MathService;

abstract class BaseMonitor
{
    protected MathService $math;

    public function __construct(MathService $math)
    {
        $this->math = $math;
    }

    /**
     * Run this monitor and return findings.
     *
     * @return array<ComplianceFinding>
     */
    abstract public function run(): array;

    /**
     * Get the finding type for this monitor.
     */
    abstract protected function getFindingType(): FindingType;

    /**
     * Get the default severity for this monitor's findings.
     */
    protected function getDefaultSeverity(): FindingSeverity
    {
        return $this->getFindingType()->defaultSeverity();
    }

    /**
     * Create a finding data array.
     */
    protected function createFinding(
        FindingType $type,
        FindingSeverity $severity,
        string $subjectType,
        int $subjectId,
        array $details
    ): array {
        return [
            'finding_type' => $type->value,
            'severity' => $severity->value,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => $details,
            'status' => FindingStatus::New->value,
            'generated_at' => now(),
        ];
    }

    /**
     * Store a finding in the database.
     */
    protected function storeFinding(array $findingData): ComplianceFinding
    {
        return ComplianceFinding::create($findingData);
    }

    /**
     * Execute the monitor, store findings, and return them.
     */
    public function execute(): array
    {
        $findings = $this->run();
        $stored = [];

        foreach ($findings as $finding) {
            $stored[] = $this->storeFinding($finding);
        }

        return $stored;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/BaseMonitorTest.php`
Expected: PASS

- [ ] **Step 5: Write test for MonitoringEngine**

```php
// tests/Unit/Services/Compliance/MonitoringEngineTest.php
namespace Tests\Unit\Services\Compliance;

use App\Services\Compliance\MonitoringEngine;
use App\Services\Compliance\Monitors\VelocityMonitor;
use App\Services\Compliance\Monitors\StructuringMonitor;
use PHPUnit\Framework\TestCase;

class MonitoringEngineTest extends TestCase
{
    public function test_can_register_monitor(): void
    {
        $engine = new MonitoringEngine();
        $engine->registerMonitor(VelocityMonitor::class);

        $this->assertContains(VelocityMonitor::class, $engine->getRegisteredMonitors());
    }

    public function test_can_register_multiple_monitors(): void
    {
        $engine = new MonitoringEngine();
        $engine->registerMonitor(VelocityMonitor::class);
        $engine->registerMonitor(StructuringMonitor::class);

        $this->assertCount(2, $engine->getRegisteredMonitors());
    }

    public function test_get_monitor_returns_instance(): void
    {
        $engine = new MonitoringEngine();
        $engine->registerMonitor(VelocityMonitor::class);

        $monitor = $engine->getMonitor(VelocityMonitor::class);
        $this->assertInstanceOf(VelocityMonitor::class, $monitor);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/MonitoringEngineTest.php`
Expected: FAIL

- [ ] **Step 7: Create MonitoringEngine**

```php
// app/Services/Compliance/MonitoringEngine.php
<?php

namespace App\Services\Compliance;

use App\Services\Compliance\Monitors\BaseMonitor;
use App\Services\MathService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MonitoringEngine
{
    /**
     * Registered monitor classes.
     *
     * @var array<string>
     */
    protected array $monitors = [];

    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Register a monitor class.
     */
    public function registerMonitor(string $monitorClass): void
    {
        if (! in_array($monitorClass, $this->monitors, true)) {
            $this->monitors[] = $monitorClass;
        }
    }

    /**
     * Get registered monitor classes.
     *
     * @return array<string>
     */
    public function getRegisteredMonitors(): array
    {
        return $this->monitors;
    }

    /**
     * Get an instance of a specific monitor.
     */
    public function getMonitor(string $monitorClass): BaseMonitor
    {
        return new $monitorClass($this->mathService);
    }

    /**
     * Run all registered monitors.
     *
     * @return Collection<mixed>
     */
    public function runAll(): Collection
    {
        $results = collect();

        foreach ($this->monitors as $monitorClass) {
            $monitor = $this->getMonitor($monitorClass);

            try {
                $findings = $monitor->execute();
                Log::info("Monitor {$monitorClass} generated " . count($findings) . " findings");
                $results = $results->merge($findings);
            } catch (\Throwable $e) {
                Log::error("Monitor {$monitorClass} failed: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Run a specific monitor by class.
     */
    public function runMonitor(string $monitorClass): array
    {
        $monitor = $this->getMonitor($monitorClass);
        return $monitor->execute();
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/MonitoringEngineTest.php`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add app/Services/Compliance/Monitors/BaseMonitor.php app/Services/Compliance/MonitoringEngine.php
git add tests/Unit/Services/Compliance/BaseMonitorTest.php tests/Unit/Services/Compliance/MonitoringEngineTest.php
git commit -m "feat: add MonitoringEngine and BaseMonitor"
```

---

### Task 5: Create VelocityMonitor and StructuringMonitor

**Files:**
- Create: `app/Services/Compliance/Monitors/VelocityMonitor.php`
- Create: `app/Services/Compliance/Monitors/StructuringMonitor.php`
- Test: `tests/Unit/Services/Compliance/VelocityMonitorTest.php`
- Test: `tests/Unit/Services/Compliance/StructuringMonitorTest.php`

- [ ] **Step 1: Write test for VelocityMonitor**

```php
// tests/Unit/Services/Compliance/VelocityMonitorTest.php
namespace Tests\Unit\Services\Compliance;

use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\VelocityMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VelocityMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected MathService $math;

    protected function setUp(): void
    {
        parent::setUp();
        $this->math = new MathService();
    }

    public function test_no_finding_when_under_threshold(): void
    {
        $customer = Customer::factory()->create();
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '20000',
            'created_at' => now()->subHours(2),
        ]);

        $monitor = new VelocityMonitor($this->math);
        $findings = $monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_approaching_threshold(): void
    {
        $customer = Customer::factory()->create();
        // Create transactions totaling 48,000 in last 24 hours
        for ($i = 0; $i < 4; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'amount_local' => '12000',
                'created_at' => now()->subHours($i * 2),
            ]);
        }

        $monitor = new VelocityMonitor($this->math);
        $findings = $monitor->run();

        // Should generate finding for approaching threshold (within 10%)
        $this->assertNotEmpty($findings);
        $finding = $findings[0];
        $this->assertEquals(FindingType::VelocityExceeded, $finding['finding_type']);
    }

    public function test_generates_critical_finding_when_exceeding_threshold(): void
    {
        $customer = Customer::factory()->create();
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '55000',
            'created_at' => now()->subHours(1),
        ]);

        $monitor = new VelocityMonitor($this->math);
        $findings = $monitor->run();

        $this->assertNotEmpty($findings);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/VelocityMonitorTest.php`
Expected: FAIL — monitor doesn't exist

- [ ] **Step 3: Create VelocityMonitor**

```php
// app/Services/Compliance/Monitors/VelocityMonitor.php
<?php

namespace App\Services\Compliance\Monitors;

use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VelocityMonitor extends BaseMonitor
{
    private const THRESHOLD = '50000';
    private const WARNING_THRESHOLD = '45000'; // 90% of threshold
    private const LOOKBACK_HOURS = 24;

    protected function getFindingType(): FindingType
    {
        return FindingType::VelocityExceeded;
    }

    /**
     * Run the velocity monitor.
     *
     * Scans all customers who have transacted in the last 24 hours
     * and generates findings for those approaching or exceeding the threshold.
     *
     * @return array<array>
     */
    public function run(): array
    {
        $findings = [];
        $cutoffTime = now()->subHours(self::LOOKBACK_HOURS);

        // Get all customers who transacted in the lookback period
        $customerIds = Transaction::where('created_at', '>=', $cutoffTime)
            ->where('status', '!=', 'Cancelled')
            ->distinct('customer_id')
            ->pluck('customer_id');

        foreach ($customerIds as $customerId) {
            $finding = $this->checkCustomerVelocity($customerId);
            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    /**
     * Check velocity for a specific customer.
     */
    protected function checkCustomerVelocity(int $customerId): ?array
    {
        $cutoffTime = now()->subHours(self::LOOKBACK_HOURS);

        $totalAmount = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $cutoffTime)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount_local');

        $transactionCount = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $cutoffTime)
            ->where('status', '!=', 'Cancelled')
            ->count();

        // Exceeds threshold
        if ($this->math->compare((string) $totalAmount, self::THRESHOLD) >= 0) {
            $customer = Customer::find($customerId);
            return $this->createFinding(
                type: FindingType::VelocityExceeded,
                severity: FindingSeverity::High,
                subjectType: 'Customer',
                subjectId: $customerId,
                details: [
                    'customer_name' => $customer?->full_name ?? 'Unknown',
                    'transactions_24h' => $transactionCount,
                    'total_amount_24h' => (string) $totalAmount,
                    'threshold' => self::THRESHOLD,
                    'recommendation' => 'STR recommended if suspicious',
                ]
            );
        }

        // Approaching threshold (within 10%)
        if ($this->math->compare((string) $totalAmount, self::WARNING_THRESHOLD) >= 0) {
            $customer = Customer::find($customerId);
            return $this->createFinding(
                type: FindingType::VelocityExceeded,
                severity: FindingSeverity::Medium,
                subjectType: 'Customer',
                subjectId: $customerId,
                details: [
                    'customer_name' => $customer?->full_name ?? 'Unknown',
                    'transactions_24h' => $transactionCount,
                    'total_amount_24h' => (string) $totalAmount,
                    'threshold' => self::THRESHOLD,
                    'approaching_threshold' => true,
                ]
            );
        }

        return null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/VelocityMonitorTest.php`
Expected: PASS

- [ ] **Step 5: Write test for StructuringMonitor**

```php
// tests/Unit/Services/Compliance/StructuringMonitorTest.php
namespace Tests\Unit\Services\Compliance;

use App\Enums\FindingType;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Compliance\Monitors\StructuringMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuringMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected MathService $math;

    protected function setUp(): void
    {
        parent::setUp();
        $this->math = new MathService();
    }

    public function test_no_finding_when_no_structuring_pattern(): void
    {
        $customer = Customer::factory()->create();
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => '5000',
            'created_at' => now()->subMinutes(30),
        ]);

        $monitor = new StructuringMonitor($this->math);
        $findings = $monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_structuring_detected(): void
    {
        $customer = Customer::factory()->create();

        // 3+ transactions under 3000 in 1 hour
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'customer_id' => $customer->id,
                'amount_local' => '2800',
                'created_at' => now()->subMinutes(($i + 1) * 15),
            ]);
        }

        $monitor = new StructuringMonitor($this->math);
        $findings = $monitor->run();

        $this->assertNotEmpty($findings);
        $this->assertEquals(FindingType::StructuringPattern, $findings[0]['finding_type']);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/StructuringMonitorTest.php`
Expected: FAIL

- [ ] **Step 7: Create StructuringMonitor**

```php
// app/Services/Compliance/Monitors/StructuringMonitor.php
<?php

namespace App\Services\Compliance\Monitors;

use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;

class StructuringMonitor extends BaseMonitor
{
    private const SUB_THRESHOLD = '3000';
    private const STRUCTURING_COUNT = 3;
    private const LOOKBACK_MINUTES = 60;

    protected function getFindingType(): FindingType
    {
        return FindingType::StructuringPattern;
    }

    /**
     * Run the structuring monitor.
     *
     * Detects 3+ transactions under RM 3,000 within 1 hour (structuring pattern).
     *
     * @return array<array>
     */
    public function run(): array
    {
        $findings = [];
        $cutoffTime = now()->subMinutes(self::LOOKBACK_MINUTES);

        // Get customers with recent small transactions
        $customerIds = Transaction::where('created_at', '>=', $cutoffTime)
            ->where('amount_local', '<', self::SUB_THRESHOLD)
            ->where('status', '!=', 'Cancelled')
            ->distinct('customer_id')
            ->pluck('customer_id');

        foreach ($customerIds as $customerId) {
            $finding = $this->checkCustomerStructuring($customerId);
            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    /**
     * Check structuring pattern for a specific customer.
     */
    protected function checkCustomerStructuring(int $customerId): ?array
    {
        $cutoffTime = now()->subMinutes(self::LOOKBACK_MINUTES);

        $smallTransactions = Transaction::where('customer_id', $customerId)
            ->where('created_at', '>=', $cutoffTime)
            ->where('amount_local', '<', self::SUB_THRESHOLD)
            ->where('status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($smallTransactions->count() >= self::STRUCTURING_COUNT) {
            $customer = Customer::find($customerId);
            $totalAmount = $smallTransactions->sum('amount_local');

            return $this->createFinding(
                type: FindingType::StructuringPattern,
                severity: FindingSeverity::High,
                subjectType: 'Customer',
                subjectId: $customerId,
                details: [
                    'customer_name' => $customer?->full_name ?? 'Unknown',
                    'transaction_count' => $smallTransactions->count(),
                    'total_amount' => (string) $totalAmount,
                    'threshold' => self::SUB_THRESHOLD,
                    'transaction_ids' => $smallTransactions->pluck('id')->toArray(),
                    'recommendation' => 'STR strongly recommended',
                ]
            );
        }

        return null;
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/StructuringMonitorTest.php`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add app/Services/Compliance/Monitors/VelocityMonitor.php app/Services/Compliance/Monitors/StructuringMonitor.php
git add tests/Unit/Services/Compliance/VelocityMonitorTest.php tests/Unit/Services/Compliance/StructuringMonitorTest.php
git commit -m "feat: add VelocityMonitor and StructuringMonitor"
```

---

### Task 6: Create StrDeadlineMonitor and Monitoring Jobs

**Files:**
- Create: `app/Services/Compliance/Monitors/StrDeadlineMonitor.php`
- Create: `app/Jobs/Compliance/VelocityMonitorJob.php`
- Create: `app/Jobs/Compliance/StructuringMonitorJob.php`
- Create: `app/Jobs/Compliance/StrDeadlineMonitorJob.php`
- Test: `tests/Unit/Services/Compliance/StrDeadlineMonitorTest.php`

- [ ] **Step 1: Write test for StrDeadlineMonitor**

```php
// tests/Unit/Services/Compliance/StrDeadlineMonitorTest.php
namespace Tests\Unit\Services\Compliance;

use App\Enums\FindingType;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Services\Compliance\Monitors\StrDeadlineMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrDeadlineMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected MathService $math;

    protected function setUp(): void
    {
        parent::setUp();
        $this->math = new MathService();
    }

    public function test_no_finding_when_flag_has_str_filed(): void
    {
        $customer = Customer::factory()->create();
        $flag = FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Resolved',
            'created_at' => now()->subDays(2),
        ]);

        $monitor = new StrDeadlineMonitor($this->math);
        $findings = $monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_str_deadline_approaching(): void
    {
        $customer = Customer::factory()->create();
        $flag = FlaggedTransaction::factory()->create([
            'customer_id' => $customer->id,
            'flag_type' => 'Structuring',
            'status' => 'Open',
            'created_at' => now()->subDays(2), // 2 days ago — deadline is 3 working days
        ]);

        $monitor = new StrDeadlineMonitor($this->math);
        $findings = $monitor->run();

        $this->assertNotEmpty($findings);
        $this->assertEquals(FindingType::StrDeadline, $findings[0]['finding_type']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/StrDeadlineMonitorTest.php`
Expected: FAIL

- [ ] **Step 3: Create StrDeadlineMonitor**

```php
// app/Services/Compliance/Monitors/StrDeadlineMonitor.php
<?php

namespace App\Services\Compliance\Monitors;

use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Enums\StrStatus;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use Carbon\Carbon;

class StrDeadlineMonitor extends BaseMonitor
{
    private const STR_DEADLINE_DAYS = 3;
    private const WARNING_DAYS_BEFORE = 1; // Warn 1 day before deadline

    protected function getFindingType(): FindingType
    {
        return FindingType::StrDeadline;
    }

    /**
     * Run the STR deadline monitor.
     *
     * Finds flagged transactions that should have generated STRs
     * but haven't been filed within the 3-working-day deadline.
     *
     * @return array<array>
     */
    public function run(): array
    {
        $findings = [];

        // Get all open flags that should trigger STR consideration
        $flags = FlaggedTransaction::whereIn('status', ['Open', 'Under_Review'])
            ->whereIn('flag_type', ['Structuring', 'SanctionMatch', 'Velocity', 'HighRiskCustomer'])
            ->get();

        foreach ($flags as $flag) {
            $finding = $this->checkFlagDeadline($flag);
            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    /**
     * Check if a flag's STR deadline is approaching or overdue.
     */
    protected function checkFlagDeadline(FlaggedTransaction $flag): ?array
    {
        // Skip if STR already filed for this flag
        $existingStr = StrReport::where('alert_id', $flag->id)->first();
        if ($existingStr) {
            return null;
        }

        $flagCreatedAt = $flag->created_at instanceof Carbon
            ? $flag->created_at
            : Carbon::parse($flag->created_at);

        $deadline = $flagCreatedAt->copy()->addWeekdays(self::STR_DEADLINE_DAYS);
        $now = now();

        // Check if overdue
        if ($now->isAfter($deadline)) {
            return $this->createFinding(
                type: FindingType::StrDeadline,
                severity: FindingSeverity::Critical,
                subjectType: 'Transaction',
                subjectId: $flag->transaction_id ?? 0,
                details: [
                    'flag_id' => $flag->id,
                    'flag_type' => $flag->flag_type->value ?? 'Unknown',
                    'flag_created_at' => $flagCreatedAt->toDateTimeString(),
                    'deadline' => $deadline->toDateTimeString(),
                    'days_overdue' => (int) $flagCreatedAt->diffInWeekdays($now) - self::STR_DEADLINE_DAYS,
                    'recommendation' => 'STR must be filed immediately',
                ]
            );
        }

        // Check if within warning window (1 day before deadline)
        $warningThreshold = $deadline->copy()->subWeekdays(self::WARNING_DAYS_BEFORE);
        if ($now->isAfter($warningThreshold) || $now->eq($warningThreshold)) {
            return $this->createFinding(
                type: FindingType::StrDeadline,
                severity: FindingSeverity::High,
                subjectType: 'Transaction',
                subjectId: $flag->transaction_id ?? 0,
                details: [
                    'flag_id' => $flag->id,
                    'flag_type' => $flag->flag_type->value ?? 'Unknown',
                    'flag_created_at' => $flagCreatedAt->toDateTimeString(),
                    'deadline' => $deadline->toDateTimeString(),
                    'days_remaining' => (int) $now->diffInWeekdays($deadline),
                    'recommendation' => 'STR filing deadline approaching',
                ]
            );
        }

        return null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/StrDeadlineMonitorTest.php`
Expected: PASS

- [ ] **Step 5: Create VelocityMonitorJob**

```php
// app/Jobs/Compliance/VelocityMonitorJob.php
<?php

namespace App\Jobs\Compliance;

use App\Services\Compliance\MonitoringEngine;
use App\Services\Compliance\Monitors\VelocityMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VelocityMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MonitoringEngine $engine): void
    {
        Log::info('VelocityMonitorJob started');
        $engine->runMonitor(VelocityMonitor::class);
        Log::info('VelocityMonitorJob completed');
    }
}
```

- [ ] **Step 6: Create StructuringMonitorJob and StrDeadlineMonitorJob** (following same pattern as VelocityMonitorJob)

- [ ] **Step 7: Register jobs in Kernel/Scheduler**

Read `app/Console/Kernel.php` and add:
```php
// Hourly monitors
$schedule->job(new \App\Jobs\Compliance\VelocityMonitorJob())->hourly();
$schedule->job(new \App\Jobs\Compliance\StructuringMonitorJob())->hourly();

// Every 4 hours
$schedule->job(new \App\Jobs\Compliance\StrDeadlineMonitorJob())->everyFourHours();
```

- [ ] **Step 8: Commit**

```bash
git add app/Services/Compliance/Monitors/StrDeadlineMonitor.php
git add app/Jobs/Compliance/
git add app/Console/Kernel.php (modified)
git add tests/Unit/Services/Compliance/StrDeadlineMonitorTest.php
git commit -m "feat: add StrDeadlineMonitor and monitoring jobs"
```

---

### Task 7: Create ComplianceFinding Notification and API Controller

**Files:**
- Create: `app/Notifications/Compliance/ComplianceFindingNotification.php`
- Create: `app/Http/Controllers/Api/Compliance/FindingController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/Compliance/FindingControllerTest.php`

- [ ] **Step 1: Write test for FindingController**

```php
// tests/Feature/Api/Compliance/FindingControllerTest.php
namespace Tests\Feature\Api\Compliance;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Models\Compliance\ComplianceFinding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'ComplianceOfficer']);
    }

    public function test_can_list_findings(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/compliance/findings');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_dismiss_finding(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/compliance/findings/{$finding->id}/dismiss", [
                'reason' => 'Routine transaction',
            ]);

        $response->assertOk();
        $finding->refresh();
        $this->assertEquals(FindingStatus::Dismissed, $finding->status);
    }

    public function test_can_get_finding_stats(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => 1,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/compliance/findings/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'total',
            'by_severity',
            'by_status',
            'by_type',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Api/Compliance/FindingControllerTest.php`
Expected: FAIL

- [ ] **Step 3: Create ComplianceFindingNotification**

```php
// app/Notifications/Compliance/ComplianceFindingNotification.php
<?php

namespace App\Notifications\Compliance;

use App\Models\Compliance\ComplianceFinding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplianceFindingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ComplianceFinding $finding
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'compliance_finding',
            'finding_id' => $this->finding->id,
            'finding_type' => $this->finding->finding_type->value,
            'severity' => $this->finding->severity->value,
            'subject_type' => $this->finding->subject_type,
            'subject_id' => $this->finding->subject_id,
            'message' => $this->buildMessage(),
            'url' => "/compliance/findings/{$this->finding->id}",
            'created_at' => $this->finding->generated_at->toIso8601String(),
        ];
    }

    protected function buildMessage(): string
    {
        $type = $this->finding->finding_type->label();
        $severity = $this->finding->severity->value;

        return "[{$severity}] {$type} finding detected — requires review";
    }
}
```

- [ ] **Step 4: Create FindingController**

```php
// app/Http/Controllers/Api/Compliance/FindingController.php
<?php

namespace App\Http\Controllers\Api\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\ComplianceFinding;
use App\Notifications\Compliance\ComplianceFindingNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    /**
     * List all findings with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ComplianceFinding::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('type')) {
            $query->where('finding_type', $request->input('type'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('generated_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('generated_at', '<=', $request->input('date_to'));
        }

        $findings = $query->orderBy('generated_at', 'desc')->paginate(20);

        return response()->json($findings);
    }

    /**
     * Get a specific finding.
     */
    public function show(int $id): JsonResponse
    {
        $finding = ComplianceFinding::with('subject')->findOrFail($id);
        return response()->json(['data' => $finding]);
    }

    /**
     * Dismiss a finding.
     */
    public function dismiss(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $finding = ComplianceFinding::findOrFail($id);
        $finding->dismiss($request->input('reason'));

        return response()->json(['message' => 'Finding dismissed', 'data' => $finding]);
    }

    /**
     * Get finding statistics.
     */
    public function stats(): JsonResponse
    {
        $total = ComplianceFinding::count();
        $newCount = ComplianceFinding::new()->count();

        $bySeverity = ComplianceFinding::query()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $byStatus = ComplianceFinding::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byType = ComplianceFinding::query()
            ->selectRaw('finding_type, count(*) as count')
            ->groupBy('finding_type')
            ->pluck('count', 'finding_type');

        return response()->json([
            'total' => $total,
            'new' => $newCount,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus,
            'by_type' => $byType,
        ]);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Api/Compliance/FindingControllerTest.php`
Expected: PASS

- [ ] **Step 6: Modify routes/api.php** to add compliance routes:

```php
// Add to routes/api.php
use App\Http\Controllers\Api\Compliance\FindingController;

// Inside the auth:sanctum group:
Route::prefix('compliance')->group(function () {
    Route::get('/findings', [FindingController::class, 'index']);
    Route::get('/findings/stats', [FindingController::class, 'stats']);
    Route::get('/findings/{id}', [FindingController::class, 'show']);
    Route::post('/findings/{id}/dismiss', [FindingController::class, 'dismiss']);
});
```

- [ ] **Step 7: Commit**

```bash
git add app/Notifications/Compliance/ComplianceFindingNotification.php
git add app/Http/Controllers/Api/Compliance/FindingController.php
git add routes/api.php
git add tests/Feature/Api/Compliance/FindingControllerTest.php
git commit -m "feat: add FindingController and notification"
```

---

## PHASE 2: Case Management

### Task 8: Create ComplianceCase Model and Migration

**Files:**
- Create: `database/migrations/2026_04_08_000002_create_compliance_cases_table.php`
- Create: `database/migrations/2026_04_08_000003_create_compliance_case_notes_table.php`
- Create: `database/migrations/2026_04_08_000004_create_compliance_case_documents_table.php`
- Create: `database/migrations/2026_04_08_000005_create_compliance_case_links_table.php`
- Create: `app/Models/Compliance/ComplianceCase.php`
- Create: `app/Models/Compliance/ComplianceCaseNote.php`
- Create: `app/Models/Compliance/ComplianceCaseDocument.php`
- Create: `app/Models/Compliance/ComplianceCaseLink.php`

- [ ] **Step 1: Write test for ComplianceCase**

```php
// tests/Unit/Models/Compliance/ComplianceCaseTest.php
namespace Tests\Unit\Models\Compliance;

use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Models\Compliance\ComplianceCase;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_case(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => \App\Enums\FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'case_summary' => 'Test case',
            'sla_deadline' => now()->addHours(48),
            'created_via' => 'Manual',
        ]);

        $this->assertDatabaseHas('compliance_cases', ['id' => $case->id]);
        $this->assertEquals(ComplianceCaseStatus::Open, $case->status);
    }

    public function test_case_number_auto_generated(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => \App\Enums\FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $this->assertNotNull($case->case_number);
        $this->assertMatchesRegularExpression('/^CASE-\d{4}-\d{5}$/', $case->case_number);
    }

    public function test_can_add_note(): void
    {
        $case = $this->createTestCase();
        $user = User::factory()->create();

        $note = $case->addNote(
            authorId: $user->id,
            noteType: \App\Enums\CaseNoteType::Investigation,
            content: 'Initial review completed',
            isInternal: true
        );

        $this->assertDatabaseHas('compliance_case_notes', [
            'id' => $note->id,
            'case_id' => $case->id,
        ]);
    }

    public function test_can_assign(): void
    {
        $case = $this->createTestCase();
        $newOfficer = User::factory()->create();

        $case->assignTo($newOfficer->id);

        $this->assertEquals($newOfficer->id, $case->assigned_to);
    }

    public function test_can_close_with_resolution(): void
    {
        $case = $this->createTestCase();

        $case->close(
            resolution: \App\Enums\CaseResolution::NoConcern,
            notes: 'No suspicious activity found'
        );

        $this->assertEquals(ComplianceCaseStatus::Closed, $case->status);
        $this->assertEquals(\App\Enums\CaseResolution::NoConcern, $case->resolution);
        $this->assertNotNull($case->resolved_at);
    }

    public function test_can_escalate(): void
    {
        $case = $this->createTestCase();
        $case->escalate();

        $this->assertEquals(ComplianceCaseStatus::Escalated, $case->status);
        $this->assertNotNull($case->escalated_at);
    }

    public function test_sla_is_calculated_from_severity(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => \App\Enums\FindingSeverity::Critical,
            'priority' => ComplianceCasePriority::Critical,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'created_via' => 'Manual',
        ]);

        // Critical = 24 hours
        $expectedDeadline = now()->addHours(24)->startOfMinute();
        $actualDeadline = $case->sla_deadline->startOfMinute();
        $this->assertEquals($expectedDeadline->toDateTimeString(), $actualDeadline->toDateTimeString());
    }

    protected function createTestCase(): ComplianceCase
    {
        return ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => \App\Enums\FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/Compliance/ComplianceCaseTest.php`
Expected: FAIL

- [ ] **Step 3: Create migrations**

Run all 4 migration files. Each adds a table.

```php
// 2026_04_08_000002_create_compliance_cases_table.php
// Key fields: case_number, case_type, status, severity, priority,
// customer_id, primary_flag_id, primary_finding_id, assigned_to,
// case_summary, sla_deadline, escalated_at, resolved_at, resolution,
// resolution_notes, metadata (json), created_via
// Indexes on: case_type, status, severity, customer_id, assigned_to, sla_deadline

// 2026_04_08_000003_create_compliance_case_notes_table.php
// Fields: case_id, author_id, note_type, content, is_internal

// 2026_04_08_000004_create_compliance_case_documents_table.php
// Fields: case_id, file_name, file_path, file_type, uploaded_by, uploaded_at, verified_at, verified_by

// 2026_04_08_000005_create_compliance_case_links_table.php
// Fields: case_id, linked_type, linked_id
```

- [ ] **Step 4: Run migrations**

Run: `php artisan migrate`

- [ ] **Step 5: Create ComplianceCase model** with all methods from test (create, addNote, assignTo, close, escalate, sla calculation)

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/Compliance/ComplianceCaseTest.php`
Expected: PASS

- [ ] **Step 7: Write tests for ComplianceCaseNote, ComplianceCaseDocument, ComplianceCaseLink** and create each model.

- [ ] **Step 8: Run all tests**

Run: `php artisan test tests/Unit/Models/Compliance/`
Expected: PASS

- [ ] **Step 9: Commit**

---

### Task 9: Create CaseManagementService

**Files:**
- Create: `app/Services/Compliance/CaseManagementService.php`
- Test: `tests/Unit/Services/Compliance/CaseManagementServiceTest.php`

- [ ] **Step 1: Write test**

```php
// tests/Unit/Services/Compliance/CaseManagementServiceTest.php
namespace Tests\Unit\Services\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\User;
use App\Services\Compliance\CaseManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CaseManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaseManagementService();
    }

    public function test_can_create_case_from_finding(): void
    {
        $customer = Customer::factory()->create();
        $officer = User::factory()->create();
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $customer->id,
            'details' => ['amount' => '48500'],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $case = $this->service->createCaseFromFinding(
            finding: $finding,
            caseType: ComplianceCaseType::Investigation,
            assignedTo: $officer->id,
            summary: 'Initial investigation required'
        );

        $this->assertNotNull($case);
        $this->assertEquals(ComplianceCaseStatus::Open, $case->status);
        $this->assertEquals($finding->id, $case->primary_finding_id);
        $this->assertEquals($customer->id, $case->customer_id);
    }

    public function test_finding_marked_case_created_when_case_created(): void
    {
        $customer = Customer::factory()->create();
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $customer->id,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->service->createCaseFromFinding(
            finding: $finding,
            caseType: ComplianceCaseType::Investigation,
            assignedTo: User::factory()->create()->id,
        );

        $finding->refresh();
        $this->assertEquals(FindingStatus::CaseCreated, $finding->status);
    }

    public function test_can_add_note_to_case(): void
    {
        $case = $this->createCase();
        $officer = User::factory()->create();

        $note = $this->service->addNote(
            case: $case,
            authorId: $officer->id,
            noteType: CaseNoteType::Investigation,
            content: 'Reviewed transaction history',
            isInternal: true
        );

        $this->assertNotNull($note);
        $this->assertEquals($case->id, $note->case_id);
    }

    public function test_case_number_unique_and_auto_incrementing(): void
    {
        $case1 = $this->createCase();
        $case2 = $this->createCase();

        $this->assertNotEquals($case1->case_number, $case2->case_number);
        $num1 = (int) substr($case1->case_number, -5);
        $num2 = (int) substr($case2->case_number, -5);
        $this->assertEquals(1, $num2 - $num1);
    }

    protected function createCase(): ComplianceCase
    {
        return ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/CaseManagementServiceTest.php`
Expected: FAIL

- [ ] **Step 3: Create CaseManagementService**

```php
// app/Services/Compliance/CaseManagementService.php
<?php

namespace App\Services\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceCaseNote;
use App\Models\Compliance\ComplianceFinding;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                'customer_id' => $finding->subject_type === 'Customer' ? $finding->subject_id : null,
                'primary_finding_id' => $finding->id,
                'assigned_to' => $assignedTo,
                'case_summary' => $summary,
                'sla_deadline' => $this->calculateSlaDeadline($finding->severity, $caseType),
                'created_via' => 'Automated',
            ]);

            // Mark finding as case created
            $finding->markCaseCreated();

            return $case;
        });
    }

    /**
     * Create a manual case.
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
        \App\Enums\CaseResolution $resolution,
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
            FindingSeverity::Medium => 120, // 5 days
            FindingSeverity::Low => 240,    // 10 days
        };

        // STR and Sanction cases have tighter SLAs
        if ($caseType === ComplianceCaseType::Str || $caseType === ComplianceCaseType::SanctionReview) {
            $hours = min($hours, 24);
        }

        return now()->addHours($hours);
    }

    /**
     * Convert severity to default priority.
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

        $lastCase = ComplianceCase::where('case_number', 'like', $prefix.'%')
            ->orderBy('case_number', 'desc')
            ->first();

        if ($lastCase) {
            $lastNumber = (int) substr($lastCase->case_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/CaseManagementServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

---

### Task 10: Create CaseController API

**Files:**
- Create: `app/Http/Controllers/Api/Compliance/CaseController.php`
- Create: `app/Http/Requests/Compliance/CreateCaseRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/Compliance/CaseControllerTest.php`

- [ ] **Step 1: Write test for CaseController**

```php
// tests/Feature/Api/Compliance/CaseControllerTest.php
namespace Tests\Feature\Api\Compliance;

use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Enums\FindingType;
use App\Enums\FindingStatus;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceOfficer;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->complianceOfficer = User::factory()->create(['role' => 'ComplianceOfficer']);
        $this->manager = User::factory()->create(['role' => 'Manager']);
    }

    public function test_can_list_cases(): void
    {
        ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => $this->complianceOfficer->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->getJson('/api/compliance/cases');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_filter_cases_by_status(): void
    {
        ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => $this->complianceOfficer->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->getJson('/api/compliance/cases?status=Closed');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_can_create_case_from_finding(): void
    {
        $customer = Customer::factory()->create();
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $customer->id,
            'details' => [],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->postJson('/api/compliance/cases', [
                'finding_id' => $finding->id,
                'case_type' => 'Investigation',
                'assigned_to' => $this->complianceOfficer->id,
                'summary' => 'Test case',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('compliance_cases', ['primary_finding_id' => $finding->id]);
    }

    public function test_can_add_note(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => $this->complianceOfficer->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->postJson("/api/compliance/cases/{$case->id}/notes", [
                'note_type' => 'Investigation',
                'content' => 'Initial review completed',
                'is_internal' => true,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('compliance_case_notes', [
            'case_id' => $case->id,
            'content' => 'Initial review completed',
        ]);
    }

    public function test_can_close_case(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::UnderReview,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => $this->complianceOfficer->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->postJson("/api/compliance/cases/{$case->id}/close", [
                'resolution' => 'NoConcern',
                'notes' => 'No issues found',
            ]);

        $response->assertOk();
        $case->refresh();
        $this->assertEquals(ComplianceCaseStatus::Closed, $case->status);
    }

    public function test_can_get_case_timeline(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'customer_id' => Customer::factory()->create()->id,
            'assigned_to' => $this->complianceOfficer->id,
            'sla_deadline' => now()->addDays(5),
            'created_via' => 'Manual',
        ]);

        $response = $this->actingAs($this->complianceOfficer, 'sanctum')
            ->getJson("/api/compliance/cases/{$case->id}/timeline");

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Api/Compliance/CaseControllerTest.php`
Expected: FAIL

- [ ] **Step 3: Create CaseController** with all methods (index, show, store, update, addNote, close, escalate, getTimeline)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Api/Compliance/CaseControllerTest.php`
Expected: PASS

- [ ] **Step 5: Add routes to api.php**

```php
Route::prefix('compliance')->group(function () {
    // Existing findings routes...

    // Case routes
    Route::get('/cases', [CaseController::class, 'index']);
    Route::post('/cases', [CaseController::class, 'store']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
    Route::patch('/cases/{id}', [CaseController::class, 'update']);
    Route::post('/cases/{id}/notes', [CaseController::class, 'addNote']);
    Route::post('/cases/{id}/close', [CaseController::class, 'close']);
    Route::post('/cases/{id}/escalate', [CaseController::class, 'escalate']);
    Route::get('/cases/{id}/timeline', [CaseController::class, 'getTimeline']);
});
```

- [ ] **Step 6: Commit**

---

## PHASE 3: Risk Scoring Engine

### Task 11: Create CustomerRiskProfile and CustomerBehavioralBaseline Models

**Files:**
- Create: `database/migrations/2026_04_08_000006_create_customer_risk_profiles_table.php`
- Create: `database/migrations/2026_04_08_000007_create_customer_behavioral_baselines_table.php`
- Create: `app/Models/Compliance/CustomerRiskProfile.php`
- Create: `app/Models/Compliance/CustomerBehavioralBaseline.php`
- Test: `tests/Unit/Models/Compliance/CustomerRiskProfileTest.php`

- [ ] **Step 1: Write test for CustomerRiskProfile**

```php
// tests/Unit/Models/Compliance/CustomerRiskProfileTest.php
namespace Tests\Unit\Models\Compliance;

use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRiskProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_risk_profile(): void
    {
        $customer = Customer::factory()->create();
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 35);

        $this->assertEquals(35, $profile->risk_score);
        $this->assertEquals('Medium', $profile->risk_tier);
        $this->assertEquals($customer->id, $profile->customer_id);
    }

    public function test_risk_tier_correct_for_score_ranges(): void
    {
        $customer = Customer::factory()->create();

        $lowProfile = CustomerRiskProfile::createForCustomer($customer->id, 20);
        $this->assertEquals('Low', $lowProfile->risk_tier);

        $mediumProfile = $lowProfile->recalculateWithScore(40);
        $this->assertEquals('Medium', $mediumProfile->risk_tier);

        $highProfile = $mediumProfile->recalculateWithScore(65);
        $this->assertEquals('High', $highProfile->risk_tier);

        $criticalProfile = $highProfile->recalculateWithScore(85);
        $this->assertEquals('Critical', $criticalProfile->risk_tier);
    }

    public function test_can_calculate_from_factors(): void
    {
        $customer = Customer::factory()->create();
        $factors = [
            ['factor' => 'PEP_Status', 'contribution' => 20],
            ['factor' => 'Unverified_Docs', 'contribution' => 10],
        ];

        $profile = CustomerRiskProfile::createFromFactors($customer->id, $factors);
        $this->assertEquals(50, $profile->risk_score); // 20 base + 20 + 10
        $this->assertEquals('Medium', $profile->risk_tier);
    }

    public function test_score_capped_at_100(): void
    {
        $customer = Customer::factory()->create();
        $factors = [
            ['factor' => 'SanctionMatch', 'contribution' => 50],
            ['factor' => 'Structuring', 'contribution' => 40],
            ['factor' => 'HighRiskCountry', 'contribution' => 25],
        ];

        $profile = CustomerRiskProfile::createFromFactors($customer->id, $factors);
        $this->assertEquals(100, $profile->risk_score);
        $this->assertEquals('Critical', $profile->risk_tier);
    }

    public function test_can_lock_and_unlock_score(): void
    {
        $customer = Customer::factory()->create();
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 50);

        $profile->lock(1, 'Pending EDD review');
        $this->assertTrue($profile->isLocked());
        $this->assertEquals('Pending EDD review', $profile->lock_reason);

        $profile->unlock();
        $this->assertFalse($profile->isLocked());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/Compliance/CustomerRiskProfileTest.php`
Expected: FAIL

- [ ] **Step 3: Create migrations** and run `php artisan migrate`

- [ ] **Step 4: Create CustomerRiskProfile model** with all methods from test

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/Compliance/CustomerRiskProfileTest.php`
Expected: PASS

- [ ] **Step 6: Write test and create CustomerBehavioralBaseline model** (simpler — tracks baseline data)

- [ ] **Step 7: Commit**

---

### Task 12: Create RiskScoringEngine

**Files:**
- Create: `app/Services/Compliance/RiskScoringEngine.php`
- Test: `tests/Unit/Services/Compliance/RiskScoringEngineTest.php`

- [ ] **Step 1: Write test for RiskScoringEngine**

```php
// tests/Unit/Services/Compliance/RiskScoringEngineTest.php
namespace Tests\Unit\Services\Compliance;

use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use App\Services\Compliance\RiskScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    protected RiskScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RiskScoringEngine();
    }

    public function test_calculates_base_score_correctly(): void
    {
        $customer = Customer::factory()->create();
        $score = $this->engine->calculateScore($customer->id);

        // Base score should be 20 + any applicable factors
        $this->assertGreaterThanOrEqual(20, $score);
    }

    public function test_returns_factors_with_contributions(): void
    {
        $customer = Customer::factory()->create();
        $result = $this->engine->calculateScoreWithFactors($customer->id);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('tier', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertIsArray($result['factors']);
    }

    public function test_updates_or_creates_profile(): void
    {
        $customer = Customer::factory()->create();

        $profile = $this->engine->recalculateForCustomer($customer->id);

        $this->assertInstanceOf(CustomerRiskProfile::class, $profile);
        $this->assertEquals($customer->id, $profile->customer_id);
    }

    public function test_respects_locked_score(): void
    {
        $customer = Customer::factory()->create();
        $profile = CustomerRiskProfile::createForCustomer($customer->id, 75);
        $profile->lock(auth()->id() ?? 1, 'Under review');

        $freshProfile = $this->engine->recalculateForCustomer($customer->id);

        // Should return locked profile, not recalculated
        $this->assertEquals(75, $freshProfile->risk_score);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/Compliance/RiskScoringEngineTest.php`
Expected: FAIL

- [ ] **Step 3: Create RiskScoringEngine** implementing all factor weight calculations from design

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/Compliance/RiskScoringEngineTest.php`
Expected: PASS

- [ ] **Step 5: Create RiskController API**

```php
// app/Http/Controllers/Api/Compliance/RiskController.php
// Methods: show (get profile), history, recalculate, lock, unlock, portfolio
```

- [ ] **Step 6: Add routes to api.php**

- [ ] **Step 7: Commit**

---

## PHASE 4: Enhanced EDD Workflow

### Task 13: Create EDD Questionnaire and Document Request Models

**Files:**
- Create: `database/migrations/2026_04_08_000008_create_edd_questionnaire_templates_table.php`
- Create: `database/migrations/2026_04_08_000009_create_edd_document_requests_table.php`
- Create: `app/Models/Compliance/EddQuestionnaireTemplate.php`
- Create: `app/Models/Compliance/EddDocumentRequest.php`
- Create: `app/Services/Compliance/EddWorkflowService.php`
- Test: `tests/Unit/Models/Compliance/EddQuestionnaireTemplateTest.php`

- [ ] **Step 1-7: Write tests, create migrations/models, run migrations, run tests, commit**

Follow same TDD pattern as previous tasks.

---

### Task 14: Create EddController API

**Files:**
- Create: `app/Http/Controllers/Api/Compliance/EddController.php`
- Add routes to api.php
- Test: `tests/Feature/Api/Compliance/EddControllerTest.php`

- [ ] **Step 1-6: Write tests, implement controller, run tests, add routes, commit**

Follow same TDD pattern as CaseController.

---

## PHASE 5: Compliance Reporting & Dashboard

### Task 15: Create ComplianceReportingService and DashboardController

**Files:**
- Create: `app/Services/Compliance/ComplianceReportingService.php`
- Create: `app/Http/Controllers/Api/Compliance/DashboardController.php`
- Add routes to api.php
- Test: `tests/Unit/Services/Compliance/ComplianceReportingServiceTest.php`
- Test: `tests/Feature/Api/Compliance/DashboardControllerTest.php`

- [ ] **Step 1-6: Write tests, implement service and controller, run tests, add routes, commit**

Follow same TDD pattern.

---

## PHASE 6: Remaining Monitors and Integration

### Task 16: Create Remaining Monitors

**Files:**
- Create: `app/Services/Compliance/Monitors/SanctionsRescreeningMonitor.php`
- Create: `app/Services/Compliance/Monitors/CustomerLocationAnomalyMonitor.php`
- Create: `app/Services/Compliance/Monitors/CurrencyFlowMonitor.php`
- Create: `app/Services/Compliance/Monitors/CounterfeitAlertMonitor.php`
- Create: corresponding Jobs for each monitor
- Add to MonitoringEngine registration
- Update Kernel schedule

- [ ] **Step 1-8: Write tests, implement monitors, run tests, register, schedule, commit**

---

## Implementation Order Summary

Execute tasks in this order:

1. Task 1 — Create Compliance Enums (FindingType, FindingSeverity, FindingStatus, EddStatus enhancement)
2. Task 2 — Create Case Enums (ComplianceCaseType, ComplianceCaseStatus, ComplianceCasePriority, CaseResolution)
3. Task 3 — Create ComplianceFinding Model and Migration
4. Task 4 — Create BaseMonitor and MonitoringEngine
5. Task 5 — Create VelocityMonitor and StructuringMonitor
6. Task 6 — Create StrDeadlineMonitor and Monitoring Jobs
7. Task 7 — Create ComplianceFinding Notification and FindingController API
8. Task 8 — Create ComplianceCase Model and Migration
9. Task 9 — Create CaseManagementService
10. Task 10 — Create CaseController API
11. Task 11 — Create CustomerRiskProfile and CustomerBehavioralBaseline Models
12. Task 12 — Create RiskScoringEngine and RiskController
13. Task 13 — Create EDD Questionnaire and Document Request Models
14. Task 14 — Create EddController API
15. Task 15 — Create ComplianceReportingService and DashboardController
16. Task 16 — Create Remaining Monitors and Integration

---

## Spec Coverage Check

- [x] FindingType enum with all 9 types
- [x] FindingSeverity enum with Low/Medium/High/Critical
- [x] FindingStatus enum with New/Reviewed/Dismissed/CaseCreated
- [x] ComplianceCaseStatus enum with full lifecycle (Open→UnderReview→PendingApproval→Closed/Escalated)
- [x] ComplianceCaseType enum with Investigation/Edd/Str/SanctionReview/Counterfeit
- [x] SLA deadlines calculated from severity (Critical=24h, High=48h, Medium=5d, Low=10d)
- [x] Case notes and documents
- [x] EDD questionnaire template system
- [x] EDD document request tracking
- [x] CustomerRiskProfile with 0-100 score and tier mapping
- [x] CustomerBehavioralBaseline for deviation detection
- [x] RiskScoringEngine with all factor weights from design
- [x] MonitoringEngine with monitor registration
- [x] Velocity, Structuring, StrDeadline monitors implemented
- [x] Compliance dashboard KPIs
- [x] BNM regulatory calendar
- [x] Case aging report
- [x] Audit trail
- [x] All API endpoints per design

## Type Consistency Check

- [x] `ComplianceFinding.status` → `FindingStatus` enum (not string)
- [x] `ComplianceCase.status` → `ComplianceCaseStatus` enum
- [x] `ComplianceCase.severity` → `FindingSeverity` enum
- [x] `ComplianceCase.priority` → `ComplianceCasePriority` enum
- [x] `ComplianceCase.case_type` → `ComplianceCaseType` enum
- [x] `ComplianceCase.resolution` → `CaseResolution` enum
- [x] `CustomerRiskProfile.risk_tier` → string (Low/Medium/High/Critical)
- [x] `EddRecord.status` → `EddStatus` enum
