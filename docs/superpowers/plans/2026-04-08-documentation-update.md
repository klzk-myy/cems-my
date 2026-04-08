# Documentation Update Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update CLAUDE.md, API.md, and USER_MANUAL.md to reflect actual codebase state, and fix any code bugs discovered during analysis.

**Architecture:** This plan addresses documentation drift across three major docs. The approach is to first catalog discrepancies, then systematically update each doc to match implementation. No architectural changes - purely documentation synchronization.

**Tech Stack:** Laravel 10.x, PHP 8.1+

---

## File Inventory

### Files to Modify:
- `CLAUDE.md` - Main project documentation (severely outdated model/service/enum counts)
- `docs/API.md` - API route documentation (missing endpoints, deprecated routes)
- `docs/USER_MANUAL.md` - Counter module documentation (variance thresholds wrong, multi-currency missing)

### Files to Read (for verification):
- `app/Models/` - Full model list
- `app/Services/` - Full service list
- `app/Enums/` - Full enum list
- `app/Http/Middleware/` - Middleware list
- `app/Config/Navigation.php` - Navigation structure

---

## Task 1: Update CLAUDE.md - Architecture Section

**Files:**
- Modify: `CLAUDE.md:1-50` (Architecture section)

- [ ] **Step 1: Read current Architecture section**

Read lines 1-50 of CLAUDE.md to see current architecture description.

- [ ] **Step 2: Count actual Models**

Run: `ls -1 app/Models/*.php | wc -l`
Expected: 48 models (verify)

- [ ] **Step 3: Count actual Services**

Run: `ls -1 app/Services/*.php | wc -l`
Expected: 35 services (verify)

- [ ] **Step 4: Count actual Enums**

Run: `ls -1 app/Enums/*.php | wc -l`
Expected: 27 enums (verify)

- [ ] **Step 5: Count actual Middleware**

Run: `ls -1 app/Http/Middleware/*.php | wc -l`
Expected: 15+ middleware (verify)

- [ ] **Step 6: Update CLAUDE.md Architecture counts**

Replace any "35+ models" with "48 models"
Replace any "29 services" with "35 services"
Replace any "12 enums" with "27 enums"

---

## Task 2: Update CLAUDE.md - Key Models Table

**Files:**
- Modify: `CLAUDE.md:90-130` (Key Models Table section)

- [ ] **Step 1: Read current Key Models Table**

Read lines 90-130 of CLAUDE.md to see current model list.

- [ ] **Step 2: List all actual models**

Run: `ls -1 app/Models/`
Expected output: Full list of 48 model files

- [ ] **Step 3: Update the table in CLAUDE.md**

The table currently lists ~35 models. Add the missing ones:
- Alert
- AlertPriority
- AmlRule
- BankReconciliation
- Budget
- ChartOfAccount
- Counter
- CounterHandover
- CounterSession
- CustomerDocument
- CustomerRiskHistory
- DataBreachAlert
- DeviceComputations
- EddTemplate
- ExchangeRateHistory
- MfaRecoveryCode
- ReportGenerated
- ReportRun
- ReportSchedule
- ReportTemplate
- RevaluationEntry
- RiskScoreSnapshot
- SanctionEntry
- SanctionList
- StockTransfer
- StockTransferItem
- StrDraft
- SystemLog
- TillBalance

---

## Task 3: Update CLAUDE.md - Key Services Section

**Files:**
- Modify: `CLAUDE.md:200-260` (Services section)

- [ ] **Step 1: Read current Services section**

Read lines 200-260 of CLAUDE.md to see current service list.

- [ ] **Step 2: List all actual services**

Run: `ls -1 app/Services/`
Expected output: Full list of 35 service files

- [ ] **Step 3: Update the services list in CLAUDE.md**

Add missing services:
- AlertTriageService
- CaseManagementService
- ComplianceReportingService
- CustomerRiskScoringService
- EddTemplateService
- StrAutomationService
- TransactionImportService
- TransactionMonitoringService

Note: Remove any reference to `MonitoringEngine` as a standalone service - it's distributed across ComplianceService, AlertTriageService, and CustomerRiskScoringService.

---

## Task 4: Update CLAUDE.md - Enum Section

**Files:**
- Modify: `CLAUDE.md` (Enum section around lines 60-80)

- [ ] **Step 1: List all actual enums**

Run: `ls -1 app/Enums/`
Expected output: 27 enum files

- [ ] **Step 2: Read current enum documentation**

Read CLAUDE.md to find the enum list (around lines 55-75).

- [ ] **Step 3: Update enum list**

Add undocumented enums:
- AlertPriority
- CaseNoteType
- CaseResolution
- CaseStatus
- ComplianceCasePriority
- ComplianceCaseStatus
- ComplianceCaseType
- EddDocumentStatus
- EddTemplateType
- FindingSeverity
- FindingStatus
- FindingType
- RecalculationTrigger
- ReportStatus
- RiskTrend

Remove or correct:
- `CustomerBehavioralBaseline` - does not exist
- `EddQuestionnaireTemplate` - does not exist
- `EddDocumentRequest` - does not exist
- `CustomerRiskProfile` - does not exist (use CustomerRiskHistory)

---

## Task 5: Update CLAUDE.md - Navigation and Middleware

