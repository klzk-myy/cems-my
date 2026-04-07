# Compliance Intelligence Hub — Design Specification

**Date:** 2026-04-07
**Status:** Approved
**Version:** 1.0

---

## 1. Overview

A modular compliance intelligence layer built on top of the existing CEMS-MY compliance infrastructure. The system adds five new modules that work together to provide proactive, case-driven compliance management with dynamic risk scoring and automated BNM reporting.

**Scope:** All BNM AML/CFT compliance workflows for the CEMS-MY Currency Exchange Management System.

---

## 2. Architecture

### 2.1 Hub-and-Spoke Design

A central `ComplianceHub` orchestrates five specialized modules. Each module is self-contained with its own service class, events, and (where needed) dedicated models/tables. Modules communicate through well-defined interfaces, not direct dependencies.

```
ComplianceHub
├── MonitoringEngine (scheduled jobs → findings)
├── CaseManagementService (Case, CaseNote, CaseDocument, CaseLink)
├── EddWorkflowService (EnhancedDiligenceRecord, Questionnaire, DocumentRequest)
├── RiskScoringEngine (CustomerRiskProfile, CustomerBehavioralBaseline)
└── ComplianceReportingService (Dashboard, Calendar, AuditTrail, AutoReports)
```

### 2.2 Key Principles

- Each monitor is a standalone class (`app/Services/Compliance/Monitors/`) — add new monitors without touching existing code
- Findings are stored but not auto-linked to cases — officer reviews and creates cases manually
- Cases are not auto-created from findings — prevents case inflation from monitoring noise
- EDD is always linked to a case — no standalone EDD without context
- Risk scores are recalculation-based (not additive) — prevents score drift
- All cross-module communication flows through Laravel events
- No auto-submit to BNM — all reports require human approval

---

## 3. Module 1: Monitoring Engine

### 3.1 Scheduled Jobs

| Job | Frequency | Purpose |
|-----|-----------|---------|
| `SanctionsRescreeningJob` | Weekly | Re-screen all customers against updated sanction entries |
| `VelocityMonitorJob` | Hourly | Scans for customers exceeding transaction velocity thresholds |
| `StructuringPatternJob` | Hourly | Detects structuring (3+ sub-threshold transactions in 1 hour) |
| `AggregateTransactionJob` | Daily | Detects related transactions that should be combined |
| `StrDeadlineMonitorJob` | Every 4 hours | Finds flags that should have generated STRs but haven't |
| `CustomerLocationAnomalyJob` | Daily | Flags transactions in locations far from customer's registered address |
| `CurrencyFlowMonitorJob` | Daily | Detects unusual currency round-tripping patterns |
| `CounterfeitAlertMonitorJob` | Daily | Checks for counterfeit currency reports (BNM integration) |
| `RiskScoreRecalculationJob` | Weekly | Recalculates dynamic risk scores for all active customers |

### 3.2 ComplianceFinding Model

```json
{
  "finding_type": "VelocityExceeded",
  "severity": "High",
  "subject_type": "Customer",
  "subject_id": 42,
  "details": {
    "customer_name": "Ahmad Fauzi",
    "transactions_24h": 8,
    "total_amount_24h": "48500.00",
    "threshold": "50000.00",
    "recommendation": "STR recommended"
  },
  "generated_at": "2026-04-07T10:00:00Z"
}
```

**Finding statuses:** New → Reviewed → Dismissed / CaseCreated

---

## 4. Module 2: Case Management

### 4.1 ComplianceCase Model

| Field | Type | Description |
|-------|------|-------------|
| `case_number` | string | Auto-generated: CASE-2026-00001 |
| `case_type` | enum | Investigation, Edd, Str, SanctionReview, Counterfeit |
| `status` | enum | Open, UnderReview, PendingApproval, Closed, Escalated |
| `severity` | enum | Low, Medium, High, Critical |
| `priority` | enum | Low, Medium, High, Critical |
| `customer_id` | FK | Link to customer |
| `primary_flag_id` | FK, nullable | Link to FlaggedTransaction |
| `primary_finding_id` | FK, nullable | Link to ComplianceFinding |
| `assigned_to` | FK | Compliance officer |
| `case_summary` | text | Officer's initial assessment |
| `sla_deadline` | datetime | Calculated from case type + severity |
| `resolution` | enum | NoConcern, WarningIssued, EddRequired, StrFiled, ClosedNoAction |
| `created_via` | enum | Automated, Manual |

### 4.2 SLA Deadlines

| Severity | Deadline |
|----------|----------|
| Critical | 24 hours |
| High | 48 hours |
| Medium | 5 days |
| Low | 10 days |

