# Multi-Branch Management Design

## Overview

**Type:** Feature — Multi-Branch Support
**Date:** 2026-04-08
**Approach:** Shared COA + Branch Dimension (Approach A)

Enable CEMS-MY to manage multiple branches (head office + branches), where each branch operates as a standalone entity for transactions, counters, and accounting, while customer data remains centralized. Admin users see consolidated reports across all branches; branch managers see only their own branch.

## Architecture

```
Branches
├── HQ (head_office, is_main=true) — Admin only
├── BR001 (branch)
├── BR002 (branch)
└── BR003 (sub_branch)

Shared/Centralized (no branch scope)
├── customers
├── aml_rules
├── sanction_lists
├── edd_templates
├── compliance_cases
├── alerts
└── flagged_transactions

Branch-Scoped
├── users (branch_id FK)
├── counters (branch_id FK)
├── transactions (branch_id FK)
├── journal_entries (branch_id FK)
├── journal_lines (branch_id FK)
├── currency_positions (branch_id FK)
├── till_balances (branch_id FK)
└── str_reports (branch_id FK — already exists)
```

## Data Model

### Branch Model

File: `app/Models/Branch.php`

```php
class Branch extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'address', 'city', 'state',
        'postal_code', 'country', 'phone', 'email',
        'is_active', 'is_main',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    // Relationships
    public function users(): HasMany
    public function counters(): HasMany
    public function transactions(): HasMany
    public function journalEntries(): HasMany
    public function currencyPositions(): HasMany
    public function tillBalances(): HasMany
    public function counterSessions(): HasManyThrough
    public function parent(): BelongsTo
    public function children(): HasMany
}
```

**Constants:**
- `TYPE_HEAD_OFFICE = 'head_office'`
- `TYPE_BRANCH = 'branch'`
- `TYPE_SUB_BRANCH = 'sub_branch'`

### Branch-Scoped FKs

| Table | Column | Migration |
|-------|--------|-----------|
| `users` | `branch_id` | Already in `2026_04_09_000001_create_branches_table.php` |
| `counters` | `branch_id` | New migration |
| `transactions` | `branch_id` | New migration |
| `journal_entries` | `branch_id` | Already in migration |
| `journal_lines` | `branch_id` | New migration |
| `currency_positions` | `branch_id` | New migration |
| `till_balances` | `branch_id` | New migration |

**Centralized (unchanged — no branch_id):**
- `customers`, `customer_documents`, `sanction_lists`, `aml_rules`, `edd_templates`, `compliance_cases`, `alerts`, `flagged_transactions`

## Access Control

### Role Methods on UserRole Enum

```php
// In app/Enums/UserRole.php
public function canManageAllBranches(): bool  // Admin only
public function canAccessBranch(Branch $branch, User $user): bool  // branch match or Admin
```

### Middleware: CheckBranchAccess

Applied to branch-scoped routes. Non-Admin users can only access resources where `branch_id` matches their assigned `branch_id`.

### Navigation Changes

**New System section entry (Admin only):**
```
System
├── Tasks
├── Audit Log
├── Users
└── Branches     ← NEW (Admin only, role:admin)
```

**Header branch selector** (Admin only): dropdown — "All Branches" | "HQ" | "BR001" | "BR002" | ...

**Manager/Teller view:** No branch selector shown; all queries auto-scoped to their `branch_id`.

## Branch CRUD UI (Admin)

Routes:

| URI | Method | Purpose | Access |
|-----|--------|---------|--------|
| `/branches` | GET | List all branches | Admin |
| `/branches/create` | GET | Create form | Admin |
| `/branches` | POST | Create branch | Admin |
| `/branches/{id}` | GET | Branch detail | Admin |
| `/branches/{id}/edit` | GET | Edit form | Admin |
| `/branches/{id}` | PUT/PATCH | Update branch | Admin |
| `/branches/{id}` | DELETE | Deactivate branch | Admin |

**Branch Detail page includes:**
- Overview (code, name, type, address, contact, status)
- Counters list
- Users list
- Recent transactions (last 10)
- Recent journal entries (last 10)

**Deactivation:** Sets `is_active = false`. Does not delete — foreign keys use `cascadeOnDelete()` only for nullable relationships; existing data is retained.

## Branch-Scoped Accounting

### Shared COA

All branches share the same 18-account Chart of Accounts (existing `ChartOfAccount` model, no changes).

### Report Filtering

