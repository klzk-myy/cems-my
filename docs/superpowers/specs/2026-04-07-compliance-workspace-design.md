# Compliance Workspace Enhancement - Design Specification

**Date:** 2026-04-07
**Project:** CEMS-MY Compliance Module Enhancement
**Approach:** Integrated five-module compliance workspace built as Laravel modular monolith

---

## 1. Overview

Build an integrated Compliance Workspace with five modules operating within the existing Laravel codebase:
1. **Alert Triage & Case Management** — Unified alert queue, priority scoring, case grouping, assignment
2. **STR Automation Studio** — Pre-generate STR drafts, narrative assistance, filing deadline tracking
3. **Customer Risk Scoring Dashboard** — Periodic risk snapshots, trend analysis, re-screening automation
4. **EDD Workflow Templates** — Structured questionnaires for different EDD scenarios
5. **Compliance Reporting Module** — Web UI for BNM report generation, scheduling, deadline calendar, KPIs

All modules share data layers and cross-reference each other via Laravel events.

---

## 2. Architecture

### Module Location

```
app/Compliance/
├── Http/
│   ├── Controllers/
│   │   ├── ComplianceWorkspaceController.php
│   │   ├── AlertTriageController.php
│   │   ├── CaseManagementController.php
│   │   ├── StrStudioController.php
│   │   ├── RiskDashboardController.php
│   │   ├── EddTemplateController.php
│   │   └── ComplianceReportingController.php
│   └── Requests/
│       ├── CreateCaseRequest.php
│       ├── CreateAlertRequest.php
│       ├── CreateStrDraftRequest.php
│       ├── CreateRiskScoreSnapshotRequest.php
│       ├── CreateEddTemplateRequest.php
│       └── RunReportRequest.php
├── Services/
│   ├── AlertTriageService.php
│   ├── CaseManagementService.php
│   ├── StrAutomationService.php
│   ├── CustomerRiskScoringService.php
│   ├── EddTemplateService.php
│   └── ComplianceReportingService.php
├── Models/
│   ├── Alert.php
│   ├── Case.php
│   ├── StrDraft.php
│   ├── RiskScoreSnapshot.php
│   ├── EddTemplate.php
│   ├── ReportSchedule.php
│   ├── ReportRun.php
│   └── ReportTemplate.php
├── Enums/
│   ├── AlertPriority.php
│   ├── CaseStatus.php
│   ├── RiskTrend.php
│   ├── ReportStatus.php
│   └── EddTemplateType.php
├── Events/
│   ├── AlertCreated.php
│   ├── CaseOpened.php
│   ├── StrDraftGenerated.php
│   ├── RiskScoreUpdated.php
│   └── ReportGenerated.php
├── Notifications/
│   ├── AlertAssignedNotification.php
│   ├── CaseDeadlineApproachingNotification.php
│   ├── StrDeadlineApproachingNotification.php
│   └── ReportFailedNotification.php
└── Resources/
    └── Api/
        ├── AlertResource.php
        ├── CaseResource.php
        └── StrDraftResource.php
```

### Existing Integration Points

- Reuses: `FlaggedTransaction`, `StrReport`, `EnhancedDiligenceRecord`, `Customer`, `Transaction`, `User`
- Reuses services: `ComplianceService`, `TransactionMonitoringService`, `SanctionScreeningService`, `StrReportService`, `EddService`, `AmlRuleService`, `ReportingService`
- Reuses auth middleware: `role:compliance`, `role:manager`
- Reuses audit logging: `SystemLog`
- Reuses navigation: `app/Config/Navigation.php`

---

## 3. Module 1: Alert Triage & Case Management

### 3.1 Models

**Alert**
- Links to `FlaggedTransaction` (1:1) or creates standalone
- Fields: `id`, `flagged_transaction_id` (nullable), `customer_id`, `type` (ComplianceFlagType), `priority` (AlertPriority enum), `risk_score` (int 0-100), `reason`, `source` (enum: Manual, System, Rule), `assigned_to` (user_id), `case_id` (nullable), `created_at`, `updated_at`
- Indexes: `customer_id`, `priority`, `assigned_to`, `case_id`