### 4.3 Case Lifecycle

```
Open → UnderReview → PendingApproval → Closed
   ↘ Escalated → UnderReview
```

### 4.4 Case Components

| Component | Purpose |
|-----------|---------|
| `CaseNote` | Timestamped notes (Investigation, Update, Decision, Escalation) |
| `CaseDocument` | Uploaded evidence files (encrypted storage) |
| `CaseLink` | Links to related transactions, EDD records, STRs |

---

## 5. Module 3: Enhanced EDD Workflow

### 5.1 EddRecord Model

| Field | Type | Description |
|-------|------|-------------|
| `edd_reference` | string | Auto-generated: EDD-2026-00001 |
| `case_id` | FK | Always linked to a case |
| `customer_id` | FK | |
| `status` | enum | PendingQuestionnaire, QuestionnaireSubmitted, UnderReview, PendingApproval, Approved, Rejected, Expired |
| `risk_level` | enum | Low, Medium, High, Critical |
| `expiry_date` | date | When EDD must be renewed |
| `questionnaire_responses` | JSON | Key:value from template |
| `linked_str_id` | FK, nullable | Linked STR if filed |

### 5.2 EDD Expiry Rules

| Risk Level | Expiry |
|-------------|--------|
| High / Critical | 6 months |
| Medium | 12 months |
| Low | 24 months |

### 5.3 Questionnaire System

Templates stored in `edd_questionnaire_templates` with JSON questions:

```json
{
  "section": "Source of Funds",
  "fields": [
    {
      "key": "employment_status",
      "label": "Employment Status",
      "type": "select",
      "options": ["Employed", "Self-Employed", "Business Owner", "Retired", "Unemployed"],
      "required": true
    }
  ]
}
```

### 5.4 Document Request System

`EddDocumentRequest` tracks required documents through: Pending → Received → Verified / Rejected

### 5.5 EDD Approval Workflow

```
PendingQuestionnaire → QuestionnaireSubmitted → UnderReview → PendingApproval → Approved
                                                                         ↘ Rejected
```

---

## 6. Module 4: Risk Scoring Engine

### 6.1 CustomerRiskProfile Model

| Field | Type | Description |
|-------|------|-------------|
| `risk_score` | integer 0-100 | Calculated score |
| `risk_tier` | enum | Low (0-25), Medium (26-50), High (51-75), Critical (76-100) |
| `risk_factors` | JSON | Contributing factors with weights |
| `locked_until` | date | Prevents auto-recalculation |
| `lock_reason` | string | Why officer locked |

### 6.2 Score Calculation

```
Base Score = 20

Factor Contributions:
├── Geographic Risk: Malaysia=0, Regional=+10, High-Risk=+25
├── PEP Status: Regular=0, PEP=+20, PEP Associate=+15, Sanction Match=+50
├── Transaction Deviation: Consistent=0, 10-25% above=+5, 25-50%=+10, >50%=+20
├── Velocity Flags: None=0, 1-2 in 90 days=+10, 3+=+20
├── Structuring: None=0, 1 detection=+25, 2+=+40
├── EDD History: None=0, Approved in 12mo=+5, Rejected=+15
├── Document Status: All verified=0, Missing/unverified=+10
└── Sanctions: No match=0, Possible=+30, Confirmed=+50
```

### 6.3 Behavioral Baseline

`CustomerBehavioralBaseline` tracks per-customer norms for deviation detection:
- `currency_codes` — commonly traded currencies
- `avg_transaction_size_myr` — moving average
- `avg_transaction_frequency` — transactions per month
- `preferred_counter_ids` — common counter locations
- `registered_location` — registered address zone

### 6.4 Recalculation Triggers

1. **Scheduled:** Weekly via `RiskScoreRecalculationJob`
2. **Event-driven:** New flag, transaction ≥ RM 50,000, EDD status change, sanctions result change, document update

---

## 7. Module 5: Compliance Reporting & Dashboard

### 7.1 Dashboard KPIs (`/compliance`)

- Case summary: Open, UnderReview, Escalated, Closed counts
- STR status: Pending, Due Today, Overdue, Filed counts
- EDD status: Active, Due <30 days, Expired counts
- Open findings (last 7 days) by severity
- Risk distribution across customer portfolio
- Case aging (time in current status)

### 7.2 BNM Regulatory Calendar

Filing deadlines driven by config:
- **LCTR:** Monthly — submit within 7 working days of month end
- **LMCA:** Monthly — submit within 7 working days of month end
- **QLVR:** Quarterly — submit within 10 working days of quarter end
- **STR:** Within 3 working days of suspicion arising (rolling)

