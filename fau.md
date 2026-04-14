# CEMS-MY Fault Analysis Report

**Date:** 2026-04-14
**System:** CEMS-MY Laravel Currency Exchange Management System
**Analysis:** Comprehensive fault identification across logical, workflow, coding, security, and data integrity categories

---

## Test Suite Status

- **187 passed, 33 skipped** - Core functionality working but several integration tests pending setup
- **0 failures** - No test suite failures at time of analysis

---

## CRITICAL FAULTS

### 1. EDD Record Completion Check Bypass - Logical/Workflow

**File:** `app/Services/EddService.php` (lines 89-97)

```php
public function isRecordComplete(EnhancedDiligenceRecord $record): bool
{
    $required = [
        $record->source_of_funds,
        $record->purpose_of_transaction,
    ];

    return ! in_array(null, $required, true) && ! empty($record->source_of_funds);
}
```

**Issue:** The method returns `true` if `source_of_funds` is non-null AND non-empty. However, it does NOT validate `purpose_of_transaction` is non-empty - only that it's not null. An empty string `""` would pass the null check but fail business validation.

**Impact:** EDD records can be marked complete and submitted for review even when `purpose_of_transaction` is an empty string, allowing incomplete compliance records to progress through approval workflow.

**Fix:**
```php
return ! empty($record->source_of_funds) && ! empty($record->purpose_of_transaction);
```

---

### 2. TillBalance Query Defaults to 'MAIN' - Data Integrity

**File:** `app/Services/TransactionService.php` (line 416)

```php
$tillBalance = TillBalance::where('till_id', $lockedTransaction->till_id ?? 'MAIN')
```

**Issue:** When `till_id` is null, the code defaults to `'MAIN'` string. This creates a silent fallback that could:
- Create till balances for a non-existent 'MAIN' till
- Mask missing till_id configuration issues
- Cause transactions to be recorded against wrong till

**Impact:** Transactions could be incorrectly associated with a default till rather than failing fast on missing configuration.

**Fix:** Remove default; require valid till_id or throw exception.

---

### 3. Counter Handover Race Condition - Coding

**File:** `app/Services/CounterService.php` (lines 401-424)

```php
$lockedBalance = TillBalance::where('id', $existingBalance->id)
    ->lockForUpdate()
    ->first();

if ($lockedBalance) {
    $lockedBalance->closing_balance = $count['amount'];
    // ... multiple field updates ...
    $lockedBalance->save();
}
```

**Issue:** The code updates the `lockedBalance` object but does NOT re-lock it with `lockForUpdate()` before saving. Between the `lockForUpdate()` call and the `save()` call, another concurrent process could modify the same row.

**Impact:** In high-volume counter scenarios, handover variance calculations could be corrupted by concurrent updates.

---

### 4. Handover Creates Duplicate TillBalance Records - Data Integrity

**File:** `app/Services/CounterService.php` (lines 417-424)

```php
// Create a new balance record for the new session user
TillBalance::create([
    'till_id' => (string) $newSession->counter_id,
    'currency_code' => $currencyCode,
    'opening_balance' => $count['amount'],
    'date' => $today,
    'opened_by' => $toUser->id,
]);
```

**Issue:** After updating `closing_balance` and `variance` on the existing record, the code creates a NEW TillBalance record. However, there may already be an existing open TillBalance for this till/date/currency, potentially creating duplicate records.

**Impact:** Could result in multiple open TillBalance records for same till/date/currency combination, breaking balance calculations.

---

### 5. Sanctions Screening SQL LIKE Wildcards Not Escaped - Security

**File:** `app/Services/ComplianceService.php` (lines 121-132)

```php
public function checkSanctionMatch(Customer $customer): bool
{
    $customerName = $customer->full_name;

    $matches = DB::table('sanction_entries')
        ->where('entity_name', 'ilike', '%'.$customerName.'%')
        ->orWhere('aliases', 'ilike', '%'.$customerName.'%')
        ->count();

    return $matches > 0;
}
```

**Issue:** The `$customerName` is directly interpolated into the SQL query without escaping LIKE wildcards (`%`, `_`). If `$customerName` contains these characters, they cause unexpected matching behavior.

**Impact:** False positives/negatives in sanctions screening - critical compliance failure.

**Fix:**
```php
$escapedName = str_replace(['%', '_'], ['\\%', '\\_'], $customerName);
```

---

## HIGH SEVERITY

### 6. Till Balance Race Condition on Close - Coding

**File:** `app/Services/CounterService.php` (lines 116-120)