**Case**
- Groups related alerts
- Fields: `id`, `case_number` (auto-generated CASE-YYYYMMDD-XXXX), `customer_id`, `status` (CaseStatus enum), `priority` (derived from alerts), `assigned_to`, `opened_by`, `sla_deadline` (datetime), `resolved_at`, `notes`, `created_at`, `updated_at`
- Relationships: hasMany Alerts, hasMany StrDrafts, hasMany EnhancedDiligenceRecords

### 3.2 Enums

**AlertPriority:** Critical (red), High (orange), Medium (yellow), Low (green)
**CaseStatus:** Open, InProgress, PendingReview, Resolved, Closed
**RiskTrend:** Improving, Stable, Deteriorating

### 3.3 AlertTriageService

```
Priority scoring algorithm:
- Base score from amount (higher = higher priority)
- +20 if customer risk level is High/Critical
- +15 if multiple flags on same customer in 7 days
- +10 if PEP customer
- +10 if high-risk country involved
- +5 if velocity rule triggered
- +5 if structuring pattern detected
- Score capped at 100

SLA deadlines:
- Critical: 4 hours
- High: 8 hours
- Medium: 24 hours
- Low: 72 hours
```

### 3.4 CaseManagementService

- Groups alerts by: same customer (any alert in 7 days), same counter session, same day
- Auto-assigns based on workload balance (fewest open cases)
- Merges duplicate cases automatically
- Case resolution requires all linked alerts to be resolved

### 3.5 Routes

```
GET    /compliance/workspace              # Main workspace dashboard
GET    /compliance/alerts                # Alert queue
POST   /compliance/alerts                # Create manual alert
GET    /compliance/alerts/{id}           # Alert detail
PATCH  /compliance/alerts/{id}/assign    # Assign to officer
PATCH  /compliance/alerts/{id}/resolve   # Resolve alert
GET    /compliance/cases                 # Case list
POST   /compliance/cases                 # Create case from alerts
GET    /compliance/cases/{id}            # Case detail
PATCH  /compliance/cases/{id}           # Update case
POST   /compliance/cases/{id}/merge      # Merge with another case
POST   /compliance/cases/{id}/link-alert # Link additional alert
```

### 3.6 UI Layout

- Split view: left sidebar (filterable alert queue), right panel (selected alert/case detail)
- Bulk actions: assign, resolve, link to case
- Color-coded priority badges
- SLA countdown timer on critical/high priority alerts

---

## 4. Module 2: STR Automation Studio

### 4.1 Model

**StrDraft**
- Pre-STR storage before formal StrReport creation
- Fields: `id`, `case_id` (nullable), `alert_ids` (JSON array), `customer_id`, `transaction_ids` (JSON), `narrative` (text), `suspected_activity`, `confidence_score` (0-100), `filing_deadline` (date), `status` (draft, pending, approved, converted), `converted_to_str_id` (nullable, FK to StrReport), `created_by`, `created_at`, `updated_at`

### 4.2 StrAutomationService

```
generateFromCase(case_id):
1. Collect all alerts linked to case
2. Aggregate transaction data for past 30 days
3. Detect common patterns across alerts
4. Generate suggested narrative based on flag types
5. Calculate confidence score based on evidence strength
6. Set filing deadline = suspicion date + 3 working days

suggestNarrative(alert_types, transaction_patterns):
- Maps flag combinations to BNM reason codes
- Suggests narrative templates per scenario:
  - Velocity only: "Multiple transactions aggregating to RM{X} within 24 hours..."
  - Structuring: "Suspected structuring: {N} transactions under RM 50,000 within {T}..."
  - Combined: Combined narrative with all patterns
```

### 4.3 Routes

```
GET    /compliance/str-studio                    # STR studio dashboard
GET    /compliance/str-studio/create/{case_id}   # Create STR draft from case
POST   /compliance/str-studio/draft             # Save draft
GET    /compliance/str-studio/{id}              # View/edit draft
POST   /compliance/str-studio/{id}/generate-narrative  # AI-assisted narrative
POST   /compliance/str-studio/{id}/submit       # Submit for review
POST   /compliance/str-studio/{id}/convert      # Convert to formal StrReport
GET    /compliance/str-studio/deadlines         # Filing deadline calendar
```

