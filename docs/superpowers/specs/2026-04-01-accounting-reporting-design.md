# CEMS-MY Accounting & Reporting Modules
## Design Specification

**Date:** 2026-04-01
**Project:** CEMS-MY Money Services Business Edition
**Module:** Comprehensive Accounting and Reporting System
**Stack:** PHP 8.2, Laravel 11, MySQL 8.0, BCMath
**Compliance:** BNM AML/CFT Policy, MIA Standards

---

## 1. Executive Summary

This specification defines the implementation of a comprehensive double-entry accounting system and regulatory reporting module for CEMS-MY. The system will replace the current simplified journal entry logging with a proper ledger system, enable automated month-end revaluation, and generate BNM-compliant LCTR and MSB(2) reports.

---

## 2. Scope

### 2.1 In Scope

| Component | Description |
|-----------|-------------|
| **Double-Entry Accounting** | Full journal entries, ledger, trial balance |
| **Financial Statements** | P&L, Balance Sheet, Trial Balance |
| **Regulatory Reports** | LCTR (monthly), MSB(2) (daily) |
| **Export Formats** | CSV, PDF, Excel |
| **Month-End Revaluation** | Automated with email notifications |
| **Account Management** | Chart of Accounts CRUD |

### 2.2 Out of Scope

- Multi-currency accounting consolidation
- Budget and forecast modules
- Tax computation (deferred to Phase 2)
- Bank reconciliation automation

---

## 3. Database Schema

### 3.1 Chart of Accounts

```sql
CREATE TABLE chart_of_accounts (
    account_code VARCHAR(20) PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    parent_code VARCHAR(20) NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_code) REFERENCES chart_of_accounts(account_code)
);
```

### 3.2 Journal Entries

```sql
CREATE TABLE journal_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED NULL,
    description TEXT NOT NULL,
    status ENUM('Draft', 'Posted', 'Reversed') DEFAULT 'Posted',
    posted_by BIGINT UNSIGNED NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reversed_by BIGINT UNSIGNED NULL,
    reversed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id),
    FOREIGN KEY (reversed_by) REFERENCES users(id),
    INDEX idx_date (entry_date),
    INDEX idx_reference (reference_type, reference_id)
);
```

### 3.3 Journal Lines

```sql
CREATE TABLE journal_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    debit DECIMAL(18, 4) DEFAULT 0,
    credit DECIMAL(18, 4) DEFAULT 0,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code),
    INDEX idx_entry (journal_entry_id),
    INDEX idx_account (account_code)
);
```

### 3.4 Account Ledger

```sql
CREATE TABLE account_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) NOT NULL,
    entry_date DATE NOT NULL,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    debit DECIMAL(18, 4) DEFAULT 0,
    credit DECIMAL(18, 4) DEFAULT 0,
    running_balance DECIMAL(18, 4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_account_date (account_code, entry_date)
);
```

### 3.5 Report Templates

```sql
CREATE TABLE report_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    report_type ENUM('LCTR', 'MSB2', 'Trial_Balance', 'PL', 'Balance_Sheet', 'Currency_Position') NOT NULL,
    template_config JSON,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.6 Generated Reports

```sql
CREATE TABLE reports_generated (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    generated_by BIGINT UNSIGNED NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500) NULL,
    file_format ENUM('CSV', 'PDF', 'XLSX') NOT NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_type_period (report_type, period_start, period_end)
);
```

### 3.7 Chart of Accounts Seed Data

```
ASSETS
1000 - Cash - MYR
1100 - Cash - USD
1200 - Cash - EUR
1300 - Cash - GBP
1400 - Cash - SGD
2000 - Foreign Currency Inventory
2100 - Accounts Receivable
2200 - Prepaid Expenses

LIABILITIES
3000 - Accounts Payable
3100 - Accrued Expenses

EQUITY
4000 - Paid-in Capital
4100 - Retained Earnings
4200 - Unrealized Forex Gains/Losses

REVENUE
5000 - Revenue - Forex Trading
5100 - Revenue - Revaluation Gain

