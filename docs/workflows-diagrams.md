# CEMS-MY Workflow Diagrams

**Currency Exchange Management System - Malaysia**

**Version**: 2.0
**Last Updated**: April 2026
**Document Type**: Workflow Documentation

---

## Table of Contents

1. [Transaction Workflow](#1-transaction-workflow)
2. [User Authentication Flow](#2-user-authentication-flow)
3. [Compliance/AML Workflow](#3-complianceaml-workflow)
4. [Counter Management Workflow](#4-counter-management-workflow)
5. [Accounting Workflow](#5-accounting-workflow)
6. [Budget Workflow](#6-budget-workflow)
7. [Reconciliation Workflow](#7-reconciliation-workflow)
8. [User Management Workflow](#8-user-management-workflow)
9. [Reporting Workflow (BNM Reports)](#9-reporting-workflow-bnm-reports)

---

## 1. Transaction Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/transactions` | List transactions |
| GET | `/transactions/create` | Create transaction form |
| POST | `/transactions` | Store new transaction |
| POST | `/transactions/{id}/approve` | Approve pending transaction |
| POST | `/transactions/{id}/cancel` | Cancel transaction |
| POST | `/transactions/{id}/confirm` | Confirm large transaction |

### Services

- **CurrencyPositionService** - Manage currency stock positions
- **ComplianceService** - CDD level, hold decisions (supports PEP/sanction override parameters)
- **TransactionMonitoringService** - AML flagging
- **AccountingService** - Journal entry creation

### CDD Level Determination

```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│    amount >= 50000?  ────── YES ──────► [ENHANCED]             │
│           │                                                     │
│          NO                                                      │
│           │                                                     │
│           ▼                                                     │
│    customer is PEP?  ────── YES ──────► [ENHANCED]            │
│           │                                                     │
│          NO                                                      │
│           │                                                     │
│           ▼                                                     │
│    customer is HIGH RISK?  ── YES ──► [ENHANCED]             │
│           │                                                     │
│          NO                                                      │
│           │                                                     │
│           ▼                                                     │
│    amount >= 3000?  ────── YES ──────► [STANDARD]              │
│           │                                                     │
│          NO                                                      │
│           │                                                     │
│           ▼                                                     │
│               [SIMPLIFIED]                                      │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Transaction Lifecycle (Updated)

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│    [Created] ──► [Pending] ──► [Approved] ──► [Completed]           │
│        │             │                │                │             │
│        │             ▼                ▼                ▼             │
│        │        [Cancelled]      [OnHold]         [Cancelled]        │
│        │             │                │                │             │
│        │             │                ▼                ▼             │
│        │             │            [Pending] ──► [Completed]         │
│        │             │                │                             │
│        │             │                ▼                             │
│        │             │           [Cancelled]                       │
│        │             │                                             │
│        └─────────────┴────────────────────────────────────────► [Refunded]
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Status Values**: Draft | Pending | PendingApproval | Approved | Processing | Completed | OnHold | Cancelled | Reversed | Rejected | Failed | Finalized

### Transaction State Machine Transitions (Updated)

| Current State | Valid Transitions |
|--------------|------------------|
| Draft | PendingApproval, Cancelled |
| PendingApproval | Approved, Rejected, Cancelled |
| **Pending** | **Approved, OnHold, Cancelled** *(NEW)* |
| Approved | Processing, Cancelled |
| Processing | Completed, Failed, Cancelled |
| Completed | Finalized, Reversed, Cancelled |
| **OnHold** | **Pending, Approved, Cancelled** *(NEW)* |
| Failed | PendingApproval, Pending, Cancelled |
| Rejected | Cancelled |

### Approval Workflow for Large Transactions (>= RM 50,000)

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│  [Transaction Created with status=Pending]                           │
│           │                                                         │
│           ▼                                                         │
│  ┌─────────────────────┐                                            │
│  │ AML Monitoring Run  │                                            │
│  └─────────────────────┘                                            │
│           │                                                         │
│           ▼                                                         │
│  ┌─────────────────────┐                                            │
│  │ High Priority Flags?│                                            │
│  └─────────────────────┘                                            │
│        │       │                                                     │
│       Yes      No                                                   │
│        │       │                                                   │
│        ▼       ▼                                                    │
│  [BLOCKED]    [Manager Approval Required]                           │
│  (stays       │                                                     │
│   Pending)    ▼                                                     │
│              ┌─────────────────────┐                                  │
│              │ Manager Approves?   │                                  │
│              └─────────────────────┘                                  │
│                    │       │                                        │
│                   Yes      No                                       │
│                    │       │                                        │
│                    ▼       ▼                                        │
│              [Completed]  [Cancelled]                                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Cancellation Journal Entries (Fixed)

For **BUY** transactions:
- Debit: Cash (MYR) - full amount_local
- Credit: Foreign Currency Inventory - full amount_local

For **SELL** transactions (FIXED):
- Debit: Foreign Currency Inventory - **cost_basis** (avg_cost_rate × amount_foreign)
- Credit: Cash (MYR) - sale proceeds (amount_local)
- If gain/loss exists, create additional entry to FOREX_TRADING_REVENUE or FOREX_LOSS

---

## 2. User Authentication Flow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/login` | Show login form |
| POST | `/login` | Process login |
| POST | `/logout` | Logout |
| GET | `/mfa/setup` | MFA setup page |
| POST | `/mfa/setup` | Store MFA secret |
| GET | `/mfa/verify` | MFA verification page |
| POST | `/mfa/verify` | Process MFA code |
| POST | `/mfa/disable` | Disable MFA |
| GET | `/mfa/trusted-devices` | Manage trusted devices |

### Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   [Start]                                                             │
│      │                                                                │
│      ▼                                                                │
│   [Login Form]                                                        │
│      │                                                                │
│      ▼                                                                │
│   ┌─────────────────────┐                                            │
│   │ Credentials Valid? │                                            │
│   └─────────────────────┘                                            │
│        │       │                                                      │
│      Yes      No                                                     │
│        │       │                                                      │
│        ▼       ▼                                                      │
│   ┌──────────┐  [Login Failed]                                       │
│   │MFA Enabled│                                                       │
│   └──────────┘                                                       │
│      │     │                                                          │
│     Yes    No                                                         │
│      │     │                                                          │
│      ▼     ▼                                                          │
│   [Trusted?]  [Dashboard]                                             │
│      │                                                                │
│    Yes│No                                                             │
│      │  │                                                             │
│      ▼  ▼                                                             │
│  [Dashboard] [Enter TOTP]                                             │
│                 │                                                     │
│                 ▼                                                     │
│          ┌──────────────┐                                            │
│          │  Code Valid? │                                             │
│          └──────────────┘                                            │
│             │      │                                                  │
│           Yes     No                                                  │
│             │      │                                                  │
│             ▼      ▼                                                  │
│        [Dashboard] [Retry / Recovery Code]                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**MFA Methods**: TOTP (6-digit) | Recovery Codes (10-digit) | Trusted Device

---

## 3. Compliance/AML Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/compliance` | Compliance dashboard |
| GET | `/compliance/flagged` | Flagged transactions list |
| PATCH | `/compliance/flags/{id}/assign` | Assign flag |
| PATCH | `/compliance/flags/{id}/resolve` | Resolve flag |
| POST | `/compliance/flags/{id}/generate-str` | Generate STR |
| GET | `/str` | STR list |
| POST | `/str` | Store STR |
| POST | `/str/{id}/submit-review` | Submit for review |
| POST | `/str/{id}/submit-approval` | Submit for approval |
| POST | `/str/{id}/approve` | Approve STR (FIXED: sets Submitted not PendingApproval) |
| POST | `/str/{id}/submit` | Submit to goAML |

### Flag Types

| Flag Type | Description |
|-----------|-------------|
| Velocity | 24-hour threshold exceeded |
| Structuring | Multiple small transactions |
| LargeAmount | Aggregate concern |
| EddRequired | Enhanced due diligence required |
| PepStatus | PEP customer |
| SanctionMatch | Sanctions list match |
| HighRiskCustomer | High risk rating |
| RoundAmount | Round amount detection |
| ProfileDeviation | Volume exceeds profile |
| ManualReview | Unusual pattern |

### Flag Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   [Transaction Created]                                               │
│         │                                                            │
│         ▼                                                            │
│   [AML Monitoring]                                                   │
│         │                                                            │
│         ▼                                                            │
│   [Flag Created]                                                     │
│         │                                                            │
│         ▼                                                            │
│   ┌──────────┬───────────┬───────────┐                              │
│   │   OPEN   │UNDERREVIEW│ RESOLVED  │                              │
│   └──────────┴───────────┴───────────┘                              │
│       │           │            │                                    │
│       ▼           ▼            ▼                                     │
│   [Assign]   [Review]    [Clear/False Positive]                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### STR Lifecycle (Fixed)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│   [Draft] ──► [PendingReview] ──► [PendingApproval] ──► [Submitted] ──► [Acknowledged]
│       │              │                      │                   │
│       ▼              ▼                      ▼                   ▼
│   [Edit/Delete]  [Submit for         [Approve/Reject]    [Submit to goAML]
│                   Review]
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

**Key Fix**: After approval, STR status transitions to `Submitted` (not `PendingApproval`) for BNM goAML submission within 3 working days.

---

## 4. Counter Management Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/counters` | List counters |
| POST | `/counters/{id}/open` | Open counter |
| POST | `/counters/{id}/close` | Close counter |
| GET | `/counters/{id}/status` | Get counter status |
| GET | `/counters/{id}/history` | Session history |
| POST | `/counters/{id}/handover` | Process handover |

### Services: CounterService

**Counter Session Status**: Open | Closed | HandedOver

### Variance Thresholds

| Threshold | Amount | Requirement |
|-----------|--------|-------------|
| Yellow | RM 100 | requires notes |
| Red | RM 500 | requires supervisor approval |

### Counter Lifecycle (Updated)

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│      [Closed]                                                         │
│         │                                                             │
│         ▼                                                             │
│   [Open Session] ──► [Enter Floats]                                  │
│         │                                                             │
│         ▼                                                             │
│   [Active Counter]                                                    │
│         │                                                             │
│    ┌────┴────┐                                                        │
│    │         │                                                        │
│    ▼         ▼                                                        │
│  [Close]  [Handover]                                                  │
│    │         │                                                         │
│    ▼         ▼                                                         │
│  [Verify] [Manager]                                                    │
│    │      Approval                                                     │
│    │         │                                                         │
│    ▼         ▼                                                         │
│  Varianc  [Physical]                                                   │
│    │       Count                                                      │
│    │         │                                                         │
│    ▼         ▼                                                         │
│  Yellow?    [New Session Opened for Receiver]                         │
│    │                                                             │
│   Yes│No                                                             │
│    │  │                                                              │
│    ▼  ▼                                                              │
│ [Notes] [Closed]                                                      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### TillBalance Handling During Handover (Fixed)

**Previous Issue**: TillBalance records were deleted during handover, destroying audit trail.

**Fixed Behavior**:
1. Existing open TillBalance record is updated with closing info (closing_balance, variance, closed_at, closed_by, notes)
2. Handover details stored in `notes` field as JSON: `{type, from_user, to_user, supervisor, handover_time, physical_count, previous_opening, variance}`
3. CounterHandover table captures variance_myr and other handover metadata
4. Same TillBalance record is re-opened with new `opened_by` user

**Result**: Audit trail preserved while maintaining unique constraint on (till_id, date, currency_code).

### Permissions

| Action | Allowed Roles |
|--------|---------------|
| Open counter | Authenticated user |
| Close counter | Manager or Admin |
| Handover | Manager or Admin |

---

## 5. Accounting Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/journal` | List journal entries |
| POST | `/accounting/journal` | Store journal entry |
| POST | `/accounting/journal/{id}/reverse` | Reverse entry |
| GET | `/accounting/ledger` | Chart of accounts |
| GET | `/accounting/ledger/{code}` | Account ledger detail |
| GET | `/accounting/trial-balance` | Trial balance |
| GET | `/accounting/profit-loss` | P&L statement |
| GET | `/accounting/balance-sheet` | Balance sheet |
| POST | `/accounting/revaluation/run` | Run revaluation |
| POST | `/accounting/periods/{id}/close` | Close period |

### Chart of Accounts

| Code Range | Type | Examples |
|------------|------|----------|
| 1000-2200 | Assets | Cash (MYR), Cash (Foreign), Bank, Currency Inventory, Receivables |
| 3000-3100 | Liabilities | Accounts Payable, Accruals |
| 4000-4200 | Equity | Capital, Retained Earnings |
| 5000-5100 | Revenue | Forex Trading Revenue, Revaluation Gains |
| 6000-6200 | Expenses | Forex Loss, Revaluation Loss, Operating Expenses |

### Journal Entry Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   [Create Entry]                                                      │
│        │                                                              │
│        ▼                                                              │
│   ┌─────────────────────┐                                           │
│   │ Debits = Credits?   │                                           │
│   └─────────────────────┘                                           │
│        │       │                                                      │
│      Yes      No                                                     │
│        │       │                                                      │
│        │       ▼                                                      │
│        │   [Show Error]                                               │
│        │                                                              │
│        ▼                                                              │
│   [Post to Ledger]                                                    │
│        │                                                              │
│        ▼                                                              │
│   [Update Account Balances]                                          │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Period Closing Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   [Select Period]                                                     │
│        │                                                              │
│        ▼                                                              │
│   ┌──────────────────────────┐                                      │
│   │ All Entries Balanced?    │                                      │
│   └──────────────────────────┘                                      │
│        │       │                                                      │
│      Yes      No                                                     │
│        │       │                                                      │
│        │       ▼                                                      │
│        │   [Show Error]                                               │
│        │                                                              │
│        ▼                                                              │
│   [Create Closing Entries]                                           │
│        │                                                              │
│        ▼                                                              │
│   [Close Period]                                                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Permissions**: Manager, Admin

---

## 6. Budget Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/budget` | View budget report |
| POST | `/accounting/budget` | Store budget |
| PUT | `/accounting/budget/{id}` | Update budget |

### Services: BudgetService

### Budget vs Actual Calculation

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   Variance = Budget Amount - Actual Amount                          │
│   Variance % = (Variance / Budget Amount) × 100                     │
│                                                                      │
│   IF Actual > Budget ──► Over Budget (negative variance)           │
│   IF Actual < Budget ──► Under Budget (positive variance)           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Period Code Format**: YYYY-MM (e.g., "2024-01")

---

## 7. Reconciliation Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/reconciliation` | View reconciliation |
| POST | `/accounting/reconciliation/import` | Import bank statement |
| POST | `/accounting/reconciliation/{id}/exception` | Mark as exception |
| GET | `/accounting/reconciliation/report` | Generate report |

### Services: ReconciliationService

**Match Status**: unmatched | matched | exception

### Check Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   [Issued] ──► [Presented] ──► [Cleared]                            │
│       │                                                  │  │        │
│       │                                                  └───┘       │
│       ▼                                                             │
│   [Stopped]                                                         │
│                                                                      │
│   [Presented] ──► [Returned]                                        │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Permissions**: Manager, Admin

---

## 8. User Management Workflow

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | List users |
| POST | `/users` | Store new user |
| GET | `/users/{id}` | View user |
| PUT | `/users/{id}` | Update user |
| DELETE | `/users/{id}` | Delete user |
| POST | `/users/{id}/toggle` | Toggle active status |

### User Roles

| Role | Description |
|------|-------------|
| Admin | Full system access |
| Manager | Approval authority, counter management |
| ComplianceOfficer | Compliance review authority |
| Teller | Basic transaction creation |

### Password Requirements

Minimum 12 characters containing: lowercase, uppercase, digit, special character

### Deletion Protections

- Cannot delete last admin
- Cannot delete self
- Cannot deactivate last admin

**Permissions**: Admin only, MFA required

---

## 9. Reporting Workflow (BNM Reports)

### Entry Points

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/reports/lctr` | LCTR report view |
| GET | `/reports/lctr/generate` | Generate LCTR |
| GET | `/reports/msb2` | MSB2 report view |
| GET | `/reports/msb2/generate` | Generate MSB2 |
| GET | `/reports/lmca` | LMCA report view |
| GET | `/reports/lmca/generate` | Generate LMCA |
| GET | `/reports/quarterly-lvr` | Quarterly LVR view |
| GET | `/reports/quarterly-lvr/generate` | Generate QLVR |
| GET | `/reports/position-limit` | Position limit report |
| GET | `/reports/position-limit/generate` | Generate PLR |
| GET | `/reports/history` | Report history |
| GET | `/reports/download/{file}` | Download report |

### Report Types

| Report | Description | Threshold |
|--------|-------------|-----------|
| LCTR | Large Cash Transaction Report | >= RM 50,000 (Monthly) |
| MSB2 | Daily MSB Summary | All transactions |
| LMCA | Monthly LMCA Form | Monthly |
| QLVR | Quarterly Large Value Report | >= RM 50,000 (Quarterly) |
| PLR | Position Limit Report | All positions |

### CTOS Reporting Threshold

**RM 10,000** - All cash transactions (Buy and Sell)

**Permissions**: Manager, Admin

---

## Document History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-04-06 | Initial workflow documentation | CEMS-MY Team |
| 2.0 | 2026-04-12 | Updated with logical inconsistency fixes (STR workflow, OnHold path, SELL cancellation, TillBalance preservation, state machine transitions) | CEMS-MY Team |

---

**END OF WORKFLOW DIAGRAMS**

*Generated: 2026-04-12 | CEMS-MY Workflow Documentation*