### 7.3 Case Aging & SLA Report

Metrics: Average time to first response, average resolution time by severity, cases breaching SLA, oldest open case.

### 7.4 Automated Report Generation

```
Every day 6 AM       → Generate MSB2 for previous day
1st of month 7 AM    → Generate LCTR + LMCA for previous month
1st of quarter 7 AM  → Generate QLVR for previous quarter
Every weekday 8 AM   → StrDeadlineMonitorJob — notify if STR due today
```

Auto-generated reports enter **Pending Approval** state — manager reviews before BNM submission.

### 7.5 Audit Trail Report

Exportable (CSV) report of all compliance actions: Case ID, type, status, opened/closed dates, assigned officer, resolution, linked STR, action counts.

---

## 8. Database Schema

### New Tables

```
compliance_findings
├── id, finding_type, severity, subject_type, subject_id
├── details (JSON), status, generated_at
├── created_at, updated_at

compliance_cases
├── id, case_number, case_type, status, severity, priority
├── customer_id, primary_flag_id, primary_finding_id
├── assigned_to, case_summary, sla_deadline
├── escalated_at, resolved_at, resolution, resolution_notes
├── metadata (JSON), created_via
├── created_at, updated_at

compliance_case_notes
├── id, case_id, author_id, note_type, content, is_internal, created_at

compliance_case_documents
├── id, case_id, file_name, file_path, file_type
├── uploaded_by, uploaded_at, verified_at, verified_by

compliance_case_links
├── id, case_id, linked_type, linked_id, created_at

customer_risk_profiles
├── id, customer_id (unique), risk_score, risk_tier
├── risk_factors (JSON), previous_score, score_changed_at
├── next_scheduled_recalculation, recalculation_trigger
├── locked_until, locked_by, lock_reason
├── created_at, updated_at

customer_behavioral_baselines
├── id, customer_id (unique), currency_codes (JSON)
├── avg_transaction_size_myr, avg_transaction_frequency
├── preferred_counter_ids (JSON), registered_location
├── last_calculated_at, baseline_version

edd_questionnaire_templates
├── id, name, version, is_active, questions (JSON)
├── created_at, updated_at

edd_document_requests
├── id, edd_record_id, document_type, status
├── file_path, rejection_reason, uploaded_at
├── verified_at, verified_by
├── created_at, updated_at
```

---

## 9. API Endpoints

### Case Management
```
GET    /api/compliance/cases              List cases (filterable)
POST   /api/compliance/cases              Create case
GET    /api/compliance/cases/{id}         Get case details
PATCH  /api/compliance/cases/{id}         Update case
POST   /api/compliance/cases/{id}/notes   Add note
POST   /api/compliance/cases/{id}/documents  Upload document
POST   /api/compliance/cases/{id}/links   Link entity
POST   /api/compliance/cases/{id}/close   Close case
POST   /api/compliance/cases/{id}/escalate Escalate case
GET    /api/compliance/cases/{id}/timeline Get event timeline
```

### Findings
```
GET    /api/compliance/findings           List findings
POST   /api/compliance/findings/{id}/dismiss  Dismiss finding
POST   /api/compliance/findings/{id}/create-case  Convert to case
GET    /api/compliance/findings/stats     Statistics
```

### EDD
```
GET    /api/compliance/edd                List EDD records
POST   /api/compliance/edd                Create EDD
GET    /api/compliance/edd/{id}           Get EDD details
PATCH  /api/compliance/edd/{id}           Update EDD
POST   /api/compliance/edd/{id}/questionnaire  Submit questionnaire
POST   /api/compliance/edd/{id}/approve   Approve
POST   /api/compliance/edd/{id}/reject    Reject
POST   /api/compliance/edd/{id}/documents/{docId}/verify Verify document
GET    /api/compliance/edd/templates     List templates
```

### Risk Scoring
```
GET    /api/compliance/risk/{customerId}           Current profile
GET    /api/compliance/risk/{customerId}/history   Score history
POST   /api/compliance/risk/{customerId}/recalculate Recalculate
POST   /api/compliance/risk/{customerId}/lock      Lock score
POST   /api/compliance/risk/{customerId}/unlock    Unlock
GET    /api/compliance/risk/portfolio              Risk distribution
```

### Dashboard
```
GET    /api/compliance/dashboard           KPIs
GET    /api/compliance/calendar            Filing calendar
GET    /api/compliance/case-aging           SLA metrics
GET    /api/compliance/audit-trail         Export audit trail
GET    /api/compliance/reports/auto        Auto-generated reports
```

