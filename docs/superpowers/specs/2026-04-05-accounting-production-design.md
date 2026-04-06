# Accounting Production Features - Design Specification

**Date:** 2026-04-05
**Status:** Draft
**Author:** Claude

## Overview

Add production-ready features to the CEMS-MY accounting module:
1. Journal Entry Workflow (Draft → Pending → Posted)
2. Enhanced Chart of Accounts (bank accounts, cost centers, departments)
3. Cash Flow Statement + Financial Ratios
4. Fiscal Year Management (year-end closing, retained earnings)

---

## 1. Journal Entry Workflow

### Current State
- Journal entries are created and immediately "Posted"
- No draft/pending states
- No approval process
- No entry numbering system

### Proposed State

**New Statuses:**
| Status | Description | Who Can Create | Who Can Post |
|--------|-------------|----------------|--------------|
| Draft | Entry created but not submitted | Manager, Admin | Creator |
| Pending | Submitted for approval | Manager, Admin | Approver |
| Approved | Ready to post (auto on approval) | Manager, Admin | - |
| Posted | Entry posted to ledger | Manager, Admin | Poster |
| Reversed | Entry has been reversed | Manager, Admin | - |

**New Fields in `journal_entries` table:**
```php
$table->string('entry_number', 20)->unique()->after('id');
// Format: JE-YYYYMM-XXXX (e.g., JE-202604-0001)
$table->enum('status', ['Draft', 'Pending', 'Posted', 'Reversed'])->default('Draft');
$table->foreignId('created_by')->constrained('users');
$table->foreignId('approved_by')->nullable();
$table->timestamp('approved_at')->nullable();
$table->text('approval_notes')->nullable();
$table->foreignId('cost_center_id')->nullable();
$table->foreignId('department_id')->nullable();
```

**Workflow:**
1. User creates draft entry → Status = Draft
2. User submits for approval → Status = Pending
3. Manager/Admin approves → Status = Posted, creates ledger entries
4. OR Manager/Admin rejects → Status = Draft, returns with notes

**Security:**
- Only Manager/Admin can approve/post entries
- Creator cannot approve their own entries (segregation of duties)
- All actions logged to SystemLog

---

## 2. Enhanced Chart of Accounts

### Current State
- 18 basic accounts (Asset, Liability, Equity, Revenue, Expense)
- No bank accounts, cost centers, or departments
- Simple account_code as primary key

### Proposed State

**New Account Types:**
```php
// Extended account_type enum with subcategories
Asset:
  - Cash (1000-1499) - Bank accounts
  - Receivable (1500-1999) - AR accounts
  - Inventory (2000-2499) - Currency inventory
Liability:
  - Payable (3000-3499) - AP accounts
  - Accrued (3500-3999) - Accruals
Equity:
  - Capital (4000-4499) - Paid-in capital
  - Retained (4500-4999) - Retained earnings
  - Current Year (4999) - Current year P&L
Revenue:
  - Operating (5000-5499) - Core revenue
  - Non-Operating (5500-5999) - Other income
Expense:
  - Direct (6000-6499) - Cost of goods sold
  - Operating (6500-6999) - Opex
  - Financial (7000-7499) - Interest, etc.
```

**New Tables:**

`cost_centers` table:
```php
$table->id();
$table->string('code', 20)->unique();
$table->string('name', 100);
$table->text('description')->nullable();
$table->boolean('is_active')->default(true);
$table->foreignId('department_id');
$table->timestamps();
```

`departments` table:
```php
$table->id();
$table->string('code', 20)->unique();
$table->string('name', 100);
$table->boolean('is_active')->default(true);
$table->timestamps();
```

**New Fields in `chart_of_accounts`:**
```php
$table->string('account_code', 20)->unique();
$table->string('account_name', 255);
$table->enum('account_type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
$table->string('account_class', 50)->nullable(); // 'Cash', 'Receivable', 'Payable', etc.
$table->string('parent_code', 20)->nullable();
$table->boolean('is_active')->default(true);
$table->boolean('allow_journal')->default(true); // Some accounts auto-post
$table->foreignId('cost_center_id')->nullable();
$table->foreignId('department_id')->nullable();
```

