# Fault Analysis (Logical / Workflow / Coding)

Scope: high-impact faults in AML/compliance workflows, transaction lifecycle, money precision, concurrency, and authorization. Findings are based on current code plus the existing “fault catalog” unit test (`tests/Unit/FaultAnalysisTest.php`).

## Summary (highest-risk first)

- **Critical**: Money precision broken in AML rule structuring evaluation due to **float casting** and mixed numeric types.
- **High**: Sanctions screening uses **unescaped LIKE wildcards** and **database-specific `ilike`**, causing false positives/negatives depending on input and DB engine.
- **High**: Working-day calculation used for STR deadlines has an **off-by-one** (exclusive end date), misreporting remaining days / overdue status.
- **High**: Multiple places compare statuses as **raw strings** (e.g. `'Cancelled'`) while other parts treat status as enums; this risks silently including/excluding records in compliance monitors and aggregate checks.
- **Medium**: Multiple money aggregations use Eloquent `sum()` without enforcing string/decimal handling; in at least one place the code explicitly casts to float, contradicting the “no floats for money” rule.
- **Medium**: Some controllers instantiate services directly instead of dependency injection, bypassing shared configuration/mocks and making behavior drift between HTTP paths.

---

## Fault #1 (Workflow/validation): EDD completeness accepts empty `purpose_of_transaction`

- **Severity**: High (compliance workflow correctness)
- **Impact**: EDD record can advance to `PendingReview` even when `purpose_of_transaction` is empty string.
- **Evidence**:
  - `app/Services/EddService.php` checks only `null` and only enforces non-empty `source_of_funds`, not `purpose_of_transaction`.
  - A dedicated regression test describes this exact issue: `tests/Unit/FaultAnalysisTest.php`.

Problematic logic:
- `EddService::isRecordComplete()` returns true when `purpose_of_transaction === ''` (empty string), because it only checks for `null`.

Suggested fix:
- Treat both fields as required **non-empty strings** (e.g. `trim($value) !== ''`), not merely non-null.
- Add/keep unit tests that cover both null and empty-string cases (already present in `FaultAnalysisTest`).

---

## Fault #5 (Logic/data quality): Sanctions screening wildcard injection + DB portability

- **Severity**: High
- **Impact**:
  - Customer names containing `%` or `_` behave as SQL LIKE wildcards, creating **false matches** (false sanctions hits) or masking true matches.
  - Use of `ilike` makes the query **PostgreSQL-specific**; on MySQL/MariaDB, this operator will fail or behave unexpectedly.
- **Evidence**:
  - `app/Services/ComplianceService.php`:
    - `->where('entity_name', 'ilike', '%'.$customerName.'%')`
    - `->orWhere('aliases', 'ilike', '%'.$customerName.'%')`
  - `tests/Unit/FaultAnalysisTest.php` explicitly calls out “wildcards not escaped”.

Suggested fix:
- Escape `%` and `_` in the input and provide an explicit escape character (DB-dependent), or use a safer search strategy:
  - Prefer normalized token matching, trigram/full-text search, or exact-match against preprocessed aliases.
- Replace `ilike` with a DB-agnostic approach:
  - e.g. `LOWER(column) LIKE LOWER(?)` if you must stay portable, or use Laravel’s driver-specific capabilities.
- Add integration tests for the actual DB driver used in production.

---

## Fault #10 (Logical): `countWorkingDays()` off-by-one (exclusive end date)

- **Severity**: High (deadline/overdue reporting)
- **Impact**: STR deadline calculations can show **incorrect days remaining**. Example: Monday → Tuesday counts only 1 day in current loop.
- **Evidence**:
  - `app/Services/ComplianceService.php` `countWorkingDays()` uses `while ($current->lt($to))`, excluding the end date.
  - Reproduced in `tests/Unit/FaultAnalysisTest.php` (“Bug: should be 2”).

Suggested fix:
- Decide whether the business definition is inclusive or exclusive, then implement consistently:
  - If inclusive, use `lte()` or adjust bounds (e.g. normalize to start-of-day and add 1).
- Add tests for:
  - same-day ranges
  - weekend boundaries
  - overdue scenarios (negative days remaining).

---

## Fault (Critical money precision): AML rule structuring uses floats

