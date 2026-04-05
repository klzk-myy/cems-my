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
| `TillBalance` | Daily till opening/closing |
| `FlaggedTransaction` | AML alerts requiring review |
| `StrReport` | Suspicious Transaction Reports |

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
- `tests/Feature/UserManagementTest.php` - RBAC verification
- `tests/Unit/MathServiceTest.php` - BCMath precision

## Important Conventions

- **Money**: Always use `MathService` or BCMath functions. Never use PHP `float` for currency.
- **Enums**: All magic strings (statuses, types, roles) should be converted to PHP enums.
- **Audit**: Critical operations must create `SystemLog` entries.
- **RBAC**: Check permissions via enum methods, not string comparison.
- **Services over Controllers**: Business logic belongs in services, not controllers.