**Enhanced Seeder (ChartOfAccountsSeeder):**
Creates 50+ accounts covering:
- Bank accounts (10): Maybank, CIMB, Public Bank, etc.
- Cash accounts by currency
- Receivable accounts by customer type
- Payable accounts by vendor type
- Full expense categories

---

## 3. Cash Flow Statement + Financial Ratios

### Cash Flow Statement

**Direct Method:** Cash receipts - Cash payments

```php
// Cash Flow Categories
Operating Activities:
  - Cash from customer transactions
  - Cash paid to suppliers
  - Cash paid for salaries
  - Cash paid for other operating expenses

Investing Activities:
  - Purchase/sale of fixed assets
  - Investment income received

Financing Activities:
  - Proceeds from loans
  - Loan repayments
  - Dividend payments
```

**Implementation:**
- New `CashFlowService` with methods:
  - `getCashFlowStatement(string $fromDate, string $toDate): array`
  - `getOperatingCashFlow(): string`
  - `getInvestingCashFlow(): string`
  - `getFinancingCashFlow(): string`

### Financial Ratios

**Liquidity Ratios:**
| Ratio | Formula | Description |
|-------|---------|-------------|
| Current Ratio | Current Assets / Current Liabilities | Short-term solvency |
| Quick Ratio | (Current Assets - Inventory) / Current Liabilities | Immediate solvency |
| Cash Ratio | Cash / Current Liabilities | Cash-only solvency |

**Profitability Ratios:**
| Ratio | Formula | Description |
|-------|---------|-------------|
| Gross Profit Margin | (Revenue - COGS) / Revenue | Core profitability |
| Net Profit Margin | Net Income / Revenue | Bottom-line profitability |
| ROE | Net Income / Equity | Return on equity |
| ROA | Net Income / Total Assets | Return on assets |

**Leverage Ratios:**
| Ratio | Formula | Description |
|-------|---------|-------------|
| Debt-to-Equity | Total Debt / Equity | Financial leverage |
| Debt-to-Assets | Total Debt / Total Assets | Asset financing |

**Efficiency Ratios:**
| Ratio | Formula | Description |
|-------|---------|-------------|
| Asset Turnover | Revenue / Total Assets | Asset efficiency |
| Inventory Turnover | COGS / Inventory | Inventory efficiency |

**New `FinancialRatioService`:**
```php
public function getLiquidityRatios(string $asOfDate): array
public function getProfitabilityRatios(string $fromDate, string $toDate): array
public function getLeverageRatios(string $asOfDate): array
public function getEfficiencyRatios(string $fromDate, string $toDate): array
public function getAllRatios(string $asOfDate, string $fromDate, string $toDate): array
```

**New View:** `/accounting/ratios` - Dashboard with all ratios, benchmarks, and trend charts

---

## 4. Fiscal Year Management

### Current State
- Monthly accounting periods only
- No fiscal year concept
- No year-end closing procedures

### Proposed State

**New Table: `fiscal_years`**
```php
$table->id();
$table->string('year_code', 10)->unique(); // 'FY2026'
$table->date('start_date');
$table->date('end_date');
$table->enum('status', ['Open', 'Closed', 'Archived'])->default('Open');
$table->foreignId('closed_by')->nullable();
$table->timestamp('closed_at')->nullable();
$table->timestamps();
```

**Year-End Closing Procedure:**
1. All monthly periods in the year must be closed first
2. Run Trial Balance as of year-end date
3. Create closing entries:
   - Close all Revenue accounts → Income Summary (4998)
   - Close all Expense accounts → Income Summary (4998)
   - Close Income Summary → Retained Earnings (4999)
4. Lock fiscal year (status = Closed)
5. Create opening balances for new fiscal year

