# Audit Trail Enhancement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 18 comprehensive audit logging methods to AuditService and wire them into all relevant controllers for BNM-compliant comprehensive audit coverage.

**Architecture:** Extend `App\Services\AuditService` with domain-specific logging methods following the existing `logStrAction()` pattern. Each method accepts action, entity ID, and data array, then delegates to `logWithSeverity()`. Controllers call these new methods instead of raw `SystemLog::create()` calls.

**Tech Stack:** Laravel 10, PHP 8.1, Eloquent, Existing AuditService/hash-chain pattern

---

## File Structure

**Primary File:**
- `app/Services/AuditService.php` — Add 18 new domain methods (lines 409+)

**Controller Updates:**
- `app/Http/Controllers/MfaController.php` — Add MFA event logging
- `app/Http/Controllers/StockTransferController.php` — Add stock transfer logging
- `app/Http/Controllers/JournalEntryWorkflowController.php` — Add journal workflow logging
- `app/Http/Controllers/Compliance/AlertTriageController.php` — Add alert event logging
- `app/Http/Controllers/Compliance/CaseManagementController.php` — Add case event logging
- `app/Http/Controllers/Compliance/EddTemplateController.php` — Add EDD template logging
- `app/Http/Controllers/Report/RegulatoryReportController.php` — Add report generation logging
- `app/Http/Controllers/DataBreachAlertController.php` — Add data breach logging
- `app/Http/Middleware/SessionTimeout.php` — Add session event logging
- `app/Services/TransactionMonitoringService.php` — Add AML monitor logging
- `app/Services/CustomerRiskScoringService.php` — Add customer risk logging
- `app/Services/SanctionScreeningService.php` — Add sanctions logging
- `app/Services/RevaluationService.php` — Add position event logging
- `app/Http/Controllers/Api/V1/AuthController.php` — Add API access logging
- `app/Http/Controllers/Api/V1/ReportController.php` — Add API report logging
- `app/Http/Controllers/BranchController.php` — Add cross-branch access logging
- `app/Http/Controllers/TransactionBatchController.php` — Add batch operation logging

**Test File:**
- `tests/Unit/AuditServiceTest.php` — Add tests for new methods

---

## Task 1: Add MFA Event Logging Method to AuditService

**Files:**
- Modify: `app/Services/AuditService.php:409`

- [ ] **Step 1: Add logMfaEvent method to AuditService**

Add after line 267 (after `logCddDecision` method):

```php
/**
 * Log MFA (Multi-Factor Authentication) events.
 *
 * @param  string  $action  MFA action (mfa_setup_started, mfa_setup_completed,
 *                          mfa_verification_success, mfa_verification_failed,
 *                          mfa_disable_requested, mfa_disable_completed,
 *                          mfa_recovery_code_used, mfa_trusted_device_added,
 *                          mfa_trusted_device_removed)
 * @param  int|null  $userId  User ID (null if not authenticated)
 * @param  array  $data  Additional context data
 */
public function logMfaEvent(string $action, ?int $userId = null, array $data = []): SystemLog
{
    $severity = match ($action) {
        'mfa_verification_failed', 'mfa_disable_requested', 'mfa_recovery_code_used',
        'mfa_trusted_device_removed' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'user_id' => $userId ?? auth()->id(),
            'entity_type' => 'MfaEvent',
            'entity_id' => $data['entity_id'] ?? null,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run PHP syntax check**

Run: `php -l app/Services/AuditService.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Services/AuditService.php
git commit -m "feat(audit): add logMfaEvent method to AuditService"
```

---

## Task 2: Add Stock Transfer Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logStockTransferEvent method**

Add after `logMfaEvent` method:

```php
/**
 * Log stock transfer events.
 *
 * @param  string  $action  Transfer action (stock_transfer_created,
 *                          stock_transfer_approved_bm, stock_transfer_approved_hq,
 *                          stock_transfer_dispatched, stock_transfer_partially_received,
 *                          stock_transfer_completed, stock_transfer_cancelled,
 *                          stock_transfer_variance_exceeded)
 * @param  int  $transferId  Stock transfer ID
 * @param  array  $data  Transfer data with old/new values
 */
