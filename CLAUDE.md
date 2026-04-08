analyx# CLAUDE.md0

# All permission granted. Directly proceed. Never ask for permission

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CEMS-MY is a Laravel 10.x Currency Exchange Management System for Malaysian Money Services Businesses (MSB), compliant with Bank Negara Malaysia (BNM) AML/CFT requirements. It handles foreign currency trading, till management, compliance reporting, and double-entry accounting.

## Common Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=TransactionWorkflowTest

# Run a single test class
php artisan test --filter=MathServiceTest

# Run via test runner script (includes category filtering)
php test-runner.php

# Lint (PSR-12 via Laravel Pint)
./vendor/bin/pint

# Clear caches
php artisan config:clear && php artisan route:clear && php artisan view:clear

# List routes
php artisan route:list

# Run a specific Artisan command
php artisan report:msb2 --date=2026-04-06
```

## Architecture

### Layer Structure

```
app/
├── Console/Commands/     # Artisan CLI commands (scheduled reports, compliance tasks)
├── Enums/                # PHP 8.1 enums replacing magic strings
├── Events/               # Event classes (TransactionCreated, CounterSessionOpened, etc.)
├── Http/
│   ├── Controllers/      # Thin controllers, delegate to services
│   └── Middleware/       # CheckRole, EnsureMfaVerified, SessionTimeout
├── Models/               # Eloquent models (35+)
└── Services/             # Business logic (29 services)
```

### Full Documentation

See `docs/` directory:
- `USER_MANUAL.md` - End-user guide
- `DEPLOYMENT.md` - Production deployment
- `API.md` - REST API reference
- `DATABASE_SCHEMA.md` - Schema documentation
- `trading-module-analysis.md` - System architecture
- `logical-faults-analysis.md` - Security review

### Key Architectural Patterns

**1. Enum-Based RBAC**
All role checks use PHP enums in `App\Enums\`:
- `UserRole::Teller`, `UserRole::Manager`, `UserRole::ComplianceOfficer`, `UserRole::Admin`
- Permission methods on enums: `$role->canApproveLargeTransactions()`, `$role->canViewReports()`
- All status/type enums organized by domain:
  - **Transaction**: `TransactionStatus`, `TransactionType`
  - **Customer**: `CddLevel`, `EddStatus`, `EddRiskLevel`
  - **Session**: `CounterSessionStatus`
  - **Compliance**: `FlagStatus`, `StrStatus`, `AmlRuleType`, `ComplianceFlagType`
  - **Accounting**: `AccountCode`
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
- `MonitoringEngine` runs automated compliance monitors via background jobs:
  - `VelocityMonitor` - Detects velocity/structuring patterns (7-day lookback)
  - `StructuringMonitor` - Transaction aggregation detection
  - `SanctionsRescreeningMonitor` - Monthly rescreening of all customers
  - `StrDeadlineMonitor` - STR submission deadline tracking
  - `CustomerLocationAnomalyMonitor` - Geographic anomaly detection
  - `CurrencyFlowMonitor` - Currency flow pattern analysis
  - `CounterfeitAlertMonitor` - Counterfeit currency detection
- `ComplianceReportingService` provides dashboard KPIs, calendar, case aging, audit trail
- `CaseManagementService` manages compliance cases with notes, documents, links
- `RiskScoringEngine` calculates customer risk scores with lock/unlock capability
- All cancellations require manager approval (segregation of duties)
- **Refunds** are processed through compliance pipeline

**6. Event-Driven Architecture**
Events fire for critical operations (`TransactionCreated`, `CounterSessionOpened`, etc.) with listeners for audit logging, notifications, and compliance triggers.

**7. Background Processing**
Laravel queues handle async compliance screening, STR report submission, and sanctions rescreening via `App\Jobs\`.

**8. Role Hierarchy**
Permissions inherit upward: `Admin` > `ComplianceOfficer` > `Manager` > `Teller`.
- Managers can approve large transactions but not configure system settings
- Compliance Officers handle AML workflows, not daily operations

**9. Security Features**
- MFA required for ALL roles including Tellers (BNM compliance)
- Rate limiting on sensitive endpoints (login: 5/min, transactions: 30/min, STR: 10/min)
- Session timeout (configurable, default 15 minutes idle)
- Audit log with cryptographic hash chaining (tampering protection)
- Password complexity requirements (min 12 chars, mixed case, number, special char)

**Note on MFA scope**: MFA is enforced on sensitive operations (transaction creation, approvals, admin functions). Non-sensitive read operations (viewing transactions, customers, counters) do not require MFA. This balances security with usability while meeting BNM requirements for MFA on high-risk activities.

### Middleware Stack

Routes use these middleware:
- `auth` - All authenticated users
- `role:manager` - Manager or admin only
- `role:compliance` - Compliance officer or admin only
- `mfa.verified` - MFA verification required
- `throttle:{name}` - Rate limiting
- `session.timeout` - Idle session timeout

### Navigation Structure

The sidebar navigation is organized by function:

**Operations** (All authenticated users):
- Dashboard (`/dashboard`)
- Transactions (`/transactions`)
- Customers (`/customers`)
- Counters (`/counters`)
- Stock & Cash (`/stock-cash`)

**Compliance & AML** (Compliance officers):
- Compliance Portal (`/compliance`)
  - Flagged Transactions, EDD Records, AML Rules
- STR Reports (`/str`)

**Accounting** (Managers/Admin):
- Accounting Dashboard (`/accounting`)
  - Journal Entries, Ledger, Trial Balance
  - P&L, Balance Sheet, Cash Flow, Ratios
  - Periods, Fiscal Years, Revaluation
  - Reconciliation, Budget

**Reports** (Managers/Admin):
- Reports Dashboard (`/reports`)
  - MSB2, LCTR, LMCA, Quarterly LVR
  - Position Limits, Report History

**System** (Role-based):
- Tasks (`/tasks`)
- Audit Log (`/audit`)
- Users (`/users`) - Admin only

Configuration: `app/Config/Navigation.php`

### Key Models

| Model | Purpose |
|-------|---------|
| `Transaction` | Buy/sell currency transactions with optimistic locking |
| `Customer` | KYC data, risk ratings, CDD levels |
| `CurrencyPosition` | Stock tracking with weighted avg cost |
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
| `ComplianceCase` | Compliance investigation case management |
| `ComplianceFinding` | Automated findings from monitoring engine |
| `CustomerRiskProfile` | Customer risk scoring and history |
| `CustomerBehavioralBaseline` | Customer behavioral patterns for anomaly detection |
| `EddQuestionnaireTemplate` | EDD questionnaire templates |
| `EddDocumentRequest` | EDD document requests |
| `TransactionConfirmation` | Large transaction manager confirmation |
| `BankReconciliation` | Bank reconciliation with check tracking |
| `CounterSession` | Till session with open/close lifecycle |
| `AuditLog` | Tamper-evident audit trail with hash chaining |
| `SystemLog` | Cryptographically chained system events |
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
- `ReconciliationService` - Bank reconciliation with outstanding checks
- `JournalEntryWorkflowService` - Draft → Pending → Posted workflow
- `CashFlowService` - Cash flow statement (operating/investing/financing)
- `FinancialRatioService` - Liquidity, profitability, leverage, efficiency ratios
- `FiscalYearService` - Fiscal year closing with income summary entries
- `EddService` - Enhanced Due Diligence workflow management
- `StrReportService` - Suspicious Transaction Report generation and submission
- `CounterService` - Till/counter lifecycle management (open, close, handover)
- `AuditService` - Audit log and system event management
- `SanctionScreeningService` - Customer/transaction sanctions screening
- `RiskRatingService` - Customer risk rating calculation

**Database Seeders**:
- `ChartOfAccountsSeeder` - Creates 18 default accounts
- `AccountingPeriodSeeder` - Creates current + 2 months
- `BudgetSeeder` - Sample monthly budgets
- `DepartmentSeeder` - Organizational departments
- `CostCenterSeeder` - Cost center tracking
- `EnhancedChartOfAccountsSeeder` - 50+ accounts for complete accounting

**Routes** (`/accounting`): Journal entries, ledgers, trial balance, P&L, balance sheet, cash flow, ratios, revaluation, periods, fiscal years, bank reconciliation, and budget. See `php artisan route:list --path=accounting` for full list.

### Compliance & AML

**CDD Levels** (`CddLevel` enum):
- `Simplified` - Transaction < RM 3,000
- `Standard` - RM 3,000 to RM 49,999
- `Enhanced` - ≥ RM 50,000 OR PEP OR Sanction match

**Compliance Flags** (`ComplianceFlagType` enum):
- LargeAmount, SanctionMatch, Velocity, Structuring, EddRequired, PepStatus, HighRiskCustomer, etc.

**Finding Types** (from MonitoringEngine): VelocityExceeded, StructuringPattern, AggregateTransaction, StrDeadline, SanctionMatch, LocationAnomaly, CurrencyFlowAnomaly, CounterfeitAlert, RiskScoreChange

**CTOS Reporting**: Applies to ALL cash transactions (Buy and Sell) ≥ RM 10,000

**Structuring Detection**: 7-day lookback for aggregate transactions (configurable)

**Compliance Routes** (`/compliance`):
- `/compliance` - Compliance dashboard
- `/compliance/flagged` - Flagged transactions review
- `/compliance/edd` - Enhanced Due Diligence records
- `/compliance/edd/create` - New EDD record
- `/str` - Suspicious Transaction Reports

**Compliance API Routes** (`/api/compliance`):

- `/api/compliance/dashboard` - Dashboard KPIs
- `/api/compliance/calendar` - BNM regulatory filing calendar
- `/api/compliance/case-aging` - Case aging summary
- `/api/compliance/audit-trail` - Audit trail with export
- `/api/compliance/findings` - List/filter compliance findings
- `/api/compliance/cases` - Case management CRUD
- `/api/compliance/edd` - EDD records and questionnaires
- `/api/risk/portfolio` - Risk portfolio overview

### Report Generation

BNM compliance reports via Artisan commands:
- `report:msb2` - Daily transaction summary
- `report:lctr` - Large Cash Transaction Report (≥ RM 50k) - **Monthly**
- `report:lmca` - Monthly LMCA
- `report:qlvr` - Quarterly Large Value
- `compliance:rescreen` - Monthly sanctions rescreening

### Test Organization

Tests use `RefreshDatabase` trait and are in `tests/Feature/` and `tests/Unit/`. Key test files:
- `tests/Feature/TransactionWorkflowTest.php` - Transaction creation, approval, cancellation
- `tests/Feature/RealWorldTransactionWorkflowTest.php` - End-to-end transaction scenarios
- `tests/Feature/RouteConsistencyTest.php` - Route/role access verification
- `tests/Feature/AccountingWorkflowTest.php` - Journal entries, periods, closing
- `tests/Feature/StrWorkflowTest.php` - STR creation and workflow
- `tests/Feature/CounterHandoverTest.php` - Till custody transfer
- `tests/Feature/EddWorkflowTest.php` - EDD workflow tests
- `tests/Feature/FiscalYearControllerTest.php` - Fiscal year creation, closing, opening
- `tests/Feature/FinancialStatementControllerTest.php` - Trial balance, P&L, balance sheet, cash flow, ratios
- `tests/Feature/JournalEntryWorkflowTest.php` - Journal draft → pending → posted workflow
- `tests/Unit/AmlRuleTest.php` - AML rule engine
- `tests/Unit/MathServiceTest.php` - BCMath precision
- `tests/Unit/CurrencyPositionServiceTest.php` - Stock/position calculations
- `tests/Unit/AuditServiceTest.php` - Hash chaining verification (`verifyChainIntegrity`)
- `tests/Unit/FinancialRatioServiceTest.php` - Liquidity, profitability, leverage, efficiency ratios
- `tests/Unit/CashFlowServiceTest.php` - Cash flow statement generation
- `tests/Unit/RiskRatingServiceTest.php` - Risk scoring (uses real DB, not facade mocks)
- `tests/Unit/ComplianceServiceTest.php` - CDD levels, sanctions, velocity, structuring (uses real DB)

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
- **Hash Verification**: `AuditService::verifyChainIntegrity()` verifies the tamper-evident chain by recomputing each entry's SHA-256 hash and checking the `previous_hash` chain link. Returns `{valid: bool, broken_at: int|null, message: string}`. Call this method to detect any tampering with audit log entries.
- **RBAC**: Check permissions via enum methods, not string comparison.
- **Services over Controllers**: Business logic belongs in services, not controllers.
- **Encryption**: Use `EncryptionService` with random IV per encryption (IV prepended to ciphertext).
- **File Uploads**: Sanitize filenames with `basename()` or use `Str::uuid()` for naming.
- **Query Parameters**: Use parameterized queries for user-supplied values in LIKE clauses.
- **Cancellation**: ALL transaction cancellations require manager approval.
- **Concurrency**: Use `lockForUpdate()` for position updates to prevent race conditions.