```php
$tillBalances = TillBalance::where('till_id', (string) $session->counter_id)
    ->where('date', $session->session_date)
    ->whereNull('closed_at')
    ->get()
    ->keyBy('currency_code');
```

**Issue:** The query fetches all open till balances and keys them by currency. But if another process closes one of these balances between the `get()` and subsequent updates, the update could fail silently or overwrite closed data.

**Impact:** Variance tracking could be lost if concurrent close occurs.

---

### 7. Refund Copies Original approved_by/approved_at - Data Integrity

**File:** `app/Services/TransactionCancellationService.php` (lines 450-468)

```php
return Transaction::create([
    // ... other fields ...
    'approved_by' => $original->approved_by,    // Copies original approver
    'approved_at' => $original->approved_at,   // Copies original approval time
]);
```

**Issue:** The refund transaction copies `approved_by` and `approved_at` from the original transaction. But the refund should be treated as a new transaction with its own approval trail.

**Impact:** Refund transactions appear to have been "approved" at the original transaction's time, even though no new approval occurred.

---

### 8. JournalEntry Reversal Bypasses Approval Workflow - Workflow

**File:** `app/Services/JournalEntryWorkflowService.php` (lines 287-306)

```php
$reversalEntry = JournalEntry::create([
    // ...
    'status' => 'Posted',  // Direct Posted status!
    'created_by' => $userId,
    'posted_by' => $userId,
    'posted_at' => now(),
]);
```

**Issue:** The reversal entry is created directly in `Posted` status without going through the Draft -> Pending -> Posted workflow. This bypasses the segregation of duties where the creator cannot be the approver.

**Impact:** A user can reverse their own journal entries without independent approval, violating internal controls.

---

### 9. Bulk Operations N+1 Query - Coding

**File:** `app/Services/AlertTriageService.php` (lines 280-304)

```php
foreach ($alertIds as $alertId) {
    try {
        $alert = Alert::find($alertId);  // N+1 query problem
```

**Issue:** For bulk operations with large arrays, using `find()` in a loop creates N database queries. Should use `whereIn()` for batch retrieval.

**Impact:** Performance degradation for large bulk operations (e.g., resolving 1000+ alerts).

---

### 10. countWorkingDays Off-by-One Error - Coding

**File:** `app/Services/ComplianceService.php` (lines 323-336)

```php
protected function countWorkingDays(Carbon $from, Carbon $to): int
{
    $days = 0;
    $current = $from->copy();

    while ($current->lt($to)) {  // <-- Uses less-than
        if (! $current->isWeekend()) {
            $days++;
        }
        $current->addDay();
    }

    return $days;
}
```

**Issue:** The `while ($current->lt($to))` means if `from` = Monday and `to` = Tuesday, only Monday is counted. The deadline calculation may be off by 1.

**Impact:** For STR deadline tracking, `days_remaining` calculation may be off by 1, potentially showing drafts as overdue when they are not.

---

### 11. STR Transaction Query Missing Status Filter - Logical

**File:** `app/Services/StrAutomationService.php` (line 177)

```php
protected function getTransactions(array $ids): Collection
{
    return Transaction::whereIn('id', $ids)->get();  // No status filter
}
```

**Issue:** Retrieves transactions regardless of status (Cancelled, Reversed, etc.). For STR reporting, only active/completed transactions should be included.

**Impact:** STR reports could include cancelled transactions, distorting the suspicious activity narrative.

---

### 12. Position Reversal Without Balance Check - Logical

**File:** `app/Services/TransactionCancellationService.php` (lines 481-522)

```php
public function reversePositions(Transaction $transaction): void
{
    $positionService = $this->positionService;
    $position = $positionService->getPosition(...);
    // No check if position has sufficient balance for reversal
    $positionService->updatePosition(...);
}
```

**Issue:** For Sell transactions being reversed (which would be a Buy), there's no check that the position has sufficient foreign currency balance. If the original Sell consumed the position, the reversal (Buy) might fail or create negative position.

**Impact:** Position could go negative, violating invariant that position balance >= 0.

---

## MEDIUM SEVERITY

### 13. EDD Customer ID Can Be Null - Data Integrity

**File:** `app/Services/EddService.php` (lines 25-30)

```php
$recordData = [
    'customer_id' => $flag->customer_id ?? $flag->getAttribute('customer_id'),
```

**Issue:** Uses null coalescing but `flag->customer_id` might be null from the flag record, and `getAttribute()` fallback may also be null. No validation that customer_id is set before creating EDD record.