EXPENSES
6000 - Expense - Forex Loss
6100 - Expense - Revaluation Loss
6200 - Expense - Operating
```

---

## 4. Service Architecture

### 4.1 AccountingService

**Location:** `app/Services/AccountingService.php`

**Methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `createJournalEntry(array $lines, string $referenceType, ?int $referenceId)` | Create balanced journal entry | JournalEntry |
| `postJournalEntry(JournalEntry $entry)` | Post draft entry | void |
| `reverseJournalEntry(JournalEntry $entry, string $reason)` | Create reversal entry | JournalEntry |
| `validateBalanced(array $lines)` | Check debits = credits | bool |
| `getAccountBalance(string $accountCode, ?string $date)` | Get account balance | string |

**Internal Flow:**

1. Create `journal_entries` record
2. Create `journal_lines` records (multiple debit/credit)
3. Validate: sum(debits) = sum(credits)
4. Update `account_ledger` (running balances)
5. Log to `system_logs`

### 4.2 LedgerService

**Location:** `app/Services/LedgerService.php`

**Methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `getTrialBalance(?string $date)` | Generate trial balance | array |
| `getAccountLedger(string $accountCode, string $from, string $to)` | Account ledger details | array |
| `getGeneralLedger(string $from, string $to)` | All accounts ledger | array |
| `getProfitAndLoss(string $from, string $to)` | P&L statement | array |
| `getBalanceSheet(string $asOfDate)` | Balance sheet | array |
| `recalculateRunningBalances(string $accountCode)` | Rebuild running balances | void |

### 4.3 ReportingService (Enhanced)

**Location:** `app/Services/ReportingService.php`

**Methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `generateLCTR(string $month)` | Large Cash Transaction Report | array |
| `generateMSB2(string $date)` | Daily MSB(2) report | array |
| `generateCurrencyPositionReport()` | Current positions | array |
| `generateUnrealizedPnLReport()` | Unrealized P&L summary | array |
| `getDailyTransactions(string $date)` | Transactions for date | Collection |
| `getMonthlyTransactions(string $month)` | Transactions for month | Collection |

### 4.4 ExportService

**Location:** `app/Services/ExportService.php`

**Methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `toCSV(array $data, string $filename)` | Generate CSV file | string (path) |
| `toPDF(array $data, string $template, string $filename)` | Generate PDF file | string (path) |
| `toExcel(array $data, string $filename)` | Generate Excel file | string (path) |
| `emailReport(string $to, string $subject, string $filePath)` | Send report email | void |

**Dependencies:**

- `League\Csv` for CSV generation
- `DomPDF` or `Snappy` for PDF generation
- `PhpSpreadsheet` for Excel generation

### 4.5 RevaluationService (Enhanced)

**Location:** `app/Services/RevaluationService.php`

**Methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `runRevaluation(?string $date)` | Execute revaluation | array |
| `scheduleRevaluation()` | Schedule automation | void |
| `getRevaluationStatus(string $month)` | Check status | array |
| `sendRevaluationNotification(array $results)` | Email notification | void |

**Automation Flow:**

1. Laravel Scheduler runs at 23:59 on month-end
2. Fetches closing rates from RateApiService
3. Calculates unrealized P&L per position
4. Creates journal entries automatically
5. Emails manager and compliance officer

---

## 5. Controller Routes

### 5.1 Accounting Routes

**Middleware:** `auth`, `role:manager`

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/accounting/journal` | `accounting.journal` | JournalEntryController@index |
| GET | `/accounting/journal/create` | `accounting.journal.create` | JournalEntryController@create |
| POST | `/accounting/journal` | `accounting.journal.store` | JournalEntryController@store |
| GET | `/accounting/journal/{entry}` | `accounting.journal.show` | JournalEntryController@show |
| POST | `/accounting/journal/{entry}/reverse` | `accounting.journal.reverse` | JournalEntryController@reverse |
| GET | `/accounting/ledger` | `accounting.ledger` | LedgerController@index |
| GET | `/accounting/ledger/{accountCode}` | `accounting.ledger.account` | LedgerController@account |
| GET | `/accounting/trial-balance` | `accounting.trial-balance` | FinancialStatementController@trialBalance |
| GET | `/accounting/profit-loss` | `accounting.profit-loss` | FinancialStatementController@profitLoss |
| GET | `/accounting/balance-sheet` | `accounting.balance-sheet` | FinancialStatementController@balanceSheet |

### 5.2 Reporting Routes

