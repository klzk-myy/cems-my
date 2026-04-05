# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CEMS-MY is a Laravel 10.x Currency Exchange Management System for Malaysian Money Services Businesses (MSB), compliant with Bank Negara Malaysia (BNM) AML/CFT requirements. It handles foreign currency trading, till management, compliance reporting, and double-entry accounting.

## Common Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=TransactionTest

# Run with coverage
php artisan test --coverage

# Run via test runner script (includes category filtering)
php test-runner.php

# Lint (PSR-12 via Laravel Pint)
./vendor/bin/pint

# Clear caches
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

## Architecture

### Layer Structure

```
app/
├── Console/Commands/     # Artisan CLI commands (scheduled reports, compliance tasks)
├── Enums/                # PHP 8.1 enums replacing magic strings
├── Http/
│   ├── Controllers/      # Thin controllers, delegate to services
│   └── Middleware/        # CheckRole for RBAC
├── Models/               # Eloquent models (30+)
└── Services/             # Business logic (20+ services)
```

### Key Architectural Patterns

**1. Enum-Based RBAC**
All role checks use PHP enums in `App\Enums\`:
- `UserRole::Teller`, `UserRole::Manager`, `UserRole::ComplianceOfficer`, `UserRole::Admin`
- Permission methods on enums: `$role->canApproveLargeTransactions()`, `$role->canAccessCompliance()`
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
- `RevaluationService` handles monthly currency revaluation (RM 50k+)

**4. BCMath Precision**
All monetary calculations use `App\Services\MathService` (BCMath), not floats. Never cast money values to `float`.

**5. Compliance Workflow**
- Transactions ≥ RM 50,000 require manager approval
- `ComplianceService` runs sanction screening
- `TransactionMonitoringService` detects velocity/structuring patterns

### Route Organization

Routes are in `routes/web.php` with middleware groups:
- `auth` - All authenticated users
- `role:manager` - Manager or admin only
- `role:compliance` - Compliance officer or admin only

### Key Models

| Model | Purpose |
|-------|---------|
| `Transaction` | Buy/sell currency transactions |
| `Customer` | KYC data, risk ratings |
| `CurrencyPosition` | Stock tracking with avg cost |
| `JournalEntry` | Double-entry accounting records |
| `JournalLine` | Individual debit/credit lines |
| `AccountLedger` | Running balance ledger entries |
| `ChartOfAccount` | COA with 18+ accounts, cost centers |
| `Department` | Organizational departments |
| `CostCenter` | Cost center tracking |
| `FiscalYear` | Annual fiscal year management |
| `AccountingPeriod` | Monthly periods for financial reporting |
| `Budget` | Budget vs actual tracking |
| `TillBalance` | Daily till opening/closing |
| `FlaggedTransaction` | AML alerts requiring review |
| `StrReport` | Suspicious Transaction Reports |
| `EnhancedDiligenceRecord` | EDD questionnaire records |

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
- `ReconciliationService` - Bank reconciliation
- `JournalEntryWorkflowService` - Draft → Pending → Posted workflow
- `CashFlowService` - Cash flow statement (operating/investing/financing)
- `FinancialRatioService` - Liquidity, profitability, leverage, efficiency ratios
- `FiscalYearService` - Fiscal year closing with income summary entries
- `EddService` - Enhanced Due Diligence workflow management

**Database Seeders**:
- `ChartOfAccountsSeeder` - Creates 18 default accounts
- `AccountingPeriodSeeder` - Creates current + 2 months
- `BudgetSeeder` - Sample monthly budgets

**Routes** (`/accounting`):
- `/accounting/journal` - Manual journal entries
- `/accounting/journal/create` - New journal entry
- `/accounting/journal/workflow` - Journal entry approval workflow
- `/accounting/ledger` - Chart of accounts / account ledgers
- `/accounting/trial-balance` - Trial balance report
- `/accounting/profit-loss` - P&L statement
- `/accounting/balance-sheet` - Balance sheet
- `/accounting/cash-flow` - Cash flow statement
- `/accounting/ratios` - Financial ratios analysis
- `/accounting/revaluation` - Currency revaluation
- `/accounting/periods` - Period management
- `/accounting/fiscal-years` - Fiscal year management
- `/accounting/reconciliation` - Bank reconciliation
- `/accounting/budget` - Budget vs actual

**Compliance Routes** (`/compliance`):
- `/compliance` - Compliance dashboard
- `/compliance/flagged` - Flagged transactions review
- `/compliance/edd` - Enhanced Due Diligence records
- `/compliance/edd/create` - New EDD record
- `/str` - Suspicious Transaction Reports

### Report Generation

BNM compliance reports are generated via scheduled Artisan commands:
- `report:msb2` - Daily transaction summary
- `report:lctr` - Large Cash Transaction Report (≥ RM 50k)
- `report:lmca` - Monthly LMCA
- `report:qlvr` - Quarterly Large Value
- `compliance:rescreen` - Monthly sanctions rescreening

### Test Organization

Tests use `RefreshDatabase` trait and are in `tests/Feature/` and `tests/Unit/`. Key test files:
- `tests/Feature/TransactionTest.php` - Core transaction workflow
- `tests/Feature/RealWorldTransactionWorkflowTest.php` - End-to-end daily workflow
- `tests/Feature/UserManagementTest.php` - RBAC verification
- `tests/Feature/EddWorkflowTest.php` - EDD workflow tests
- `tests/Feature/ReportsViewTest.php` - Reports dashboard tests
- `tests/Unit/MathServiceTest.php` - BCMath precision
- `tests/Unit/AccountingServiceTest.php` - Accounting journal tests
- `tests/Unit/LedgerServiceTest.php` - Ledger and trial balance tests
- `tests/Unit/ComplianceServiceTest.php` - Compliance CDD/CDD level tests

**Total: 364 tests, 1,063 assertions**

## Important Conventions

- **Money**: Always use `MathService` or BCMath functions. Never use PHP `float` for currency.
- **Enums**: All magic strings (statuses, types, roles) should be converted to PHP enums.
- **Audit**: Critical operations must create `SystemLog` entries.
- **RBAC**: Check permissions via enum methods, not string comparison.
- **Services over Controllers**: Business logic belongs in services, not controllers.