public function logStockTransferEvent(string $action, int $transferId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'stock_transfer_partially_received', 'stock_transfer_cancelled',
        'stock_transfer_variance_exceeded' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'StockTransfer',
            'entity_id' => $transferId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check**

Run: `php -l app/Services/AuditService.php`

- [ ] **Step 3: Commit**

```bash
git add app/Services/AuditService.php
git commit -m "feat(audit): add logStockTransferEvent method"
```

---

## Task 3: Add Journal Workflow Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logJournalWorkflowEvent method**

Add after `logStockTransferEvent`:

```php
/**
 * Log journal entry workflow events.
 *
 * @param  string  $action  Workflow action (journal_entry_submitted,
 *                          journal_entry_approved, journal_entry_rejected)
 * @param  int  $entryId  Journal entry ID
 * @param  array  $data  Workflow data
 */
public function logJournalWorkflowEvent(string $action, int $entryId, array $data = []): SystemLog
{
    $severity = $action === 'journal_entry_rejected' ? 'WARNING' : 'INFO';

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'JournalEntry',
            'entity_id' => $entryId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

Run: `php -l app/Services/AuditService.php`

```bash
git add app/Services/AuditService.php
git commit -m "feat(audit): add logJournalWorkflowEvent method"
```

---

## Task 4: Add Compliance Alert Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logComplianceAlertEvent method**

```php
/**
 * Log compliance alert events.
 *
 * @param  string  $action  Alert action (compliance_alert_created,
 *                          compliance_alert_triaged, compliance_alert_assigned,
 *                          compliance_alert_dismissed, compliance_alert_escalated,
 *                          compliance_alert_resolved, compliance_alert_bulk_dismissed)
 * @param  int  $alertId  Alert ID
 * @param  array  $data  Alert data
 */
public function logComplianceAlertEvent(string $action, int $alertId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'compliance_alert_created', 'compliance_alert_escalated' => 'WARNING',
        'compliance_alert_bulk_dismissed' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'Alert',
            'entity_id' => $alertId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logComplianceAlertEvent method"
```

---

## Task 5: Add Compliance Case Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logComplianceCaseEvent method**

```php
/**
 * Log compliance case events.
 *
 * @param  string  $action  Case action (compliance_case_created,
 *                          compliance_case_status_changed, compliance_case_assigned,
 *                          compliance_case_note_added, compliance_case_document_linked,
 *                          compliance_case_linked_to_transaction,
 *                          compliance_case_linked_to_customer,
 *                          compliance_case_priority_changed)
 * @param  int  $caseId  Case ID
 * @param  array  $data  Case data
 */
public function logComplianceCaseEvent(string $action, int $caseId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'compliance_case_priority_changed' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'ComplianceCase',
            'entity_id' => $caseId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logComplianceCaseEvent method"
```

---

## Task 6: Add EDD Template Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logEddTemplateEvent method**

```php
/**
 * Log EDD template events.
 *
 * @param  string  $action  Template action (edd_template_created,
 *                          edd_template_updated, edd_template_deleted,
 *                          edd_template_duplicated)
 * @param  int  $templateId  Template ID
 * @param  array  $data  Template data
 */
public function logEddTemplateEvent(string $action, int $templateId, array $data = []): SystemLog
{
    $severity = $action === 'edd_template_deleted' ? 'WARNING' : 'INFO';

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'EddTemplate',
            'entity_id' => $templateId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logEddTemplateEvent method"
```

---

## Task 7: Add Regulatory Report Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logRegulatoryReportEvent method**

```php
/**
 * Log regulatory report events.
 *
 * @param  string  $action  Report action (regulatory_report_msb2_generated,
 *                          regulatory_report_lctr_generated,
 *                          regulatory_report_lmca_generated,
 *                          regulatory_report_qlvr_generated,
 *                          regulatory_report_position_limit_generated,
 *                          regulatory_report_submitted,
 *                          regulatory_report_acknowledged)
 * @param  int  $reportId  Report ID
 * @param  array  $data  Report data
 */
public function logRegulatoryReportEvent(string $action, int $reportId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'regulatory_report_submitted' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'ReportGenerated',
            'entity_id' => $reportId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logRegulatoryReportEvent method"
```

---

## Task 8: Add Data Breach Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logDataBreachEvent method**

```php
/**
 * Log data breach events.
 *
 * @param  string  $action  Breach action (data_breach_detected,
 *                          data_breach_acknowledged, data_breach_investigation_started,
 *                          data_breach_resolved, data_breach_false_positive)
 * @param  int  $breachId  Data breach alert ID
 * @param  array  $data  Breach data
 */
public function logDataBreachEvent(string $action, int $breachId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'data_breach_detected', 'data_breach_acknowledged' => 'CRITICAL',
        'data_breach_investigation_started' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'DataBreachAlert',
            'entity_id' => $breachId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logDataBreachEvent method"
```

---

## Task 9: Add Session Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logSessionEvent method**

```php
/**
 * Log session events.
 *
 * @param  string  $action  Session action (session_timeout,
 *                          session_extended, session_concurrent_blocked)
 * @param  array  $data  Session data
 */
public function logSessionEvent(string $action, array $data = []): SystemLog
{
    $severity = match ($action) {
        'session_concurrent_blocked' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'user_id' => $data['user_id'] ?? auth()->id(),
            'entity_type' => 'Session',
            'entity_id' => $data['session_id'] ?? null,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logSessionEvent method"
```

---

## Task 10: Add Permission Denied Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logPermissionDenied method**

```php
/**
 * Log permission denied events.
 *
 * @param  string  $resource  Resource being accessed
 * @param  string  $action  Action attempted
 * @param  string  $reason  Reason for denial
 * @param  array  $data  Additional context
 */
public function logPermissionDenied(string $resource, string $action, string $reason, array $data = []): SystemLog
{
    return $this->logWithSeverity(
        'permission_denied',
        [
            'user_id' => auth()->id(),
            'entity_type' => $resource,
            'entity_id' => $data['entity_id'] ?? null,
            'new_values' => [
                'action' => $action,
                'reason' => $reason,
                'resource' => $resource,
                'attempted_at' => now()->toIso8601String(),
            ],
        ],
        'WARNING'
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logPermissionDenied method"
```

---

## Task 11: Add Customer Risk Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logCustomerRiskEvent method**

```php
/**
 * Log customer risk events.
 *
 * @param  string  $action  Risk action (customer_risk_score_changed,
 *                          customer_risk_level_upgraded,
 *                          customer_risk_level_downgraded,
 *                          customer_risk_locked, customer_risk_unlocked)
 * @param  int  $customerId  Customer ID
 * @param  array  $data  Risk data
 */
public function logCustomerRiskEvent(string $action, int $customerId, array $data = []): SystemLog
{
    $severity = match ($action) {
        'customer_risk_level_upgraded', 'customer_risk_locked' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'Customer',
            'entity_id' => $customerId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logCustomerRiskEvent method"
```

---

## Task 12: Add AML Monitor Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logAmlMonitorEvent method**

```php
/**
 * Log AML monitoring events.
 *
 * @param  string  $action  Monitor action (aml_velocity_alert_triggered,
 *                          aml_structuring_detected,
 *                          aml_sanctions_rescreen_completed, aml_rule_triggered)
 * @param  int|null  $entityId  Entity ID (transaction, customer, etc.)
 * @param  array  $data  Monitor data
 */
public function logAmlMonitorEvent(string $action, ?int $entityId = null, array $data = []): SystemLog
{
    $severity = match ($action) {
        'aml_velocity_alert_triggered', 'aml_structuring_detected',
        'aml_rule_triggered' => 'ERROR',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => $data['entity_type'] ?? 'AmlMonitor',
            'entity_id' => $entityId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logAmlMonitorEvent method"
```

---

## Task 13: Add Sanction Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logSanctionEvent method**

```php
/**
 * Log sanctions screening events.
 *
 * @param  string  $action  Sanction action (sanction_screening_hit,
 *                          sanction_screening_passed, sanction_manual_override,
 *                          sanction_block_overridden)
 * @param  int|null  $entityId  Entity ID (customer, transaction)
 * @param  array  $data  Sanction data
 */
public function logSanctionEvent(string $action, ?int $entityId = null, array $data = []): SystemLog
{
    $severity = match ($action) {
        'sanction_screening_hit' => 'ERROR',
        'sanction_manual_override' => 'WARNING',
        'sanction_block_overridden' => 'CRITICAL',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => $data['entity_type'] ?? 'Sanction',
            'entity_id' => $entityId,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logSanctionEvent method"
```

---

## Task 14: Add Position Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logPositionEvent method**

```php
/**
 * Log currency position events.
 *
 * @param  string  $action  Position action (position_revaluation_run,
 *                          position_limit_breach, position_manual_adjustment)
 * @param  array  $data  Position data
 */
public function logPositionEvent(string $action, array $data = []): SystemLog
{
    $severity = match ($action) {
        'position_limit_breach', 'position_manual_adjustment' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'entity_type' => 'CurrencyPosition',
            'entity_id' => $data['position_id'] ?? null,
            'old_values' => $data['old'] ?? [],
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logPositionEvent method"
```

---

## Task 15: Add Report Access Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logReportAccessEvent method**

```php
/**
 * Log report access events.
 *
 * @param  string  $action  Access action (report_customer_history_viewed,
 *                          report_ctos_exported, report_audit_log_viewed,
 *                          report_data_export)
 * @param  array  $data  Access data
 */
public function logReportAccessEvent(string $action, array $data = []): SystemLog
{
    $severity = match ($action) {
        'report_ctos_exported', 'report_audit_log_viewed',
        'report_data_export' => 'WARNING',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'user_id' => auth()->id(),
            'entity_type' => $data['entity_type'] ?? 'Report',
            'entity_id' => $data['entity_id'] ?? null,
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logReportAccessEvent method"
```

---

## Task 16: Add API Access Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logApiAccessEvent method**

```php
/**
 * Log API access events.
 *
 * @param  string  $action  API action (api_login_success, api_login_failed,
 *                          api_str_submitted, api_bulk_import)
 * @param  array  $data  API access data
 */
public function logApiAccessEvent(string $action, array $data = []): SystemLog
{
    $severity = match ($action) {
        'api_login_failed' => 'WARNING',
        'api_str_submitted' => 'CRITICAL',
        default => 'INFO',
    };

    return $this->logWithSeverity(
        $action,
        [
            'user_id' => $data['user_id'] ?? auth()->id(),
            'entity_type' => 'ApiAccess',
            'entity_id' => $data['entity_id'] ?? null,
            'new_values' => $data['new'] ?? [],
        ],
        $severity
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logApiAccessEvent method"
```

---

## Task 17: Add Branch Access Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logBranchAccessEvent method**

```php
/**
 * Log cross-branch access events.
 *
 * @param  int  $accessedBranchId  Branch ID being accessed
 * @param  string  $resource  Resource type accessed
 * @param  int  $resourceId  Resource ID accessed
 * @param  array  $data  Access data
 */
public function logBranchAccessEvent(int $accessedBranchId, string $resource, int $resourceId, array $data = []): SystemLog
{
    return $this->logWithSeverity(
        'cross_branch_access',
        [
            'user_id' => auth()->id(),
            'entity_type' => $resource,
            'entity_id' => $resourceId,
            'new_values' => [
                'accessed_branch_id' => $accessedBranchId,
                'accessed_branch_name' => $data['branch_name'] ?? null,
                'user_branch_id' => auth()->user()->branch_id ?? null,
            ],
        ],
        'WARNING'
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logBranchAccessEvent method"
```

---

## Task 18: Add Batch Operation Event Logging Method

**Files:**
- Modify: `app/Services/AuditService.php`

- [ ] **Step 1: Add logBatchOperationEvent method**

```php
/**
 * Log batch operation events.
 *
 * @param  string  $action  Batch action (batch_import_completed,
 *                          batch_approval_completed)
 * @param  array  $data  Batch operation data
 */
public function logBatchOperationEvent(string $action, array $data = []): SystemLog
{
    return $this->logWithSeverity(
        $action,
        [
            'user_id' => auth()->id(),
            'entity_type' => 'BatchOperation',
            'entity_id' => $data['batch_id'] ?? null,
            'new_values' => [
                'items_processed' => $data['items_processed'] ?? 0,
                'items_succeeded' => $data['items_succeeded'] ?? 0,
                'items_failed' => $data['items_failed'] ?? 0,
            ],
        ],
        'INFO'
    );
}
```

- [ ] **Step 2: Run syntax check and commit**

```bash
php -l app/Services/AuditService.php
git add app/Services/AuditService.php
git commit -m "feat(audit): add logBatchOperationEvent method"
```

---

## Task 19: Wire MFA Logging into MfaController

**Files:**
- Modify: `app/Http/Controllers/MfaController.php`

First read the file to understand current state.

- [ ] **Step 1: Add AuditService import and inject it**

Add to constructor:
```php
use App\Services\AuditService;

public function __construct(
    protected MfaService $mfaService,
    protected AuditService $auditService
) {}
```

- [ ] **Step 2: Add logMfaEvent call to setupStore method**

After `$this->mfaService->enableMfa($user);` add:
```php
$this->auditService->logMfaEvent('mfa_setup_completed', $user->id, [
    'new' => ['method' => 'totp'],
]);
```

- [ ] **Step 3: Add logMfaEvent call to verifyStore method**

After `if (! $valid)` block (failed verification):
```php
if (! $valid) {
    return back()->withErrors(['code' => 'Invalid code. Please try again.']);
}

// Mark session as verified
$request->session()->put('mfa_verified', true);
$request->session()->put('mfa_verified_at', now()->timestamp);

$this->auditService->logMfaEvent('mfa_verification_success', $user->id);
```

After the invalid code block (before return):
```php
$this->auditService->logMfaEvent('mfa_verification_failed', $user->id, [
    'new' => ['reason' => 'invalid_code'],
]);
return back()->withErrors(['code' => 'Invalid code. Please try again.']);
```

- [ ] **Step 4: Add logMfaEvent call to disable method**

After `$this->mfaService->disableMfa($user);`:
```php
$this->auditService->logMfaEvent('mfa_disable_completed', $user->id);
```

- [ ] **Step 5: Add logMfaEvent call for trusted device removal**

In `removeDevice` method after successful removal:
```php
$this->auditService->logMfaEvent('mfa_trusted_device_removed', $user->id, [
    'new' => ['device_id' => $deviceId],
]);
```

- [ ] **Step 6: Run syntax check and commit**

```bash
php -l app/Http/Controllers/MfaController.php
git add app/Http/Controllers/MfaController.php
git commit -m "feat(audit): add MFA event logging to MfaController"
```

---

## Task 20: Wire Stock Transfer Logging into StockTransferController

**Files:**
- Modify: `app/Http/Controllers/StockTransferController.php`

- [ ] **Step 1: Add AuditService to constructor**

```php
use App\Services\AuditService;

public function __construct()
{
    $this->stockTransferService = new StockTransferService(auth()->user());
    $this->auditService = app(AuditService::class);
}
```

- [ ] **Step 2: Add property and initialization**

Add property:
```php
protected AuditService $auditService;
```

Update constructor:
```php
public function __construct()
{
    $this->stockTransferService = new StockTransferService(auth()->user());
    $this->auditService = app(AuditService::class);
}
```

- [ ] **Step 3: Log in store method**

After `$transfer = $this->getService()->createRequest($validated);`:
```php
$this->auditService->logStockTransferEvent('stock_transfer_created', $transfer->id, [
    'new' => [
        'transfer_number' => $transfer->transfer_number,
        'source_branch' => $transfer->source_branch_name,
        'destination_branch' => $transfer->destination_branch_name,
        'type' => $transfer->type,
    ],
]);
```

- [ ] **Step 4: Log in approveBm method**

After `$this->getService()->approveByBranchManager($stockTransfer);`:
```php
$this->auditService->logStockTransferEvent('stock_transfer_approved_bm', $stockTransfer->id, [
    'new' => ['approved_by' => auth()->user()->username],
]);
```

- [ ] **Step 5: Log in approveHq method**

```php
$this->auditService->logStockTransferEvent('stock_transfer_approved_hq', $stockTransfer->id, [
    'new' => ['approved_by' => auth()->user()->username],
]);
```

- [ ] **Step 6: Log in dispatch method**

```php
$this->auditService->logStockTransferEvent('stock_transfer_dispatched', $stockTransfer->id);
```

- [ ] **Step 7: Log in receive method**

```php
$this->auditService->logStockTransferEvent('stock_transfer_partially_received', $stockTransfer->id, [
    'new' => ['received_items' => $request->items],
]);
```

- [ ] **Step 8: Log in complete method**

```php
$this->auditService->logStockTransferEvent('stock_transfer_completed', $stockTransfer->id);
```

- [ ] **Step 9: Log in cancel method**

```php
$this->auditService->logStockTransferEvent('stock_transfer_cancelled', $stockTransfer->id, [
    'new' => ['reason' => $request->reason, 'cancelled_by' => auth()->user()->username],
]);
```

- [ ] **Step 10: Run syntax check and commit**

```bash
php -l app/Http/Controllers/StockTransferController.php
git add app/Http/Controllers/StockTransferController.php
git commit -m "feat(audit): add stock transfer event logging to StockTransferController"
```

---

## Task 21: Wire Journal Workflow Logging into JournalEntryWorkflowController

**Files:**
- Modify: `app/Http/Controllers/JournalEntryWorkflowController.php`

- [ ] **Step 1: Add AuditService import and inject**

```php
use App\Services\AuditService;

public function __construct(
    JournalEntryWorkflowService $workflowService,
    protected AuditService $auditService
) {
    $this->workflowService = $workflowService;
}
```

- [ ] **Step 2: Log in submit method**

After `$entry = $this->workflowService->submitForApproval($entry);`:
```php
$this->auditService->logJournalWorkflowEvent('journal_entry_submitted', $entry->id, [
    'new' => ['submitted_by' => auth()->user()->username],
]);
```

- [ ] **Step 3: Log in approve method (approve path)**

After `$entry = $this->workflowService->approve($entry, $notes);`:
```php
$this->auditService->logJournalWorkflowEvent('journal_entry_approved', $entry->id, [
    'new' => ['approved_by' => auth()->user()->username, 'notes' => $notes],
]);
```

- [ ] **Step 4: Log in approve method (reject path)**

After `$entry = $this->workflowService->reject($entry, $notes);`:
```php
$this->auditService->logJournalWorkflowEvent('journal_entry_rejected', $entry->id, [
    'new' => ['rejected_by' => auth()->user()->username, 'notes' => $notes],
]);
```

- [ ] **Step 5: Run syntax check and commit**

```bash
php -l app/Http/Controllers/JournalEntryWorkflowController.php
git add app/Http/Controllers/JournalEntryWorkflowController.php
git commit -m "feat(audit): add journal workflow event logging"
```

---

## Task 22: Add AML Monitor Logging to TransactionMonitoringService

**Files:**
- Modify: `app/Services/TransactionMonitoringService.php`

- [ ] **Step 1: Add AuditService to constructor**

First read the file to understand its structure.

```php
use App\Services\AuditService;

public function __construct(
    // ... existing dependencies
    protected AuditService $auditService
) {
    // ... existing constructor
}
```

- [ ] **Step 2: Add velocity alert logging**

In the velocity monitoring code, after creating an alert:
```php
$this->auditService->logAmlMonitorEvent('aml_velocity_alert_triggered', $transactionId, [
    'entity_type' => 'Transaction',
    'new' => [
        'customer_id' => $customerId,
        'velocity_amount' => $totalAmount,
        'transaction_count' => $count,
    ],
]);
```

- [ ] **Step 3: Add structuring detection logging**

When structuring pattern is detected:
```php
$this->auditService->logAmlMonitorEvent('aml_structuring_detected', $transactionId, [
    'entity_type' => 'Transaction',
    'new' => [
        'customer_id' => $customerId,
        'pattern' => 'aggregate_transactions',
    ],
]);
```

- [ ] **Step 4: Add sanctions rescreen logging**

After sanctions rescreen completes:
```php
$this->auditService->logAmlMonitorEvent('aml_sanctions_rescreen_completed', null, [
    'new' => [
        'customers_screened' => $count,
        'hits_found' => $hits,
    ],
]);
```

- [ ] **Step 5: Run syntax check and commit**

```bash
php -l app/Services/TransactionMonitoringService.php
git add app/Services/TransactionMonitoringService.php
git commit -m "feat(audit): add AML monitor event logging"
```

---

## Task 23: Add Customer Risk Logging to CustomerRiskScoringService

**Files:**
- Modify: `app/Services/CustomerRiskScoringService.php`

- [ ] **Step 1: Add AuditService injection**

Read the file first.

```php
use App\Services\AuditService;

public function __construct(
    // ... existing dependencies
    protected AuditService $auditService
) {}
```

- [ ] **Step 2: Add risk change logging**

After a risk score change is saved:
```php
if ($oldScore !== $newScore) {
    $action = $newScore > $oldScore ? 'customer_risk_level_upgraded' : 'customer_risk_level_downgraded';
    $this->auditService->logCustomerRiskEvent($action, $customerId, [
        'old' => ['risk_score' => $oldScore],
        'new' => ['risk_score' => $newScore],
    ]);
}
```

- [ ] **Step 3: Add risk lock/unlock logging**

When a risk lock is applied or removed:
```php
$action = $isLocked ? 'customer_risk_locked' : 'customer_risk_unlocked';
$this->auditService->logCustomerRiskEvent($action, $customerId);
```

- [ ] **Step 4: Run syntax check and commit**

```bash
php -l app/Services/CustomerRiskScoringService.php
git add app/Services/CustomerRiskScoringService.php
git commit -m "feat(audit): add customer risk event logging"
```

---

## Task 24: Add Sanctions Logging to SanctionScreeningService

**Files:**
- Modify: `app/Services/SanctionScreeningService.php`

- [ ] **Step 1: Add AuditService injection**

Read the file first.

```php
use App\Services\AuditService;

public function __construct(
    // ... existing dependencies
    protected AuditService $auditService
) {}
```

- [ ] **Step 2: Add sanctions hit logging**

When a sanction match is found:
```php
$this->auditService->logSanctionEvent('sanction_screening_hit', $customerId ?? $transactionId, [
    'entity_type' => $customerId ? 'Customer' : 'Transaction',
    'new' => [
        'matched_list' => $matchedListName,
        'matched_entity' => $matchedEntityName,
    ],
]);
```

- [ ] **Step 3: Add manual override logging**

When a sanctions block is manually overridden:
```php
$this->auditService->logSanctionEvent('sanction_block_overridden', $entityId, [
    'entity_type' => $entityType,
    'new' => [
        'reason' => $reason,
        'overridden_by' => auth()->user()->username,
    ],
]);
```

- [ ] **Step 4: Run syntax check and commit**

```bash
php -l app/Services/SanctionScreeningService.php
git add app/Services/SanctionScreeningService.php
git commit -m "feat(audit): add sanction screening event logging"
```

---

## Task 25: Add Revaluation Logging to RevaluationService

**Files:**
- Modify: `app/Services/RevaluationService.php`

- [ ] **Step 1: Add AuditService injection**

Read the file first.

```php
use App\Services\AuditService;

public function __construct(
    // ... existing dependencies
    protected AuditService $auditService
) {}
```

- [ ] **Step 2: Add revaluation run logging**

After revaluation entries are created:
```php
$this->auditService->logPositionEvent('position_revaluation_run', [
    'new' => [
        'entries_created' => count($entries),
        'total_gain_loss' => $totalGainLoss,
        'period' => $period,
    ],
]);
```

- [ ] **Step 3: Add position limit breach logging**

When a position limit breach is detected:
```php
$this->auditService->logPositionEvent('position_limit_breach', [
    'new' => [
        'currency' => $currencyCode,
        'current_position' => $currentPosition,
        'limit' => $limit,
    ],
]);
```

- [ ] **Step 4: Run syntax check and commit**

```bash
php -l app/Services/RevaluationService.php
git add app/Services/RevaluationService.php
git commit -m "feat(audit): add currency position event logging"
```

---

## Task 26: Add Tests for New AuditService Methods

**Files:**
- Modify: `tests/Unit/AuditServiceTest.php`

- [ ] **Step 1: Read existing test file to understand pattern**

- [ ] **Step 2: Add test for logMfaEvent**

```php
public function test_log_mfa_event_creates_system_log(): void
{
    $userId = $this->user->id;
    $log = $this->auditService->logMfaEvent('mfa_verification_success', $userId);

    $this->assertInstanceOf(SystemLog::class, $log);
    $this->assertEquals('mfa_verification_success', $log->action);
    $this->assertEquals('INFO', $log->severity);
    $this->assertEquals($userId, $log->user_id);
    $this->assertEquals('MfaEvent', $log->entity_type);
}

public function test_log_mfa_event_failed_sets_warning_severity(): void
{
    $log = $this->auditService->logMfaEvent('mfa_verification_failed', $this->user->id);

    $this->assertEquals('WARNING', $log->severity);
}
```

- [ ] **Step 3: Add test for logStockTransferEvent**

```php
public function test_log_stock_transfer_event_creates_system_log(): void
{
    $transferId = 1;
    $log = $this->auditService->logStockTransferEvent('stock_transfer_created', $transferId, [
        'new' => ['transfer_number' => 'ST-001'],
    ]);

    $this->assertInstanceOf(SystemLog::class, $log);
    $this->assertEquals('stock_transfer_created', $log->action);
    $this->assertEquals('StockTransfer', $log->entity_type);
    $this->assertEquals($transferId, $log->entity_id);
}

public function test_log_stock_transfer_variance_sets_warning(): void
{
    $log = $this->auditService->logStockTransferEvent('stock_transfer_variance_exceeded', 1);

    $this->assertEquals('WARNING', $log->severity);
}
```

- [ ] **Step 4: Add similar tests for remaining 16 methods**

Following the same pattern as steps 2-3.

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=AuditServiceTest
```

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/AuditServiceTest.php
git commit -m "test(audit): add unit tests for new AuditService methods"
```

---

## Task 27: Final Verification

- [ ] **Step 1: Run all tests**

```bash
php artisan test
```

- [ ] **Step 2: Verify no syntax errors across all modified files**

```bash
php -l app/Services/AuditService.php && \
php -l app/Http/Controllers/MfaController.php && \
php -l app/Http/Controllers/StockTransferController.php && \
php -l app/Http/Controllers/JournalEntryWorkflowController.php && \
php -l app/Services/TransactionMonitoringService.php && \
php -l app/Services/CustomerRiskScoringService.php && \
php -l app/Services/SanctionScreeningService.php && \
php -l app/Services/RevaluationService.php
```

- [ ] **Step 3: Commit final changes**

```bash
git add -A && git commit -m "feat(audit): complete audit trail enhancement implementation"
```

---

## Spec Coverage Check

| Spec Requirement | Task |
|------------------|------|
| logMfaEvent method | Task 1 |
| logStockTransferEvent method | Task 2 |
| logJournalWorkflowEvent method | Task 3 |
| logComplianceAlertEvent method | Task 4 |
| logComplianceCaseEvent method | Task 5 |
| logEddTemplateEvent method | Task 6 |
| logRegulatoryReportEvent method | Task 7 |
| logDataBreachEvent method | Task 8 |
| logSessionEvent method | Task 9 |
| logPermissionDenied method | Task 10 |
| logCustomerRiskEvent method | Task 11 |
| logAmlMonitorEvent method | Task 12 |
| logSanctionEvent method | Task 13 |
| logPositionEvent method | Task 14 |
| logReportAccessEvent method | Task 15 |
| logApiAccessEvent method | Task 16 |
| logBranchAccessEvent method | Task 17 |
| logBatchOperationEvent method | Task 18 |
| MFA controller wiring | Task 19 |
| Stock transfer controller wiring | Task 20 |
| Journal workflow controller wiring | Task 21 |
| AML monitor service wiring | Task 22 |
| Customer risk service wiring | Task 23 |
| Sanctions service wiring | Task 24 |
| Revaluation service wiring | Task 25 |
| Unit tests | Task 26 |
| Final verification | Task 27 |
