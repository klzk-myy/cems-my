# CEMS-MY User Manual

**Currency Exchange Management System - Malaysia**

**Version**: 1.0  
**Last Updated**: April 2026  
**Document Type**: User Guide

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [User Roles & Permissions](#3-user-roles--permissions)
4. [Daily Operations](#4-daily-operations)
5. [Transaction Management](#5-transaction-management)
6. [Customer Management](#6-customer-management)
7. [Stock & Cash Management](#7-stock--cash-management)
8. [Compliance & Reporting](#8-compliance--reporting)
   - [8.1 Compliance Dashboard](#81-compliance-dashboard)
   - [8.2 Flagged Transactions](#82-flagged-transactions)
   - [8.3 Generating Reports](#83-generating-reports)
   - [8.4 Enhanced Due Diligence (EDD)](#84-enhanced-due-diligence-edd)
   - [8.5 BNM Compliance Reports](#85-bnm-compliance-reports)
9. [Accounting Operations](#9-accounting-operations)
   - [9.1 Chart of Accounts](#91-chart-of-accounts)
   - [9.2 Journal Entries](#92-journal-entries)
   - [9.3 Journal Entry Workflow](#93-journal-entry-workflow)
   - [9.4 Cash Flow Statement](#94-cash-flow-statement)
   - [9.5 Financial Ratios](#95-financial-ratios)
   - [9.6 Fiscal Year Management](#96-fiscal-year-management)
   - [9.7 Month-End Revaluation](#97-month-end-revaluation)
   - [9.8 Financial Statements](#98-financial-statements)
10. [Troubleshooting](#10-troubleshooting)
11. [Appendix](#11-appendix)

---

## 1. Introduction

### 1.1 About CEMS-MY

CEMS-MY is a comprehensive Currency Exchange Management System designed for Malaysian Money Services Businesses (MSB). It provides:

- **Real-time trading** of foreign currencies
- **Compliance management** for BNM AML/CFT regulations
- **Complete audit trail** of all operations
- **Automated accounting** following MIA standards
- **Multi-role access** for secure operations

### 1.2 System Access

**Login URL**: `https://your-domain.com/login`

**Supported Browsers**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Session Timeout**: 8 hours (480 minutes)

---

## 2. Getting Started

### 2.1 First Login

1. Navigate to the login page
2. Enter your **email** and **password**
3. Click "Login"
4. If MFA is enabled, enter the 6-digit code from your authenticator app

### 2.2 Dashboard Overview

After login, you'll see the main dashboard:

```
┌─────────────────────────────────────────────────────────────┐
│  CEMS-MY Dashboard                                          │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐      │
│  │ Today's  │ │ Active   │ │ Pending  │ │ Flagged  │      │
│  │ Revenue  │ │ Tills    │ │ Approval │ │ Trans.   │      │
│  │ RM 5,250 │ │    3     │ │    2     │ │    1     │      │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘      │
│                                                             │
│  [Navigation Menu]                                          │
│  ├── Dashboard                                              │
│  ├── Transactions                                           │
│  ├── Customers                                              │
│  ├── Stock & Cash                                           │
│  ├── Reports                                                │
│  ├── Accounting                                             │
│  ├── Compliance                                             │
│  └── Settings                                               │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Navigation

- **Top Navigation**: Quick access to common functions
- **Sidebar Menu**: Full navigation by module
- **User Menu**: Profile, settings, and logout

---

## 3. User Roles & Permissions

### 3.1 Role Overview

| Role | Description | Key Permissions |
|------|-------------|-----------------|
| **Teller** | Front-line staff | Create transactions, view customers, manage their own till |
| **Manager** | Operations supervisor | Approve large transactions, manage all tills, view reports |
| **Compliance Officer** | Compliance specialist | Review flagged transactions, access audit logs, generate compliance reports |
| **Admin** | System administrator | Full system access, user management, system configuration |

### 3.2 Role Matrix

| Action | Teller | Manager | Compliance Officer | Admin |
|--------|--------|---------|-------------------|-------|
| Create Transaction | ✅ | ✅ | ❌ | ✅ |
| Approve >RM 50k | ❌ | ✅ | ❌ | ✅ |
| View All Transactions | Own only | ✅ | ✅ | ✅ |
| Open/Close Till | Own only | All | ❌ | ✅ |
| View Compliance Reports | ❌ | ❌ | ✅ | ✅ |
| Manage Users | ❌ | ❌ | ❌ | ✅ |
| System Configuration | ❌ | ❌ | ❌ | ✅ |

---

## 4. Daily Operations

### 4.1 Starting Your Day

**For Tellers:**

1. **Login** to the system
2. **Open Your Till** (Stock & Cash → Open Till)
3. **Enter Opening Balance** (count physical cash)
4. **Verify** the amount matches previous closing

**Step-by-Step: Opening a Till**

1. Navigate to **Stock & Cash**
2. Click **"Open Till"**
3. Select your **Till ID** (e.g., TILL-001)
4. Select **Currency** (e.g., USD)
5. Enter **Opening Balance** (physical count)
6. Add any **Notes** if needed
7. Click **"Open Till"**

### 4.2 Ending Your Day

**For Tellers:**

1. **Complete all pending transactions**
2. **Close Your Till** (Stock & Cash → Close Till)
3. **Enter Closing Balance** (count physical cash)
4. **Review Variance** report
5. **Sign off** if variance is acceptable

**Step-by-Step: Closing a Till**

1. Navigate to **Stock & Cash**
2. Click **"Close Till"**
3. Select your **Till ID**
4. Select **Currency**
5. Enter **Closing Balance** (physical count)
6. Review **Expected vs Actual**
7. System calculates **Variance**
8. If variance ≠ 0, add **Variance Reason**
9. Click **"Close Till"**

**Variance Explanation:**
- **Positive Variance**: More cash than expected (more sales than recorded)
- **Negative Variance**: Less cash than expected (more purchases than recorded)
- **Acceptable Variance**: Within RM 5 (investigate if larger)

---

## 5. Transaction Management

### 5.1 Creating a Transaction

**Step-by-Step: New Transaction**

1. Navigate to **Transactions → New Transaction**
2. **Select Transaction Type**:
   - **Buy**: Purchase foreign currency from customer (you give MYR, receive foreign currency)
   - **Sell**: Sell foreign currency to customer (you give foreign currency, receive MYR)

3. **Select Customer**:
   - Search existing customer by name/IC/passport
   - Or click **"+ New Customer"** to create

4. **Select Currency** (e.g., USD, EUR, GBP)

5. **Enter Amount**:
   - Option A: Enter foreign amount → System calculates MYR
   - Option B: Enter MYR amount → System calculates foreign

6. **Review Exchange Rate** (auto-populated from current rates)

7. **Select Purpose** (e.g., Travel, Business, Remittance)

8. **Enter Source of Funds** (e.g., Salary, Savings, Business Income)

9. **Check Compliance Indicators**:
   - 🟢 Green: Standard processing
   - 🟡 Yellow: Enhanced Due Diligence required
   - 🔴 Red: Sanctions hit detected

10. Click **"Create Transaction"**

### 5.2 Transaction Status

| Status | Description | Action Required |
|--------|-------------|-----------------|
| **Completed** | Transaction finished | None |
| **Pending** | Awaiting manager approval | Manager to approve |
| **OnHold** | Compliance hold | Compliance review |
| **Cancelled** | Transaction cancelled | None |

### 5.3 Large Transaction Approval (≥RM 50,000)

**For Transactions ≥RM 50,000:**

1. Teller creates transaction
2. System sets status to **"Pending"**
3. Manager receives notification
4. Manager reviews:
   - Customer information
   - Transaction purpose
   - Source of funds
   - CDD/EDD level
5. Manager clicks **"Approve"** or **"Reject"**
6. If approved, transaction completes
7. If rejected, status remains "Pending" or changes to "OnHold"

### 5.4 Refunding a Transaction

**Requirements:**
- Transaction must be **Completed**
- Within **24 hours** of original transaction
- Cannot refund a refund

**Steps:**

1. Navigate to **Transactions**
2. Find the transaction to refund
3. Click **"Refund"** button (only appears if refundable)
4. Confirm refund reason
5. System creates reverse transaction
6. Original transaction marked as refunded

### 5.5 Cancelling a Transaction

**Requirements:**
- Transaction must be **Pending** or **OnHold**
- Cannot cancel Completed transactions (use Refund instead)

**Steps:**

1. Navigate to **Transactions**
2. Find the transaction
3. Click **"Cancel"**
4. Enter cancellation reason
5. Confirm cancellation

---

## 6. Customer Management

### 6.1 Creating a New Customer

**Required Information:**

1. **Full Name** (as per ID)
2. **Identification Type**:
   - MyKad (Malaysian citizens)
   - Passport (Foreigners)
   - Other ( specify)
3. **ID Number**
4. **Nationality**
5. **Date of Birth**
6. **Contact Information**:
   - Phone number
   - Email (optional)
   - Address

**Optional Information:**
- Occupation
- Employer
- Estimated monthly volume
- Purpose of transactions

### 6.2 Customer Risk Rating

System automatically assigns risk levels:

| Risk Level | Criteria | CDD Level |
|------------|----------|-----------|
| **Low** | Regular customer, small transactions | Simplified |
| **Medium** | Moderate volume, occasional large transactions | Standard |
| **High** | Large volumes, PEP, complex structures | Enhanced |

### 6.3 Customer Search

**Search Options:**
- Name (partial match)
- ID/Passport number
- Phone number
- Date range

**View Customer Profile:**
- Personal information
- Transaction history
- Risk rating
- Compliance flags
- Notes

---

## 7. Stock & Cash Management

### 7.1 Currency Positions

**Viewing Positions:**

1. Navigate to **Stock & Cash → Positions**
2. See real-time balances for all currencies:

```
┌──────────┬──────────┬─────────────┬─────────────┐
│ Currency │ Balance  │ Avg Cost    │ Unrealized  │
│          │          │ Rate        │ P&L         │
├──────────┼──────────┼─────────────┼─────────────┤
│ USD      │ 15,250.00│ 4.6500      │ +RM 1,525   │
│ EUR      │ 8,500.00 │ 5.1200      │ -RM 340     │
│ GBP      │ 3,200.00 │ 6.0200      │ +RM 128     │
└──────────┴──────────┴─────────────┴─────────────┘
```

### 7.2 Till Balances

**Till Report:**

```
┌────────────────────────────────────────────────┐
│ Till Report - TILL-001                         │
├────────────────────────────────────────────────┤
│ Date: 2026-04-04                              │
│ Currency: USD                                  │
├────────────────────────────────────────────────┤
│ Opening Balance:    5,000.00 USD               │
│ + Purchases (Buy):    +2,500.00 USD             │
│ - Sales (Sell):       -1,200.00 USD             │
│ Expected Balance:     6,300.00 USD              │
│                                                  │
│ Closing Balance:      6,300.00 USD              │
│ Variance:              0.00 USD                 │
│ Status: ✅ Balanced                              │
└────────────────────────────────────────────────┘
```

### 7.3 Reconciliation

**Daily Reconciliation:**

1. Count physical cash for each currency
2. Compare with system expected balance
3. Investigate any variances
4. Record variance reasons
5. Sign off on reconciliation

**Common Variance Causes:**
- Counting errors
- Unrecorded transactions
- Rate rounding differences
- System errors (report immediately)

---

## 8. Compliance & Reporting

### 8.1 Compliance Dashboard

**Access:** Compliance Officers & Admins

**Features:**
- Flagged transactions requiring review
- Sanctions screening hits
- Suspicious activity alerts
- Regulatory report generation

### 8.2 Flagged Transactions

**Review Process:**

1. Navigate to **Compliance → Flagged Transactions**
2. Review flagged transaction
3. Check:
   - Customer profile
   - Transaction details
   - Risk indicators
   - Sanctions screening results
4. Take action:
   - **Clear**: Remove flag, approve transaction
   - **Hold**: Keep on hold for investigation
   - **Report**: Submit STR (Suspicious Transaction Report)

### 8.3 Generating Reports

**Available Reports:**

1. **Transaction Report**
   - Date range
   - Currency filter
   - Status filter
   - Export to CSV/Excel/PDF

2. **Compliance Report**
   - Flagged transactions
   - Large transactions (≥RM 50k)
   - Customer risk summary
   - Sanctions screening log

3. **Financial Report**
   - Revenue summary
   - Currency position
   - P&L statement
   - Cash flow

**Report Generation Steps:**

1. Navigate to **Reports**
2. Select report type
3. Set date range
4. Apply filters
5. Click **"Generate"**
6. Preview or export

### 8.4 Enhanced Due Diligence (EDD)

**When Required:**
- Transactions ≥RM 100,000
- High-risk customers (PEP, complex structures)
- Unusual transaction patterns
- Compliance officer request

**EDD Questionnaire:**

1. **Source of Funds**
   - Salary income
   - Business proceeds
   - Investment returns
   - Inheritance/gift
   - Other (specify)

2. **Purpose of Transaction**
   - Travel/education abroad
   - Business payment
   - Investment
   - Family maintenance
   - Other (specify)

3. **Employment Information**
   - Company name
   - Position
   - Monthly income range

4. **Source of Wealth**
   - Total assets estimate
   - Income sources

**EDD Status Flow:**
- `Incomplete` → `Pending Review` → `Approved` / `Rejected`

**EDD Risk Levels:**
- Low (simplified review)
- Medium (standard review)
- High (enhanced review)
- Critical (immediate escalation)

### 8.5 BNM Compliance Reports

**Required Reports:**
- Daily Transaction Summary
- Large Cash Transaction Report (>RM 50k)
- Suspicious Transaction Reports (STR)
- Monthly Compliance Summary

**Export Format:** BNM-specified formats

---

## 9. Accounting Operations

### 9.1 Chart of Accounts

**Account Structure:**

```
1000 - Cash - MYR
1100 - Cash - USD
1200 - Cash - EUR
...
2000 - Foreign Currency Inventory
2100 - Unrealized Forex Gains/Losses
4000 - Revenue - Forex Trading
4100 - Revenue - Revaluation Gain
5000 - Expense - Revaluation Loss
```

### 9.2 Journal Entries

**Viewing Entries:**

1. Navigate to **Accounting → Journal Entries**
2. View entries by date range
3. Filter by account
4. Export to Excel

**Sample Entry:**

```
Entry #: JE-2026-04-04-001
Date: 2026-04-04 10:30:15
Description: Buy USD 1,000 @ 4.75

Dr Foreign Currency Inventory (1100)    4,750.00
    Cr Cash - MYR (1000)                        4,750.00

Created by: teller01
```

### 9.3 Journal Entry Workflow

**Purpose:** Multi-level approval for manual journal entries before posting

**Workflow States:**
- `Draft` → `Pending` → `Posted` / `Reversed`

**Roles:**
- **Teller/Manager**: Create draft entries
- **Manager**: Submit draft for approval
- **Manager/Admin**: Approve and post entries

**Workflow Steps:**

1. Create journal entry (saved as Draft)
2. Submit entry for approval (status → Pending)
3. Reviewer approves (status → Posted, creates ledger entries)
4. Or: Reviewer rejects (entry remains Pending, can be reversed)

**Viewing Workflow:**

1. Navigate to **Accounting → Workflow**
2. See all pending entries
3. Review entry details
4. Click **Approve** or **Reject**

### 9.4 Cash Flow Statement

**Purpose:** Track actual cash movements across operating, investing, and financing activities

**Sections:**
- **Operating Activities**: Core business cash flows (transactions, compliance)
- **Investing Activities**: Asset purchases/sales, currency revaluation
- **Financing Activities**: Capital contributions, owner drawings

**Generating Cash Flow:**

1. Navigate to **Accounting → Cash Flow**
2. Select date range (start and end dates)
3. Click **"Generate Cash Flow"**
4. View cash movements by category
5. Export to PDF/Excel

### 9.5 Financial Ratios

**Purpose:** Key performance and health metrics

**Ratio Categories:**

**Liquidity Ratios:**
- Current Ratio = Current Assets / Current Liabilities
- Quick Ratio = (Current Assets - Inventory) / Current Liabilities

**Profitability Ratios:**
- Gross Profit Margin = Gross Profit / Revenue × 100
- Net Profit Margin = Net Profit / Revenue × 100
- ROE = Net Profit / Equity × 100
- ROA = Net Profit / Total Assets × 100

**Leverage Ratio:**
- Debt-to-Equity = Total Liabilities / Total Equity

**Efficiency Ratios:**
- Asset Turnover = Revenue / Average Total Assets
- Inventory Turnover = COGS / Average Inventory

**Generating Ratios:**

1. Navigate to **Accounting → Ratios**
2. Select period
3. Click **"Calculate Ratios"**
4. View ratio analysis with benchmarks
5. Export report

### 9.6 Fiscal Year Management

**Purpose:** Manage annual accounting periods and year-end closing

**Features:**
- Create fiscal years (12-month periods)
- View fiscal year report
- Close fiscal year (generate closing entries)
- Track year status (Open, Closed, Archived)

**Year-End Closing Process:**

1. Navigate to **Accounting → Fiscal Years**
2. Select fiscal year to close
3. Click **"Close Year"**
4. System generates closing entries:
   - Close Revenue accounts → Income Summary → Retained Earnings
   - Close Expense accounts → Income Summary → Retained Earnings
5. Year status changes to `Closed`

**Closing Entries Generated:**
```
Dr Revenue (all)     xxx
    Cr Income Summary              xxx

Dr Income Summary    xxx
    Cr Expense (all)              xxx

Dr Income Summary    xxx
    Cr Retained Earnings          xxx
```

### 9.7 Month-End Revaluation

**Purpose:** Calculate unrealized gains/losses on currency positions

**Steps:**

1. Navigate to **Accounting → Revaluation**
2. Select month to revalue
3. System fetches current exchange rates
4. Review calculated gains/losses
5. Click **"Process Revaluation"**
6. System creates journal entries

### 9.8 Financial Statements

**Balance Sheet:**
- Assets: Cash, Inventory, Receivables
- Liabilities: Payables, Accruals
- Equity: Capital, Retained Earnings

**Income Statement:**
- Revenue: Trading income
- Expenses: Operating costs
- Net Profit/Loss

**Steps to Generate:**

1. Navigate to **Accounting → Financial Statements**
2. Select statement type
3. Select period
4. Click **"Generate"**
5. Review and export

---

## 10. Troubleshooting

### 10.1 Common Issues

**Cannot Login**
- ✓ Check email/password spelling
- ✓ Verify account is active (contact admin)
- ✓ Clear browser cache and cookies
- ✓ Try incognito/private mode

**Transaction Cannot Be Completed**
- ✓ Check customer information is complete
- ✓ Verify exchange rate is current
- ✓ Ensure sufficient stock (for Sell transactions)
- ✓ Check if approval is required (≥RM 50k)

**Till Won't Close**
- ✓ Complete all pending transactions
- ✓ Check for unbalanced variance
- ✓ Verify closing balance entry
- ✓ Contact manager if variance is significant

**Rate Not Updating**
- ✓ Check internet connection
- ✓ Verify API service status
- ✓ Contact admin to check API configuration

### 10.2 Error Messages

| Error | Solution |
|-------|----------|
| "Insufficient balance" | Check currency position, ensure sufficient stock |
| "Transaction already processed" | Refresh page, transaction may have been approved |
| "Unauthorized" | Contact admin for role upgrade |
| "Rate expired" | Refresh rates before creating transaction |
| "Customer not found" | Create new customer or check spelling |

### 10.3 Getting Help

**Internal Support:**
- Contact your system administrator
- Check with your manager
- Review this manual

**Technical Support:**
- Email: support@cems-my.com
- Phone: +60-XXX-XXXXXXX (9am-6pm MYT)
- Ticket System: https://support.cems-my.com

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
| **Buy** | Purchase foreign currency from customer (you give MYR) |
| **Sell** | Sell foreign currency to customer (you receive MYR) |
| **CDD** | Customer Due Diligence - verifying customer identity |
| **EDD** | Enhanced Due Diligence - additional verification for high-risk |
| **KYC** | Know Your Customer - customer identification process |
| **AML** | Anti-Money Laundering - preventing money laundering |
| **CFT** | Countering Financing of Terrorism - preventing terrorist financing |
| **STR** | Suspicious Transaction Report - report to regulator |
| **PEP** | Politically Exposed Person - high-risk customer type |
| **Till** | Cash drawer/counter for conducting transactions |
| **Variance** | Difference between expected and actual cash |

### 11.3 BNM Regulatory Requirements

**Transaction Thresholds:**
- ≥RM 50,000: Manager approval required
- ≥RM 100,000: Enhanced Due Diligence
- Suspicious: Report to Compliance Officer

**Record Keeping:**
- Transaction records: 7 years
- Customer records: 7 years from account closure
- Compliance reports: Permanent

### 11.4 Contact Information

**System Administrator:**
- Name: [Your Admin Name]
- Email: admin@your-company.com
- Phone: +60-XXX-XXXXXXX

**Compliance Officer:**
- Name: [Your Compliance Officer]
- Email: compliance@your-company.com
- Phone: +60-XXX-XXXXXXX

**Emergency Support:**
- Phone: +60-XXX-XXXXXXX (24/7)
- Email: emergency@cems-my.com

---

## Document Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-04-04 | Initial release | CEMS-MY Team |

---

**END OF USER MANUAL**
