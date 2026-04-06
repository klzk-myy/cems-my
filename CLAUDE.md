# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CEMS-MY is a Laravel 10.x Currency Exchange Management System for Malaysian Money Services Businesses (MSB), compliant with Bank Negara Malaysia (BNM) AML/CFT requirements. It handles foreign currency trading, till management, compliance reporting, and double-entry accounting.

## Common Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=TransactionWorkflowTest

# Run via test runner script (includes category filtering)
php test-runner.php

# Lint (PSR-12 via Laravel Pint)
./vendor/bin/pint

# Clear caches
php artisan config:clear && php artisan route:clear && php artisan view:clear

# List routes
php artisan route:list
```

## Architecture

### Layer Structure

```
app/
├── Console/Commands/     # Artisan CLI commands (scheduled reports, compliance tasks)
├── Enums/                # PHP 8.1 enums replacing magic strings
├── Events/               # Event classes (TransactionCreated, etc.)
├── Http/
│   ├── Controllers/      # Thin controllers, delegate to services
│   └── Middleware/       # CheckRole, EnsureMfaVerified, SessionTimeout
├── Models/               # Eloquent models (35+)
└── Services/             # Business logic (20+ services)
```

### Full Documentation

See `docs/` directory for detailed guides: USER_MANUAL.md, DEPLOYMENT.md, API.md, DATABASE_SCHEMA.md.

### Key Architectural Patterns

**1. Enum-Based RBAC**
All role checks use PHP enums in `App\Enums\`:
- `UserRole::Teller`, `UserRole::Manager`, `UserRole::ComplianceOfficer`, `UserRole::Admin`
- Permission methods on enums: `$role->canApproveLargeTransactions()`, `$role->canViewReports()`
- Models return enum instances, not strings

**2. Service Layer**
Controllers inject services via constructor dependency injection (no `app()` service locator):
```php
public function __construct(
    protected CurrencyPositionService $positionService,
    protected ComplianceService $complianceService,
) {}
```

**3. Double-Entry Accounting**
- `AccountingService` creates journal entries for every transaction
- `LedgerService` maintains running balances
- `RevaluationService` handles monthly currency revaluation
- Account codes use `AccountCode` enum (e.g., `AccountCode::CASH_MYR->value`)

**4. BCMath Precision**
All monetary calculations use `App\Services\MathService` (BCMath), not floats. Never cast money values to `float`.

**5. Compliance Workflow**
- Transactions ≥ RM 50,000 require manager approval
- `ComplianceService` runs CDD determination and CTOS reporting (both Buy and Sell)
- `TransactionMonitoringService` detects velocity/structuring patterns (7-day lookback)
- All cancellations require manager approval (segregation of duties)
- **Refunds** are processed through compliance pipeline

**6. Security Features**
- MFA required for ALL roles including Tellers (BNM compliance)
- Rate limiting on sensitive endpoints (login: 5/min, transactions: 30/min, STR: 10/min)
- Session timeout (configurable, default 15 minutes idle)
- Audit log with cryptographic hash chaining (tampering protection)
- Password complexity requirements (min 12 chars, mixed case, number, special char)

### Middleware Stack

Routes use these middleware:
- `auth` - All authenticated users
- `role:manager` - Manager or admin only
- `role:compliance` - Compliance officer or admin only
- `mfa.verified` - MFA verification required
- `throttle:{name}` - Rate limiting
- `session.timeout` - Idle session timeout

### Key Models

| Model | Purpose |
|-------|---------|
| `Transaction` | Buy/sell currency transactions with optimistic locking |
| `Customer` | KYC data, risk ratings, CDD levels |
| `CurrencyPosition` | Stock tracking with weighted avg cost |
| `JournalEntry` | Double-entry accounting records |
| `ChartOfAccount` | COA with 18 default accounts |
| `AccountingPeriod` | Monthly periods for financial reporting |
| `Budget` | Budget vs actual tracking |
| `TillBalance` | Daily till opening/closing |
| `FlaggedTransaction` | AML alerts requiring review |
| `StrReport` | Suspicious Transaction Reports |
| `TransactionConfirmation` | Large transaction manager confirmation |
| `BankReconciliation` | Bank reconciliation with check tracking |

### Accounting Module

**Chart of Accounts** (18 accounts):
- Asset: 1000-2200 (Cash, Inventory, Receivables)
- Liability: 3000-3100 (Payables, Accruals)
- Equity: 4000-4200 (Capital, Retained Earnings)
- Revenue: 5000-5100 (Forex Trading, Revaluation Gains)
- Expense: 6000-6200 (Forex Loss, Revaluation Loss, Operating)

**Services**:
- `AccountingService` - Journal entry creation and reversal
- `LedgerService` - Trial balance, P&L, balance sheet
- `RevaluationService` - Monthly currency revaluation
- `PeriodCloseService` - Period closing with validation
- `BudgetService` - Budget vs actual reporting
- `ReconciliationService` - Bank reconciliation with outstanding checks

**Routes** (`/accounting`):
- `/accounting/journal` - Manual journal entries
- `/accounting/journal/create` - Create journal entry
- `/accounting/journal/{entry}` - View journal entry
- `/accounting/journal/{entry}/reverse` - Reverse journal entry
- `/accounting/ledger` - Chart of accounts / account ledgers
- `/accounting/ledger/{accountCode}` - Account ledger detail
- `/accounting/trial-balance` - Trial balance report
- `/accounting/profit-loss` - P&L statement
- `/accounting/balance-sheet` - Balance sheet
- `/accounting/revaluation` - Currency revaluation
- `/accounting/revaluation/run` - Run revaluation
- `/accounting/periods` - Period management
- `/accounting/periods/{period}/close` - Close period
- `/accounting/reconciliation` - Bank reconciliation
- `/accounting/reconciliation/import` - Import bank statement
- `/accounting/budget` - Budget vs actual

### Compliance & AML

**CDD Levels** (`CddLevel` enum):
- `Simplified` - Transaction < RM 3,000
- `Standard` - RM 3,000 to RM 49,999
- `Enhanced` - ≥ RM 50,000 OR PEP OR Sanction match

**Compliance Flags** (`ComplianceFlagType` enum):
- LargeAmount, SanctionMatch, Velocity, Structuring, EddRequired, PepStatus, HighRiskCustomer, etc.

**CTOS Reporting**: Applies to ALL cash transactions (Buy and Sell) ≥ RM 10,000

**Structuring Detection**: 7-day lookback for aggregate transactions (configurable)

### Report Generation

BNM compliance reports via Artisan commands:
- `report:msb2` - Daily transaction summary
- `report:lctr` - Large Cash Transaction Report (≥ RM 50k)
- `report:lmca` - Monthly LMCA
- `report:qlvr` - Quarterly Large Value
- `compliance:rescreen` - Monthly sanctions rescreening

### Test Organization

Tests use `RefreshDatabase` trait and are in `tests/Feature/` and `tests/Unit/`. Key test files:
- `tests/Feature/TransactionWorkflowTest.php` - Transaction creation, approval, cancellation
- `tests/Feature/RouteConsistencyTest.php` - Route/role access verification
- `tests/Feature/AccountingWorkflowTest.php` - Journal entries, periods, closing
- `tests/Feature/StrWorkflowTest.php` - STR creation and workflow
- `tests/Unit/AmlRuleTest.php` - AML rule engine
- `tests/Unit/MathServiceTest.php` - BCMath precision

### Counter Management

Counters (tills) with full lifecycle:
- `/counters/{counter}/open` - Open counter with opening floats
- `/counters/{counter}/close` - Close counter with closing floats
- `/counters/{counter}/handover` - Transfer custody between users
- `/counters/{counter}/status` - Real-time counter status
- `/counters/{counter}/history` - Transaction history

## Important Conventions

- **Money**: Always use `MathService` or BCMath functions. Never use PHP `float` for currency.
- **Enums**: All magic strings (statuses, types, roles) should be converted to PHP enums.
- **Audit**: Critical operations must create `SystemLog` entries with hash chaining.
- **RBAC**: Check permissions via enum methods, not string comparison.
- **Services over Controllers**: Business logic belongs in services, not controllers.
- **Encryption**: Use `EncryptionService` with random IV per encryption (IV prepended to ciphertext).
- **File Uploads**: Sanitize filenames with `basename()` or use `Str::uuid()` for naming.
- **Query Parameters**: Use parameterized queries for user-supplied values in LIKE clauses.
- **Cancellation**: ALL transaction cancellations require manager approval.
- **Concurrency**: Use `lockForUpdate()` for position updates to prevent race conditions.
