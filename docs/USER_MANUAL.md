# CEMS-MY User Manual

**Currency Exchange Management System - Malaysia**

**Version**: 2.0
**Last Updated**: April 2026
**Document Type**: User Guide

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [User Roles & Permissions](#3-user-roles--permissions)
4. [Counter/Till Management](#4-countertill-management)
5. [Transaction Management](#5-transaction-management)
6. [Customer Management](#6-customer-management)
7. [Compliance & AML](#7-compliance--aml)
8. [Accounting Operations](#8-accounting-operations)
9. [Reports & Regulatory Filing](#9-reports--regulatory-filing)
10. [Troubleshooting](#10-troubleshooting)
11. [Appendix](#11-appendix)

---

## 1. Introduction

### 1.1 About CEMS-MY

CEMS-MY is a Laravel-based Currency Exchange Management System designed for Malaysian Money Services Businesses (MSB). It provides:

- **Foreign currency trading** (buy/sell transactions)
- **Counter/till management** with opening, closing, and handover workflows
- **BNM AML/CFT compliance** including CDD, STR reporting, and sanctions screening
- **Double-entry accounting** with journal entries, ledger, and financial statements
- **Budget vs actual tracking** and bank reconciliation
- **Multi-role access control** with MFA enforcement

### 1.2 System Access

**Authentication**: Session-based (not token-based)

**MFA Requirement**: ALL users must set up MFA before using the system. There is no option to disable MFA.

**Login URL**: `/login`

**Session Timeout**: Configurable (default 15 minutes idle)

**Supported Browsers**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### 1.3 Navigation Structure

```
Top Navigation Bar
├── Main
│   └── Dashboard
├── Operations
│   ├── Transactions
│   └── Customers
├── Counter Management
│   ├── Counters
│   └── Branches (Admin only)
├── Stock Management
│   ├── Stock & Cash
│   └── Stock Transfers
├── Compliance & AML
│   ├── Compliance Dashboard
│   ├── Compliance Workspace
│   ├── Alert Triage
│   ├── Cases
│   ├── Flagged Transactions
│   ├── EDD Records
│   ├── EDD Templates
│   ├── AML Rules
│   ├── Risk Dashboard
│   ├── STR Studio
│   ├── Compliance Reporting
│   └── STR Reports
├── Accounting
│   ├── Accounting Dashboard
│   ├── Journal Entries
│   ├── Ledger
│   ├── Trial Balance
│   ├── Profit & Loss
│   ├── Balance Sheet
│   ├── Cash Flow
│   ├── Financial Ratios
│   ├── Revaluation
│   ├── Reconciliation
│   ├── Budget
│   ├── Periods
│   └── Fiscal Years
├── Reports
│   ├── Reports Dashboard
│   ├── MSB2 Report
│   ├── LCTR
│   ├── LMCA
│   ├── Quarterly LVR
│   ├── Position Limits
│   └── Report History
└── System
    ├── Tasks
    ├── Transaction Imports
    ├── Audit Log
    ├── Test Results
    ├── Users (Admin only)
    └── Data Breach Alerts (Admin only)
```

---

## 2. Getting Started

### 2.1 First Login

1. Navigate to `/login`
2. Enter your **email** and **password**
3. If MFA is not yet set up, you will be redirected to MFA setup
4. Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)
5. Enter the 6-digit code to verify
6. Upon successful verification, you are logged in

### 2.2 Dashboard Overview

After login, the dashboard displays:

- **Today's Revenue**: Sum of completed transactions
- **Active Counters**: Number of currently open counters
- **Pending Approvals**: Transactions awaiting manager approval
- **Flagged Transactions**: Compliance flags requiring review

---

## 3. User Roles & Permissions

### 3.1 Role Overview

| Role | Description | Key Permissions |
|------|-------------|-----------------|
| **Teller** | Front-line staff conducting transactions | Create transactions, view own transactions, manage own counter session |
| **Manager** | Operations supervisor | Approve large transactions (>=RM 50k), open/close any counter, view reports, manage users |
| **Compliance Officer** | Compliance specialist | Review flagged transactions, manage STR reports, view audit logs, access compliance reports |
| **Admin** | System administrator | Full system access, user management, system configuration |

### 3.2 Role Matrix

| Action | Teller | Manager | Compliance Officer | Admin |
|--------|--------|---------|-------------------|-------|
| Create Transaction | Yes | Yes | No | Yes |
| Approve >=RM 50k | No | Yes | No | Yes |
| View All Transactions | No | Yes | Yes | Yes |
| Open/Close Own Counter | Yes | Yes | No | Yes |
| Open/Close Any Counter | No | Yes | No | Yes |
| Counter Handover | No | Yes (approve) | No | Yes |
| View Compliance Reports | No | Yes | Yes | Yes |
| Review Flagged Transactions | No | No | Yes | Yes |
| Manage STR Reports | No | No | Yes | Yes |
| Manage Users | No | No | No | Yes |
| System Configuration | No | No | No | Yes |
| View Audit Logs | No | No | Yes | Yes |
| Cancel Any Transaction | No | Yes | No | Yes |

### 3.3 Rate Override Limits

Users can apply rate overrides within their role limits:

| Role | Rate Override Limit |
|------|---------------------|
| Teller | +/- 0.5% from base rate |
| Manager | +/- 2.0% from base rate |
| Compliance Officer | View only (no overrides) |
| Admin (Principal Officer) | Unlimited |

Overrides beyond these limits require approval from a higher role.

---

## 4. Counter/Till Management

### 4.1 Counter Concepts

A **Counter** (also called till) is a dedicated cash drawer for conducting currency exchange transactions. Each counter:

- Has a unique code (e.g., TILL-001)
- Can have multiple currency sessions per day
- Tracks opening and closing balances
- Supports handover between users

### 4.2 Opening a Counter

**Who can open**: Any user assigned to the counter (Teller, Manager, Admin)

**Steps**:

1. Navigate to **Counters** or **Stock & Cash**
2. Click **"Open Counter"**
3. Select your **Counter ID**
4. Select the **Currencies** for this session (can add multiple currencies)
5. Enter the **Opening Balance** for each currency (count physical cash first)
   - The system uses `opening_floats` array to store balances per currency
   - Example: `[{"currency": "USD", "amount": "1000.00"}, {"currency": "EUR", "amount": "500.00"}]`
6. Add any **Notes** if needed
7. Click **"Open"**

The system records the opening balance and the counter session begins. Multi-currency support allows tracking separate balances for each currency in a single session.

### 4.3 Closing a Counter

**Who can close**: The user who opened, or any Manager/Admin

**Steps**:

1. Navigate to **Counters**
2. Click **"Close Counter"**
3. Select your **Counter ID**
4. The system shows expected balance based on transactions
5. Enter the **Closing Balance** (count physical cash)
6. System calculates **Variance** (expected vs actual)
7. If variance exists, enter a **Variance Reason**
8. Click **"Close"**

**Variance Types**:
- **Positive**: More cash than expected (rare, investigate immediately)
- **Negative**: Less cash than expected (common causes: counting errors, unrecorded transactions)

**Variance Thresholds**:
- **Yellow**: > RM 100 (requires explanation notes)
- **Red**: > RM 500 (requires supervisor approval)

*Note: Earlier documentation incorrectly stated thresholds of RM 5. The actual thresholds are RM 100 (Yellow) and RM 500 (Red).*

### 4.4 Counter Handover

When transferring custody between users (e.g., shift change), a Manager or Admin initiates the handover:

**Handover Process**:

1. Navigate to **Counters**
2. Click **"Handover"**
3. Select the **Counter ID** being handed over
4. Select the **Outgoing User** (current session holder)
5. Select the **Incoming User** (receiving the counter)
6. Enter **Physical Cash Counts** for each currency (required)
7. Add **Variance Notes** if needed (required if variance > RM 100)
8. **Manager or Admin approval is required** on the route itself - the handover cannot be completed without appropriate role authorization

**Who can perform handover**: Manager or Admin only

**Note**: The system validates the physical count against expected balance and applies variance thresholds. The handover route enforces role-based access control (Manager or Admin) directly.

### 4.5 Counter Status

Navigate to **Counters > Status** to view:

- Currently open counters
- Current session holders
- Currency and balance information
- Session start time

### 4.6 Stock Transfers

Stock transfers move currency inventory between branches.

**Stock Transfer Workflow** (6 stages):

```
[Create (Manager)] --> [Approve BM (Manager)] --> [Approve HQ (Admin)] --> [Dispatch (Admin)] --> [Receive (Admin)] --> [Complete (Admin)]
        |                    |                          |                    |                   |                    |
        v                    v                          v                    v                   v                    v
   [Cancelled]          [Rejected]                  [Rejected]           [Cancelled]        [Cancelled]         [Finalized]
```

| Stage | Role Required | Description |
|-------|--------------|-------------|
| **Create** | Manager | Initiator creates transfer request with items |
| **Approve BM** | Manager | Branch Manager approves the transfer |
| **Approve HQ** | Admin | HQ/Principal Officer final approval |
| **Dispatch** | Admin | Stock is dispatched from source branch |
| **Receive** | Admin | Destination branch receives and confirms items |
| **Complete** | Admin | Transfer is finalized and closed |

**Creating a Stock Transfer**:

1. Navigate to **Stock Transfers**
2. Click **"Create Transfer"**
3. Select **Source Branch** and **Destination Branch**
4. Select **Transfer Type** (Standard, Emergency, Scheduled, Return)
5. Add transfer items (currency, quantity, rate, value)
6. Add notes if needed
7. Click **"Create Transfer"**

**Transfer Types**:
- **Standard**: Normal inter-branch transfer
- **Emergency**: Urgent transfer with expedited approval
- **Scheduled**: Pre-planned transfer at specific time
- **Return**: Return of stock to source branch

---

## 5. Transaction Management

### 5.1 Transaction Types

| Type | Description | Journal Effect |
|------|-------------|----------------|
| **Buy** | Purchase foreign currency from customer (you give MYR, receive foreign currency) | Dr Foreign Currency Inventory, Cr Cash MYR |
| **Sell** | Sell foreign currency to customer (you give foreign currency, receive MYR) | Dr Cash MYR, Cr Foreign Currency Inventory |

### 5.2 Creating a Transaction

**Who can create**: Teller, Manager, Admin

**Steps**:

1. Navigate to **Transactions > New Transaction**
2. Select **Transaction Type** (Buy or Sell)
3. **Select or Create Customer**:
   - Search existing customer by name, ID, or phone
   - Click "+ New Customer" to create a new customer record
4. **Select Currency** (e.g., USD, EUR, GBP, SGD)
5. **Enter Amount**:
   - Option A: Enter foreign amount -> System calculates MYR
   - Option B: Enter MYR amount -> System calculates foreign
6. **Review Exchange Rate** (auto-populated from current rates)
7. **Select Purpose** (e.g., Travel, Business, Remittance, Investment)
8. **Enter Source of Funds** (e.g., Salary, Savings, Business Income, Inheritance)
9. **Add Notes** (optional)
10. Click **"Create Transaction"**

The system automatically:
- Calculates CDD level based on amount and customer risk
- Applies compliance flags if triggered
- Creates journal entries for double-entry accounting

### 5.3 Transaction Workflow

```
[Draft] --> [PendingApproval] --> [Approved] --> [Processing] --> [Completed] --> [Finalized]
    |              |                  |              |               |
    v              v                  v              v               v
[Cancelled]   [Rejected]         [OnHold]     [Failed]        [Reversed]
```

**Status Definitions** (12 total states):

| Status | Description | Next Actions |
|--------|-------------|--------------|
| **Draft** | Initial state, transaction being created, not yet submitted | Submit for approval |
| **PendingApproval** | Awaiting manager approval (>=RM 50k or Enhanced CDD) | Manager approves or rejects |
| **Approved** | Approved and ready for processing | Proceeds to processing |
| **Processing** | Stock movements, accounting, compliance running | Completes or fails |
| **Completed** | All side effects completed, funds exchanged | Can be finalized or refunded |
| **Finalized** | Day-end processed, cannot be modified | No further action |
| **Cancelled** | Cancelled before completion | No further action |
| **Reversed** | Reversed after completion with compensating entries | No further action |
| **Failed** | Processing failed, awaiting recovery | Retry or cancel |
| **Rejected** | Rejected during approval (distinct from cancelled) | No further action |
| **Pending** | Legacy state, awaiting action | Varies |
| **OnHold** | Compliance flag applied, transaction blocked | Compliance review required |

### 5.4 Large Transaction Approval (>=RM 50,000)

Transactions >= RM 50,000 require Manager approval:

1. Teller creates transaction
2. System sets status to **"Pending"**
3. Manager reviews:
   - Customer information and risk rating
   - Transaction purpose and source of funds
   - CDD/EDD documentation
4. Manager clicks **"Approve"** or **"Reject"**
5. If approved, transaction proceeds to completion
6. If rejected, transaction remains Pending with rejection reason

### 5.5 Compliance Flags and Holds

Transactions are automatically placed **OnHold** when:

- Amount >= RM 50,000
- Customer is PEP (Politically Exposed Person)
- Customer is high risk
- Customer matches sanctions list
- Enhanced Due Diligence required

The transaction remains on hold until a Compliance Officer reviews and resolves the flag.

### 5.6 Refunding a Transaction

**Requirements**:
- Original transaction must be **Completed**
- Within 24 hours of original transaction
- Not already refunded
- Not itself a refund

**Steps**:

1. Navigate to the original **Completed** transaction
2. Click **"Refund"** button (appears if refundable)
3. Enter **Refund Reason**
4. Confirm refund

The system:
- Creates a reverse transaction (opposite type)
- Marks original transaction as refunded
- Creates reversing journal entries
- Processes through compliance pipeline

### 5.7 Cancelling a Transaction

**Requirements**:
- Transaction must be **Pending** or **OnHold**
- Cannot cancel Completed transactions (use Refund instead)

**Steps**:

1. Navigate to the transaction
2. Click **"Cancel"**
3. Enter **Cancellation Reason**
4. Confirm cancellation

Manager approval is required for cancellations (segregation of duties).

### 5.8 Rate Overrides

If the displayed rate needs adjustment:

1. Enter the **Override Rate**
2. System shows deviation percentage
3. If deviation exceeds role limit, approval is requested
4. Manager/Admin approves override if needed
5. Transaction proceeds with overridden rate

---

## 6. Customer Management

### 6.1 Customer Data

**Required Information**:

| Field | Description |
|-------|-------------|
| Full Name | As per ID document |
| ID Type | MyKad (Malaysian), Passport (Foreign), Others |
| ID Number | Encrypted at rest |
| Nationality | Country of citizenship |
| Date of Birth | For age verification |
| Phone | Contact number |
| Address | Full address |

**Optional Information**:
- Email
- Occupation
- Employer Name and Address
- Estimated Annual Volume

### 6.2 Customer Risk Rating

System automatically assigns risk levels:

| Risk Rating | Criteria | CDD Level Applied |
|-------------|----------|-------------------|
| **Low** | Regular customer, small transactions | Simplified |
| **Medium** | Moderate volume, occasional large transactions | Standard |
| **High** | Large volumes, PEP, sanctioned, or high-risk country | Enhanced |

### 6.3 CDD Levels (Customer Due Diligence)

| Level | Trigger Condition | Requirements |
|-------|-------------------|--------------|
| **Simplified** | Amount < RM 3,000 AND not PEP/High risk | Basic verification |
| **Standard** | Amount RM 3,000 - RM 49,999 AND not PEP/High risk | Full verification, ID document |
| **Enhanced** | Amount >= RM 50,000 OR PEP OR Sanction match OR High risk | Extended verification, additional docs, senior approval |

### 6.4 PEP Status

Politically Exposed Persons (PEP) are automatically flagged:

- Customer can declare PEP status during onboarding
- System checks against defined PEP lists
- All PEP transactions require Enhanced CDD
- Periodic rescreening occurs

### 6.5 Customer Search

Search customers by:
- Name (partial match)
- ID/Passport number
- Phone number
- Risk rating

### 6.6 Customer Profile

View customer details including:
- Personal information
- Transaction history
- Risk rating and CDD level
- Compliance flags
- KYC documents
- Notes and history

---

## 7. Compliance & AML

### 7.1 Compliance Dashboard

Access: Compliance Officers and Admins

Displays:
- Open compliance flags
- Pending STR reports
- Sanctions screening alerts
- CDD/EDD queue

### 7.2 Compliance Flags

**Flag Lifecycle**:

```
[Open] --> [Under Review] --> [Resolved]
    |              |
    v              v
[Escalated]   [Escalated]
```

**Flag Types**:

| Flag Type | Description | Severity |
|-----------|-------------|----------|
| LargeAmount | Transaction >= RM 50,000 | Medium |
| SanctionsHit | Potential match with sanctions list | Critical |
| Velocity | 24-hour transaction count exceeded | Medium |
| Structuring | Potential splitting of transactions | High |
| EddRequired | Enhanced Due Diligence needed | Medium |
| PepStatus | Customer is PEP | Medium |
| SanctionMatch | Confirmed sanctions match | Critical |
| HighRiskCustomer | Customer has High risk rating | Medium |
| UnusualPattern | Transaction deviates from pattern | Low |
| HighRiskCountry | Customer from high-risk country | Medium |
| RoundAmount | Round number requiring review | Low |
| ProfileDeviation | Volume exceeds customer profile | Low |

**Review Process**:

1. Navigate to **Compliance > Flagged Transactions**
2. Select a flagged transaction
3. Review:
   - Customer profile and risk rating
   - Transaction details
   - Compliance indicators
   - Sanctions screening results
4. Take action:
   - **Clear**: Remove flag, continue transaction
   - **Hold**: Keep on hold for investigation
   - **Escalate**: Escalate to senior compliance
   - **Generate STR**: Create Suspicious Transaction Report

### 7.3 STR Workflow (Suspicious Transaction Report)

**STR Status Lifecycle**:

```
[Draft] --> [Pending Review] --> [Pending Approval] --> [Submitted] --> [Acknowledged]
```

**Creating an STR**:

1. Navigate to **STR** or from a flagged transaction
2. Click **"Create STR"**
3. Select the flagged transaction(s)
4. Enter:
   - Summary of suspicion
   - Supporting evidence
   - Recommended action
5. Click **"Save as Draft"**

**STR Review Process**:

1. **Draft**: Compliance officer drafts the report
2. **Pending Review**: Senior compliance reviews
3. **Pending Approval**: Manager approves
4. **Submitted**: Sent to regulator (BNM)
5. **Acknowledged**: Regulator acknowledges receipt

**Submitting for Review**:

1. From Draft, click **"Submit for Review"**
2. Status changes to **Pending Review**
3. Reviewer clicks **"Approve"** or **"Request Changes"**
4. If approved, status becomes **Pending Approval**
5. Approver submits to regulator

### 7.4 Sanctions Screening

- All new customers are screened against sanctions lists
- Existing customers are rescreened monthly
- Transactions with sanctions hits are immediately placed OnHold
- Critical flags require immediate compliance review

### 7.5 AML Rules Engine

Configurable rules trigger flags based on:

- Transaction amount thresholds
- Velocity limits (transaction count in time window)
- Structuring detection (multiple transactions near threshold)
- Customer risk profile
- Geographic risk factors

---

## 8. Accounting Operations

### 8.1 Chart of Accounts

**18 Default Accounts**:

| Code | Account Name | Category |
|------|-------------|----------|
| 1000 | Cash (MYR) | Asset |
| 2000 | Foreign Currency Inventory | Asset |
| 2100 | Accounts Receivable | Asset |
| 2200 | Other Current Assets | Asset |
| 3000 | Accounts Payable | Liability |
| 3100 | Accruals | Liability |
| 4000 | Capital | Equity |
| 4100 | Retained Earnings | Equity |
| 4200 | Current Year Earnings | Equity |
| 5000 | Forex Trading Revenue | Revenue |
| 5100 | Revaluation Gains | Revenue |
| 6000 | Forex Loss | Expense |
| 6100 | Revaluation Loss | Expense |
| 6200 | Operating Expenses | Expense |

### 8.2 Double-Entry Accounting

Every transaction creates journal entries:

**Buy USD Example** (Rate: 4.75, Amount: USD 1,000):

```
Dr Foreign Currency Inventory (2000)    RM 4,750.00
    Cr Cash - MYR (1000)                    RM 4,750.00
```

**Sell USD Example** (Rate: 4.80, Amount: USD 1,000):

```
Dr Cash - MYR (1000)                    RM 4,800.00
    Cr Foreign Currency Inventory (2000)    RM 4,750.00
    Cr Forex Trading Revenue (5000)            RM 50.00
```

### 8.3 Journal Entry Management

**Viewing Entries**:

1. Navigate to **Accounting > Journal**
2. Filter by date range and account
3. View entry details and supporting documents

**Creating Manual Entries**:

1. Navigate to **Accounting > Journal > Create**
2. Select date and description
3. Add line items (account, debit, credit)
4. Click **"Create Entry"**

**Reversing Entries**:

1. Navigate to **Accounting > Journal > [Entry]**
2. Click **"Reverse"**
3. Enter reversal reason
4. System creates reversing entry

### 8.4 Ledger and Account View

**Chart of Accounts**:

1. Navigate to **Accounting > Ledger**
2. View all accounts with current balances

**Account Ledger Detail**:

1. Navigate to **Accounting > Ledger > [Account Code]**
2. View all transactions posting to this account
3. Running balance shown for each entry

### 8.5 Financial Statements

**Trial Balance**:

1. Navigate to **Accounting > Trial Balance**
2. Select accounting period
3. View all accounts with debit/credit columns

**Profit & Loss Statement**:

1. Navigate to **Accounting > Profit & Loss**
2. Select period (month, quarter, year)
3. View revenue, expenses, and net profit

**Balance Sheet**:

1. Navigate to **Accounting > Balance Sheet**
2. Select as-of date
3. View assets, liabilities, and equity

### 8.6 Revaluation

Monthly currency revaluation calculates unrealized gains/losses:

1. Navigate to **Accounting > Revaluation**
2. Select month to revalue
3. System fetches current exchange rates
4. Review calculated gains/losses per currency
5. Click **"Process Revaluation"**
6. System creates journal entries:
   - Unrealized gains -> Revaluation Gains (5100)
   - Unrealized losses -> Revaluation Loss (6100)

### 8.7 Period Management

**Accounting Periods** are monthly periods for reporting:

1. Navigate to **Accounting > Periods**
2. View all periods and their status (Open/Closed)
3. **Closing a Period**:
   - Validates all entries are posted
   - Prevents new entries in closed periods
   - Requires Manager approval

### 8.8 Budget vs Actual

**Budget Reports**:

1. Navigate to **Accounting > Budget**
2. View budget vs actual by account
3. Variance shown as amount and percentage
4. Filter by period and account

### 8.9 Bank Reconciliation

**Import Bank Statement**:

1. Navigate to **Accounting > Reconciliation > Import**
2. Upload bank statement file
3. System matches transactions to entries

**Reconciliation Process**:

1. View imported transactions vs system entries
2. Mark matched items
3. Identify outstanding items (checks, deposits)
4. Record exceptions
5. Generate reconciliation report

---

## 9. Reports & Regulatory Filing

### 9.1 Available Reports

| Report | Description | Access |
|--------|-------------|--------|
| MSB2 | Daily transaction summary for BNM | Manager, Compliance, Admin |
| LCTR | Large Cash Transaction Report (>=RM 50k) | Manager, Compliance, Admin |
| LMCA | Monthly Large Cash Aggregate | Manager, Compliance, Admin |
| QLVR | Quarterly Large Value Report | Manager, Compliance, Admin |
| PLR | Position Limit Report | Manager, Compliance, Admin |
| Compliance Summary | Flagged transactions, STR status | Compliance, Admin |
| Customer Analysis | Customer transaction patterns | Manager, Compliance, Admin |
| Profitability | Revenue and cost analysis | Manager, Admin |
| Position Limit | Currency position vs limits | Manager, Compliance, Admin |

### 9.2 BNM Report Generation

**MSB2 (Daily Summary)**:

1. Navigate to **Reports > MSB2**
2. Select date
3. Click **"Generate"**
4. Preview and export

**LCTR (Large Cash Transaction Report)**:

1. Navigate to **Reports > LCTR**
2. Select date range
3. System filters transactions >= RM 50,000
4. Preview and export

**LMCA (Monthly Large Cash Aggregate)**:

1. Navigate to **Reports > LMCA**
2. Select month
3. System aggregates large transactions
4. Preview and export

**QLVR (Quarterly Large Value)**:

1. Navigate to **Reports > Quarterly LVR**
2. Select quarter
3. System generates report
4. Preview and export

**PLR (Position Limit)**:

1. Navigate to **Reports > Position Limit**
2. Select date
3. System calculates currency positions vs limits
4. Preview and export

### 9.3 Report Export

Reports can be exported in formats:
- PDF (for filing)
- Excel (for analysis)
- CSV (for data processing)

---

## 10. Troubleshooting

### 10.1 Common Issues

**Cannot Login**
- Verify email and password spelling
- Check if account is active (contact admin)
- Ensure MFA code is correct and not expired
- Clear browser cache and cookies

**MFA Setup Failed**
- Ensure authenticator app time is synchronized
- Try scanning QR code again
- Use recovery codes if available

**Transaction Stuck on Pending**
- Check if manager approval is required
- Contact manager to approve
- Verify user has approval permission

**Counter Cannot Be Opened**
- Check if counter is already open (view status)
- Close existing session first
- Contact manager if session is orphaned

**Transaction Cannot Be Completed**
- Verify customer information is complete
- Check exchange rate is current
- Ensure sufficient stock (for Sell transactions)
- Check if approval is required (>=RM 50k)

**Till Won't Close**
- Complete all pending transactions
- Investigate any variance beyond threshold
- Verify closing balance entry
- Contact manager for variance approval

**Rate Not Updating**
- Check exchange rate service is running
- Contact admin to verify API configuration

### 10.2 Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Insufficient balance" | Not enough foreign currency | Check currency position, source additional stock |
| "Transaction already processed" | Duplicate submission | Refresh page, check transaction status |
| "Unauthorized" | Insufficient permissions | Contact admin for role upgrade |
| "Rate expired" | Exchange rate too old | Refresh rates before creating transaction |
| "Customer not found" | Customer doesn't exist | Create new customer or check spelling |

### 10.3 Getting Help

**Internal Support**:
- Contact system administrator
- Check with your manager
- Review this manual

---

## 11. Appendix

### 11.1 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl + N | New Transaction |
| Ctrl + S | Save (when in form) |
| Ctrl + F | Search/Find |
| Esc | Close/Cancel |
| F1 | Help |

### 11.2 Glossary

| Term | Definition |
|------|------------|
| **Buy** | Purchase foreign currency from customer (you give MYR, receive foreign) |
| **Sell** | Sell foreign currency to customer (you give foreign currency, receive MYR) |
| **CDD** | Customer Due Diligence - verifying customer identity and risk |
| **EDD** | Enhanced Due Diligence - additional verification for high-risk customers |
| **KYC** | Know Your Customer - customer identification process |
| **AML** | Anti-Money Laundering - preventing money laundering |
| **CFT** | Countering Financing of Terrorism - preventing terrorist financing |
| **STR** | Suspicious Transaction Report - report to BNM |
| **PEP** | Politically Exposed Person - high-risk customer type |
| **Counter/Till** | Cash drawer/counter for conducting transactions |
| **Variance** | Difference between expected and actual cash balance |
| **MFAR** | Multi-Factor Authentication - required for all users |

### 11.3 BNM Regulatory Thresholds

| Threshold | Requirement |
|-----------|-------------|
| < RM 3,000 | Simplified CDD |
| RM 3,000 - RM 49,999 | Standard CDD |
| >= RM 50,000 | Manager approval required |
| >= RM 50,000 | Large Transaction Report (LCTR) |
| >= RM 10,000 (cash) | CTOS reporting (Buy and Sell) |
| All cash transactions | CTOS reporting for >= RM 10,000 |

### 11.4 Record Retention

| Record Type | Retention Period |
|-------------|-------------------|
| Transaction records | 7 years |
| Customer records | 7 years from account closure |
| Compliance reports | Permanent |
| Audit logs | 7 years |
| Accounting records | 7 years |

### 11.5 Session Timeouts

| Event | Timeout Action |
|-------|----------------|
| Idle for 15 minutes (default) | Automatic logout |
| MFA verification | Required on each login |
| Rate limit exceeded | Temporary block |

---

## Document Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-04-04 | Initial release | CEMS-MY Team |
| 2.0 | 2026-04-06 | Updated to match actual implementation - session auth, MFA requirement, counter workflow, transaction states, compliance flags, STR workflow, accounting structure, BNM reports | CEMS-MY Team |

---

**END OF USER MANUAL**