### 4.4 UI Layout

- Three-panel: case summary (left), draft editor (center), suggested narrative (right)
- Filing deadline countdown prominently displayed
- Convert to STR button (only when confidence >= 80% and deadline <= 48h)
- History of all drafts per customer

---

## 5. Module 3: Customer Risk Scoring Dashboard

### 5.1 Model

**RiskScoreSnapshot**
- Periodic risk score records
- Fields: `id`, `customer_id`, `snapshot_date`, `overall_score` (0-100), `velocity_score`, `structuring_score`, `geographic_score`, `amount_score`, `trend` (RiskTrend), `factors` (JSON - key risk factors), `next_screening_date`, `created_at`

### 5.2 CustomerRiskScoringService

```
calculateRiskScore(customer_id):
1. Fetch past 90 days of transactions
2. Calculate sub-scores:
   - Velocity: total amount in 24h window, compare to threshold
   - Structuring: count of sub-50k transactions within 1h
   - Geographic: high-risk country involvement
   - Amount: max single transaction vs customer profile
3. Aggregate weighted scores (capped at 100)
4. Determine trend vs previous 3 snapshots

rescreenCustomer(customer_id):
- Run full sanctions screening
- Update risk score
- Alert if significant change (>20 points)
```

### 5.3 Routes

```
GET    /compliance/risk-dashboard              # Risk overview
GET    /compliance/risk-dashboard/customer/{id}  # Customer risk detail
GET    /compliance/risk-dashboard/trends      # Trend analysis
POST   /compliance/risk-dashboard/rescreen    # Trigger rescreen
GET    /compliance/risk-dashboard/schedule    # Configure auto-rescreening
```

### 5.4 UI Layout

- Grid of customer risk cards (score, trend indicator, last updated)
- Filter by risk level: Critical, High, Medium, Low
- Click-through to customer risk detail with:
  - Line chart of score over time
  - Breakdown of sub-scores
  - Factor list (what drove the score)
  - Action buttons: rescreen, open EDD, create case

---

## 6. Module 4: EDD Workflow Templates

### 6.1 Model

**EddTemplate**
- Structured EDD questionnaires
- Fields: `id`, `name`, `type` (EddTemplateType enum), `description`, `questions` (JSON - question tree with conditions), `version`, `is_active`, `created_by`, `created_at`, `updated_at`

**EddTemplateType enum:**
- PEP (Politically Exposed Person)
- HighRiskCountry (Customer from high-risk country)
- UnusualPattern (Unusual transaction pattern detected)
- SanctionMatch (Sanctions list match)
- LargeTransaction (Transaction >= RM 50,000)
- HighRiskIndustry (Customer in high-risk industry)

### 6.2 Question Structure (JSON)

```json
{
  "sections": [
    {
      "title": "Source of Funds",
      "questions": [
        {
          "id": "sof_1",
          "text": "What is the primary source of funds for this transaction?",
          "type": "select",
          "required": true,
          "options": ["Employment income", "Business income", "Investment proceeds", "Inheritance", "Gift", "Other"]
        },
        {
          "id": "sof_1_other",
          "text": "Please specify",
          "type": "text",
          "required": true,
          "condition": {"question": "sof_1", "value": "Other"}
        },
        {
          "id": "sof_2",
          "text": "Provide supporting documentation",
          "type": "file_upload",
          "required": true,
          "allowed_types": ["pdf", "jpg", "png"]
        }
      ]
    }
  ]
}
```

### 6.3 EddTemplateService

```
createFromTemplate(template_id, customer_id, context):
- Load template
- Build conditional question tree based on context
- Create EnhancedDiligenceRecord linked to customer
- Assign to compliance officer

validateResponses(record_id, responses):
- Check required fields
- Validate file uploads
- Flag incomplete sections
```

### 6.4 Routes