**Impact:** EDD records without customer linkage cannot be properly tracked in compliance workflow.

---

### 14. Hardcoded 'Posted' Status String - Coding

**File:** `app/Services/JournalEntryWorkflowService.php` (line 292)

```php
'status' => 'Posted',  // Hardcoded string
```

**Issue:** Uses hardcoded string 'Posted' instead of referencing a constant or enum. If status values change, this could break.

**Impact:** Maintenance risk; status string duplication across codebase.

---

### 15. Supervisor/Manager Role Conflation - Coding

**File:** `app/Services/ApprovalWorkflowService.php` (lines 292-300)

```php
protected function canApprove(User $user, string $requiredRole): bool
{
    return match ($requiredRole) {
        'supervisor' => $user->role->isManager(), // Supervisors are managers in this system
        'manager' => $user->role->isManager(),
        'admin' => $user->role->isAdmin(),
        default => false,
    };
}
```

**Issue:** The comment says "Supervisors are managers in this system" but there's no `isSupervisor()` method on the role. The system conflates supervisor and manager roles.

**Impact:** Supervisor-level approvals may actually require manager-level authorization, reducing the tiered approval structure.

---

### 16. Transaction Cancellation approved_by Not Properly Set - Logical

**File:** `app/Services/TransactionCancellationService.php` (lines 172-177)

```php
$result = $stateMachine->transitionTo(TransactionStatus::Cancelled, [
    'reason' => $reason ?? 'Cancellation approved',
    'user_id' => $approver->id,
    'approved_by' => $approver->id,  // <-- This is stored in transition context
]);
```

**Issue:** The `approved_by` is passed to `transitionTo()` but the model's `approved_by` field may not be updated depending on how `transitionTo` handles the context array.

**Impact:** Audit trail gap - cannot trace who approved cancellation from transaction record alone.

---

## SUMMARY TABLE

| # | File | Line | Category | Severity | Description |
|---|------|------|----------|----------|-------------|
| 1 | EddService.php | 89-97 | Logical | Critical | isRecordComplete() doesn't validate non-empty purpose |
| 2 | TransactionService.php | 416 | Data | Critical | Defaults to 'MAIN' till masking null till_id |
| 3 | CounterService.php | 401-424 | Coding | High | Race condition on handover balance update |
| 4 | CounterService.php | 417-424 | Data | High | Creates duplicate TillBalance on handover |
| 5 | ComplianceService.php | 121-132 | Security | High | SQL LIKE wildcards not escaped in sanctions check |
| 6 | CounterService.php | 116-120 | Coding | Medium | Till balance query not locked during iteration |
| 7 | TransactionCancellationService.php | 450-468 | Data | Medium | Refund copies original approved_by/approved_at |
| 8 | JournalEntryWorkflowService.php | 287-306 | Workflow | Medium | Reversal bypasses approval workflow |
| 9 | AlertTriageService.php | 280-304 | Coding | Medium | N+1 query in bulk resolve |
| 10 | ComplianceService.php | 323-336 | Coding | Medium | countWorkingDays off-by-one error |
| 11 | StrAutomationService.php | 177 | Logical | Medium | Transaction query missing status filter |
| 12 | TransactionCancellationService.php | 481-522 | Logical | Medium | Position reversal without balance check |
| 13 | EddService.php | 25-30 | Data | Low | EDD customer_id can be null |
| 14 | JournalEntryWorkflowService.php | 292 | Coding | Low | Hardcoded 'Posted' status string |
| 15 | ApprovalWorkflowService.php | 292-300 | Coding | Low | Supervisor/Manager conflation |
| 16 | TransactionCancellationService.php | 172-177 | Logical | Low | approved_by not properly set on cancellation |

---

## RECOMMENDED ACTIONS

### Immediate (Critical):
1. Fix `isRecordComplete()` to validate non-empty purpose_of_transaction
2. Escape LIKE wildcards in sanctions screening query
3. Remove 'MAIN' default; fail fast on null till_id

### High Priority:
4. Add proper locking around TillBalance updates in CounterService
5. Prevent duplicate TillBalance creation during handover
6. Add position balance validation before reversal

### Medium Priority:
7. Implement chunk-based bulk operations in AlertTriageService
8. Fix working day calculation off-by-one
9. Add status filter to transaction queries for STR generation
10. Use constants/enums for status values instead of strings

### Low Priority:
11. Add customer_id validation in EddService
12. Implement proper supervisor role separate from manager
13. Ensure cancellation properly sets approved_by on transaction model

---

*Report generated: 2026-04-14*