| Report | Branch Filter | Admin Default | Manager Default |
|--------|--------------|---------------|-----------------|
| Trial Balance | Yes | All Branches | Own branch |
| P&L | Yes | All Branches | Own branch |
| Balance Sheet | Yes | All Branches | Own branch |
| Cash Flow | Yes | All Branches | Own branch |
| Ledger | Yes | All Branches | Own branch |
| Financial Ratios | Yes | All Branches | Own branch |

**For Admin:** When "All Branches" selected, reports aggregate all branch data in real-time (sum/group by branch_id).
**For Manager:** Branch selector hidden, always shows own branch only.

### LedgerService Changes

```php
// Accept optional branch_id parameter
public function getLedgerEntries(..., ?int $branchId = null): Collection
public function getTrialBalance(..., ?int $branchId = null): Collection
public function getProfitLoss(..., ?int $branchId = null): array
```

Same pattern for `FinancialRatioService`, `CashFlowService`.

## API Endpoints

```
GET    /api/v1/branches                  — list all (Admin)
POST   /api/v1/branches                  — create (Admin)
GET    /api/v1/branches/{id}             — get one
PUT    /api/v1/branches/{id}             — update (Admin)
DELETE /api/v1/branches/{id}            — deactivate (Admin)

GET    /api/v1/branches/{id}/counters
GET    /api/v1/branches/{id}/users
GET    /api/v1/branches/{id}/transactions
GET    /api/v1/branches/{id}/journal-entries

GET    /api/v1/reports/trial-balance?branch_id=X|all
GET    /api/v1/reports/profit-loss?branch_id=X|all
GET    /api/v1/reports/balance-sheet?branch_id=X|all
GET    /api/v1/reports/cash-flow?branch_id=X|all
GET    /api/v1/reports/ratios?branch_id=X|all
```

## Services

### BranchService
- `listBranches()` — all active branches
- `createBranch(array $data)` — create new branch
- `updateBranch(Branch $branch, array $data)` — update
- `deactivateBranch(Branch $branch)` — soft deactivation
- `getBranchSummary(Branch $branch)` — counters, users, recent activity

### BranchScopeService
- `scopeToUserBranch(Builder $query, User $user)` — applies branch filter based on user context
- `getAccessibleBranchIds(User $user)` — returns branch IDs user can access

### Updated Services
- `LedgerService` — accepts `?int $branchId` on relevant methods
- `FinancialRatioService` — accepts `?int $branchId`
- `CashFlowService` — accepts `?int $branchId`
- `ReportingService` — filters report data by branch

## Database Migration

New migration `2026_04_11_xxxxxx_add_branch_scope_columns.php`:

```php
Schema::table('counters', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
    $table->index('branch_id');
});

Schema::table('transactions', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
    $table->index('branch_id');
});

Schema::table('journal_lines', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
    $table->index('branch_id');
});

Schema::table('currency_positions', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
    $table->index('branch_id');
});

Schema::table('till_balances', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
    $table->index('branch_id');
});
```

**Seeding:** `BranchSeeder` already exists — may need update for additional fields (`address`, `state`, `postal_code`, `phone`, `email`).

## File Inventory

| File | Action |
|------|--------|
| `app/Models/Branch.php` | Create |
| `app/Services/BranchService.php` | Create |
| `app/Services/BranchScopeService.php` | Create |
| `app/Http/Controllers/BranchController.php` | Create |
| `app/Http/Controllers/Api/V1/BranchController.php` | Create |
| `app/Http/Middleware/CheckBranchAccess.php` | Create |
| `routes/web.php` — `/branches` routes | Add |
| `routes/api_v1.php` — `/api/v1/branches` routes | Add |
| `resources/views/branches/` — list, create, edit, detail views | Create |
| `database/migrations/2026_04_11_xxxxxx_add_branch_scope_columns.php` | Create |
| `database/seeders/BranchSeeder.php` | Update |
| `app/Enums/UserRole.php` | Update (add branch methods) |
| `app/Config/Navigation.php` | Update (add Branches menu) |
| `app/Services/LedgerService.php` | Update |
| `app/Services/FinancialRatioService.php` | Update |
| `app/Services/CashFlowService.php` | Update |
| `app/Services/ReportingService.php` | Update |

## Out of Scope

- Customers remain centralized (no `branch_id` on `customers` table)
- Inter-branch stock transfers (handled by existing `StockTransfer` model)
- Cross-branch customer lookup restrictions
- Separate branch-level users table
- Per-branch COA (all branches share one COA)
- Branch-specific pricing or rate overrides