**Middleware:** `auth`, `role:manager`

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/reports/lctr` | `reports.lctr` | ReportController@lctr |
| GET | `/reports/lctr/generate` | `reports.lctr.generate` | ReportController@lctrGenerate |
| GET | `/reports/msb2` | `reports.msb2` | ReportController@msb2 |
| GET | `/reports/msb2/generate` | `reports.msb2.generate` | ReportController@msb2Generate |
| GET | `/reports/currency-position` | `reports.currency-position` | ReportController@currencyPosition |
| GET | `/reports/unrealized-pnl` | `reports.unrealized-pnl` | ReportController@unrealizedPnl |
| POST | `/reports/export` | `reports.export` | ReportController@export |

### 5.3 Revaluation Routes

**Middleware:** `auth`, `role:manager`

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/accounting/revaluation` | `accounting.revaluation` | RevaluationController@index |
| POST | `/accounting/revaluation/run` | `accounting.revaluation.run` | RevaluationController@run |
| GET | `/accounting/revaluation/history` | `accounting.revaluation.history` | RevaluationController@history |

---

## 6. Transaction Integration

### 6.1 Modified Transaction Flow

```
Current Flow (TransactionController::store):
  1. Validate input
  2. Check till is open
  3. Calculate amounts
  4. Compliance checks
  5. Create Transaction record
  6. Update CurrencyPosition
  7. Update TillBalance
  8. Log accounting entries to SystemLog (simplified)
  9. Run monitoring
  10. Commit

New Flow:
  1. Validate input
  2. Check till is open
  3. Calculate amounts
  4. Compliance checks
  5. Create Transaction record
  6. Update CurrencyPosition
  7. Update TillBalance
  8. Create proper JournalEntry with JournalLines
  9. Update AccountLedger
  10. Run monitoring
  11. Commit
```

### 6.2 Buy Transaction Journal Entry

```
Dr Foreign Currency Inventory (2000)    XXX
   Cr Cash - MYR (1000)                          XXX

(Buy XXX foreign currency @ rate)
```

### 6.3 Sell Transaction Journal Entry

```
Dr Cash - MYR (1000)                            XXX
   Cr Foreign Currency Inventory (2000)    XXX
   Cr Revenue - Forex Trading (5000)       XXX (if gain)

OR

Dr Cash - MYR (1000)                            XXX
Dr Expense - Forex Loss (6000)              XXX (if loss)
   Cr Foreign Currency Inventory (2000)    XXX

(Sell XXX foreign currency @ rate)
```

### 6.4 Revaluation Journal Entry

```
If gain:
  Dr Foreign Currency Inventory (2000)    XXX
     Cr Revenue - Revaluation Gain (5100)  XXX

If loss:
  Dr Expense - Revaluation Loss (6100)  XXX
     Cr Foreign Currency Inventory (2000)    XXX
```

---

## 7. Report Formats

### 7.1 LCTR (Large Cash Transaction Report)

**Frequency:** Monthly
**Threshold:** Transactions ≥ RM 25,000

**CSV Fields:**

```
Transaction_ID,Transaction_Date,Transaction_Time,Customer_ID_Type,Customer_ID_Number,
Customer_Name,Customer_Nationality,Transaction_Type,Currency_Code,
Amount_Local,Amount_Foreign,Exchange_Rate,Till_ID,Teller_ID,
Purpose,Source_of_Funds,CDD_Level,Status
```

### 7.2 MSB(2) Daily Report

**Frequency:** Daily
**Purpose:** Statistical submission to BNM

**CSV Fields:**

```
Date,Currency,Buy_Volume_MYR,Buy_Count,Sell_Volume_MYR,Sell_Count,
Avg_Buy_Rate,Avg_Sell_Rate,Opening_Position,Closing_Position
```

### 7.3 Trial Balance Format

```
Account_Code | Account_Name           | Debit      | Credit     | Balance
--------------|------------------------|------------|------------|------------
1000         | Cash - MYR             | 125,000.00 | 98,500.00  | 26,500.00
2000         | Foreign Currency Inv.  | 85,000.00  | 42,000.00  | 43,000.00
5000         | Revenue - Forex        | 0.00       | 12,500.00  | -12,500.00
--------------|------------------------|------------|------------|------------
TOTAL        |                        | 210,000.00 | 210,000.00 | 0.00
```

### 7.4 Profit & Loss Format

```
PROFIT AND LOSS STATEMENT
Period: [From Date] to [To Date]

REVENUE
  Forex Trading Revenue          | 12,500.00
  Revaluation Gain               | 2,300.00
---------------------------------|-----------
Total Revenue                    | 14,800.00

EXPENSES
  Forex Loss                     | (1,200.00)
  Revaluation Loss               | (800.00)
  Operating Expenses             | (500.00)
---------------------------------|-----------
Total Expenses                   | (2,500.00)
---------------------------------|-----------
NET PROFIT/(LOSS)                | 12,300.00
```

### 7.5 Balance Sheet Format

