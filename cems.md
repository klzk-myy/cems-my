# Currency Exchange Management System - Complete Design Specification

**Version:** 1.0  
**Date:** 2026-04-02  
**Status:** Draft  
**Author:** Design Team  
**Target Implementation:** PHP 8.2+ / MySQL 8.0+ / Apache 2.4+

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Compliance Framework](#2-compliance-framework)
3. [System Architecture](#3-system-architecture)
4. [Module Specifications](#4-module-specifications)
5. [Database Design](#5-database-design)
6. [API Design](#6-api-design)
7. [Security Architecture](#7-security-architecture)
8. [User Interface Design](#8-user-interface-design)
9. [Integration Points](#9-integration-points)
10. [Deployment Architecture](#10-deployment-architecture)
11. [Testing Strategy](#11-testing-strategy)
12. [Data Migration](#12-data-migration)
13. [Training & Documentation](#13-training--documentation)
14. [Appendices](#14-appendices)

---

## 1. Executive Summary

### 1.1 Project Overview

**Project Name:** Currency Exchange Management System (CurMgt)  
**Business Type:** BNM-Licensed Money Changer Operations  
**Deployment Model:** On-premise / Self-hosted cloud  
**Target Users:** Licensed money changer outlets in Malaysia

### 1.2 Business Objectives

- Automate all money changing operations from POS to accounting
- Ensure full compliance with BNM regulations and MIA standards
- Enable multi-branch operations with centralized HQ control
- Provide real-time visibility into stock, positions, and financial performance
- Implement comprehensive AML/CFT compliance framework
- Reduce manual errors and operational risks

### 1.3 Scope Summary

| Category | Components |
|----------|------------|
| **Core Operations** | POS, Stock Management, Customer Management, Day-End Procedures |
| **Financial** | Accounting Engine, Financial Reporting, Stock Valuation |
| **Compliance** | AML/CFT Module, Regulatory Reporting, Position & Risk Management |
| **Management** | HQ Dashboard, Workflow & Task Management, Inter-Branch Operations |
| **Administration** | User Management, Branch Management, Audit Trail, System Settings |

### 1.4 Key Success Criteria

- 100% transaction accuracy with complete audit trail
- BNM regulatory reports generated automatically with ≤1 hour preparation time
- Real-time stock and position visibility across all branches
- AML/CFT alerts triggered within 5 minutes of suspicious activity
- Day-end reconciliation completed within 30 minutes per branch
- Zero data loss with automated backup and recovery

---

## 2. Compliance Framework

### 2.1 Regulatory Bodies

| Authority | Jurisdiction | Key Requirements |
|-----------|--------------|------------------|
| Bank Negara Malaysia (BNM) | Central Bank | MSB licensing, reporting, AML/CFT |
| Malaysia Institute of Accountants (MIA) | Professional Body | Financial reporting standards (MFRS) |
| Securities Commission Malaysia | Securities | Anti-money laundering regulations |
| Royal Malaysian Customs | Tax | Sales tax, service tax compliance |

### 2.2 BNM Compliance Requirements

#### 2.2.1 Licensing Requirements (Money Services Business Act 2011)

- **License Type:** Class A - Money Changing Services
- **Capital Requirements:** Minimum paid-up capital as per BNM guidelines
- **Reporting Obligations:**
  - Monthly: Form LMCA submission by 10th of following month
  - Quarterly: Large value transaction report
  - Annually: Compliance report, audited financial statements
  - Ad-hoc: Suspicious Transaction Reports (STR) within 24 hours

#### 2.2.2 Record-Keeping Requirements

| Record Type | Retention Period | Format |
|-------------|------------------|--------|
| Transaction records | 7 years minimum | Electronic/Physical |
| Customer identification | 7 years after relationship ends | Electronic/Physical |
| STR documentation | 7 years | Electronic |
| Financial records | 7 years minimum | Electronic/Physical |
| Audit trail | 7 years minimum | Electronic |

#### 2.2.3 Transaction Thresholds

| Threshold | Requirement |
|-----------|-------------|
| < RM3,000 | Simplified verification (walk-in allowed) |
| RM3,000 - RM10,000 | Standard CDD, identity verification required |
| RM10,000 - RM50,000 | Enhanced documentation, proof of address |
| ≥ RM50,000 | Enhanced due diligence, STR consideration, CTR filing |
| Structuring detection | Multiple transactions within 24 hours designed to avoid thresholds |

#### 2.2.4 Know Your Customer (KYC) Requirements

**Individual Customers:**
- MyKad (Malaysian) or Passport (Foreigner) - copy retained
- Proof of address (for transactions ≥RM10,000)
- Source of funds declaration (for large transactions)
- Purpose of transaction (for transactions ≥RM50,000)

**Corporate Customers:**
- Certificate of incorporation
- Business registration (SSM)
- Board resolution authorizing signatories
- Beneficial ownership declaration
- Authorized signatory identification

#### 2.2.5 AML/CFT Obligations

- Customer risk assessment and categorization
- PEP (Politically Exposed Person) screening
- Sanctions screening (UN, OFAC, Malaysian lists)
- Transaction monitoring with automated alerts
- Suspicious Transaction Report (STR) filing via goAML
- Cash Transaction Report (CTR) for ≥RM50,000
- Internal compliance training records
- Independent audit of compliance function

### 2.3 MIA Financial Reporting Standards

#### 2.3.1 Applicable Standards

| Standard | Description | Application |
|----------|-------------|-------------|
| MFRS 101 | Presentation of Financial Statements | Balance sheet, P&L format |
| MFRS 102 | Inventories | Stock valuation methods |
| MFRS 121 | Property, Plant and Equipment | Fixed asset depreciation |
| MFRS 1012 | Income Taxes | Tax provisions |
| MFRS 1102 | Foreign Currency Translation | FX revaluation, exchange gains/losses |

#### 2.3.2 Chart of Accounts Requirements

- Standard numbering system (4-digit codes minimum)
- Clear segregation: Assets, Liabilities, Equity, Revenue, Expenses
- Multi-currency support with base currency reporting
- Branch-level and consolidated reporting capability
- Trial balance reconciliation to transaction records

#### 2.3.3 Financial Statements

**Monthly:**
- Trial balance
- Profit & loss statement
- Balance sheet
- Cash flow statement (summary)

**Annually:**
- Full financial statements (audited)
- Directors' report
- Auditors' report
- Notes to financial statements

### 2.4 Internal Policies

- Segregation of duties (cashier vs. supervisor vs. manager)
- Dual control for high-value transactions
- Rate override approval limits
- Stock adjustment authorization matrix
- Branch opening/closing procedures
- Incident reporting and escalation

---

## 3. System Architecture

### 3.1 Architecture Overview

**Pattern:** Modular Monolith with Service Layer  
**Structure:** MVC (Model-View-Controller) + Domain Services  
**Database:** Centralized MySQL with branch-level data partitioning via branch_id

```
┌─────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐│
│  │Web UI    │  │Mobile UI │  │Report    │  │Admin Dashboard   ││
│  │(Bootstrap│  │(Future)  │  │Viewer    │  │                  ││
│  │5)        │  │          │  │(PDF/Excel│  │                  ││
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘│
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER                        │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    Controllers (PHP)                      │  │
│  │  POS │ Stock │ Customer │ AML │ Accounting │ Reporting │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                         SERVICE LAYER                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────────────┐ │
│  │Transaction│ │Stock     │ │Customer  │ │Compliance          │ │
│  │Service    │ │Service   │ │Service   │ │Service             │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────────────┘ │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────────────┐ │
│  │Accounting │ │Reporting │ │Workflow  │ │Notification        │ │
│  │Service    │ │Service   │ │Service   │ │Service             │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATA ACCESS LAYER                       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Repositories / Data Mappers (PHP)            │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATABASE LAYER                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    MySQL 8.0+ Database                    │  │
│  │         ┌───────────────────────────────────────┐        │  │
│  │         │ Tables │ Views │ Stored Procedures │  │        │  │
│  │         └───────────────────────────────────────┘        │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Directory Structure

```
curmgt/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── POSController.php
│   │   ├── StockController.php
│   │   ├── CustomerController.php
│   │   ├── AMLController.php
│   │   ├── AccountingController.php
│   │   ├── ReportingController.php
│   │   ├── WorkflowController.php
│   │   ├── InterBranchController.php
│   │   └── AdminController.php
│   ├── Services/
│   │   ├── TransactionService.php
│   │   ├── StockService.php
│   │   ├── CustomerService.php
│   │   ├── AMLService.php
│   │   ├── AccountingService.php
│   │   ├── ReportingService.php
│   │   ├── WorkflowService.php
│   │   ├── NotificationService.php
│   │   ├── RateService.php
│   │   └── InterBranchService.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Branch.php
│   │   ├── Customer.php
│   │   ├── Transaction.php
│   │   ├── Stock.php
│   │   ├── Account.php
│   │   ├── JournalEntry.php
│   │   └── [Additional Models...]
│   ├── Repositories/
│   │   ├── TransactionRepository.php
│   │   ├── StockRepository.php
│   │   ├── CustomerRepository.php
│   │   └── [Additional Repositories...]
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── PermissionMiddleware.php
│   │   ├── BranchAccessMiddleware.php
│   │   └── AuditMiddleware.php
│   ├── Helpers/
│   │   ├── DateHelper.php
│   │   ├── CurrencyHelper.php
│   │   ├── ValidationHelper.php
│   │   └── PDFHelper.php
│   └── Exceptions/
│       ├── BusinessException.php
│       ├── ValidationException.php
│       └── ComplianceException.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── permissions.php
│   ├── routes.php
│   └── compliance.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── fonts/
│   └── uploads/
│       ├── customers/
│       ├── documents/
│       └── reports/
├── resources/
│   └── views/
│       ├── layouts/
│       ├── pos/
│       ├── stock/
│       ├── customer/
│       ├── aml/
│       ├── accounting/
│       ├── reporting/
│       ├── admin/
│       └── components/
├── database/
│   ├── migrations/
│   ├── seeds/
│   └── backups/
├── storage/
│   ├── logs/
│   ├── cache/
│   └── exports/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── scripts/
│   ├── backup.sh
│   ├── reports_cron.php
│   └── compliance_checks.php
├── docs/
│   ├── api/
│   ├── user_manual/
│   └── technical/
├── vendor/
├── .env.example
├── .htaccess
└── composer.json
```

### 3.3 Technology Stack

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| Web Server | Apache | 2.4+ | HTTP server, mod_rewrite for routing |
| PHP Runtime | PHP | 8.2+ | Application logic, strict types enabled |
| Database | MySQL | 8.0+ | Primary data store, InnoDB engine |
| Frontend Framework | Bootstrap | 5.3 | UI components, responsive design |
| JavaScript | Vanilla JS | ES6+ | Frontend interactions, no framework |
| PDF Generation | TCPDF/MPDF | Latest | Report generation |
| Excel Export | PhpSpreadsheet | Latest | Excel report generation |
| Email | PHPMailer | Latest | Email notifications |
| Cache | APCu | Latest | Application caching |
| PDF Viewer | PDF.js | Latest | In-browser PDF viewing |

### 3.4 Design Principles

1. **Single Responsibility:** Each class has one reason to change
2. **Dependency Injection:** Services injected, not instantiated
3. **Repository Pattern:** Data access abstracted from business logic
4. **Configuration over Code:** Settings in config files, not hardcoded
5. **Fail Secure:** Default deny, explicit allow for permissions
6. **Audit Everything:** All state changes logged with user/timestamp
7. **Idempotency:** Operations safe to retry
8. **Soft Deletes:** No hard deletes, use deleted_at timestamps
9. **Transaction Integrity:** ACID compliance for financial operations
10. **Regulatory First:** Compliance requirements drive design

---

## 4. Module Specifications

### 4.1 Point of Sale (POS) Module

#### 4.1.1 Functional Requirements

**FR-POS-001: Counter Management**
- System shall support multiple counters per branch
- Each counter has assigned float (cash in multiple currencies)
- Counter can be opened/closed with balance verification
- Only one user per counter session at a time
- Counter handover requires supervisor verification

**FR-POS-002: Rate Board Management**
- Display real-time buy/sell rates for all active currency pairs
- Support rate override with approval workflow
- Rate change history maintained
- Support corporate vs. walk-in rates
- Automatic rate adjustment alerts (optional)

**FR-POS-003: Transaction Processing**
- Transaction types: Buy Foreign Currency, Sell Foreign Currency, Both (Exchange)
- Support multiple payment methods (cash, bank transfer)
- Automatic calculation with rate application
- Transaction hold/resume functionality
- Transaction cancellation with supervisor approval
- Receipt printing with customizable format

**FR-POS-004: Customer Selection**
- Quick customer search by name, ID, phone
- New customer registration from POS screen
- Walk-in mode for transactions below threshold
- Customer risk level display
- Transaction history quick view

**FR-POS-005: Payment Processing**
- Multi-currency payment acceptance
- Change calculation (local or foreign)
- Denomination capture for cash payments
- Payment validation before transaction completion
- Support for split payments (future)

#### 4.1.2 Business Rules

**BR-POS-001:** Transaction cannot proceed if customer verification level insufficient for amount

| Amount (MYR) | Minimum Verification Required |
|--------------|-------------------------------|
| < 3,000 | None (walk-in allowed) |
| 3,000 - 10,000 | Identity verification |
| 10,000 - 50,000 | Identity + Address verification |
| ≥ 50,000 | Enhanced due diligence + STR consideration |

**BR-POS-002:** Rate override exceeding threshold requires approval

| Role | Rate Override Limit |
|------|---------------------|
| Cashier | ±0.5% from base rate |
| Senior Cashier | ±1.0% from base rate |
| Branch Manager | ±2.0% from base rate |
| Principal Officer | Unlimited |

**BR-POS-003:** Transaction cannot proceed if:
- Stock insufficient for currency
- Position limit would be breached
- Customer on sanctions list
- Counter not open
- System in maintenance mode

**BR-POS-004:** Each transaction creates:
- Transaction record (primary)
- Stock movement entries (2: decrease one currency, increase another)
- Journal entries (minimum 2 for double-entry)
- Customer transaction link (if customer identified)

#### 4.1.3 Data Flow

```
Customer Selection → Verification Check
         ↓
Currency & Amount Entry → Rate Application
         ↓
Rate Override? → Yes → Approval Request
         ↓ No
Stock Availability Check
         ↓
Payment Entry → Denomination Capture
         ↓
Transaction Validation
         ↓
[Database Transaction Begin]
  - Create transaction record
  - Update stock balances
  - Create journal entries
  - Update position tracking
  - Log audit trail
[Database Transaction Commit]
         ↓
Receipt Printing
         ↓
Transaction Complete
```

#### 4.1.4 UI Components

**Main POS Screen:**
- Left panel: Customer selection/search
- Center: Rate board (grid of currency pairs)
- Right panel: Transaction details, payment entry
- Bottom: Action buttons (Hold, Clear, Process, Cancel)
- Top: Counter info, user info, alerts

**Rate Board:**
- Currency pair (FROM/TO)
- Buy rate (we buy from customer)
- Sell rate (we sell to customer)
- Spread display
- Last updated timestamp
- Click to select for transaction

**Transaction Detail Panel:**
- Selected currencies
- Amount fields (two-way calculation)
- Applied rate
- Total in MYR
- Customer info summary
- Payment method selection
- Change due calculation

---

### 4.2 Stock Management Module

#### 4.2.1 Functional Requirements

**FR-STK-001: Stock Inventory**
- Real-time balance per currency per location (safe, counter, vault)
- Stock movements logged with full audit trail
- Multi-location tracking within branch
- Denomination-level tracking (optional)
- Foreign currency stock aging

**FR-STK-002: Stock Movements**
- Movement types: Buy, Sell, Transfer, Deposit, Withdrawal, Adjustment
- Each movement linked to source (transaction, transfer, manual entry)
- Movement approval workflow for adjustments
- Partial movement support
- Movement reversal with audit

**FR-STK-003: Stock Valuation**
- Support multiple valuation methods: FIFO, Weighted Average, Specific ID
- Cost of sales calculation per transaction
- Unrealized gain/loss on open positions
- Revaluation at month-end (optional)
- Stock value by currency report

**FR-STK-004: Stock Reconciliation**
- Physical count entry
- Variance calculation (system vs. physical)
- Variance investigation workflow
- Adjustment posting with approval
- Reconciliation history

#### 4.2.2 Business Rules

**BR-STK-001:** Stock cannot go negative
- Pre-transaction stock check
- If insufficient, transaction blocked
- Alert sent to branch manager

**BR-STK-002:** Stock adjustments require approval

| Adjustment Type | Approval Required |
|-----------------|-------------------|
| < RM100 variance | Senior Cashier |
| RM100 - RM500 variance | Branch Manager |
| RM500 - RM5,000 variance | HQ Admin |
| > RM5,000 variance | Principal Officer |

**BR-STK-003:** Stock movements create audit entries:
- Movement ID, type, currency, amount
- From location, to location
- Source reference (transaction ID, transfer ID, manual)
- User who performed movement
- Timestamp
- Approved by (if applicable)

**BR-STK-004:** Stock valuation method selected at company setup
- Cannot be changed without principal officer approval
- Change requires historical recalculation
- Audit log of valuation method changes

#### 4.2.3 Stock Movement Types

| Type | Direction | Source | Accounting Impact |
|------|-----------|--------|-------------------|
| BUY | Increase (Foreign), Decrease (MYR) | Transaction | FX Revenue, Cost of Sales |
| SELL | Decrease (Foreign), Increase (MYR) | Transaction | FX Revenue, Cost of Sales |
| TRANSFER_IN | Increase | Inter-branch transfer | Inter-branch account |
| TRANSFER_OUT | Decrease | Inter-branch transfer | Inter-branch account |
| DEPOSIT | Decrease | Bank deposit | Bank account |
| WITHDRAWAL | Increase | Bank withdrawal | Bank account |
| ADJUSTMENT | Increase/Decrease | Manual | Variance account |
| OPENING | Increase | Day start | Opening balance |

---

### 4.3 Customer Management Module

#### 4.3.1 Functional Requirements

**FR-CUS-001: Customer Registration**
- Capture: Name, ID type/number, nationality, address, contact
- Document upload: ID copy, proof of address
- Risk categorization: Low, Medium, High
- Customer status: Active, Inactive, Suspended, Watchlisted
- Registration date and user

**FR-CUS-002: Customer Verification**
- Verification levels: None, Basic, Standard, Enhanced
- Document verification status
- Address verification status
- Verification expiry tracking (passport expiry)
- Re-verification alerts

**FR-CUS-003: Customer Risk Assessment**
- Risk factors: Country, PEP status, occupation, transaction patterns
- Automatic risk score calculation
- Manual risk override with justification
- Risk review scheduling
- Enhanced due diligence triggers

**FR-CUS-004: Transaction History**
- Complete transaction history per customer
- Filterable by date, currency, amount, branch
- Transaction pattern analysis
- Export to Excel/PDF
- Customer transaction summary

**FR-CUS-005: Watchlist Management**
- Add customer to internal watchlist
- Reason for watchlisting
- Review schedule for watchlisted customers
- Watchlist alerts at POS
- Removal from watchlist with approval

#### 4.3.2 Business Rules

**BR-CUS-001:** Customer verification level must meet transaction amount threshold

```
IF transaction_amount >= 50,000 AND customer.verification_level < 'ENHANCED':
    BLOCK transaction with message "Enhanced due diligence required"
```

**BR-CUS-002:** High-risk customers require:
- Enhanced due diligence documentation
- Approval for transactions ≥RM10,000
- Annual risk review
- Transaction limit restrictions

**BR-CUS-003:** PEP customers:
- Automatically flagged as high-risk
- Senior management approval for all transactions
- Enhanced ongoing monitoring
- Source of wealth documentation

**BR-CUS-004:** Customer data retention:
- Active customers: indefinite retention
- Inactive customers: 7 years after last transaction
- Suspicious activity customers: 7 years after investigation closed

#### 4.3.3 Customer Risk Scoring Model

| Factor | Weight | Score Range |
|--------|--------|-------------|
| Customer Type | 15% | Individual: 1, Corporate: 2, Agent: 3 |
| Geographic Risk | 20% | Low-risk country: 1, Medium: 2, High: 3 |
| PEP Status | 25% | No: 1, Yes: 3 |
| Occupation Risk | 10% | Low-risk: 1, Medium: 2, High: 3 |
| Transaction Frequency | 15% | Normal: 1, High: 2, Very High: 3 |
| Document Verification | 15% | Full: 1, Partial: 2, None: 3 |

**Risk Level Thresholds:**
- Low Risk: Score ≤ 1.5
- Medium Risk: Score 1.5 - 2.2
- High Risk: Score > 2.2

---

### 4.4 AML/CFT Compliance Module

#### 4.4.1 Functional Requirements

**FR-AML-001: Transaction Monitoring**
- Real-time transaction monitoring against configurable rules
- Automatic alert generation for rule violations
- Alert assignment to compliance officer
- Alert investigation workflow
- False positive marking with justification

**FR-AML-002: Suspicious Transaction Reporting**
- STR form generation with transaction details
- Attachment of supporting documents
- Internal approval workflow (Compliance → Principal Officer)
- Submission tracking (status, date, reference number)
- STR history and statistics

**FR-AML-003: Cash Transaction Reporting**
- Automatic CTR generation for transactions ≥RM50,000
- Daily CTR compilation
- CTR approval and submission workflow
- CTR history and amendments

**FR-AML-004: Sanctions Screening**
- Screen customers against sanctions lists at registration
- Periodic rescreening (monthly)
- Real-time screening for transactions above threshold
- Match resolution workflow (false positive vs. confirmed)
- List update management

**FR-AML-005: Compliance Dashboard**
- Open alerts count and aging
- STR submission status
- CTR generation status
- High-risk customer count
- Compliance KPIs

#### 4.4.2 Transaction Monitoring Rules

**Rule 1: Structuring Detection**
```
IF customer has multiple transactions within 24 hours:
    total = sum of all transaction amounts
    IF total >= 50,000 AND any single transaction < 50,000:
        ALERT: "Potential structuring detected"
```

**Rule 2: Unusual Amount**
```
IF transaction_amount > customer.average_transaction_amount * 3:
    AND transaction_amount > 10,000:
        ALERT: "Unusual transaction amount for customer"
```

**Rule 3: High-Risk Country**
```
IF customer.nationality IN high_risk_countries:
    AND transaction_amount >= 3,000:
        ALERT: "High-risk country transaction"
```

**Rule 4: Round Amount**
```
IF transaction_amount in MYR is multiple of 10,000:
    AND transaction_amount >= 10,000:
        ALERT: "Round amount transaction - review purpose"
```

**Rule 5: Rapid Succession**
```
IF customer has >= 3 transactions within 1 hour:
    ALERT: "Rapid succession transactions"
```

**Rule 6: Customer Profile Deviation**
```
IF customer.annual_volume_estimate > 0:
    IF current_month_volume > annual_estimate / 12 * 2:
        ALERT: "Transaction volume exceeds customer profile"
```

#### 4.4.3 Sanctions Lists

| List | Source | Update Frequency |
|------|--------|------------------|
| UN Consolidated List | United Nations | Monthly |
| OFAC SDN List | US Treasury | Monthly |
| ASEAN Watchlist | ASEAN | Quarterly |
| BNM Watchlist | Bank Negara Malaysia | As updated |
| Internal Watchlist | Company | As needed |

#### 4.4.4 STR Workflow

```
Alert Generated → Assigned to Compliance Officer
         ↓
Investigation (document gathering, analysis)
         ↓
Decision Point: Suspicious or Not Suspicious?
         ↓                    ↓
Not Suspicious           Suspicious
    ↓                        ↓
False Positive           Draft STR
    ↓                        ↓
Close Alert              Compliance Manager Review
    ↓                        ↓
                         Principal Officer Approval
                                  ↓
                         Submit to BNM via goAML
                                  ↓
                         Acknowledgment Received
                                  ↓
                         Close Case
```

---

### 4.5 Accounting Engine Module

#### 4.5.1 Functional Requirements

**FR-ACC-001: Chart of Accounts**
- Hierarchical account structure (4-digit codes minimum)
- Account types: Asset, Liability, Equity, Revenue, Expense
- Multi-currency accounts with base currency tracking
- Account status: Active, Inactive
- Account opening balances

**FR-ACC-002: Journal Entries**
- Automatic journal entry creation from transactions
- Manual journal entry with approval workflow
- Journal entry types: Standard, Adjustment, Opening, Reversal
- Supporting document attachment
- Journal entry reversal (with new entry)

**FR-ACC-003: Period Management**
- Accounting periods (monthly)
- Period open/close status
- Period closing checklist
- Prior period adjustments with approval
- Year-end closing procedures

**FR-ACC-004: Financial Statements**
- Trial Balance (branch and consolidated)
- Profit & Loss Statement (monthly, quarterly, annually)
- Balance Sheet (monthly, quarterly, annually)
- Cash Flow Statement (summary)
- Statement of Changes in Equity

**FR-ACC-005: Currency Revaluation**
- Month-end revaluation of foreign currency balances
- Unrealized gain/loss calculation
- Revaluation posting to P&L
- Revaluation history

#### 4.5.2 Chart of Accounts Structure

**Assets (1000-1999)**
```
1000 - Current Assets
  1010 - Cash on Hand
    1011 - Cash on Hand - MYR - Branch 1
    1012 - Cash on Hand - USD - Branch 1
    ...
  1020 - Cash at Bank
    1021 - Bank Account - Maybank
    1022 - Bank Account - CIMB
  1030 - Foreign Currency Stock
    1031 - FC Stock - USD
    1032 - FC Stock - EUR
    ...
  1040 - Inter-Branch Receivables
  1050 - Accounts Receivable
  1060 - Prepayments
1100 - Non-Current Assets
  1110 - Property, Plant & Equipment
  1120 - Accumulated Depreciation
```

**Liabilities (2000-2999)**
```
2000 - Current Liabilities
  2010 - Accounts Payable
  2020 - Inter-Branch Payables
  2030 - Accruals
  2040 - Customer Deposits
```

**Equity (3000-3999)**
```
3000 - Equity
  3010 - Share Capital
  3020 - Retained Earnings
  3030 - Current Year Profit/Loss
```

**Revenue (4000-4999)**
```
4000 - Revenue
  4010 - FX Trading Income
  4020 - Commission Income
  4030 - Other Income
  4040 - FX Gain/Loss - Realized
  4050 - FX Gain/Loss - Unrealized
```

**Expenses (5000-5999)**
```
5000 - Expenses
  5010 - Cost of Sales - FX
  5020 - Staff Costs
  5030 - Rental Expenses
  5040 - Utilities
  5050 - Depreciation
  5060 - Compliance Costs
  5070 - Other Operating Expenses
```

#### 4.5.3 Transaction Journal Entries

**Buy Foreign Currency (Customer sells foreign to us):**

Customer exchanges USD 1,000 at rate 4.20 (we pay MYR 4,200)

```
Dr. Cash on Hand - USD    USD 1,000 @ 4.20 = MYR 4,200
Dr. Cost of Sales - FX                       MYR XX
    Cr. Cash on Hand - MYR                    MYR 4,200
    Cr. FX Trading Income                     MYR XX
    
(Where XX is the spread/profit margin)
```

**Sell Foreign Currency (Customer buys foreign from us):**

Customer buys USD 500 at rate 4.25 (we receive MYR 2,125)

```
Dr. Cash on Hand - MYR                        MYR 2,125
    Cr. Cash on Hand - USD    USD 500 @ 4.20 = MYR 2,100
    Cr. FX Trading Income                      MYR 25
```

#### 4.5.4 Period Closing Checklist

- [ ] All transactions entered for the period
- [ ] Day-end procedures completed for all branches
- [ ] Inter-branch settlements reconciled
- [ ] Bank reconciliations completed
- [ ] Stock variances investigated and posted
- [ ] Accruals and prepayments updated
- [ ] FX revaluation executed
- [ ] Depreciation posted
- [ ] Trial balance reviewed
- [ ] Period closed by authorized user

---

### 4.6 Financial Reporting Suite

#### 4.6.1 Operational Reports

**Daily Reports:**
- Daily Transaction Summary (by branch, counter, currency, user)
- Daily Stock Position Report
- Daily Cash Position Report
- Daily P&L Estimate
- Counter Performance Report

**Weekly Reports:**
- Weekly Transaction Analysis
- Weekly Position Report
- Currency Volume Analysis
- Customer Acquisition Report

**Monthly Reports:**
- Monthly Transaction Summary
- Monthly Stock Movement Report
- Monthly P&L Statement
- Monthly Balance Sheet
- Branch Performance Comparison
- Currency Profitability Analysis
- Customer Transaction Report (CTR candidates)

#### 4.6.2 Regulatory Reports

**BNM Form LMCA (Monthly):**
- Total transactions by currency (buy/sell)
- Total volume by currency
- Stock position by currency
- Number of customers served
- Staff count
- Branch operating hours

**CTR Report (Monthly):**
- List of all transactions ≥RM50,000
- Customer details for each transaction
- Purpose of transaction
- Verification documents

**Quarterly Reports:**
- Large value transaction report
- Position limit utilization report
- Compliance activities summary

#### 4.6.3 Management Reports

- Revenue Trend Analysis (daily, weekly, monthly)
- Gross Margin by Currency Pair
- Counter Efficiency Analysis
- Staff Productivity Report
- Customer Segment Analysis
- Stock Turnover Analysis
- Risk Exposure Summary
- Compliance KPI Dashboard

#### 4.6.4 Report Features

- Export to Excel, PDF, CSV
- Email scheduling and distribution
- Report templates with customization
- Saved report configurations
- Report version history
- Report archival (7 years)
- Interactive drill-down (web reports)

---

### 4.7 Regulatory Reporting Module

#### 4.7.1 BNM Report Generation

**Form LMCA (Monthly):**

| Field | Data Source |
|-------|-------------|
| License Number | System config |
| Reporting Period | User input |
| Total Buy Transactions (by currency) | Transaction table (aggregated) |
| Total Sell Transactions (by currency) | Transaction table (aggregated) |
| Total Volume (by currency) | Transaction table (sum) |
| Opening Stock (by currency) | Stock table (period start) |
| Closing Stock (by currency) | Stock table (period end) |
| Number of Customers | Customer transaction distinct count |
| Number of Staff | User table (active) |
| Operating Hours | Branch config |

**Auto-Generation Schedule:**
- Generated on 1st of each month (for previous month)
- Branch manager review by 5th
- HQ consolidation by 7th
- Principal officer approval by 9th
- Submission to BNM by 10th

#### 4.7.2 Submission Workflow

```
Report Generated → Data Validation Checks
         ↓
Passed Validation → Branch Manager Review
         ↓
Review Comments Added (if any) → Revision or Approval
         ↓
HQ Consolidation (if multi-branch)
         ↓
Final Review by Compliance
         ↓
Principal Officer Approval
         ↓
Submit to BNM Portal (manual or API)
         ↓
Acknowledgment Receipt Captured
         ↓
Report Archived
```

#### 4.7.3 Validation Rules

- All currency codes are ISO 4217 compliant
- Opening stock + purchases - sales + adjustments = Closing stock
- Transaction counts match between report and source
- No null or invalid values in required fields
- License number format validated
- Period dates are valid and sequential

---

### 4.8 Position & Risk Management Module

#### 4.8.1 Position Tracking

**Real-time Position:**
- Position per currency (base currency equivalent)
- Position by branch
- Position by counter (if applicable)
- Net position (overall exposure)
- Position change from opening

**Position Limits:**

| Limit Type | Description | Alert Thresholds |
|------------|-------------|------------------|
| Single Currency Limit | Maximum stock in one currency | 80%, 90%, 100% |
| Total Position Limit | Total foreign currency exposure | 80%, 90%, 100% |
| Imbalance Limit | Max buy/sell ratio deviation | 70%, 85%, 100% |
| Intraday Limit | Maximum position change in a day | 80%, 90%, 100% |

#### 4.8.2 Risk Metrics

**Value at Risk (VaR):**
- Calculate VaR at 95% confidence level
- Based on historical volatility
- Rolling 30-day calculation
- Report daily VaR

**Mark-to-Market P&L:**
- Revalue open positions at current market rates
- Calculate unrealized gain/loss
- Track P&L throughout the day
- End-of-day P&L snapshot

**Volatility Analysis:**
- Currency pair volatility (daily, weekly, monthly)
- Volatility alerts (significant increases)
- Correlation between currency pairs
- Volatility contribution to overall risk

#### 4.8.3 Alerts & Notifications

**Alert Types:**
- Position limit approaching (80%, 90%, 100%)
- Position limit breached
- Large transaction (above threshold)
- Unusual position movement
- Profit/loss threshold reached
- Volatility spike

**Notification Channels:**
- In-system notification (dashboard)
- Email notification
- SMS notification (critical alerts)
- Push notification (future mobile app)

**Alert Management:**
- Alert acknowledgment
- Alert escalation (if not acknowledged within SLA)
- Alert resolution logging
- Alert statistics and reporting

---

### 4.9 Day-End Procedures Module

#### 4.9.1 End-of-Day Workflow

**Step 1: Close All Counters**
- Verify no pending transactions
- Close counter sessions
- Verify counter floats

**Step 2: Cash Count**
- Physical count per currency per counter
- Denomination capture (optional)
- Count verification by supervisor
- Variance recording

**Step 3: Stock Reconciliation**
- System stock vs. physical stock
- Variance calculation per currency
- Variance investigation
- Adjustment posting (if approved)

**Step 4: Day-End Posting**
- Finalize all accounting entries
- Calculate daily P&L
- Generate daily reports
- Backup data

**Step 5: Close Business Day**
- Day-end checklist completion
- Supervisor sign-off
- System day-end process
- Next business day opened

#### 4.9.2 Reconciliation Tolerance

| Variance Type | Tolerance | Action |
|----------------|-----------|--------|
| Minor variance (< RM10) | Auto-adjust | Log and post |
| Small variance (RM10-100) | Senior Cashier approval | Investigation required |
| Medium variance (RM100-500) | Branch Manager approval | Full investigation |
| Large variance (> RM500) | HQ approval | Incident report |

#### 4.9.3 Day-End Checklist

- [ ] All counters closed
- [ ] All transactions processed
- [ ] Cash count completed
- [ ] Stock reconciliation completed
- [ ] Variances investigated
- [ ] Adjustments posted
- [ ] Daily reports generated
- [ ] Data backup verified
- [ ] Day closed by supervisor

---

### 4.10 HQ Management Dashboard

#### 4.10.1 Dashboard Widgets

**Real-Time Widgets:**
- Today's total revenue (all branches)
- Transaction count (all branches)
- Active counters (live)
- Open positions (by currency)
- Pending approvals count

**Performance Widgets:**
- Branch revenue comparison (MTD)
- Top performing currencies
- Top performing branches
- Customer growth trend

**Alert Widgets:**
- Compliance alerts (critical, high, medium)
- Position limit alerts
- Stock alerts (low stock)
- Pending approvals aging

**Compliance Widgets:**
- High-risk customer count
- Open STR cases
- Pending CTR filings
- Compliance training status

#### 4.10.2 Branch Monitoring

- Branch status (open/closed)
- Active counters per branch
- Live transaction feed (anonymized)
- Stock levels by branch
- Position utilization by branch
- Staff on duty

#### 4.10.3 Approval Workflows

| Request Type | Initial Approver | Final Approver |
|--------------|------------------|----------------|
| Rate Override > 2% | Branch Manager | Principal Officer |
| High-Value Transaction | Branch Manager | Principal Officer |
| Stock Transfer | Branch Manager (sender) | HQ Admin |
| Stock Adjustment > RM5,000 | HQ Admin | Principal Officer |
| Customer Onboarding (Corporate) | Branch Manager | Compliance |
| Manual Journal Entry | Branch Accountant | Branch Manager |
| Customer Watchlist Add | Compliance Officer | Principal Officer |

---

### 4.11 Workflow & Task Management Module

#### 4.11.1 Task Categories

| Category | Examples | Default Priority | SLA |
|----------|----------|------------------|-----|
| Compliance | STR review, alert investigation | High | 24 hours |
| Customer | KYC follow-up, document renewal | Medium | 48 hours |
| Operations | Stock request, bank deposit | Medium | Same day |
| Admin | Report submission, audit prep | Low | 1 week |
| Approval | Rate override, high-value txn | Urgent | 2 hours |

#### 4.11.2 Task Workflow

```
Task Created → Assigned to User/Role
         ↓
Notification Sent (in-system, email)
         ↓
Task Acknowledged (optional)
         ↓
Task In Progress
         ↓
Task Completed → Verification (if required)
         ↓
Task Closed
         ↓
Audit Log Updated
```

#### 4.11.3 Recurring Tasks

- Daily: Compliance alert review, day-end checklist
- Weekly: Branch performance review, stock analysis
- Monthly: CTR compilation, compliance review, report submission
- Quarterly: Risk assessment review, policy review
- Annually: License renewal, audit preparation, compliance training

#### 4.11.4 Escalation Rules

| Priority | SLA | Escalation Trigger | Escalated To |
|----------|-----|-------------------|--------------|
| Urgent | 2 hours | 1 hour no acknowledgment | Branch Manager |
| High | 24 hours | 12 hours no acknowledgment | Compliance Manager |
| Medium | 48 hours | 24 hours no acknowledgment | Branch Manager |
| Low | 1 week | 3 days no acknowledgment | Branch Manager |

---

### 4.12 Inter-Branch Operations Module

#### 4.12.1 Stock Transfer Workflow

```
Branch A Requests Transfer → Branch Manager A Approves
         ↓
HQ Reviews Request → HQ Approves
         ↓
Branch A Prepares Stock → Dispatch Recorded
         ↓
Stock In Transit (status tracking)
         ↓
Branch B Receives Stock → Verification
         ↓
Variance? 
    Yes → Investigation → Adjustment
    No → Complete Transfer
         ↓
Both Branches Stock Updated
         ↓
Accounting Entries Created
         ↓
Inter-Branch Accounts Reconciled
```

#### 4.12.2 Transfer Types

| Type | Description | Approval |
|------|-------------|----------|
| Standard Transfer | Regular stock rebalancing | Branch Manager + HQ |
| Emergency Transfer | Urgent stock shortage | Branch Manager (expedited) |
| Scheduled Transfer | Recurring automated transfers | Pre-approved |
| Return Transfer | Return excess stock to HQ | Branch Manager |

#### 4.12.3 Inter-Branch Accounting

**Branch A (Sender):**
```
Dr. Inter-Branch Receivable - Branch B    MYR value at transfer rate
    Cr. Foreign Currency Stock            MYR cost value
    Cr. Transfer Gain/Loss (if any)       Variance
```

**Branch B (Receiver):**
```
Dr. Foreign Currency Stock                MYR value at received rate
    Cr. Inter-Branch Payable - Branch A   MYR value at transfer rate
    Cr. Transfer Gain/Loss (if any)       Variance
```

#### 4.12.4 Settlement

- Monthly settlement of inter-branch balances
- Settlement statement generated
- Payment instructions created
- Settlement confirmation recorded
- Inter-branch accounts cleared

---

### 4.13 System Administration Module

#### 4.13.1 User Management

**User Fields:**
- Username, password (hashed)
- Full name, email, phone
- Role assignment
- Branch assignment
- Status (Active, Inactive, Locked)
- Last login, failed attempts
- Password expiry

**Password Policy:**
- Minimum 12 characters
- At least 1 uppercase, 1 lowercase, 1 number, 1 special character
- Cannot reuse last 5 passwords
- Expires every 90 days
- Account locks after 5 failed attempts

#### 4.13.2 Role-Based Access Control (RBAC)

**Pre-defined Roles:**

| Role | Permissions |
|------|-------------|
| Super Admin | Full system access, all settings |
| Principal Officer | All branches, all reports, approvals, compliance |
| Compliance Officer | AML/CFT module, customer risk, STR/CTR |
| Branch Manager | Full branch access, approvals, reports |
| Senior Cashier | Counter operations, day-end, adjustments (limited) |
| Cashier | Counter operations, basic transactions |
| Viewer | Read-only access to assigned modules |

**Granular Permissions:**
- Module-level (view, create, edit, delete)
- Action-level (approve, export, print)
- Branch-level (specific branches or all)
- Data-level (own data only, branch data, all data)

#### 4.13.3 Audit Trail

**Logged Actions:**
- User login/logout (successful and failed)
- Transaction create/modify/delete
- Customer create/modify/delete
- Stock adjustments
- Journal entries
- Report generation and export
- Configuration changes
- User/role/branch changes

**Audit Log Fields:**
- Log ID
- Timestamp
- User ID
- Action type
- Module
- Entity type and ID
- Old value (JSON)
- New value (JSON)
- IP address
- User agent
- Branch ID

#### 4.13.4 System Configuration

**Company Settings:**
- Company name, registration number
- BNM license number
- Base currency
- Operating hours
- Address, contact

**Transaction Settings:**
- Default rate spread
- Rate override thresholds
- Transaction thresholds for CDD
- CTR threshold
- Maximum transaction limits

**Compliance Settings:**
- Risk scoring weights
- Alert thresholds
- Sanctions lists configuration
- STR escalation contacts

**Accounting Settings:**
- Chart of accounts
- Accounting periods
- Stock valuation method
- Depreciation methods
- FX revaluation method

---

## 5. Database Design

### 5.1 Database Overview

**Database Name:** `curmgt`  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Engine:** InnoDB (all tables)  
**Timezone:** UTC stored, displayed in Malaysia Time (UTC+8)

### 5.2 Table Naming Convention

- Table names: lowercase, snake_case, plural (e.g., `users`, `transactions`)
- Primary key: `id` (BIGINT UNSIGNED AUTO_INCREMENT)
- Foreign keys: `{table}_id` (e.g., `branch_id`, `user_id`)
- Timestamps: `created_at`, `updated_at`, `deleted_at`
- Soft deletes: `deleted_at` (NULL = active, DATETIME = deleted)

### 5.3 Table Definitions

---

#### Table: `users`

System users (staff members)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Login username |
| password_hash | VARCHAR(255) | NOT NULL | Bcrypt password hash |
| full_name | VARCHAR(100) | NOT NULL | Full name |
| email | VARCHAR(100) | UNIQUE | Email address |
| phone | VARCHAR(20) | | Phone number |
| role_id | TINYINT UNSIGNED | FK, NOT NULL | Role reference |
| branch_id | BIGINT UNSIGNED | FK, NULL | Assigned branch (NULL = HQ) |
| status | ENUM('active','inactive','locked') | NOT NULL, DEFAULT 'active' | Account status |
| password_changed_at | DATETIME | NOT NULL | Last password change |
| failed_attempts | TINYINT UNSIGNED | DEFAULT 0 | Consecutive failed logins |
| last_login_at | DATETIME | | Last successful login |
| last_login_ip | VARCHAR(45) | | Last login IP address |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (username)
- UNIQUE KEY (email)
- KEY (role_id)
- KEY (branch_id)
- KEY (status)

**Foreign Keys:**
- FK_users_role_id → roles(id) ON DELETE RESTRICT
- FK_users_branch_id → branches(id) ON DELETE SET NULL

---

#### Table: `roles`

User roles with permission templates

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | TINYINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| name | VARCHAR(50) | UNIQUE, NOT NULL | Role name |
| code | VARCHAR(20) | UNIQUE, NOT NULL | Role code (e.g., 'admin', 'cashier') |
| description | TEXT | | Role description |
| is_system | BOOLEAN | DEFAULT FALSE | System role (cannot delete) |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Pre-defined Roles:**
1. Super Admin (code: 'super_admin')
2. Principal Officer (code: 'principal_officer')
3. Compliance Officer (code: 'compliance_officer')
4. Branch Manager (code: 'branch_manager')
5. Senior Cashier (code: 'senior_cashier')
6. Cashier (code: 'cashier')
7. Viewer (code: 'viewer')

---

#### Table: `permissions`

Permission definitions

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| module | VARCHAR(50) | NOT NULL | Module name |
| action | VARCHAR(50) | NOT NULL | Action name |
| name | VARCHAR(100) | NOT NULL | Display name |
| description | TEXT | | Permission description |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Unique Key:** (module, action)

---

#### Table: `role_permissions`

Role-permission mapping (many-to-many)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| role_id | TINYINT UNSIGNED | FK, NOT NULL | Role reference |
| permission_id | INT UNSIGNED | FK, NOT NULL | Permission reference |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Primary Key:** (role_id, permission_id)

**Foreign Keys:**
- FK_role_permissions_role_id → roles(id) ON DELETE CASCADE
- FK_role_permissions_permission_id → permissions(id) ON DELETE CASCADE

---

#### Table: `branches`

Branch/outlet information

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| code | VARCHAR(10) | UNIQUE, NOT NULL | Branch code |
| name | VARCHAR(100) | NOT NULL | Branch name |
| address | TEXT | | Full address |
| city | VARCHAR(50) | | City |
| state | VARCHAR(50) | | State |
| postcode | VARCHAR(10) | | Postcode |
| phone | VARCHAR(20) | | Phone number |
| email | VARCHAR(100) | | Email address |
| manager_id | BIGINT UNSIGNED | FK, NULL | Branch manager user ID |
| status | ENUM('active','inactive','suspended') | NOT NULL, DEFAULT 'active' | Branch status |
| open_time | TIME | NOT NULL, DEFAULT '09:00:00' | Opening time |
| close_time | TIME | NOT NULL, DEFAULT '18:00:00' | Closing time |
| timezone | VARCHAR(50) | DEFAULT 'Asia/Kuala_Lumpur' | Branch timezone |
| is_hq | BOOLEAN | DEFAULT FALSE | Is HQ branch |
| position_limit_myr | DECIMAL(15,2) | DEFAULT 100000.00 | Position limit in MYR |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (code)
- KEY (status)

**Foreign Keys:**
- FK_branches_manager_id → users(id) ON DELETE SET NULL

---

#### Table: `counters`

Counter/workstation within a branch

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| code | VARCHAR(10) | NOT NULL | Counter code |
| name | VARCHAR(50) | NOT NULL | Counter name |
| status | ENUM('active','inactive') | NOT NULL, DEFAULT 'active' | Counter status |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Unique Key:** (branch_id, code)

**Foreign Keys:**
- FK_counters_branch_id → branches(id) ON DELETE CASCADE

---

#### Table: `counter_sessions`

Counter opening/closing sessions

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| counter_id | BIGINT UNSIGNED | FK, NOT NULL | Counter reference |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | User who opened |
| session_date | DATE | NOT NULL | Business date |
| opened_at | DATETIME | NOT NULL | Session opened timestamp |
| closed_at | DATETIME | NULL | Session closed timestamp |
| opened_by | BIGINT UNSIGNED | FK, NOT NULL | User who opened |
| closed_by | BIGINT UNSIGNED | FK, NULL | User who closed |
| opening_float_myr | DECIMAL(15,2) | NOT NULL | Opening float in MYR |
| closing_float_myr | DECIMAL(15,2) | NULL | Closing float in MYR |
| status | ENUM('open','closed') | NOT NULL, DEFAULT 'open' | Session status |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- KEY (counter_id, session_date)
- KEY (status)

**Foreign Keys:**
- FK_counter_sessions_counter_id → counters(id) ON DELETE RESTRICT
- FK_counter_sessions_user_id → users(id) ON DELETE RESTRICT
- FK_counter_sessions_opened_by → users(id) ON DELETE RESTRICT
- FK_counter_sessions_closed_by → users(id) ON DELETE SET NULL

---

#### Table: `currencies`

Currency master data

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | SMALLINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| code | CHAR(3) | UNIQUE, NOT NULL | ISO 4217 currency code |
| name | VARCHAR(50) | NOT NULL | Currency name |
| symbol | VARCHAR(5) | NOT NULL | Currency symbol |
| decimal_places | TINYINT UNSIGNED | DEFAULT 2 | Decimal places |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| is_base | BOOLEAN | DEFAULT FALSE | Is base currency (MYR) |
| country | VARCHAR(50) | | Country/region |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Pre-defined Currencies:**
MYR (base), USD, EUR, GBP, JPY, AUD, CAD, CHF, CNY, HKD, SGD, THB, IDR, PHP, INR, KRW, TWD, AED, SAR, BND

---

#### Table: `currency_pairs`

Active trading currency pairs

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| from_currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Source currency |
| to_currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Target currency |
| pair_code | VARCHAR(7) | UNIQUE, NOT NULL | Pair code (e.g., 'USD/MYR') |
| default_spread | DECIMAL(8,6) | NOT NULL | Default spread percentage |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| display_order | INT UNSIGNED | DEFAULT 0 | Display order |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Foreign Keys:**
- FK_currency_pairs_from_currency_id → currencies(id) ON DELETE RESTRICT
- FK_currency_pairs_to_currency_id → currencies(id) ON DELETE RESTRICT

---

#### Table: `exchange_rates`

Exchange rate history

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| currency_pair_id | INT UNSIGNED | FK, NOT NULL | Currency pair reference |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch-specific (NULL = all branches) |
| effective_date | DATE | NOT NULL | Effective date |
| effective_time | TIME | NOT NULL | Effective time |
| buy_rate | DECIMAL(12,6) | NOT NULL | We buy at this rate |
| sell_rate | DECIMAL(12,6) | NOT NULL | We sell at this rate |
| base_rate | DECIMAL(12,6) | NOT NULL | Base/market rate |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | User who set the rate |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (currency_pair_id, effective_date, effective_time)
- KEY (branch_id)

**Foreign Keys:**
- FK_exchange_rates_currency_pair_id → currency_pairs(id) ON DELETE RESTRICT
- FK_exchange_rates_branch_id → branches(id) ON DELETE CASCADE
- FK_exchange_rates_created_by → users(id) ON DELETE RESTRICT

---

#### Table: `customers`

Customer master data

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| customer_no | VARCHAR(20) | UNIQUE, NOT NULL | Customer number |
| type | ENUM('individual','corporate') | NOT NULL | Customer type |
| name | VARCHAR(200) | NOT NULL | Full name / Company name |
| id_type | ENUM('mykad','passport','br_no','other') | NOT NULL | ID type |
| id_number | VARCHAR(50) | NOT NULL | ID number |
| id_expiry_date | DATE | NULL | ID expiry (passport) |
| nationality | VARCHAR(50) | NOT NULL | Nationality |
| date_of_birth | DATE | NULL | Date of birth |
| gender | ENUM('male','female','other') | NULL | Gender |
| occupation | VARCHAR(100) | NULL | Occupation |
| address | TEXT | NULL | Full address |
| city | VARCHAR(50) | NULL | City |
| state | VARCHAR(50) | NULL | State |
| postcode | VARCHAR(10) | NULL | Postcode |
| country | VARCHAR(50) | NOT NULL | Country |
| phone | VARCHAR(20) | NULL | Phone number |
| email | VARCHAR(100) | NULL | Email address |
| pep_status | BOOLEAN | DEFAULT FALSE | Is PEP |
| risk_level | ENUM('low','medium','high') | NOT NULL, DEFAULT 'low' | Risk level |
| risk_score | DECIMAL(5,2) | DEFAULT 0.00 | Calculated risk score |
| verification_level | ENUM('none','basic','standard','enhanced') | DEFAULT 'none' | CDD level |
| verification_date | DATE | NULL | Last verification date |
| status | ENUM('active','inactive','suspended','watchlisted') | NOT NULL, DEFAULT 'active' | Status |
| registered_branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch where registered |
| registered_by | BIGINT UNSIGNED | FK, NOT NULL | User who registered |
| notes | TEXT | NULL | Additional notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (customer_no)
- KEY (id_type, id_number)
- KEY (risk_level)
- KEY (status)
- KEY (registered_branch_id)

**Foreign Keys:**
- FK_customers_registered_branch_id → branches(id) ON DELETE RESTRICT
- FK_customers_registered_by → users(id) ON DELETE RESTRICT

---

#### Table: `customer_documents`

Customer KYC documents

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| customer_id | BIGINT UNSIGNED | FK, NOT NULL | Customer reference |
| document_type | VARCHAR(50) | NOT NULL | Document type |
| document_name | VARCHAR(200) | NOT NULL | Document name |
| file_path | VARCHAR(500) | NOT NULL | File path |
| file_size | INT UNSIGNED | NOT NULL | File size in bytes |
| file_type | VARCHAR(50) | NOT NULL | MIME type |
| expiry_date | DATE | NULL | Document expiry |
| verified | BOOLEAN | DEFAULT FALSE | Verified status |
| verified_by | BIGINT UNSIGNED | FK, NULL | Verified by user |
| verified_at | DATETIME | NULL | Verification timestamp |
| uploaded_by | BIGINT UNSIGNED | FK, NOT NULL | Uploaded by user |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- KEY (customer_id)
- KEY (document_type)

**Foreign Keys:**
- FK_customer_documents_customer_id → customers(id) ON DELETE CASCADE
- FK_customer_documents_verified_by → users(id) ON DELETE SET NULL
- FK_customer_documents_uploaded_by → users(id) ON DELETE RESTRICT

---

#### Table: `customer_transactions`

Customer transaction history link

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| customer_id | BIGINT UNSIGNED | FK, NOT NULL | Customer reference |
| transaction_id | BIGINT UNSIGNED | FK, NOT NULL | Transaction reference |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Unique Key:** (customer_id, transaction_id)

**Foreign Keys:**
- FK_customer_transactions_customer_id → customers(id) ON DELETE RESTRICT
- FK_customer_transactions_transaction_id → transactions(id) ON DELETE RESTRICT

---

#### Table: `transactions`

Main transaction records

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| transaction_no | VARCHAR(30) | UNIQUE, NOT NULL | Transaction number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| counter_id | BIGINT UNSIGNED | FK, NOT NULL | Counter reference |
| counter_session_id | BIGINT UNSIGNED | FK, NOT NULL | Session reference |
| transaction_date | DATE | NOT NULL | Transaction date |
| transaction_time | TIME | NOT NULL | Transaction time |
| transaction_type | ENUM('buy','sell','exchange') | NOT NULL | Transaction type |
| from_currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Source currency |
| to_currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Target currency |
| from_amount | DECIMAL(18,4) | NOT NULL | Amount in source currency |
| to_amount | DECIMAL(18,4) | NOT NULL | Amount in target currency |
| rate | DECIMAL(12,6) | NOT NULL | Applied rate |
| rate_override | BOOLEAN | DEFAULT FALSE | Was rate overridden |
| rate_override_approved_by | BIGINT UNSIGNED | FK, NULL | Approver (if override) |
| base_rate | DECIMAL(12,6) | NOT NULL | Base rate at time |
| spread | DECIMAL(8,6) | NOT NULL | Spread percentage |
| gross_profit | DECIMAL(15,2) | NOT NULL | Gross profit (MYR) |
| cost_of_sales | DECIMAL(15,2) | NOT NULL | Cost of sales (MYR) |
| payment_method | ENUM('cash','bank_transfer') | NOT NULL, DEFAULT 'cash' | Payment method |
| customer_type | ENUM('walk_in','registered') | NOT NULL | Customer type |
| customer_id | BIGINT UNSIGNED | FK, NULL | Customer reference (if registered) |
| customer_name | VARCHAR(200) | NULL | Customer name (walk-in) |
| customer_id_type | VARCHAR(20) | NULL | ID type (walk-in) |
| customer_id_number | VARCHAR(50) | NULL | ID number (walk-in) |
| purpose | VARCHAR(200) | NULL | Purpose (if ≥50k) |
| status | ENUM('completed','cancelled','reversed') | NOT NULL, DEFAULT 'completed' | Status |
| cancelled_by | BIGINT UNSIGNED | FK, NULL | Cancelled by |
| cancelled_at | DATETIME | NULL | Cancelled timestamp |
| cancellation_reason | TEXT | NULL | Cancellation reason |
| reversal_of | BIGINT UNSIGNED | FK, NULL | Original transaction (if reversal) |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | Created by user |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (transaction_no)
- KEY (branch_id, transaction_date)
- KEY (transaction_date)
- KEY (customer_id)
- KEY (status)

**Foreign Keys:**
- FK_transactions_branch_id → branches(id) ON DELETE RESTRICT
- FK_transactions_counter_id → counters(id) ON DELETE RESTRICT
- FK_transactions_counter_session_id → counter_sessions(id) ON DELETE RESTRICT
- FK_transactions_from_currency_id → currencies(id) ON DELETE RESTRICT
- FK_transactions_to_currency_id → currencies(id) ON DELETE RESTRICT
- FK_transactions_customer_id → customers(id) ON DELETE SET NULL
- FK_transactions_rate_override_approved_by → users(id) ON DELETE SET NULL
- FK_transactions_cancelled_by → users(id) ON DELETE SET NULL
- FK_transactions_reversal_of → transactions(id) ON DELETE SET NULL
- FK_transactions_created_by → users(id) ON DELETE RESTRICT

---

#### Table: `transaction_items`

Transaction line items (for denomination capture)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| transaction_id | BIGINT UNSIGNED | FK, NOT NULL | Transaction reference |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency |
| denomination | DECIMAL(18,4) | NOT NULL | Denomination value |
| quantity | INT UNSIGNED | NOT NULL | Quantity |
| total | DECIMAL(18,4) | NOT NULL | Total amount |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Foreign Keys:**
- FK_transaction_items_transaction_id → transactions(id) ON DELETE CASCADE
- FK_transaction_items_currency_id → currencies(id) ON DELETE RESTRICT

---

#### Table: `stocks`

Stock balance by currency, branch, location

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency reference |
| location | ENUM('safe','counter','vault','transit') | NOT NULL, DEFAULT 'safe' | Stock location |
| balance | DECIMAL(18,4) | NOT NULL, DEFAULT 0.0000 | Current balance |
| cost_value_myr | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Cost value in MYR |
| avg_cost | DECIMAL(12,6) | NOT NULL, DEFAULT 0.000000 | Average cost rate |
| last_updated | DATETIME | NOT NULL | Last update timestamp |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Unique Key:** (branch_id, currency_id, location)

**Foreign Keys:**
- FK_stocks_branch_id → branches(id) ON DELETE RESTRICT
- FK_stocks_currency_id → currencies(id) ON DELETE RESTRICT

---

#### Table: `stock_movements`

Stock movement history

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| movement_no | VARCHAR(30) | UNIQUE, NOT NULL | Movement number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency reference |
| movement_type | ENUM('buy','sell','transfer_in','transfer_out','deposit','withdrawal','adjustment','opening') | NOT NULL | Movement type |
| direction | ENUM('in','out') | NOT NULL | Direction |
| amount | DECIMAL(18,4) | NOT NULL | Amount moved |
| cost_myr | DECIMAL(15,2) | NOT NULL | Cost in MYR |
| from_location | VARCHAR(20) | NULL | Source location |
| to_location | VARCHAR(20) | NULL | Target location |
| source_type | VARCHAR(50) | NOT NULL | Source type (transaction, transfer, manual) |
| source_id | BIGINT UNSIGNED | NULL | Source reference ID |
| reference | VARCHAR(100) | NULL | Reference number |
| notes | TEXT | NULL | Notes |
| approved_by | BIGINT UNSIGNED | FK, NULL | Approved by user |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | Created by user |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (movement_no)
- KEY (branch_id, currency_id)
- KEY (movement_type)
- KEY (created_at)

**Foreign Keys:**
- FK_stock_movements_branch_id → branches(id) ON DELETE RESTRICT
- FK_stock_movements_currency_id → currencies(id) ON DELETE RESTRICT
- FK_stock_movements_approved_by → users(id) ON DELETE SET NULL
- FK_stock_movements_created_by → users(id) ON DELETE RESTRICT

---

#### Table: `stock_counts`

Physical stock count records

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency reference |
| location | VARCHAR(20) | NOT NULL | Location |
| count_date | DATE | NOT NULL | Count date |
| count_time | TIME | NOT NULL | Count time |
| system_balance | DECIMAL(18,4) | NOT NULL | System balance |
| physical_balance | DECIMAL(18,4) | NOT NULL | Physical count |
| variance | DECIMAL(18,4) | NOT NULL | Variance amount |
| variance_myr | DECIMAL(15,2) | NOT NULL | Variance in MYR |
| status | ENUM('pending','investigating','resolved','posted') | NOT NULL, DEFAULT 'pending' | Status |
| investigation_notes | TEXT | NULL | Investigation notes |
| resolution | TEXT | NULL | Resolution details |
| counted_by | BIGINT UNSIGNED | FK, NOT NULL | Counted by user |
| verified_by | BIGINT UNSIGNED | FK, NULL | Verified by user |
| approved_by | BIGINT UNSIGNED | FK, NULL | Approved by user |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- KEY (branch_id, count_date)
- KEY (status)

**Foreign Keys:**
- FK_stock_counts_branch_id → branches(id) ON DELETE RESTRICT
- FK_stock_counts_currency_id → currencies(id) ON DELETE RESTRICT
- FK_stock_counts_counted_by → users(id) ON DELETE RESTRICT
- FK_stock_counts_verified_by → users(id) ON DELETE SET NULL
- FK_stock_counts_approved_by → users(id) ON DELETE SET NULL

---

#### Table: `accounts`

Chart of accounts

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| code | VARCHAR(10) | UNIQUE, NOT NULL | Account code |
| name | VARCHAR(100) | NOT NULL | Account name |
| account_type | ENUM('asset','liability','equity','revenue','expense') | NOT NULL | Account type |
| parent_id | INT UNSIGNED | FK, NULL | Parent account |
| currency_id | SMALLINT UNSIGNED | FK, NULL | Currency (for multi-currency accounts) |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| is_system | BOOLEAN | DEFAULT FALSE | System account |
| opening_balance | DECIMAL(15,2) | DEFAULT 0.00 | Opening balance |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch-specific (NULL = all) |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Foreign Keys:**
- FK_accounts_parent_id → accounts(id) ON DELETE SET NULL
- FK_accounts_currency_id → currencies(id) ON DELETE SET NULL
- FK_accounts_branch_id → branches(id) ON DELETE CASCADE

---

#### Table: `journal_entries`

Journal entries (double-entry)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| entry_no | VARCHAR(30) | UNIQUE, NOT NULL | Entry number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| entry_date | DATE | NOT NULL | Entry date |
| entry_type | ENUM('auto','manual','adjustment','opening','reversal') | NOT NULL | Entry type |
| source_type | VARCHAR(50) | NULL | Source type |
| source_id | BIGINT UNSIGNED | NULL | Source reference |
| description | TEXT | NOT NULL | Description |
| total_debit | DECIMAL(15,2) | NOT NULL | Total debit |
| total_credit | DECIMAL(15,2) | NOT NULL | Total credit |
| status | ENUM('draft','approved','posted','cancelled') | NOT NULL, DEFAULT 'draft' | Status |
| approved_by | BIGINT UNSIGNED | FK, NULL | Approved by |
| approved_at | DATETIME | NULL | Approval timestamp |
| posted_by | BIGINT UNSIGNED | FK, NULL | Posted by |
| posted_at | DATETIME | NULL | Post timestamp |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | Created by |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (entry_no)
- KEY (branch_id, entry_date)
- KEY (entry_type)
- KEY (status)

**Foreign Keys:**
- FK_journal_entries_branch_id → branches(id) ON DELETE RESTRICT
- FK_journal_entries_approved_by → users(id) ON DELETE SET NULL
- FK_journal_entries_posted_by → users(id) ON DELETE SET NULL
- FK_journal_entries_created_by → users(id) ON DELETE RESTRICT

---

#### Table: `journal_entry_lines`

Journal entry line items

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| journal_entry_id | BIGINT UNSIGNED | FK, NOT NULL | Journal entry reference |
| account_id | INT UNSIGNED | FK, NOT NULL | Account reference |
| debit | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Debit amount |
| credit | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Credit amount |
| currency_id | SMALLINT UNSIGNED | FK, NULL | Foreign currency (if applicable) |
| foreign_amount | DECIMAL(18,4) | NULL | Amount in foreign currency |
| exchange_rate | DECIMAL(12,6) | NULL | Exchange rate used |
| description | TEXT | NULL | Line description |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (journal_entry_id)
- KEY (account_id)

**Foreign Keys:**
- FK_journal_entry_lines_journal_entry_id → journal_entries(id) ON DELETE CASCADE
- FK_journal_entry_lines_account_id → accounts(id) ON DELETE RESTRICT
- FK_journal_entry_lines_currency_id → currencies(id) ON DELETE SET NULL

---

#### Table: `inter_branch_transfers`

Inter-branch stock transfers

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| transfer_no | VARCHAR(30) | UNIQUE, NOT NULL | Transfer number |
| from_branch_id | BIGINT UNSIGNED | FK, NOT NULL | Source branch |
| to_branch_id | BIGINT UNSIGNED | FK, NOT NULL | Target branch |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency |
| amount | DECIMAL(18,4) | NOT NULL | Amount |
| transfer_rate | DECIMAL(12,6) | NOT NULL | Transfer rate |
| transfer_value_myr | DECIMAL(15,2) | NOT NULL | Value in MYR |
| status | ENUM('requested','approved','dispatched','in_transit','received','completed','cancelled') | NOT NULL | Status |
| requested_by | BIGINT UNSIGNED | FK, NOT NULL | Requested by |
| requested_at | DATETIME | NOT NULL | Request timestamp |
| approved_by | BIGINT UNSIGNED | FK, NULL | Approved by |
| approved_at | DATETIME | NULL | Approval timestamp |
| dispatched_by | BIGINT UNSIGNED | FK, NULL | Dispatched by |
| dispatched_at | DATETIME | NULL | Dispatch timestamp |
| received_by | BIGINT UNSIGNED | FK, NULL | Received by |
| received_at | DATETIME | NULL | Receive timestamp |
| received_amount | DECIMAL(18,4) | NULL | Actual received amount |
| variance | DECIMAL(18,4) | NULL | Variance |
| variance_reason | TEXT | NULL | Variance reason |
| notes | TEXT | NULL | Notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (transfer_no)
- KEY (from_branch_id)
- KEY (to_branch_id)
- KEY (status)

**Foreign Keys:**
- FK_inter_branch_transfers_from_branch_id → branches(id) ON DELETE RESTRICT
- FK_inter_branch_transfers_to_branch_id → branches(id) ON DELETE RESTRICT
- FK_inter_branch_transfers_currency_id → currencies(id) ON DELETE RESTRICT
- FK_inter_branch_transfers_requested_by → users(id) ON DELETE RESTRICT
- FK_inter_branch_transfers_approved_by → users(id) ON DELETE SET NULL
- FK_inter_branch_transfers_dispatched_by → users(id) ON DELETE SET NULL
- FK_inter_branch_transfers_received_by → users(id) ON DELETE SET NULL

---

#### Table: `aml_alerts`

AML transaction monitoring alerts

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| alert_no | VARCHAR(30) | UNIQUE, NOT NULL | Alert number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| alert_type | VARCHAR(50) | NOT NULL | Alert type |
| rule_name | VARCHAR(100) | NOT NULL | Rule name |
| severity | ENUM('low','medium','high','critical') | NOT NULL | Severity |
| transaction_ids | JSON | NOT NULL | Related transaction IDs |
| customer_id | BIGINT UNSIGNED | FK, NULL | Customer reference |
| details | JSON | NOT NULL | Alert details |
| status | ENUM('new','under_review','investigating','resolved','escalated') | NOT NULL, DEFAULT 'new' | Status |
| assigned_to | BIGINT UNSIGNED | FK, NULL | Assigned to user |
| assigned_at | DATETIME | NULL | Assignment timestamp |
| investigation_notes | TEXT | NULL | Investigation notes |
| resolution | TEXT | NULL | Resolution |
| resolved_by | BIGINT UNSIGNED | FK, NULL | Resolved by |
| resolved_at | DATETIME | NULL | Resolution timestamp |
| is_str_candidate | BOOLEAN | DEFAULT FALSE | STR candidate flag |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (alert_no)
- KEY (branch_id)
- KEY (alert_type)
- KEY (status)
- KEY (severity)
- KEY (customer_id)

**Foreign Keys:**
- FK_aml_alerts_branch_id → branches(id) ON DELETE RESTRICT
- FK_aml_alerts_customer_id → customers(id) ON DELETE SET NULL
- FK_aml_alerts_assigned_to → users(id) ON DELETE SET NULL
- FK_aml_alerts_resolved_by → users(id) ON DELETE SET NULL

---

#### Table: `str_reports`

Suspicious Transaction Reports

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| str_no | VARCHAR(30) | UNIQUE, NOT NULL | STR number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| customer_id | BIGINT UNSIGNED | FK, NOT NULL | Customer reference |
| alert_id | BIGINT UNSIGNED | FK, NULL | Related alert |
| transaction_ids | JSON | NOT NULL | Related transaction IDs |
| reason | TEXT | NOT NULL | Suspicion reason |
| supporting_documents | JSON | NULL | Document references |
| status | ENUM('draft','pending_approval','submitted','acknowledged') | NOT NULL, DEFAULT 'draft' | Status |
| drafted_by | BIGINT UNSIGNED | FK, NOT NULL | Drafted by |
| drafted_at | DATETIME | NOT NULL | Draft timestamp |
| approved_by | BIGINT UNSIGNED | FK, NULL | Approved by |
| approved_at | DATETIME | NULL | Approval timestamp |
| submitted_by | BIGINT UNSIGNED | FK, NULL | Submitted by |
| submitted_at | DATETIME | NULL | Submission timestamp |
| bnm_reference | VARCHAR(50) | NULL | BNM acknowledgment reference |
| notes | TEXT | NULL | Notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Foreign Keys:**
- FK_str_reports_branch_id → branches(id) ON DELETE RESTRICT
- FK_str_reports_customer_id → customers(id) ON DELETE RESTRICT
- FK_str_reports_alert_id → aml_alerts(id) ON DELETE SET NULL
- FK_str_reports_drafted_by → users(id) ON DELETE RESTRICT
- FK_str_reports_approved_by → users(id) ON DELETE SET NULL
- FK_str_reports_submitted_by → users(id) ON DELETE SET NULL

---

#### Table: `ctr_reports`

Cash Transaction Reports (≥RM50,000)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| ctr_no | VARCHAR(30) | UNIQUE, NOT NULL | CTR number |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| transaction_id | BIGINT UNSIGNED | FK, NOT NULL | Transaction reference |
| customer_id | BIGINT UNSIGNED | FK, NOT NULL | Customer reference |
| amount_myr | DECIMAL(15,2) | NOT NULL | Transaction amount |
| purpose | VARCHAR(200) | NULL | Purpose |
| status | ENUM('generated','submitted','acknowledged') | NOT NULL, DEFAULT 'generated' | Status |
| generated_at | DATETIME | NOT NULL | Generation timestamp |
| submitted_at | DATETIME | NULL | Submission timestamp |
| bnm_reference | VARCHAR(50) | NULL | BNM reference |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Foreign Keys:**
- FK_ctr_reports_branch_id → branches(id) ON DELETE RESTRICT
- FK_ctr_reports_transaction_id → transactions(id) ON DELETE RESTRICT
- FK_ctr_reports_customer_id → customers(id) ON DELETE RESTRICT

---

#### Table: `tasks`

Workflow tasks

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| task_no | VARCHAR(30) | UNIQUE, NOT NULL | Task number |
| title | VARCHAR(200) | NOT NULL | Task title |
| description | TEXT | NULL | Task description |
| category | ENUM('compliance','customer','operations','admin','approval') | NOT NULL | Category |
| priority | ENUM('low','medium','high','urgent') | NOT NULL, DEFAULT 'medium' | Priority |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch reference |
| assigned_to | BIGINT UNSIGNED | FK, NULL | Assigned to user |
| assigned_role_id | TINYINT UNSIGNED | FK, NULL | Assigned to role |
| due_date | DATE | NULL | Due date |
| status | ENUM('pending','in_progress','completed','cancelled') | NOT NULL, DEFAULT 'pending' | Status |
| source_type | VARCHAR(50) | NULL | Source type |
| source_id | BIGINT UNSIGNED | NULL | Source reference |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | Created by |
| completed_by | BIGINT UNSIGNED | FK, NULL | Completed by |
| completed_at | DATETIME | NULL | Completion timestamp |
| notes | TEXT | NULL | Notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (task_no)
- KEY (branch_id)
- KEY (assigned_to)
- KEY (category)
- KEY (status)
- KEY (due_date)

**Foreign Keys:**
- FK_tasks_branch_id → branches(id) ON DELETE CASCADE
- FK_tasks_assigned_to → users(id) ON DELETE SET NULL
- FK_tasks_assigned_role_id → roles(id) ON DELETE SET NULL
- FK_tasks_created_by → users(id) ON DELETE RESTRICT
- FK_tasks_completed_by → users(id) ON DELETE SET NULL

---

#### Table: `task_comments`

Task comments/updates

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| task_id | BIGINT UNSIGNED | FK, NOT NULL | Task reference |
| comment | TEXT | NOT NULL | Comment |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | Created by |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Foreign Keys:**
- FK_task_comments_task_id → tasks(id) ON DELETE CASCADE
- FK_task_comments_created_by → users(id) ON DELETE RESTRICT

---

#### Table: `notifications`

User notifications

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | User reference |
| title | VARCHAR(200) | NOT NULL | Notification title |
| message | TEXT | NOT NULL | Message |
| type | VARCHAR(50) | NOT NULL | Notification type |
| link | VARCHAR(200) | NULL | Link to related item |
| is_read | BOOLEAN | DEFAULT FALSE | Read status |
| read_at | DATETIME | NULL | Read timestamp |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (user_id, is_read)
- KEY (created_at)

**Foreign Keys:**
- FK_notifications_user_id → users(id) ON DELETE CASCADE

---

#### Table: `audit_logs`

Comprehensive audit trail

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| user_id | BIGINT UNSIGNED | FK, NULL | User reference |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch reference |
| action | VARCHAR(50) | NOT NULL | Action type |
| module | VARCHAR(50) | NOT NULL | Module name |
| entity_type | VARCHAR(50) | NOT NULL | Entity type |
| entity_id | BIGINT UNSIGNED | NULL | Entity ID |
| old_values | JSON | NULL | Old values (JSON) |
| new_values | JSON | NULL | New values (JSON) |
| ip_address | VARCHAR(45) | NULL | IP address |
| user_agent | VARCHAR(500) | NULL | User agent |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (user_id)
- KEY (branch_id)
- KEY (action)
- KEY (module)
- KEY (entity_type, entity_id)
- KEY (created_at)

**Foreign Keys:**
- FK_audit_logs_user_id → users(id) ON DELETE SET NULL
- FK_audit_logs_branch_id → branches(id) ON DELETE SET NULL

---

#### Table: `reports`

Generated reports

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| report_type | VARCHAR(50) | NOT NULL | Report type |
| report_name | VARCHAR(100) | NOT NULL | Report name |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch reference (NULL = all) |
| period_start | DATE | NULL | Period start |
| period_end | DATE | NULL | Period end |
| parameters | JSON | NULL | Report parameters |
| file_path | VARCHAR(500) | NULL | File path |
| file_type | VARCHAR(20) | NOT NULL | File type (pdf, xlsx, csv) |
| file_size | INT UNSIGNED | NULL | File size in bytes |
| generated_by | BIGINT UNSIGNED | FK, NOT NULL | Generated by user |
| generated_at | DATETIME | NOT NULL | Generation timestamp |
| status | ENUM('generating','completed','failed') | NOT NULL | Status |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (report_type)
- KEY (branch_id)
- KEY (generated_at)

**Foreign Keys:**
- FK_reports_branch_id → branches(id) ON DELETE CASCADE
- FK_reports_generated_by → users(id) ON DELETE RESTRICT

---

#### Table: `settings`

System configuration settings

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| category | VARCHAR(50) | NOT NULL | Setting category |
| key | VARCHAR(100) | NOT NULL | Setting key |
| value | TEXT | NULL | Setting value |
| type | ENUM('string','integer','decimal','boolean','json') | NOT NULL, DEFAULT 'string' | Value type |
| description | TEXT | NULL | Description |
| is_public | BOOLEAN | DEFAULT FALSE | Public (frontend) setting |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Unique Key:** (category, key)

---

#### Table: `positions`

Currency position tracking

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| currency_id | SMALLINT UNSIGNED | FK, NOT NULL | Currency reference |
| position_date | DATE | NOT NULL | Position date |
| opening_balance | DECIMAL(18,4) | NOT NULL | Opening balance |
| buy_volume | DECIMAL(18,4) | NOT NULL, DEFAULT 0 | Buy volume |
| sell_volume | DECIMAL(18,4) | NOT NULL, DEFAULT 0 | Sell volume |
| transfers_in | DECIMAL(18,4) | NOT NULL, DEFAULT 0 | Transfers in |
| transfers_out | DECIMAL(18,4) | NOT NULL, DEFAULT 0 | Transfers out |
| adjustments | DECIMAL(18,4) | NOT NULL, DEFAULT 0 | Adjustments |
| closing_balance | DECIMAL(18,4) | NOT NULL | Closing balance |
| avg_cost | DECIMAL(12,6) | NOT NULL | Average cost |
| closing_value_myr | DECIMAL(15,2) | NOT NULL | Closing value in MYR |
| unrealized_pnl | DECIMAL(15,2) | NOT NULL | Unrealized P&L |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Unique Key:** (branch_id, currency_id, position_date)

**Foreign Keys:**
- FK_positions_branch_id → branches(id) ON DELETE RESTRICT
- FK_positions_currency_id → currencies(id) ON DELETE RESTRICT

---

#### Table: `position_limits`

Position limit configuration

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NULL | Branch (NULL = company-wide) |
| currency_id | SMALLINT UNSIGNED | FK, NULL | Currency (NULL = all currencies) |
| limit_type | ENUM('single_currency','total_position','imbalance','intraday') | NOT NULL | Limit type |
| limit_myr | DECIMAL(15,2) | NOT NULL | Limit in MYR |
| alert_threshold_1 | DECIMAL(5,2) | DEFAULT 80.00 | Alert at % (e.g., 80%) |
| alert_threshold_2 | DECIMAL(5,2) | DEFAULT 90.00 | Alert at % (e.g., 90%) |
| breach_threshold | DECIMAL(5,2) | DEFAULT 100.00 | Breach at % |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Foreign Keys:**
- FK_position_limits_branch_id → branches(id) ON DELETE CASCADE
- FK_position_limits_currency_id → currencies(id) ON DELETE CASCADE

---

#### Table: `day_end_closures`

Day-end closure records

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | FK, NOT NULL | Branch reference |
| closure_date | DATE | NOT NULL | Closure date |
| status | ENUM('open','in_progress','closed') | NOT NULL, DEFAULT 'open' | Status |
| opened_by | BIGINT UNSIGNED | FK, NOT NULL | Opened by user |
| opened_at | DATETIME | NOT NULL | Open timestamp |
| closed_by | BIGINT UNSIGNED | FK, NULL | Closed by user |
| closed_at | DATETIME | NULL | Close timestamp |
| total_transactions | INT UNSIGNED | DEFAULT 0 | Total transactions |
| total_volume_myr | DECIMAL(15,2) | DEFAULT 0.00 | Total volume |
| gross_profit_myr | DECIMAL(15,2) | DEFAULT 0.00 | Gross profit |
| total_variances | INT UNSIGNED | DEFAULT 0 | Total variance count |
| variance_myr | DECIMAL(15,2) | DEFAULT 0.00 | Total variance value |
| checklist_completed | BOOLEAN | DEFAULT FALSE | Checklist completed |
| notes | TEXT | NULL | Notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Unique Key:** (branch_id, closure_date)

**Foreign Keys:**
- FK_day_end_closures_branch_id → branches(id) ON DELETE RESTRICT
- FK_day_end_closures_opened_by → users(id) ON DELETE RESTRICT
- FK_day_end_closures_closed_by → users(id) ON DELETE SET NULL

---

#### Table: `login_attempts`

Login attempt tracking

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| username | VARCHAR(50) | NOT NULL | Username attempted |
| ip_address | VARCHAR(45) | NOT NULL | IP address |
| user_agent | VARCHAR(500) | NULL | User agent |
| successful | BOOLEAN | NOT NULL | Success status |
| user_id | BIGINT UNSIGNED | FK, NULL | User (if successful) |
| failure_reason | VARCHAR(100) | NULL | Failure reason |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |

**Indexes:**
- PRIMARY KEY (id)
- KEY (username)
- KEY (ip_address)
- KEY (created_at)

**Foreign Keys:**
- FK_login_attempts_user_id → users(id) ON DELETE SET NULL

---

### 5.4 Views

#### View: `v_transaction_summary`

Daily transaction summary for reporting

```sql
CREATE VIEW v_transaction_summary AS
SELECT 
    t.branch_id,
    b.name AS branch_name,
    t.transaction_date,
    t.transaction_type,
    cf.code AS from_currency,
    ct.code AS to_currency,
    COUNT(*) AS transaction_count,
    SUM(t.from_amount) AS total_from_amount,
    SUM(t.to_amount) AS total_to_amount,
    SUM(t.gross_profit) AS total_profit,
    SUM(t.cost_of_sales) AS total_cost
FROM transactions t
JOIN branches b ON t.branch_id = b.id
JOIN currencies cf ON t.from_currency_id = cf.id
JOIN currencies ct ON t.to_currency_id = ct.id
WHERE t.status = 'completed'
GROUP BY t.branch_id, b.name, t.transaction_date, t.transaction_type, cf.code, ct.code;
```

#### View: `v_stock_position`

Current stock position by branch and currency

```sql
CREATE VIEW v_stock_position AS
SELECT 
    s.branch_id,
    b.name AS branch_name,
    s.currency_id,
    c.code AS currency_code,
    c.name AS currency_name,
    s.location,
    s.balance,
    s.cost_value_myr,
    s.avg_cost,
    s.last_updated
FROM stocks s
JOIN branches b ON s.branch_id = b.id
JOIN currencies c ON s.currency_id = c.id;
```

#### View: `v_customer_summary`

Customer transaction summary

```sql
CREATE VIEW v_customer_summary AS
SELECT 
    c.id,
    c.customer_no,
    c.name,
    c.risk_level,
    c.status,
    COUNT(DISTINCT ct.transaction_id) AS transaction_count,
    COALESCE(SUM(t.to_amount), 0) AS total_volume_myr,
    MAX(t.transaction_date) AS last_transaction_date
FROM customers c
LEFT JOIN customer_transactions ct ON c.id = ct.customer_id
LEFT JOIN transactions t ON ct.transaction_id = t.id AND t.status = 'completed'
GROUP BY c.id, c.customer_no, c.name, c.risk_level, c.status;
```

---

### 5.5 Stored Procedures

#### Procedure: `sp_process_transaction`

Process a complete transaction with stock and accounting updates

```sql
DELIMITER //
CREATE PROCEDURE sp_process_transaction(
    IN p_branch_id BIGINT,
    IN p_counter_id BIGINT,
    IN p_counter_session_id BIGINT,
    IN p_transaction_type VARCHAR(10),
    IN p_from_currency_id SMALLINT,
    IN p_to_currency_id SMALLINT,
    IN p_from_amount DECIMAL(18,4),
    IN p_to_amount DECIMAL(18,4),
    IN p_rate DECIMAL(12,6),
    IN p_payment_method VARCHAR(20),
    IN p_customer_id BIGINT,
    IN p_created_by BIGINT,
    OUT p_transaction_id BIGINT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(500)
)
BEGIN
    DECLARE v_transaction_no VARCHAR(30);
    DECLARE v_gross_profit DECIMAL(15,2);
    DECLARE v_cost_of_sales DECIMAL(15,2);
    DECLARE v_base_rate DECIMAL(12,6);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Transaction processing failed';
    END;
    
    START TRANSACTION;
    
    -- Generate transaction number
    SET v_transaction_no = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 99999), 5, '0'));
    
    -- Calculate base rate and profit (simplified)
    SET v_base_rate = p_rate; -- In production, fetch from rates table
    SET v_gross_profit = ABS(p_to_amount - p_from_amount * v_base_rate);
    SET v_cost_of_sales = p_from_amount * 0.99; -- Simplified
    
    -- Insert transaction
    INSERT INTO transactions (
        transaction_no, branch_id, counter_id, counter_session_id,
        transaction_date, transaction_time, transaction_type,
        from_currency_id, to_currency_id, from_amount, to_amount,
        rate, base_rate, gross_profit, cost_of_sales,
        payment_method, customer_type, customer_id, created_by
    ) VALUES (
        v_transaction_no, p_branch_id, p_counter_id, p_counter_session_id,
        CURDATE(), CURTIME(), p_transaction_type,
        p_from_currency_id, p_to_currency_id, p_from_amount, p_to_amount,
        p_rate, v_base_rate, v_gross_profit, v_cost_of_sales,
        p_payment_method, IF(p_customer_id IS NULL, 'walk_in', 'registered'), p_customer_id, p_created_by
    );
    
    SET p_transaction_id = LAST_INSERT_ID();
    
    -- Update stock (would call separate procedures in production)
    -- Create journal entries (would call separate procedures in production)
    
    COMMIT;
    
    SET p_success = TRUE;
    SET p_message = 'Transaction processed successfully';
END //
DELIMITER ;
```

---

### 5.6 Triggers

#### Trigger: `tr_stock_movement_audit`

Log stock changes to audit trail

```sql
DELIMITER //
CREATE TRIGGER tr_stock_movement_audit
AFTER INSERT ON stock_movements
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id, branch_id, action, module,
        entity_type, entity_id, new_values, created_at
    ) VALUES (
        NEW.created_by, NEW.branch_id, 'stock_movement', 'stock',
        'stock_movement', NEW.id,
        JSON_OBJECT(
            'movement_no', NEW.movement_no,
            'currency_id', NEW.currency_id,
            'movement_type', NEW.movement_type,
            'amount', NEW.amount
        ),
        NOW()
    );
END //
DELIMITER ;
```

---

## 6. API Design

### 6.1 API Overview

**Protocol:** REST over HTTP  
**Authentication:** Session-based (PHP sessions) + CSRF tokens  
**Response Format:** JSON  
**Error Handling:** HTTP status codes + error messages

### 6.2 Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | User login |
| POST | /api/auth/logout | User logout |
| GET | /api/auth/me | Current user info |
| POST | /api/auth/password/change | Change password |

### 6.3 Transaction Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/transactions | List transactions (paginated) |
| GET | /api/transactions/{id} | Get transaction details |
| POST | /api/transactions | Create transaction |
| POST | /api/transactions/{id}/cancel | Cancel transaction |
| GET | /api/transactions/daily-summary | Daily summary |

### 6.4 Customer Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/customers | List customers (paginated) |
| GET | /api/customers/{id} | Get customer details |
| POST | /api/customers | Create customer |
| PUT | /api/customers/{id} | Update customer |
| GET | /api/customers/{id}/transactions | Customer transaction history |
| GET | /api/customers/search | Search customers |

### 6.5 Stock Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/stock | Stock position (by branch/currency) |
| POST | /api/stock/movement | Record stock movement |
| POST | /api/stock/adjustment | Stock adjustment |
| GET | /api/stock/movements | Movement history |
| POST | /api/stock/count | Physical stock count |

### 6.6 Rate Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/rates | Current rates |
| POST | /api/rates | Update rate |
| GET | /api/rates/history | Rate history |

### 6.7 Reporting Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/reports/daily | Daily report |
| GET | /api/reports/monthly | Monthly report |
| POST | /api/reports/generate | Generate custom report |
| GET | /api/reports/list | Report list |
| GET | /api/reports/{id}/download | Download report |

### 6.8 Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/users | List users |
| POST | /api/admin/users | Create user |
| PUT | /api/admin/users/{id} | Update user |
| DELETE | /api/admin/users/{id} | Deactivate user |
| GET | /api/admin/branches | List branches |
| POST | /api/admin/branches | Create branch |
| PUT | /api/admin/branches/{id} | Update branch |
| GET | /api/admin/audit-logs | Audit logs |

### 6.9 Response Format

**Success Response:**
```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation completed successfully"
}
```

**Error Response:**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "field": "amount",
            "reason": "Amount must be positive"
        }
    }
}
```

**Paginated Response:**
```json
{
    "success": true,
    "data": [
        // Items
    ],
    "pagination": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "last_page": 5,
        "from": 1,
        "to": 20
    }
}
```

---

## 7. Security Architecture

### 7.1 Authentication Security

- Password hashing: bcrypt (cost factor 12)
- Session management: PHP sessions with secure cookies
- Session timeout: 30 minutes inactive, 8 hours maximum
- Concurrent sessions: Single session per user (configurable)
- Failed login lockout: 5 attempts, 15-minute lockout
- Password reset: Time-limited tokens (1 hour expiry)

### 7.2 Authorization

- Role-based access control (RBAC)
- Permission-level granularity
- Branch-level data isolation
- Action-level permissions (view, create, edit, delete, approve, export)
- Dynamic permission checks in controllers
- Permission denied logging

### 7.3 Input Validation

- Server-side validation for all inputs
- Type checking (int, string, decimal, date, enum)
- Length validation (min, max)
- Range validation (min, max values)
- Format validation (email, phone, ID format)
- XSS prevention (output escaping)
- SQL injection prevention (prepared statements)
- CSRF protection (token-based)

### 7.4 Data Protection

- Sensitive data encryption at rest (customer IDs, documents)
- TLS 1.3 for data in transit
- Secure file upload (type validation, size limits, virus scan)
- Secure file storage (outside web root)
- Data masking in logs (passwords, full ID numbers)
- Secure backup encryption

### 7.5 Audit Trail

- All state changes logged
- User, timestamp, IP, action, old/new values
- Immutable audit log (append-only)
- 7-year retention minimum
- Regular audit log review
- Anomaly detection alerts

### 7.6 Network Security

- HTTPS mandatory (redirect HTTP)
- CORS restrictions (same-origin by default)
- Rate limiting (per IP, per user)
- IP whitelisting for admin functions
- WAF recommended (ModSecurity)
- DDoS mitigation

### 7.7 Compliance Security

- BNM IT security guidelines compliance
- PCI-DSS awareness (if card payment added)
- Personal Data Protection Act 2010 (PDPA) compliance
- Regular security assessments
- Incident response procedures

---

## 8. User Interface Design

### 8.1 Design System

**Framework:** Bootstrap 5.3  
**Icons:** Bootstrap Icons / Font Awesome  
**Colors:**

| Element | Color | Hex |
|---------|-------|-----|
| Primary | Blue | #0d6efd |
| Success | Green | #198754 |
| Warning | Yellow | #ffc107 |
| Danger | Red | #dc3545 |
| Info | Cyan | #0dcaf0 |
| Dark | Dark Gray | #212529 |
| Light | Light Gray | #f8f9fa |

**Typography:**
- Font: System UI, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial
- Base size: 16px
- Headings: 2rem (H1) to 1rem (H6)

**Components:**
- Cards for content grouping
- Tables for data display
- Modals for forms and confirmations
- Alerts for notifications
- Badges for status indicators
- Progress bars for loading states
- Toast notifications for feedback

### 8.2 Page Layouts

**Base Layout:**
- Fixed top navigation bar (logo, user menu, notifications)
- Left sidebar (collapsible) - menu navigation
- Main content area
- Footer (minimal)

**POS Layout:**
- Full-width, no sidebar
- Left: Customer panel
- Center: Rate board
- Right: Transaction panel
- Top: Status bar (counter, user, alerts)

**Dashboard Layout:**
- Widget grid (responsive)
- Charts and KPI cards
- Quick actions panel
- Alerts panel

### 8.3 Key Screens

#### 8.3.1 Login Screen
- Logo
- Username field
- Password field
- Remember me checkbox
- Login button
- Forgot password link
- Version info

#### 8.3.2 Dashboard
- Welcome message
- Today's summary cards (revenue, transactions, customers)
- Quick actions (New Transaction, Reports)
- Alerts panel
- Recent transactions
- Charts (daily trend, currency mix)

#### 8.3.3 POS Screen
- Counter info bar
- Customer search/selection panel
- Rate board (currency pairs grid)
- Transaction form
- Payment entry
- Action buttons (Hold, Clear, Process)

#### 8.3.4 Transaction List
- Filters (date range, branch, currency, status)
- Search field
- Data table (sortable, paginated)
- Export button
- Quick actions (view, cancel)

#### 8.3.5 Customer Profile
- Customer info header
- Risk indicator badge
- Verification status
- Transaction history
- Documents section
- Notes section

#### 8.3.6 Stock Management
- Stock position overview
- Currency/branch filter
- Stock movement form
- Movement history
- Reconciliation panel

#### 8.3.7 Reports
- Report type selector
- Parameter form
- Preview panel
- Export options
- Schedule options

#### 8.3.8 Admin Settings
- Settings categories (tabs/sidebar)
- Configuration forms
- User management table
- Role management
- Audit log viewer

### 8.4 Responsive Design

- Desktop-first (primary use case)
- Tablet-friendly (for counter tablets)
- Mobile-view (for managers on-the-go)
- Breakpoints: 576px, 768px, 992px, 1200px, 1400px

### 8.5 Accessibility

- WCAG 2.1 Level AA compliance
- Semantic HTML
- Keyboard navigation
- Screen reader support
- High contrast mode
- Focus indicators
- Alt text for images
- Form labels

---

## 9. Integration Points

### 9.1 Internal Integrations

**Module Communication:**
- Transaction → Stock (automatic stock update)
- Transaction → Accounting (automatic journal entry)
- Customer → AML (risk scoring triggers)
- Stock → Position (position tracking)
- All → Audit Trail (activity logging)

### 9.2 Future External Integrations

**BNM Portal Integration:**
- Form LMCA submission via API (if available)
- Rate feed from BNM (admin reference)
- STR submission via goAML portal

**Accounting Software Export:**
- SQL Accounting export
- UBS export
- CSV general ledger export

**Bank Integration:**
- Bank statement import (for reconciliation)
- Bank API for balance checking (future)

**Document Management:**
- MyKad scanner integration
- Passport scanner integration
- Document OCR (future)

### 9.3 Notification Channels

- Email notifications (PHPMailer)
- SMS notifications (Twilio API - future)
- Push notifications (future mobile app)
- In-system notification center

---

## 10. Deployment Architecture

### 10.1 Server Requirements

**Minimum (Single Branch):**
- CPU: 2 cores
- RAM: 4 GB
- Storage: 100 GB SSD
- OS: Ubuntu 22.04 LTS / Windows Server 2019+

**Recommended (Multi-Branch):**
- CPU: 4 cores
- RAM: 8 GB
- Storage: 500 GB SSD
- OS: Ubuntu 22.04 LTS

### 10.2 Software Stack

- Apache 2.4+ with mod_rewrite, mod_ssl
- PHP 8.2+ with extensions: mysqli, pdo_mysql, json, mbstring, gd, curl, xml, zip, opcache
- MySQL 8.0+ with InnoDB
- OpenSSL for TLS

### 10.3 Deployment Topology

**Single Server (Small Deployment):**
```
                    ┌─────────────────┐
                    │   Internet      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Firewall      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │  Apache/PHP     │
                    │  MySQL          │
                    │  (Single Server)│
                    └─────────────────┘
```

**Multi-Server (Large Deployment):**
```
                    ┌─────────────────┐
                    │   Internet      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Load Balancer │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
     ┌────────▼────────┐     │     ┌────────▼────────┐
     │  Web Server 1   │     │     │  Web Server 2   │
     │  (Apache/PHP)   │     │     │  (Apache/PHP)   │
     └────────┬────────┘     │     └────────┬────────┘
              │              │              │
              └──────────────┼──────────────┘
                             │
                    ┌────────▼────────┐
                    │  Database Server│
                    │  (MySQL Primary)│
                    │  + Replica      │
                    └─────────────────┘
```

### 10.4 Backup Strategy

- Daily full backup (automated)
- Hourly incremental backup (transaction logs)
- Backup retention: 30 days daily, 12 months monthly, 7 years yearly
- Off-site backup replication
- Backup encryption
- Quarterly backup restoration testing

### 10.5 Monitoring & Alerting

- Server monitoring (CPU, memory, disk, network)
- Application monitoring (response time, errors)
- Database monitoring (connections, queries, locks)
- Log aggregation (application, access, error logs)
- Alerting (email, SMS for critical)

---

## 11. Testing Strategy

### 11.1 Unit Testing

- Test coverage: 80% minimum for business logic
- Framework: PHPUnit
- Areas: Services, Helpers, Validation
- Automated via CI/CD pipeline

### 11.2 Integration Testing

- Database integration tests
- API endpoint tests
- Module interaction tests
- Transaction flow tests

### 11.3 Functional Testing

- User acceptance testing (UAT)
- End-to-end workflow tests
- Compliance scenario tests
- Report accuracy tests

### 11.4 Performance Testing

- Load testing (1000 concurrent users)
- Stress testing (beyond normal load)
- Endurance testing (24-hour continuous)
- Database query optimization

### 11.5 Security Testing

- Vulnerability scanning (OWASP Top 10)
- Penetration testing
- SQL injection testing
- XSS testing
- CSRF testing
- Authentication/authorization testing

### 11.6 Compliance Testing

- BNM regulation compliance verification
- AML/CFT rule testing
- Financial calculation accuracy
- Audit trail completeness

### 11.7 Regression Testing

- Full regression suite after each release
- Automated regression tests
- Critical path manual testing

---

## 12. Data Migration

### 12.1 Migration Phases

**Phase 1: Data Assessment**
- Analyze existing data sources
- Map data fields to new schema
- Identify data quality issues
- Plan data cleansing

**Phase 2: Migration Preparation**
- Create migration scripts
- Set up staging environment
- Prepare validation scripts
- Document migration procedures

**Phase 3: Trial Migration**
- Migrate sample data set
- Validate data integrity
- User verification
- Performance testing

**Phase 4: Production Migration**
- Schedule migration window
- Backup existing data
- Execute migration scripts
- Validate and verify
- Go-live

### 12.2 Data Mapping

| Source System | Entity | Target Table |
|---------------|--------|--------------|
| Legacy POS | Transactions | transactions |
| Legacy POS | Customers | customers |
| Legacy POS | Stock | stocks |
| Spreadsheet | Chart of Accounts | accounts |
| Manual | Opening Balances | journal_entries |

### 12.3 Data Validation

- Count verification (record counts match)
- Amount verification (totals match)
- Referential integrity (FK relationships)
- Business rule validation
- User acceptance of migrated data

---

## 13. Training & Documentation

### 13.1 User Training

**Training Program:**
- System overview (2 hours)
- POS operations (4 hours)
- Customer management (2 hours)
- Stock management (2 hours)
- Reporting (2 hours)
- Admin functions (4 hours)
- Compliance procedures (4 hours)

**Training Materials:**
- User manual (PDF)
- Quick reference guides
- Video tutorials
- FAQ document

### 13.2 Technical Documentation

- System architecture document
- Database schema document
- API documentation
- Deployment guide
- Administrator manual
- Developer guide

### 13.3 Operational Documentation

- Standard operating procedures (SOP)
- Day-end procedures guide
- Compliance procedures
- Incident response procedures
- Backup and recovery procedures

---

## 14. Appendices

### Appendix A: Glossary

| Term | Definition |
|------|------------|
| AML | Anti-Money Laundering |
| BNM | Bank Negara Malaysia |
| CDD | Customer Due Diligence |
| CFT | Counter Financing of Terrorism |
| CTR | Cash Transaction Report |
| EDD | Enhanced Due Diligence |
| FX | Foreign Exchange |
| KYC | Know Your Customer |
| MIA | Malaysia Institute of Accountants |
| MFRS | Malaysian Financial Reporting Standards |
| PEP | Politically Exposed Person |
| POS | Point of Sale |
| RBAC | Role-Based Access Control |
| STR | Suspicious Transaction Report |

### Appendix B: Currency Codes (ISO 4217)

| Code | Currency | Country |
|------|----------|---------|
| MYR | Malaysian Ringgit | Malaysia (Base) |
| USD | US Dollar | United States |
| EUR | Euro | Eurozone |
| GBP | British Pound | United Kingdom |
| JPY | Japanese Yen | Japan |
| AUD | Australian Dollar | Australia |
| CAD | Canadian Dollar | Canada |
| CHF | Swiss Franc | Switzerland |
| CNY | Chinese Yuan | China |
| HKD | Hong Kong Dollar | Hong Kong |
| SGD | Singapore Dollar | Singapore |
| THB | Thai Baht | Thailand |
| IDR | Indonesian Rupiah | Indonesia |
| PHP | Philippine Peso | Philippines |
| INR | Indian Rupee | India |
| KRW | South Korean Won | South Korea |
| TWD | Taiwan Dollar | Taiwan |
| AED | UAE Dirham | United Arab Emirates |
| SAR | Saudi Riyal | Saudi Arabia |
| BND | Brunei Dollar | Brunei |

### Appendix C: High-Risk Countries (FATF)

List as per FATF high-risk jurisdictions (update quarterly):
- Democratic People's Republic of Korea (DPRK)
- Iran
- Myanmar
- (Refer to latest FATF list)

### Appendix D: Compliance Checklist

**Daily:**
- [ ] Review AML alerts
- [ ] Verify day-end reconciliation
- [ ] Check position limits
- [ ] Review large transactions

**Weekly:**
- [ ] Review high-risk customers
- [ ] Check pending approvals
- [ ] Verify backup completion

**Monthly:**
- [ ] Generate BNM reports
- [ ] Review CTR filings
- [ ] Compliance training review
- [ ] Financial statements review

**Quarterly:**
- [ ] Risk assessment review
- [ ] Policy review
- [ ] Internal audit review

**Annually:**
- [ ] License renewal
- [ ] External audit
- [ ] Compliance training refresh
- [ ] Policy update

---

**Document End**

*This specification is subject to refinement during implementation. All design decisions should be validated against the latest BNM guidelines and MIA standards.*