### Notifications
```
GET    /api/notifications                  List notifications
GET    /api/notifications/unread          Unread count
PATCH  /api/notifications/{id}/read       Mark as read
PATCH  /api/notifications/read-all        Mark all as read
```

---

## 10. Implementation Phases

### Phase 1 — Monitoring Engine & Findings (Weeks 1-2)
- `compliance_findings` table and model
- First 3-4 monitors: Velocity, Structuring, StrDeadline
- `ComplianceMonitorService` orchestration
- In-app notification for findings
- Findings list view and dismissal UI
- Basic dashboard showing finding counts

### Phase 2 — Case Management (Weeks 3-4)
- `ComplianceCase` model, case notes, case documents, case links
- Full case lifecycle
- Case creation from findings
- Case assignment and SLA tracking
- Case timeline and audit trail
- Case list and detail views

### Phase 3 — Risk Scoring Engine (Weeks 5-6)
- `CustomerRiskProfile` and `CustomerBehavioralBaseline` models
- Score calculation with all factor weights
- Behavioral baseline computation
- Scheduled + event-driven recalculation
- Risk portfolio dashboard
- Score lock/unlock functionality

### Phase 4 — Enhanced EDD Workflow (Weeks 7-8)
- EDD questionnaire template system
- Enhanced `EddRecord` with structured fields
- Document request and verification workflow
- EDD approval workflow with manager sign-off
- EDD expiry tracking and renewal notifications

### Phase 5 — Compliance Reporting & Dashboard (Weeks 9-10)
- Compliance dashboard with all KPIs
- BNM regulatory calendar
- Case aging and SLA report
- Automated BNM report generation (scheduled jobs)
- Audit trail export
- Report history view

### Phase 6 — Remaining Monitors & Integration (Weeks 11-12)
- Remaining monitors: SanctionsRescreening, LocationAnomaly, CurrencyFlow, CounterfeitAlert
- Cross-module event wiring
- STR filing from case workflow
- Periodic review workflow for low-risk customers

---

## 11. File Locations

New files will be created in:

```
app/
├── Services/Compliance/
│   ├── ComplianceHub.php
│   ├── Monitors/
│   │   ├── BaseMonitor.php
│   │   ├── VelocityMonitor.php
│   │   ├── StructuringMonitor.php
│   │   ├── StrDeadlineMonitor.php
│   │   ├── SanctionsRescreeningMonitor.php
│   │   ├── CustomerLocationAnomalyMonitor.php
│   │   ├── CurrencyFlowMonitor.php
│   │   └── CounterfeitAlertMonitor.php
│   ├── MonitoringEngine.php
│   ├── CaseManagementService.php
│   ├── EddWorkflowService.php
│   ├── RiskScoringEngine.php
│   └── ComplianceReportingService.php
├── Models/Compliance/
│   ├── ComplianceCase.php
│   ├── ComplianceCaseNote.php
│   ├── ComplianceCaseDocument.php
│   ├── ComplianceCaseLink.php
│   ├── ComplianceFinding.php
│   ├── CustomerRiskProfile.php
│   ├── CustomerBehavioralBaseline.php
│   ├── EddQuestionnaireTemplate.php
│   └── EddDocumentRequest.php
├── Http/Controllers/Api/
│   └── Compliance/
│       ├── CaseController.php
│       ├── FindingController.php
│       ├── EddController.php
│       ├── RiskController.php
│       └── DashboardController.php
├── Http/Requests/Compliance/
│   └── (form request classes)
├── Events/Compliance/
│   ├── ComplianceFindingCreated.php
│   ├── CaseCreated.php
│   ├── RiskScoreRecalculated.php
│   ├── EddStatusChanged.php
│   └── CaseClosed.php
├── Listeners/Compliance/
│   └── (event listeners)
├── Jobs/Compliance/
│   ├── VelocityMonitorJob.php
│   ├── StructuringMonitorJob.php
│   ├── StrDeadlineMonitorJob.php
│   ├── SanctionsRescreeningJob.php
│   ├── RiskScoreRecalculationJob.php
│   └── (other monitor jobs)
├── Enums/
│   ├── ComplianceCaseType.php
│   ├── ComplianceCaseStatus.php
│   ├── CaseResolution.php
│   ├── CasePriority.php
│   ├── CaseNoteType.php
│   ├── EddStatus.php
│   ├── EddDocumentStatus.php
│   ├── FindingType.php
│   ├── FindingSeverity.php
│   ├── FindingStatus.php
│   └── RecalculationTrigger.php
database/migrations/
└── (new migration files)
```