- **Severity**: Critical (violates money precision rules; can change risk outcomes)
- **Impact**: AML rule evaluation can be wrong near thresholds (rounding/precision loss), potentially missing suspicious cases or flagging legit ones.
- **Evidence**:
  - `app/Models/AmlRule.php` in `evaluateStructuring()`:
    - `$totalAmount = $recentTransactions->sum('amount_local') + (float) $transaction->amount_local;`
    - then `bccomp((string) $totalAmount, ...)` on a value that may already have float rounding.

Why this is dangerous:
- `sum('amount_local')` may return numeric types depending on driver; adding a float forces float arithmetic.
- Casting back to string does not restore lost precision.

Suggested fix:
- Never use float for monetary sums. Use:
  - `MathService` (preferred in this codebase) or `bcadd`/`bccomp` on strings.
  - Ensure `amount_local` is consistently treated as string/decimal with fixed scale.

---

## Fault (High): Status comparisons as raw strings (enum mismatch risk)

- **Severity**: High
- **Impact**: Compliance monitors and aggregate checks can include/exclude the wrong transactions depending on how status is stored/cast (enum vs string, label vs value).
- **Evidence (examples)**:
  - `app/Services/ComplianceService.php` `checkAggregateTransactions()`:
    - `->where('status', '!=', 'Cancelled')`
  - Monitors (e.g. `app/Services/Compliance/Monitors/VelocityMonitor.php`, `StructuringMonitor.php`):
    - `->where('status', '!=', 'Cancelled')`
  - Other parts use enum values correctly (e.g. `app/Services/Compliance/RiskScoringEngine.php` uses `TransactionStatus::Cancelled->value`).

Suggested fix:
- Standardize status storage and querying:
  - Use `TransactionStatus::Cancelled->value` everywhere (or a model scope like `Transaction::notCancelled()`).
- Add tests that confirm “cancelled” transactions are excluded across all monitors and reporting paths.

---

## Fault (Medium): Money aggregations via `sum()` without consistent precision handling

- **Severity**: Medium (can become high with large volumes or near thresholds)
- **Impact**: Reporting totals, dashboards, and AML thresholds may drift due to implicit numeric conversion.
- **Evidence (examples)**:
  - `app/Http/Controllers/DashboardController.php` sums by type using raw strings `'Buy'/'Sell'` and `sum('amount_local')`.
  - `app/Services/TransactionMonitoringService.php` and multiple report services/controllers use `sum('amount_local')` directly.
  - `app/Models/AmlRule.php` combines `sum()` with float casting (already critical above).

Suggested fix:
- For any “decisioning” (AML thresholds, holds, approvals): compute using `MathService` or SQL-cast-to-string with fixed scale.
- For reporting: if you accept DB-side sums, ensure:
  - column type is `DECIMAL(p,s)`
  - driver returns strings or you cast appropriately
  - totals are formatted/handled as strings at boundaries.

---

## Fault (Medium): Direct service instantiation bypasses DI

- **Severity**: Medium
- **Impact**: Harder to test, inconsistent configuration, may bypass decorated services/mocks.
- **Evidence**:
  - `app/Http/Controllers/DashboardController.php`:
    - `$service = new CurrencyPositionService(new \App\Services\MathService);`

Suggested fix:
- Inject `CurrencyPositionService` (and `MathService` if needed) via constructor, consistent with project conventions.

---

## Workflow/RBAC consistency observations (needs targeted verification)

These are not proven bugs yet, but are common sources of workflow faults and should be validated with tests:

- **Route middleware consistency**:
  - Web routes enforce MFA on transaction create/store and manager approvals; verify API v1 mirrors web behavior for all sensitive operations (including cancellation workflow).
- **Controller-level RBAC checks**:
  - Some endpoints already have `role:*` middleware, yet controllers still do manual checks (e.g. `DashboardController::compliance()`); ensure they can’t drift (prefer middleware-only enforcement).
- **Enum usage consistency**:
  - Transaction `type` is sometimes compared to raw strings (`'Buy'`, `'Sell'`); other code uses `TransactionType` enum objects.

Suggested follow-up:
- Add a “RouteConsistencyTest” style suite (if not already present) that asserts each sensitive route has the expected middleware (`auth`, `role:*`, `mfa.verified`, throttles).

---

## Concrete test references already in repo

- `tests/Unit/FaultAnalysisTest.php` documents and reproduces:
  - **Fault #1** (EDD completeness empty purpose)
  - **Fault #5** (sanctions LIKE wildcard escaping)
  - **Fault #10** (working day count off-by-one)