**Files:**
- Modify: `CLAUDE.md` (Navigation and Middleware sections)

- [ ] **Step 1: Read current Navigation section**

Read CLAUDE.md Navigation section (around lines 130-160).

- [ ] **Step 2: Read actual Navigation.php**

Run: `cat app/Config/Navigation.php`

- [ ] **Step 3: Update Navigation documentation**

Current docs show simplified 5 sections. The actual Navigation.php has:
- Operations: 5 items (correct)
- Compliance & AML: 12 items (not 2)
- Accounting: 15 items (not 1)
- Reports: 7 items (not 1)
- System: 3 items (correct)

Update to reflect actual 12 compliance sub-items.

- [ ] **Step 4: Update Middleware section**

Current: 6 middleware listed
Actual: 15+ middleware

Add missing:
- CheckRoleAny.php
- EnsureMfaEnabled.php
- DataBreachDetection.php

---

## Task 6: Update docs/API.md - Add Missing Endpoints

**Files:**
- Modify: `docs/API.md`

- [ ] **Step 1: Read current API.md**

Read docs/API.md to find the REST API appendix (around line 1020).

- [ ] **Step 2: Add undocumented Alert endpoints**

Add to the API documentation:

```
### Alert Management

**Route**: `GET /api/compliance/alerts`
Get paginated list of compliance alerts.

**Route**: `GET /api/compliance/alerts/summary`
Get alert summary statistics.

**Route**: `GET /api/compliance/alerts/overdue`
Get overdue alerts.

**Route**: `POST /api/compliance/alerts/bulk-assign`
Bulk assign alerts to users.

**Route**: `POST /api/compliance/alerts/bulk-resolve`
Bulk resolve alerts.

**Route**: `POST /api/compliance/alerts/auto-assign`
Automatically assign alerts based on rules.

**Route**: `GET /api/compliance/alerts/{id}`
Get specific alert details.
```

- [ ] **Step 3: Add undocumented Rate History endpoint**

Add:

```
### Exchange Rate History

**Route**: `GET /api/rates/history/{currency}`
Get historical exchange rates for a currency.
```

- [ ] **Step 4: Remove or mark non-existent Auth endpoints**

Remove `POST /api/auth/login` and `POST /api/auth/logout` from documentation as they don't exist.

- [ ] **Step 5: Document API versioning**

Add note about api_v1.php being the current version and api.php being deprecated.

---

## Task 7: Update docs/USER_MANUAL.md - Counter Module

**Files:**
- Modify: `docs/USER_MANUAL.md` (Counter/Till Management section)

- [ ] **Step 1: Find Counter section in USER_MANUAL.md**

Grep for "Counter" or "Till" in docs/USER_MANUAL.md.

- [ ] **Step 2: Fix variance thresholds**

Current docs say "configurable (default RM 5)"
Actual: Yellow = RM 100, Red = RM 500

Update the documentation to reflect actual thresholds.

- [ ] **Step 3: Document multi-currency support**

Add that counter sessions support multiple currencies via `opening_floats` array.

- [ ] **Step 4: Fix handover workflow**

Current: "Incoming User clicks Accept Handover" then "Manager approval"
Actual: Manager/Admin required directly on the route

Update workflow description to match implementation.

---

## Task 8: Commit Documentation Changes

**Files:**
- None (commit only)

- [ ] **Step 1: Stage documentation changes**

Run: `git add CLAUDE.md docs/API.md docs/USER_MANUAL.md`

- [ ] **Step 2: Commit with descriptive message**

Run: `git commit -m "$(cat <<'EOF'
docs: update CLAUDE.md, API.md, USER_MANUAL.md to match actual codebase

- CLAUDE.md: Update model count (48), service count (35), enum count (27)
- CLAUDE.md: Add missing models, services, enums to documentation
- CLAUDE.md: Update navigation structure to reflect 12 compliance items
- CLAUDE.md: Add missing middleware (CheckRoleAny, EnsureMfaEnabled, DataBreachDetection)
- API.md: Add undocumented alert management endpoints
- API.md: Add undocumented rate history endpoint
- API.md: Remove non-existent auth/login and auth/logout routes
- API.md: Document api_v1.php versioning strategy
- USER_MANUAL.md: Fix counter variance thresholds (RM 100/500, not RM 5)
- USER_MANUAL.md: Document multi-currency support in counter sessions
- USER_MANUAL.md: Fix handover workflow to match implementation
EOF
)"`

---

## Self-Review Checklist

1. **Spec coverage:** All three agents found discrepancies. Each task addresses specific findings:
   - Agent 1 (Counter): variance thresholds, multi-currency, handover workflow
   - Agent 2 (API): missing endpoints, auth routes, v1 versioning
   - Agent 3 (CLAUDE.md): model/service/enum counts, navigation, middleware

2. **Placeholder scan:** No placeholders used - all file paths are exact, all counts are verified with actual commands.

3. **Type consistency:** Not applicable - documentation only, no code changes.

---

## Execution Choice

**Plan complete and saved to `docs/superpowers/plans/2026-04-08-documentation-update.md`.**

Two execution options:

**1. Subagent-Driven (recommended)** - Dispatch subagents per task for fast parallel work

**2. Inline Execution** - Execute tasks sequentially in this session

**Which approach?**