```
BALANCE SHEET
As of: [Date]

ASSETS
  Current Assets
    Cash - MYR                   | 26,500.00
    Cash - USD                   | 15,000.00
    Foreign Currency Inventory   | 43,000.00
    Accounts Receivable          | 5,000.00
---------------------------------|-----------
Total Assets                     | 89,500.00

LIABILITIES
  Accounts Payable               | 3,000.00
  Accrued Expenses               | 1,500.00
---------------------------------|-----------
Total Liabilities                | 4,500.00

EQUITY
  Paid-in Capital                | 50,000.00
  Retained Earnings              | 30,000.00
  Unrealized Forex Gains         | 5,000.00
---------------------------------|-----------
Total Equity                     | 85,000.00
---------------------------------|-----------
Total Liabilities + Equity       | 89,500.00
```

---

## 8. Automation

### 8.1 Console Commands

**RunMonthlyRevaluation**

```php
php artisan revaluation:run [--force]
```

- Runs at 23:59 on month-end
- Calculates unrealized P&L
- Creates journal entries
- Sends email notification

**GenerateDailyMSB2**

```php
php artisan report:msb2 [--date=Y-m-d]
```

- Runs daily at 00:05
- Generates previous day's report
- Auto-saves to storage

**CleanupOldReports**

```php
php artisan reports:cleanup [--days=90]
```

- Runs monthly on 1st at 02:00
- Deletes reports older than 90 days

### 8.2 Kernel Schedule

```php
protected function schedule(Schedule $schedule)
{
    // Month-end revaluation
    $schedule->command('revaluation:run')
        ->monthlyOn(1, '23:59')
        ->when(fn() => now()->isLastOfMonth());

    // Daily MSB(2)
    $schedule->command('report:msb2')
        ->dailyAt('00:05');

    // Weekly trial balance
    $schedule->command('report:trial-balance')
        ->weekly()->sundays()->at('01:00');

    // Monthly cleanup
    $schedule->command('reports:cleanup --days=90')
        ->monthly()->onFirstOfMonth()->at('02:00');
}
```

### 8.3 Email Notifications

**Recipients:**

| Report | Recipients |
|--------|------------|
| Revaluation | Manager + Compliance Officer + Admin |
| MSB(2) | Manager + Admin |
| LCTR | Compliance Officer + Admin |

---

## 9. Views

### 9.1 Directory Structure

```
resources/views/
├── accounting/
│   ├── journal/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── show.blade.php
│   ├── ledger/
│   │   ├── index.blade.php
│   │   └── account.blade.php
│   ├── trial-balance.blade.php
│   ├── profit-loss.blade.php
│   ├── balance-sheet.blade.php
│   └── revaluation/
│       ├── index.blade.php
│       └── history.blade.php
├── reports/
│   ├── lctr.blade.php
│   ├── msb2.blade.php
│   ├── currency-position.blade.php
│   └── unrealized-pnl.blade.php
└── emails/
    ├── revaluation-complete.blade.php
    └── report-ready.blade.php
```

### 9.2 Navigation Updates

Add to sidebar navigation:

```html
<div class="nav-section">
    <h3>Accounting</h3>
    <a href="{{ route('accounting.journal') }}">Journal Entries</a>
    <a href="{{ route('accounting.ledger') }}">General Ledger</a>
    <a href="{{ route('accounting.trial-balance') }}">Trial Balance</a>
    <a href="{{ route('accounting.profit-loss') }}">Profit & Loss</a>
    <a href="{{ route('accounting.balance-sheet') }}">Balance Sheet</a>
    <a href="{{ route('accounting.revaluation') }}">Revaluation</a>
</div>

<div class="nav-section">
    <h3>Reports</h3>
    <a href="{{ route('reports.lctr') }}">LCTR (BNM)</a>
    <a href="{{ route('reports.msb2') }}">MSB(2) Daily</a>
    <a href="{{ route('reports.currency-position') }}">Currency Position</a>
    <a href="{{ route('reports.unrealized-pnl') }}">Unrealized P&L</a>
</div>
```

---

## 10. Testing

### 10.1 Unit Tests

**Test Files:**

```
tests/Unit/
├── AccountingServiceTest.php
├── LedgerServiceTest.php
├── ReportingServiceTest.php
├── ExportServiceTest.php
└── RevaluationServiceTest.php
```

**Key Scenarios:**

- Double-entry always balanced
- Reversal creates mirror entries
- Trial balance always balances
- P&L matches ledger totals
- Balance sheet balances
- LCTR threshold filtering (≥ RM 25,000)
- MSB(2) aggregation accuracy

