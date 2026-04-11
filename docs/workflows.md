# CEMS-MY Workflow Documentation

Complete workflow documentation for the Currency Exchange Management System (CEMS-MY), a Laravel-based MSB platform for Malaysian Money Services Businesses compliant with Bank Negara Malaysia (BNM) AML/CFT requirements.

## Table of Contents

1. [Transaction Workflow](#1-transaction-workflow)
2. [User Authentication Flow](#2-user-authentication-flow)
3. [Compliance/AML Workflow](#3-complianceaml-workflow)
4. [Counter Management Workflow](#4-counter-management-workflow)
5. [Stock Transfer Workflow](#5-stock-transfer-workflow)
6. [Accounting Workflow](#6-accounting-workflow)
7. [Budget Workflow](#7-budget-workflow)
8. [Reconciliation Workflow](#8-reconciliation-workflow)
9. [User Management Workflow](#9-user-management-workflow)
10. [Reporting Workflow (BNM Reports)](#10-reporting-workflow-bnm-reports)

---

## 1. Transaction Workflow

### Overview
Transactions represent currency buy/sell operations. All transactions require MFA verification and go through compliance checks based on amount and customer risk profile.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/transactions` | GET | TransactionController@index | List transactions |
| `/transactions/create` | GET | TransactionController@create | Create transaction form |
| `/transactions` | POST | TransactionController@store | Store new transaction |
| `/transactions/{id}` | GET | TransactionController@show | View transaction |
| `/transactions/{id}/approve` | POST | TransactionController@approve | Approve pending transaction |
| `/transactions/{id}/cancel` | GET/POST | TransactionController@cancel | Cancel transaction |
| `/transactions/{id}/confirm` | GET/POST | TransactionController@confirm | Confirm large transaction |

### Key Services
- `CurrencyPositionService` - Manage currency stock positions
- `ComplianceService` - CDD level determination, hold decisions
- `TransactionMonitoringService` - AML flagging and monitoring
- `AccountingService` - Journal entry creation for double-entry
- `MathService` - BCMath precision calculations

### Decision Points

#### CDD Level Determination

```
Enhanced CDD: amount >= RM 50,000 OR isPep OR hasSanctionMatch OR riskRating === 'High'
Standard CDD: amount >= RM 3,000 AND amount < RM 50,000 (AND not PEP, no sanctions, not High Risk)
Simplified CDD: amount < RM 3,000 (AND not PEP, no sanctions, not High Risk)
```

#### Hold Decision Logic

```
Enhanced CDD triggers hold:
  - amount >= RM 50,000 → Hold
  - customer is PEP → Hold
  - sanctions match → Hold
  - riskRating === 'High' → Hold

Standard/Simplified CDD → No automatic hold
```

### Status Transitions

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    TRANSACTION LIFECYCLE                    │
    └─────────────────────────────────────────────────────────────┘

    [Created] ──► [Pending] ──► [Completed]
        │              │                │
        │              ▼                ▼
        │         [Cancelled]    [Cancelled]
        │              │                │
        │              │                ▼
        │              │         [Refunded] (creates new transaction)
        │              │
        └──────────────┴─────────────────┼──────────────────────► [OnHold]
                                         │                         │
                                         ▼                         ▼
                                    [Cancelled]              [Completed]
```

### Transaction Status Values
The transaction state machine has 12 states:
- `Draft` - Initial state, transaction being created, not yet submitted
- `PendingApproval` - Submitted and awaiting approval based on amount/role rules
- `Approved` - Approved and ready for processing
- `Processing` - Stock movements, accounting, compliance are running
- `Completed` - All side effects completed
- `Finalized` - Day-end processed, cannot be modified
- `Cancelled` - Cancelled before completion (requires manager approval)
- `Reversed` - Reversed after completion with compensating transactions
- `Failed` - Processing failed, awaiting recovery
- `Rejected` - Rejected during approval (distinct from cancelled)
- `Pending` - Legacy pending state
- `OnHold` - On hold (legacy support, currently returns false)

### Required Permissions

| Action | Required Role | MFA Required |
|--------|---------------|--------------|
| Create transaction | Authenticated user | Yes |
| Approve transaction | Manager or Admin | Yes |
| Cancel transaction | Manager or Admin | Yes |
| Confirm large transaction | Manager | Yes |

### Database Model
- **Model**: `Transaction`
- **Key Fields**: `amount`, `currency`, `type` (buy/sell), `customer_id`, `counter_session_id`, `status`, `cdd_level`, `created_by`
- **Related**: `TransactionConfirmation` (for large transactions), `JournalEntry` (accounting)

---

## 2. User Authentication Flow

### Overview
Authentication includes standard login/password with mandatory MFA for all users (BNM compliance requirement).

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/login` | GET | LoginController@showLoginForm | Show login form |
| `/login` | POST | LoginController@login | Process login |
| `/logout` | POST | LoginController@logout | Logout |
| `/mfa/setup` | GET | MfaController@setup | MFA setup page |
| `/mfa/setup` | POST | MfaController@store | Store MFA secret |
| `/mfa/verify` | GET | MfaController@verify | MFA verification page |
| `/mfa/verify` | POST | MfaController@verify | Process MFA code |
| `/mfa/disable` | POST | MfaController@disable | Disable MFA |
| `/mfa/trusted-devices` | GET | MfaController@trustedDevices | Manage trusted devices |
| `/mfa/trusted-devices/{id}` | DELETE | MfaController@removeTrustedDevice | Remove trusted device |

### Key Services
- `MfaService` - TOTP generation/verification, recovery codes, trusted devices
- `SessionTimeoutMiddleware` - Idle session timeout enforcement

### Authentication Flow Diagram

```
    ┌─────────────────────────────────────────────────────────────────────┐
    │                    AUTHENTICATION FLOWCHART                          │
    └─────────────────────────────────────────────────────────────────────┘

    [Start] ──► [Login Form]
                    │
                    ▼
           ┌───────────────────┐
           │ Credentials Valid?│
           └───────────────────┘
                  │       │
                 Yes      No
                  │       │
                  ▼       ▼
         ┌────────────┐  [Login Failed]
         │ MFA Enabled?│
         └────────────┘
              │      │
             Yes     No
              │      │
              ▼      ▼
    ┌────────────┐  [Dashboard]
    │ Trusted    │
    │ Device?    │
    └────────────┘
        │      │
       Yes     No
        │      │
        ▼      ▼
    [Dashboard]  [Enter TOTP Code]
                      │
                      ▼
              ┌────────────────┐
              │ Code Valid?    │
              └────────────────┘
                  │      │
                 Yes     No
                  │      │
                  ▼      ▼
           [Dashboard]  [Retry / Use Recovery]
```

### MFA Verification Methods
1. **TOTP Code** - 6-digit code from authenticator app (30-second window)
2. **Recovery Codes** - 10-digit codes, single-use
3. **Trusted Device** - Bypasses MFA if device fingerprint matches

### Session Management
- Default session lifetime: 480 minutes (8 hours)
- `session.timeout` middleware tracks `last_activity`
- MFA verification stored in session after successful verification

### Password Requirements
- Minimum 12 characters
- Must include: lowercase, uppercase, digit, special character

### Default Users (from UserSeeder)

| Email | Password | Role |
|-------|----------|------|
| `admin@cems.my` | `Admin@123456` | admin |
| `teller1@cems.my` | `Teller@1234` | teller |
| `manager1@cems.my` | `Manager@1234` | manager |
| `compliance1@cems.my` | `Compliance@1234` | compliance_officer |

---

## 3. Compliance/AML Workflow

### Overview
AML compliance includes Customer Due Diligence (CDD) levels, transaction flagging, and Suspicious Transaction Report (STR) generation.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/compliance` | GET | DashboardController@compliance | Compliance dashboard |
| `/compliance/flagged` | GET | - | Flagged transactions list |
| `/compliance/flags/{id}/assign` | PATCH | - | Assign flag to officer |
| `/compliance/flags/{id}/resolve` | PATCH | - | Resolve flag |
| `/compliance/flags/{id}/generate-str` | POST | - | Generate STR from alert |
| `/str` | GET | StrController@index | STR list |
| `/str/create` | GET | StrController@create | Create STR |
| `/str` | POST | StrController@store | Store STR |
| `/str/{id}/submit-review` | POST | StrController@submitReview | Submit for review |
| `/str/{id}/submit-approval` | POST | StrController@submitApproval | Submit for approval |
| `/str/{id}/approve` | POST | StrController@approve | Approve STR |
| `/str/{id}/submit` | POST | StrController@submit | Submit to goAML |

### Key Services
- `ComplianceService::determineCDDLevel()` - Determine CDD level
- `ComplianceService::checkVelocity()` - 24-hour cumulative check
- `ComplianceService::checkStructuring()` - Detect structuring patterns
- `ComplianceService::checkAggregateTransactions()` - Related transactions threshold
- `ComplianceService::requiresHold()` - Hold decision
- `TransactionMonitoringService::monitorTransaction()` - Create flags based on rules
- `StrReportService::generateFromAlert()` - Generate STR from flagged transaction
- `StrReportService::submitToGoAML()` - Submit to goAML system

### Compliance Monitors (TransactionMonitoringService)

Automated compliance monitoring runs via background jobs:

| Monitor | Purpose | Detection |
| ------- | ------- | -------- |
| `VelocityMonitor` | 24-hour velocity threshold | RM 50,000+ in 24h (High), RM 45,000+ warning |
| `StructuringMonitor` | Transaction aggregation | 3+ transactions under RM 3,000 within 1 hour |
| `SanctionsRescreeningMonitor` | Weekly sanctions rescreening | Customers not screened since latest sanction update |
| `StrDeadlineMonitor` | STR filing deadline | Flags approaching 3 working day deadline |
| `CustomerLocationAnomalyMonitor` | Geographic anomalies | Foreign nationals with multiple currencies or high frequency |
| `CurrencyFlowMonitor` | Round-tripping patterns | Same currency sold then bought within 72 hours (>= RM 5,000) |
| `CounterfeitAlertMonitor` | Counterfeit currency | 30-day lookback for counterfeit flags |

### CDD Levels

```
┌─────────────────────────────────────────────────────────────────┐
│                    CDD LEVEL DETERMINATION                       │
└─────────────────────────────────────────────────────────────────┘

    IF isPep OR hasSanctionMatch → Enhanced CDD
    ELSE IF amount >= RM 50,000 OR riskRating === 'High' → Enhanced CDD
    ELSE IF amount >= RM 3,000 → Standard CDD
    ELSE → Simplified CDD
```

### Compliance Flag Types (ComplianceFlagType Enum)
- `Velocity` - 24-hour threshold exceeded
- `Structuring` - Multiple small transactions in short period
- `LargeAmount` - Aggregate transaction concern
- `EddRequired` - Enhanced due diligence required
- `PepStatus` - PEP customer
- `SanctionMatch` - Sanctions list match
- `HighRiskCustomer` - High risk rating
- `RoundAmount` - Round amount detection
- `ProfileDeviation` - Volume exceeds customer profile
- `ManualReview` - Unusual pattern requires review

### Flag Workflow

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    FLAG LIFECYCLE                           │
    └─────────────────────────────────────────────────────────────┘

    [Transaction Created] ──► [AML Monitoring] ──► [Flag Created]
                                                        │
                                                        ▼
                                                  [Flag Status]
                                                        │
                              ┌─────────────────────────┼─────────────────────────┐
                              │                         │                         │
                              ▼                         ▼                         ▼
                         [Open]                  [UnderReview]               [Resolved]
                              │                         │                         │
                              ▼                         ▼                         ▼
                     [Assign to Officer]    [Compliance Review]     [Clear/False Positive]
                              │                         │                         │
                              └─────────────────────────┴─────────────────────────┘
```

### STR Workflow (StrStatus Enum)

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    STR LIFECYCLE                            │
    └─────────────────────────────────────────────────────────────┘

    [Draft] ──► [PendingReview] ──► [PendingApproval] ──► [Submitted] ──► [Acknowledged]
         │              │                      │                   │
         ▼              ▼                      ▼                   ▼
    [Edit/Delete]  [Submit for         [Approve/Reject]      [Submit to goAML]
                     Review]
```

### Required Permissions
- View compliance dashboard: `ComplianceOfficer`, `Admin`
- Assign/resolve flags: `ComplianceOfficer`, `Admin`
- Generate STR: `ComplianceOfficer`, `Admin`
- STR workflow transitions: `ComplianceOfficer` or `Manager`

---

## 4. Counter Management Workflow

### Overview
Counters (tills) manage daily cash operations including opening, closing, and handover between users.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/counters` | GET | CounterController@index | List counters |
| `/counters/{id}/open` | GET | CounterController@open | Show open form |
| `/counters/{id}/open` | POST | CounterController@open | Open counter |
| `/counters/{id}/close` | GET | CounterController@close | Show close form |
| `/counters/{id}/close` | POST | CounterController@close | Close counter |
| `/counters/{id}/status` | GET | CounterController@status | Get counter status |
| `/counters/{id}/history` | GET | CounterController@history | Session history |
| `/counters/{id}/handover` | GET | CounterController@handover | Show handover form |
| `/counters/{id}/handover` | POST | CounterController@handover | Process handover |

### Key Services
- `CounterService::openSession()` - Open counter with locking
- `CounterService::closeSession()` - Close with variance validation
- `CounterService::initiateHandover()` - Handover between users
- `CounterService::getCounterStatus()` - Get current status
- `CounterService::getAvailableCounters()` - Get available counters

### Counter Session Status
- `Open` - Counter is active
- `Closed` - Counter is closed
- `HandedOver` - Session handed to another user

### Variance Thresholds
- **Yellow threshold**: RM 100 - requires notes
- **Red threshold**: RM 500 - requires supervisor approval

### Counter Lifecycle

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    COUNTER LIFECYCLE                        │
    └─────────────────────────────────────────────────────────────┘

    [Closed] ──► [Open Session] ──► [Active] ──► [Close Session] ──► [Closed]
                    │                                    │
                    │     ┌──────────────────────────────┤
                    │     │                              │
                    ▼     ▼                              ▼
              [Enter Floats]                     [Verify Variance]
                                                       │      │
                                                     Yellow   Red
                                                       │      │
                                                       ▼      ▼
                                                [Notes Required] [Supervisor Approval]
```

### Handover Flow

```
    [Open Counter] ──► [Initiate Handover] ──► [Manager Approval] ──► [New Session]
                           │                         │
                           ▼                         ▼
                     [Physical Count]          [Assign to User]
                           │                         │
                           └─────────────────────────┘
                                    │
                                    ▼
                             [Counter Open]
```

### Required Permissions
- Open counter: Authenticated user
- Close counter: `Manager` or `Admin`
- Handover: `Manager` or `Admin` (supervisor)
- View history: Authenticated user

---

## 5. Stock Transfer Workflow

### Overview
Inter-branch stock transfers with multi-stage approval workflow. Transfer requests originate from branches and require sequential approvals before stock is dispatched and received.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/stock-transfers` | GET | StockTransferController@index | List transfers |
| `/stock-transfers/create` | GET | StockTransferController@create | Create transfer form |
| `/stock-transfers` | POST | StockTransferController@store | Store new transfer |
| `/stock-transfers/{id}` | GET | StockTransferController@show | View transfer |
| `/stock-transfers/{id}/approve-bm` | POST | StockTransferController@approveBm | Branch manager approval |
| `/stock-transfers/{id}/approve-hq` | POST | StockTransferController@approveHq | HQ approval |
| `/stock-transfers/{id}/dispatch` | POST | StockTransferController@dispatch | Dispatch stock |
| `/stock-transfers/{id}/receive` | POST | StockTransferController@receive | Receive stock |
| `/stock-transfers/{id}/complete` | POST | StockTransferController@complete | Complete transfer |

### Transfer Status Values

| Status | Description |
| ------ | ----------- |
| `Requested` | Initial state, created by branch manager |
| `BranchManagerApproved` | Approved by branch manager |
| `HQApproved` | Approved by HQ admin |
| `InTransit` | Stock dispatched and in transit |
| `PartiallyReceived` | Some items received |
| `Completed` | All items received, transfer complete |
| `Cancelled` | Transfer cancelled |
| `Rejected` | Transfer rejected |

### Transfer Types
- `Standard` - Regular scheduled transfer
- `Emergency` - Urgent transfer
- `Scheduled` - Pre-planned transfer
- `Return` - Return transfer

### Stock Transfer Lifecycle

```
    ┌─────────────────────────────────────────────────────────────┐
    │                 STOCK TRANSFER LIFECYCLE                     │
    └─────────────────────────────────────────────────────────────┘

    [Requested] ──► [BranchManagerApproved] ──► [HQApproved]
         │                   │                       │
         ▼                   ▼                       ▼
    [Cancelled]        [Rejected]              [InTransit]
                                                    │
                                        ┌───────────┴───────────┐
                                        │                       │
                                        ▼                       ▼
                              [PartiallyReceived]         [Completed]
                                        │
                                        ▼
                                  [Completed]
```

### Stage Details

| Stage | Actor | Action |
| ----- | ----- | ------ |
| 1. Create | Manager | Creates transfer request with items and quantities |
| 2. BM Approval | Manager | Branch manager approves transfer |
| 3. HQ Approval | Admin | HQ admin approves transfer |
| 4. Dispatch | Admin | Admin marks stock as dispatched |
| 5. Receive | Admin | Admin receives stock at destination |
| 6. Complete | Admin | Finalizes transfer after all items received |

### Required Permissions

- Create transfer: `Manager` or `Admin`
- BM approval: `Manager` or `Admin`
- HQ approval: `Admin` only
- Dispatch: `Admin` only
- Receive: `Admin` only
- Complete: `Admin` only

---

## 6. Accounting Workflow

### Overview
Double-entry accounting with journal entries, period management, currency revaluation, and financial statements.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/accounting/journal` | GET | AccountingController@journal | List journal entries |
| `/accounting/journal/create` | GET | AccountingController@createJournal | Create entry form |
| `/accounting/journal` | POST | AccountingController@storeJournal | Store journal entry |
| `/accounting/journal/{id}` | GET | AccountingController@viewJournal | View journal entry |
| `/accounting/journal/{id}/reverse` | POST | AccountingController@reverseJournal | Reverse entry |
| `/accounting/ledger` | GET | LedgerController@index | Chart of accounts |
| `/accounting/ledger/{code}` | GET | LedgerController@show | Account ledger detail |
| `/accounting/trial-balance` | GET | FinancialStatementController@trialBalance | Trial balance |
| `/accounting/profit-loss` | GET | FinancialStatementController@profitLoss | P&L statement |
| `/accounting/balance-sheet` | GET | FinancialStatementController@balanceSheet | Balance sheet |
| `/accounting/revaluation` | GET | RevaluationController@index | Revaluation management |
| `/accounting/revaluation/run` | POST | RevaluationController@run | Run revaluation |
| `/accounting/periods` | GET | AccountingController@periods | Period management |
| `/accounting/periods/{id}/close` | POST | AccountingController@closePeriod | Close period |

### Key Services
- `AccountingService::createJournalEntry()` - Create balanced entry
- `AccountingService::validateBalanced()` - Validate debits = credits
- `AccountingService::reverseJournalEntry()` - Create reversal entry
- `AccountingService::getAccountBalance()` - Get running balance
- `AccountingService::getAccountActivity()` - Get period activity
- `PeriodCloseService::closePeriod()` - Close accounting period
- `PeriodCloseService::validatePeriodBalances()` - Ensure all entries balanced
- `PeriodCloseService::createClosingEntries()` - Transfer to retained earnings
- `RevaluationService::runRevaluation()` - Revalue currency positions
- `RevaluationService::runRevaluationWithJournal()` - Revalue with accounting entries

### Chart of Accounts Structure

```
    ┌─────────────────────────────────────────────────────────────────┐
    │                    CHART OF ACCOUNTS                            │
    └─────────────────────────────────────────────────────────────────┘

    ASSETS (1000-2200)
    ├── 1000 - Cash (MYR)
    ├── 1100 - Cash (Foreign Currency)
    ├── 1200 - Bank Accounts
    ├── 1300 - Currency Inventory
    ├── 2000 - Accounts Receivable
    └── 2200 - Other Current Assets

    LIABILITIES (3000-3100)
    ├── 3000 - Accounts Payable
    └── 3100 - Accruals

    EQUITY (4000-4200)
    ├── 4000 - Capital
    └── 4200 - Retained Earnings

    REVENUE (5000-5100)
    ├── 5000 - Forex Trading Revenue
    └── 5100 - Revaluation Gains

    EXPENSES (6000-6200)
    ├── 6000 - Forex Loss
    ├── 6100 - Revaluation Loss
    └── 6200 - Operating Expenses
```

### Journal Entry Flow

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    JOURNAL ENTRY FLOW                      │
    └─────────────────────────────────────────────────────────────┘

    [Create Entry] ──► [Validate Debits = Credits] ──► [Save Entry]
                            │                              │
                            ▼                              ▼
                       [Error: Debits       [Post to Ledger]
                        don't equal]                    │
                            │                            ▼
                            ▼                     [Update Account Balances]
                       [Show Error]
```

### Period Closing Flow

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    PERIOD CLOSING FLOW                      │
    └─────────────────────────────────────────────────────────────┘

    [Select Period] ──► [Validate All Entries Balanced] ──► [Create Closing Entries]
                             │                                    │
                             ▼                                    ▼
                        [Error:              [Transfer Revenue/Expense
                         Unbalanced]         to Retained Earnings]
                             │                                    │
                             ▼                                    ▼
                        [Show Error]                      [Close Period]
                                                           │
                                                           ▼
                                                     [Period = Closed]
```

### Required Permissions
- Create journal entry: `Manager`, `Admin`
- Reverse journal entry: `Manager`, `Admin`
- Close period: `Manager`, `Admin`
- Run revaluation: `Manager`, `Admin`
- View financial statements: `Manager`, `Admin`

---

## 6. Budget Workflow

### Overview
Budget vs actual tracking per accounting period and account code.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/accounting/budget` | GET | AccountingController@budget | View budget report |
| `/accounting/budget` | POST | AccountingController@storeBudget | Store budget |
| `/accounting/budget/{id}` | PUT | AccountingController@updateBudget | Update budget |

### Key Services
- `BudgetService::setBudget()` - Create or update budget
- `BudgetService::getBudgetReport()` - Budget vs actual report
- `BudgetService::getAccountsWithoutBudget()` - Expense accounts without budget

### Budget vs Actual Calculation

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    BUDGET CALCULATION                       │
    └─────────────────────────────────────────────────────────────┘

    Variance = Budget Amount - Actual Amount
    Variance % = (Variance / Budget Amount) × 100

    IF Actual > Budget → Over Budget (negative variance)
    IF Actual < Budget → Under Budget (positive variance)
```

### Period Code Format
`YYYY-MM` (e.g., "2024-01" for January 2024)

### Required Permissions
- View budget report: `Manager`, `Admin`
- Create/update budget: `Manager`, `Admin`

---

## 7. Reconciliation Workflow

### Overview
Bank statement import, automatic matching with journal entries, and outstanding check tracking.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/accounting/reconciliation` | GET | AccountingController@reconciliation | View reconciliation |
| `/accounting/reconciliation/import` | POST | AccountingController@importStatement | Import bank statement |
| `/accounting/reconciliation/{id}/exception` | POST | AccountingController@markException | Mark as exception |
| `/accounting/reconciliation/report` | GET | AccountingController@reconciliationReport | Generate report |
| `/accounting/reconciliation/export` | GET | AccountingController@exportReport | Export report |

### Key Services
- `ReconciliationService::importStatement()` - Import bank statement lines
- `ReconciliationService::autoMatch()` - Automatic matching with journal entries
- `ReconciliationService::getReconciliationReport()` - Generate reconciliation report
- `ReconciliationService::createOutstandingCheck()` - Record issued check
- `ReconciliationService::presentCheck()` - Mark check as presented
- `ReconciliationService::clearCheck()` - Mark check as cleared
- `ReconciliationService::stopCheck()` - Stop payment
- `ReconciliationService::returnCheck()` - Return check
- `ReconciliationService::markAsException()` - Mark as exception
- `ReconciliationService::getChecksAgingReport()` - Aging of outstanding checks

### Match Status Values
- `unmatched` - No matching journal entry
- `matched` - Successfully matched
- `exception` - Marked as exception (requires review)

### Check Status Lifecycle

```
    ┌─────────────────────────────────────────────────────────────┐
    │                    CHECK LIFECYCLE                          │
    └─────────────────────────────────────────────────────────────┘

    [Issued] ──► [Presented] ──► [Cleared]
        │            │
        │            ▼
        ▼       [Returned] (insufficient funds)
    [Stopped]
```

### Required Permissions
- View reconciliation: `Manager`, `Admin`
- Import bank statement: `Manager`, `Admin`
- Mark as exception: `Manager`, `Admin`

---

## 8. User Management Workflow

### Overview
User CRUD operations with role-based access control.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/users` | GET | UserController@index | List users |
| `/users/create` | GET | UserController@create | Create user form |
| `/users` | POST | UserController@store | Store new user |
| `/users/{id}` | GET | UserController@show | View user |
| `/users/{id}/edit` | GET | UserController@edit | Edit user form |
| `/users/{id}` | PUT | UserController@update | Update user |
| `/users/{id}` | DELETE | UserController@destroy | Delete user |
| `/users/{id}/toggle` | POST | UserController@toggle | Toggle active status |

### User Roles (UserRole Enum)
- `Teller` - Basic transaction creation
- `Manager` - Approval authority, counter management
- `ComplianceOfficer` - Compliance review authority
- `Admin` - Full system access

### Password Requirements
- Minimum 12 characters
- Must include: lowercase, uppercase, digit, special character

### Deletion Protections
- Cannot delete last admin user
- Cannot delete self
- Cannot deactivate last admin

### Required Permissions
All user management operations: `Admin` only, with MFA verification

---

## 9. Reporting Workflow (BNM Reports)

### Overview
BNM compliance report generation including LCTR, MSB2, LMCA, QLVR, and position limit reports.

### Entry Points

| Route | Method | Controller | Description |
|-------|--------|------------|-------------|
| `/reports/lctr` | GET | ReportController@lctr | LCTR report view |
| `/reports/lctr/generate` | GET | ReportController@generateLctr | Generate LCTR |
| `/reports/msb2` | GET | ReportController@msb2 | MSB2 report view |
| `/reports/msb2/generate` | GET | ReportController@generateMsb2 | Generate MSB2 |
| `/reports/lmca` | GET | ReportController@lmca | LMCA report view |
| `/reports/lmca/generate` | GET | ReportController@generateLmca | Generate LMCA |
| `/reports/quarterly-lvr` | GET | ReportController@quarterlyLvr | Quarterly LVR view |
| `/reports/quarterly-lvr/generate` | GET | ReportController@generateQuarterlyLvr | Generate QLVR |
| `/reports/position-limit` | GET | ReportController@positionLimit | Position limit report |
| `/reports/position-limit/generate` | GET | ReportController@generatePositionLimit | Generate PLR |
| `/reports/history` | GET | ReportController@history | Report history |
| `/reports/download/{filename}` | GET | ReportController@download | Download report |

### Report Types

| Report | Code | Description | Threshold |
|--------|------|-------------|-----------|
| Large Cash Transaction Report | LCTR | Cash transactions >= RM 50,000 | >= RM 50,000 |
| MSB2 Daily Summary | MSB2 | Daily transaction summary by currency | All |
| Monthly LMCA | LMCA | Monthly regulatory compliance | Monthly |
| Quarterly Large Value | QLVR | Quarterly large value transactions | >= RM 50,000 |
| Position Limit Report | PLR | Daily position limit monitoring | All |

### CTOS Reporting Threshold
- **RM 10,000** - All cash transactions (Buy and Sell)

### Required Permissions
- All reports: `Manager` or `Admin` only

---

## File Reference Summary

### Controllers
- `app/Http/Controllers/TransactionController.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/MfaController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/StrController.php`
- `app/Http/Controllers/CounterController.php`
- `app/Http/Controllers/AccountingController.php`
- `app/Http/Controllers/LedgerController.php`
- `app/Http/Controllers/FinancialStatementController.php`
- `app/Http/Controllers/RevaluationController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/DashboardController.php`

### Services
- `app/Services/MfaService.php`
- `app/Services/ComplianceService.php`
- `app/Services/TransactionMonitoringService.php`
- `app/Services/CounterService.php`
- `app/Services/AccountingService.php`
- `app/Services/PeriodCloseService.php`
- `app/Services/RevaluationService.php`
- `app/Services/BudgetService.php`
- `app/Services/ReconciliationService.php`
- `app/Services/StrReportService.php`
- `app/Services/ReportingService.php`
- `app/Services/CurrencyPositionService.php`
- `app/Services/MathService.php`

### Models
- `app/Models/Transaction.php`
- `app/Models/User.php`
- `app/Models/Customer.php`
- `app/Models/CounterSession.php`
- `app/Models/JournalEntry.php`
- `app/Models/FlaggedTransaction.php`
- `app/Models/StrReport.php`
- `app/Models/AccountingPeriod.php`
- `app/Models/Budget.php`
- `app/Models/BankReconciliation.php`
- `app/Models/ReportGenerated.php`
- `app/Models/MfaRecoveryCode.php`
- `app/Models/DeviceComputations.php`

### Enums
- `app/Enums/TransactionStatus.php`
- `app/Enums/UserRole.php`
- `app/Enums/CounterSessionStatus.php`
- `app/Enums/StrStatus.php`
- `app/Enums/CddLevel.php`
- `app/Enums/ComplianceFlagType.php`

### Routes
- `routes/web.php`
- `routes/auth.php`

---

*Generated on: 2026-04-06*
*Project: CEMS-MY (Currency Exchange Management System)*