```
GET    /compliance/edd-templates              # Template list
POST   /compliance/edd-templates              # Create template
GET    /compliance/edd-templates/{id}        # View/edit template
PUT    /compliance/edd-templates/{id}         # Update template
DELETE /compliance/edd-templates/{id}         # Deactivate template
POST   /compliance/edd-templates/{id}/duplicate  # Clone template
POST   /compliance/edd/from-template         # Start EDD from template
```

### 6.5 UI Layout

- Template list with type badges and usage count
- Template builder: drag-and-drop question sections, conditional logic builder
- Preview mode showing how questions appear based on conditions
- EDD response form auto-generated from template

---

## 7. Module 5: Compliance Reporting Module

### 7.1 Models

**ReportSchedule**
- Fields: `id`, `report_type`, `cron_expression`, `parameters` (JSON), `is_active`, `last_run_at`, `next_run_at`, `notification_recipients` (JSON), `created_by`, `created_at`, `updated_at`

**ReportRun**
- Fields: `id`, `schedule_id` (nullable), `report_type`, `parameters` (JSON), `status` (ReportStatus enum), `started_at`, `completed_at`, `file_path`, `generated_by` (user_id), `row_count`, `error_message` (nullable), `downloaded_count`, `created_at`

**ReportTemplate** (optional future: configurable report formats)
- Fields: `id`, `name`, `report_type`, `column_config` (JSON), `filters` (JSON), `is_default`, `created_at`

### 7.2 Enums

**ReportStatus:** Scheduled, Running, Completed, Failed

### 7.3 ComplianceReportingService

```
generateReport(type, params, user_id):
1. Validate parameters for report type
2. Create ReportRun record with status=Running
3. Call appropriate ReportingService method
4. Store output file
5. Update ReportRun with completed status
6. Fire ReportGenerated event
7. Return download path

scheduleReport(type, cron, params, recipients):
- Parse cron expression
- Calculate next_run_at
- Create ReportSchedule
- Register with Laravel scheduler

checkUpcomingDeadlines():
- Query all ReportSchedule where next_run_at <= now
- Execute due reports
- Send failure notifications if errors
```

### 7.4 Routes

```
GET    /compliance/reporting                        # Reporting dashboard
GET    /compliance/reporting/generate                # Generate report form
POST   /compliance/reporting/run                    # Execute report
GET    /compliance/reporting/history                # Past runs
GET    /compliance/reporting/history/{id}/download  # Download report
GET    /compliance/reporting/schedule               # Manage schedules
POST   /compliance/reporting/schedule               # Create schedule
PATCH  /compliance/reporting/schedule/{id}          # Update schedule
DELETE /compliance/reporting/schedule/{id}          # Delete schedule
GET    /compliance/reporting/deadlines               # Deadline calendar
GET    /api/compliance/reporting/{type}/preview     # Preview data
```

### 7.5 UI Layout

**Dashboard:**
- KPI cards: reports on time, avg generation time, failure rate
- Upcoming deadlines calendar widget
- Recent runs table

**Generate Report:**
- Step wizard: Select Type → Enter Parameters → Preview → Generate
- Supported types: MSB2 (date), LCTR (month), LMCA (month), QLVR (quarter), Position Limits (on-demand)
- Preview shows first 10 rows before generation

**Schedule Management:**
- Table of all schedules with status toggles
- Edit schedule modal with cron builder (dropdown for common schedules + advanced)
- Last run / next run columns

**History:**
- Filterable table: type, date range, status, user
- Download column with count
- Regenerate button (re-run with same params)

**Deadline Calendar:**
- Full calendar view with color-coded deadlines
- Click deadline to see associated STR/case
- Overdue items highlighted in red

### 7.6 Scheduling Integration

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule):
    $reportSchedules = ReportSchedule::where('is_active', true)->get();
    foreach ($reportSchedules as $schedule) {
        $schedule->scheduleJob($schedule);
    }
endprotected
```

---

## 8. Data Flow Between Modules

```
FlaggedTransaction created
    ↓
AlertCreated event fires
    ↓
AlertTriageService: creates Alert, calculates priority
    ↓