### 10.2 Feature Tests

**Test Files:**

```
tests/Feature/
├── JournalEntryTest.php
├── TrialBalanceTest.php
├── ProfitLossTest.php
├── BalanceSheetTest.php
├── LctrReportTest.php
├── Msb2ReportTest.php
└── RevaluationAutomationTest.php
```

**Key Scenarios:**

- Manager can create manual journal entry
- Teller cannot access accounting functions
- Revaluation runs on month-end
- Revaluation sends notification
- Reports generate correct files

### 10.3 Coverage Targets

| Component | Target |
|-----------|--------|
| AccountingService | 95% |
| LedgerService | 95% |
| ReportingService | 90% |
| ExportService | 85% |
| Controllers | 80% |

---

## 11. Dependencies

### 11.1 Composer Packages

```json
{
    "require": {
        "league/csv": "^9.0",
        "maatwebsite/excel": "^3.1",
        "barryvdh/laravel-dompdf": "^2.0"
    }
}
```

### 11.2 PHP Extensions

- bcmath (already required)
- intl (for number formatting)
- mbstring (already required)

---

## 12. Security

### 12.1 Access Control

| Role | Journal Entry | Reports | Revaluation |
|------|---------------|---------|-------------|
| Teller | No | No | No |
| Manager | Yes | Yes | Yes |
| Compliance | No | Yes | No |
| Admin | Yes | Yes | Yes |

### 12.2 Audit Trail

All accounting actions logged to `system_logs`:

- Journal entry created
- Journal entry posted
- Journal entry reversed
- Report generated
- Revaluation run

### 12.3 Data Protection

- Financial reports not accessible via direct URL
- Export files stored outside web root
- Report downloads require authentication

---

## 13. Implementation Phases

### Phase 1: Database & Models (Week 1)

- [ ] Create migrations
- [ ] Create Eloquent models
- [ ] Seed Chart of Accounts
- [ ] Unit tests for models

### Phase 2: Core Services (Week 2)

- [ ] AccountingService
- [ ] LedgerService
- [ ] Integrate with TransactionController
- [ ] Unit tests for services

### Phase 3: Controllers & Views (Week 3)

- [ ] AccountingController
- [ ] LedgerController
- [ ] FinancialStatementController
- [ ] Blade views
- [ ] Feature tests

### Phase 4: Reporting (Week 4)

- [ ] ReportingService enhancements
- [ ] ExportService
- [ ] LCTR generation
- [ ] MSB(2) generation
- [ ] Email notifications

### Phase 5: Automation (Week 5)

- [ ] Console commands
- [ ] Kernel schedule
- [ ] RevaluationService enhancements
- [ ] Integration tests

### Phase 6: Testing & Documentation (Week 6)

- [ ] Full test coverage
- [ ] User documentation
- [ ] Compliance review
- [ ] Performance testing

---

## 14. Acceptance Criteria

### 14.1 Accounting

- [ ] Every transaction creates balanced journal entry
- [ ] Trial balance always balances to zero
- [ ] P&L matches sum of revenue/expense accounts
- [ ] Balance sheet matches asset/liability/equity totals
- [ ] Manual journal entries can be created by managers
- [ ] Journal entries can be reversed (creates mirror entry)

### 14.2 Reporting

- [ ] LCTR includes all transactions ≥ RM 25,000
- [ ] LCTR excludes transactions below threshold
- [ ] MSB(2) aggregates correctly by currency
- [ ] Reports export to CSV, PDF, and Excel
- [ ] Email notifications sent on generation

### 14.3 Automation

- [ ] Revaluation runs automatically on month-end
- [ ] MSB(2) generates daily at 00:05
- [ ] Failed tasks logged and alerted
- [ ] Old reports cleaned up after 90 days

### 14.4 Security

- [ ] Role-based access enforced
- [ ] All actions logged to audit trail
- [ ] No direct file access
- [ ] Export files require authentication

---

## 15. Glossary

| Term | Definition |
|------|------------|
| LCTR | Large Cash Transaction Report - Monthly BNM submission |
| MSB(2) | Money Services Business statistical report - Daily BNM submission |
| Trial Balance | List of all account balances (must balance) |
| Journal Entry | Double-entry record with debits and credits |
| Ledger | Chronological record of account transactions |
| Revaluation | Month-end unrealized P&L calculation |

---

**Document Owner:** Development Team
**Review Cycle:** Before each phase
**Last Updated:** 2026-04-01
**Version:** 1.0