**New `FiscalYearService`:**
```php
public function createFiscalYear(string $yearCode, string $startDate, string $endDate): FiscalYear
public function closeFiscalYear(FiscalYear $year): void // Creates closing entries
public function getYearEndReport(string $yearCode): array // Trial balance + P&L
public function openNewFiscalYear(string $yearCode): void // Creates opening entries
```

**New Routes:**
- `GET /accounting/fiscal-years` - List all fiscal years
- `GET /accounting/fiscal-years/{year}` - Year details
- `POST /accounting/fiscal-years/{year}/close` - Close year
- `GET /accounting/fiscal-years/{year}/report` - Year-end report

**Enhanced AccountingPeriod:**
- Add `fiscal_year_id` foreign key
- Period close now validates all prior periods are closed
- Prevent posting to closed periods

---

## Implementation Order

1. **Database Migrations** (foundation)
   - Add columns to journal_entries
   - Create cost_centers, departments, fiscal_years tables
   - Enhance chart_of_accounts with new fields

2. **Models & Seeders**
   - Update JournalEntry, ChartOfAccount models
   - Create CostCenter, Department, FiscalYear models
   - Update seeders with 50+ accounts

3. **Services**
   - JournalEntryWorkflowService (approve, reject, post)
   - Enhanced AccountingService (with cost center support)
   - CashFlowService
   - FinancialRatioService
   - FiscalYearService

4. **Controllers & Routes**
   - JournalEntryController (workflow actions)
   - ChartOfAccountController (CRUD + hierarchy)
   - FinancialStatementController (cash flow, ratios)
   - FiscalYearController

5. **Views**
   - Journal entry workflow (approve/reject UI)
   - Enhanced chart of accounts browser
   - Cash flow statement view
   - Financial ratios dashboard
   - Fiscal year management UI

6. **Tests**
   - Journal entry workflow tests
   - Financial statement calculation tests
   - Year-end closing tests

---

## Files to Create/Modify

### New Files
- `app/Models/CostCenter.php`
- `app/Models/Department.php`
- `app/Models/FiscalYear.php`
- `app/Services/JournalEntryWorkflowService.php`
- `app/Services/CashFlowService.php`
- `app/Services/FinancialRatioService.php`
- `app/Services/FiscalYearService.php`
- `app/Http/Controllers/JournalEntryWorkflowController.php`
- `app/Http/Controllers/FiscalYearController.php`
- `resources/views/accounting/journal/workflow.blade.php`
- `resources/views/accounting/cash-flow.blade.php`
- `resources/views/accounting/ratios.blade.php`
- `resources/views/accounting/fiscal-years.blade.php`
- `database/migrations/2026_04_05_xxxx_*_enhance_journal_entries.php`
- `database/migrations/2026_04_05_xxxx_*_create_cost_centers_departments.php`
- `database/migrations/2026_04_05_xxxx_*_create_fiscal_years.php`
- `database/migrations/2026_04_05_xxxx_*_enhance_chart_of_accounts.php`
- `database/seeders/CostCenterSeeder.php`
- `database/seeders/DepartmentSeeder.php`
- `database/seeders/EnhancedChartOfAccountsSeeder.php`

### Modified Files
- `app/Models/JournalEntry.php`
- `app/Models/ChartOfAccount.php`
- `app/Services/AccountingService.php`
- `app/Services/LedgerService.php`
- `app/Http/Controllers/AccountingController.php`
- `resources/views/accounting/journal/index.blade.php`
- `resources/views/accounting/journal/create.blade.php`
- `resources/views/accounting.blade.php`
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`

---

## Dependencies

- Laravel 10.x (existing)
- MathService (existing, for precision)
- AccountingService (existing)
- LedgerService (existing)
- SystemLog (existing, for audit)

---

## Backward Compatibility

- Existing journal entries (status='Posted') remain valid
- New workflow is optional - can still create directly Posted entries
- All new fields nullable to avoid breaking existing data
- Enhanced chart of accounts can work alongside simple mode