Alert appears in triage queue
    ↓
Investigator links alerts to Case
    ↓
CaseOpened event fires
    ↓
CaseManagementService: sets SLA, assigns officer
    ↓
Investigator reviews case → creates STR draft
    ↓
StrAutomationService: generates StrDraft with narrative
    ↓
StrDraft converted to StrReport → submitted to BNM
    ↓
STR deadline tracked in deadline calendar

Case opened also triggers:
    ↓
CustomerRiskScoringService: recalculates customer risk
    ↓
RiskScoreSnapshot created with new trend
    ↓
If high-risk detected → EDDTemplateService: prompt EDD
```

---

## 9. Testing Strategy

### Unit Tests
- `AlertTriageServiceTest`: priority scoring, SLA calculation
- `CaseManagementServiceTest`: case grouping, merge logic
- `StrAutomationServiceTest`: narrative generation, confidence scoring
- `CustomerRiskScoringServiceTest`: risk score calculation, trend determination
- `EddTemplateServiceTest`: conditional questions, validation
- `ComplianceReportingServiceTest`: report generation, scheduling

### Feature Tests
- Alert workflow: create → assign → resolve
- Case workflow: create from alerts → merge → close
- STR studio: create draft → generate narrative → convert to STR
- Risk dashboard: view scores → trigger rescreen → verify update
- EDD templates: create → fill → submit → approve
- Reporting: generate → download → schedule → verify scheduled run

### Integration Tests
- End-to-end: flag → case → STR → filing
- Scheduled reports: verify cron fires and report generates

---

## 10. Dependencies & Order of Implementation

### Phase 1: Foundation
1. Create enums: AlertPriority, CaseStatus, RiskTrend, ReportStatus, EddTemplateType
2. Create models: Alert, Case, StrDraft, RiskScoreSnapshot, EddTemplate, ReportSchedule, ReportRun
3. Create base services: AlertTriageService, CaseManagementService

### Phase 2: STR & EDD
4. Create StrAutomationService, EddTemplateService
5. Build STR Studio UI
6. Build EDD Template builder UI

### Phase 3: Risk & Reporting
7. Create CustomerRiskScoringService
8. Build Risk Dashboard UI
9. Create ComplianceReportingService
10. Build Reporting UI (generate, schedule, history, deadlines)

### Phase 4: Integration
11. Wire events between modules
12. Add cross-module navigation
13. Dashboard widgets pulling from all modules

---

## 11. Navigation Integration

Add to `app/Config/Navigation.php` under Compliance & AML section:

```php
// Add under existing compliance items:
'compliance_workspace' => [
    'label' => 'Compliance Workspace',
    'route' => 'compliance.workspace',
    'icon' => 'icon-workflow',
    'roles' => ['compliance', 'manager'],
],

// Update existing STR section:
'str' => [
    'label' => 'STR Reports',
    'children' => [
        'str.index' => 'STR List',
        'compliance.str-studio' => 'STR Studio',  // NEW
    ],
],

// Add new section:
'risk_dashboard' => [
    'label' => 'Risk Dashboard',
    'route' => 'compliance.risk-dashboard',
    'icon' => 'icon-risk',
    'roles' => ['compliance', 'manager'],
],

'edd_templates' => [
    'label' => 'EDD Templates',
    'route' => 'compliance.edd-templates',
    'icon' => 'icon-template',
    'roles' => ['compliance', 'manager'],
],

'compliance_reporting' => [
    'label' => 'Reporting',
    'children' => [
        'compliance.reporting' => 'Report Dashboard',
        'compliance.reporting.generate' => 'Generate Report',
        'compliance.reporting.schedule' => 'Schedules',
        'compliance.reporting.deadlines' => 'Deadlines',
    ],
],
```

---

## 12. Security Considerations

- All compliance routes require `role:compliance` or `role:manager`
- Audit logging on all case/STR actions via existing `AuditService`
- Report download URLs are signed and expire after 1 hour
- STR drafts are encrypted at rest (sensitive financial data)
- Role separation: Tellers cannot access compliance workspace
- MFA required for STR submission (existing middleware)