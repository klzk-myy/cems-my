# CEMS-MY (Currency Exchange Management System)
## Design Specification

**Date:** 2025-03-31  
**Project:** CEMS-MY Money Services Business Edition  
**Stack:** PHP 8.2, Laravel 11 + Slim 4 Hybrid, MySQL 8.0, Tailwind CSS, Alpine.js  
**Compliance:** BNM AML/CFT Policy (Revised 2025), MSB Act 2011, PDPA 2010 (Amended 2024)

---

## 1. Executive Summary

CEMS-MY is a comprehensive currency exchange management system built for Malaysian Money Services Businesses (MSBs). It integrates trading operations, compliance workflows, accounting functions, and risk-based customer management while strictly adhering to Bank Negara Malaysia (BNM) regulatory requirements and Malaysia's Personal Data Protection Act (PDPA).

---

## 2. Architecture Overview

### 2.1 Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Primary Framework | Laravel | 11.x |
| API/Microservices | Slim Framework | 4.x |
| Database | MySQL | 8.0+ |
| Frontend Styling | Tailwind CSS | 3.x |
| Frontend JS | Alpine.js | 3.x |
| PHP | - | 8.2+ |
| Cache/Sessions | Redis | Latest |
| Math | BCMath | Native |

### 2.2 Deployment Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Web Server (Nginx)                     │
│  ┌─────────────────┐    ┌──────────────────────────────┐  │
│  │  Laravel App    │    │  Slim Microservices          │  │
│  │  - Dashboard    │    │  - Rate API Proxy            │  │
│  │  - Auth/ORM     │    │  - Transaction Engine        │  │
│  │  - Reporting    │    │  - Accounting Calculations   │  │
│  │  - Compliance   │    │  - Risk Scoring              │  │
│  └─────────────────┘    └──────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          │
┌─────────────────────────────────────────────────────────┐
│              MySQL 8.0 (InnoDB)                         │
│  - Encrypted PII fields (AES_ENCRYPT)                     │
│  - ACID transactions                                      │
│  - DECIMAL(18,4) for currency                            │
│  - DECIMAL(18,6) for rates                               │
└─────────────────────────────────────────────────────────┘
```

**Design Rationale:**
- **Laravel** accelerates UI development with built-in ORM, migrations, and auth
- **Slim** provides explicit, auditable control for financial and compliance logic
- Clear separation allows BNM auditors to review critical compliance code independently

---

## 3. Database Schema

### 3.1 Core Tables

```sql
-- Users and Authentication
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('teller', 'manager', 'compliance_officer', 'admin') DEFAULT 'teller',
    mfa_enabled TINYINT DEFAULT 0,
    mfa_secret VARCHAR(32) NULL,
    is_active TINYINT DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Customers with encrypted PII
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    id_type ENUM('MyKad', 'Passport', 'Others') NOT NULL,
    id_number_encrypted VARBINARY(512) NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    pep_status TINYINT DEFAULT 0,
    risk_score INT DEFAULT 0,
    risk_rating ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    risk_assessed_at TIMESTAMP NULL,
    last_transaction_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_id_type (id_type),
    INDEX idx_nationality (nationality),
    INDEX idx_pep (pep_status),
    INDEX idx_risk (risk_rating),
    INDEX idx_last_transaction (last_transaction_at)
) ENGINE=InnoDB;

-- Currencies
CREATE TABLE currencies (
    code VARCHAR(3) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10),
    decimal_places TINYINT DEFAULT 2,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Exchange Rates
CREATE TABLE exchange_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL,
    rate_buy DECIMAL(18, 6) NOT NULL,
    rate_sell DECIMAL(18, 6) NOT NULL,
    source VARCHAR(50) NOT NULL,
    fetched_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (currency_code) REFERENCES currencies(code),
    INDEX idx_currency_fetched (currency_code, fetched_at)
) ENGINE=InnoDB;

-- Transactions
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('Buy', 'Sell') NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    amount_local DECIMAL(18, 4) NOT NULL,
    amount_foreign DECIMAL(18, 4) NOT NULL,
    rate DECIMAL(18, 6) NOT NULL,
    purpose TEXT,
    source_of_funds VARCHAR(255),
    status ENUM('Pending', 'Completed', 'OnHold', 'Rejected', 'Reversed') DEFAULT 'Pending',
    hold_reason TEXT,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    cdd_level ENUM('Simplified', 'Standard', 'Enhanced') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (currency_code) REFERENCES currencies(code),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_customer_date (customer_id, created_at),
    INDEX idx_status (status),
    INDEX idx_type_currency (type, currency_code),
    INDEX idx_created_at (created_at),
    INDEX idx_amount (amount_local)
) ENGINE=InnoDB;
```

### 3.2 Compliance Tables

```sql
-- System Logs for Audit Trail
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Sanction Lists
CREATE TABLE sanction_lists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    list_type ENUM('UNSCR', 'MOHA', 'Internal') NOT NULL,
    source_file VARCHAR(255),
    uploaded_by BIGINT UNSIGNED NOT NULL,
    is_active TINYINT DEFAULT 1,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_list_type (list_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Sanction List Entries
CREATE TABLE sanction_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    list_id BIGINT UNSIGNED NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_type ENUM('Individual', 'Entity') DEFAULT 'Individual',
    aliases TEXT,
    nationality VARCHAR(100),
    date_of_birth DATE,
    details JSON,
    FOREIGN KEY (list_id) REFERENCES sanction_lists(id),
    INDEX idx_list (list_id),
    INDEX idx_name (entity_name),
    FULLTEXT INDEX idx_aliases (aliases)
) ENGINE=InnoDB;

-- Flagged Transactions
CREATE TABLE flagged_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    flag_type ENUM('EDD_Required', 'Sanction_Match', 'Velocity', 'Structuring', 'Manual') NOT NULL,
    flag_reason TEXT NOT NULL,
    status ENUM('Open', 'Under_Review', 'Resolved', 'Rejected') DEFAULT 'Open',
    assigned_to BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_flag_type (flag_type)
) ENGINE=InnoDB;

-- High Risk Countries
CREATE TABLE high_risk_countries (
    country_code VARCHAR(2) PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL,
    risk_level ENUM('High', 'Grey') NOT NULL,
    source VARCHAR(50) NOT NULL,
    list_date DATE NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB;

-- Risk Assessment History
CREATE TABLE customer_risk_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    old_score INT,
    new_score INT NOT NULL,
    old_rating ENUM('Low', 'Medium', 'High'),
    new_rating ENUM('Low', 'Medium', 'High') NOT NULL,
    change_reason TEXT NOT NULL,
    assessed_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (assessed_by) REFERENCES users(id),
    INDEX idx_customer_date (customer_id, created_at)
) ENGINE=InnoDB;
```

### 3.3 Accounting Tables

```sql
-- Currency Positions
CREATE TABLE currency_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL,
    till_id VARCHAR(50) DEFAULT 'MAIN',
    balance DECIMAL(18, 4) DEFAULT 0,
    avg_cost_rate DECIMAL(18, 6),
    last_valuation_rate DECIMAL(18, 6),
    unrealized_pnl DECIMAL(18, 4) DEFAULT 0,
    last_valuation_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_currency_till (currency_code, till_id),
    FOREIGN KEY (currency_code) REFERENCES currencies(code),
    INDEX idx_currency (currency_code)
) ENGINE=InnoDB;

-- Revaluation Entries
CREATE TABLE revaluation_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL,
    till_id VARCHAR(50) DEFAULT 'MAIN',
    old_rate DECIMAL(18, 6) NOT NULL,
    new_rate DECIMAL(18, 6) NOT NULL,
    position_amount DECIMAL(18, 4) NOT NULL,
    gain_loss_amount DECIMAL(18, 4) NOT NULL,
    revaluation_date DATE NOT NULL,
    posted_by BIGINT UNSIGNED NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (currency_code) REFERENCES currencies(code),
    FOREIGN KEY (posted_by) REFERENCES users(id),
    INDEX idx_currency_date (currency_code, revaluation_date),
    INDEX idx_posted (posted_at)
) ENGINE=InnoDB;

-- Till Balances
CREATE TABLE till_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    till_id VARCHAR(50) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    opening_balance DECIMAL(18, 4) NOT NULL,
    closing_balance DECIMAL(18, 4),
    variance DECIMAL(18, 4) GENERATED ALWAYS AS (closing_balance - opening_balance) STORED,
    date DATE NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    closed_by BIGINT UNSIGNED,
    closed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (currency_code) REFERENCES currencies(code),
    FOREIGN KEY (opened_by) REFERENCES users(id),
    FOREIGN KEY (closed_by) REFERENCES users(id),
    UNIQUE KEY uk_till_date_currency (till_id, date, currency_code),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- Chart of Accounts (for accounting integration)
CREATE TABLE chart_of_accounts (
    account_code VARCHAR(20) PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    parent_code VARCHAR(20),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_code) REFERENCES chart_of_accounts(account_code),
    INDEX idx_type (account_type)
) ENGINE=InnoDB;
```

### 3.4 Security & PDPA Tables

```sql
-- Data Breach Alerts
CREATE TABLE data_breach_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('Mass_Access', 'Unauthorized', 'Export_Anomaly') NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    description TEXT NOT NULL,
    record_count INT,
    triggered_by BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    is_resolved TINYINT DEFAULT 0,
    resolved_by BIGINT UNSIGNED,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (triggered_by) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Document Storage (for MyKad/Passport scans)
CREATE TABLE customer_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('MyKad', 'Passport', 'Proof_of_Address', 'Others') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    file_size INT,
    encrypted TINYINT DEFAULT 1,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_customer (customer_id),
    INDEX idx_type (document_type)
) ENGINE=InnoDB;
```

---

## 4. Module Specifications

### 4.1 Authentication & MFA Module

**Requirements:**
- Multi-factor authentication required for Admin and Manager roles
- TOTP-based MFA using QR codes
- Session management with Redis
- Password requirements: min 12 chars, uppercase, lowercase, number, special char
- Account lockout after 5 failed attempts

**Flow:**
1. User enters username/password
2. If MFA enabled, prompt for TOTP code
3. Validate code against stored secret
4. Create session with role-based permissions
5. Log authentication event to system_logs

### 4.2 Trading Dashboard Module

**Features:**
- **Live Rates Display**: AJAX polling every 60 seconds from Free Tier API
- **Rate Source**: ExchangeRate-API or Open Exchange Rates
- **Slim Endpoint**: `/api/rates/live` - caches rates for 60 seconds
- **Fast Entry**: Tab order optimized: Amount → Currency → Calculate → Confirm
- **Real-time Validation**: Check customer 24-hour velocity
- **CDD Popup**: Automatically triggered based on amount thresholds

**Transaction Flow:**
1. Teller selects customer (auto-complete)
2. Enters amount and currency
3. System calculates rate and P&L preview
4. Validates customer velocity (last 24h transactions)
5. Applies CDD rules based on amount + customer risk
6. If hold required, create flagged_transaction
7. On approval, update currency_positions

**Velocity Check:**
```php
// Sum of customer's transactions in last 24 hours
$velocity = Transaction::where('customer_id', $customerId)
    ->where('created_at', '>=', now()->subHours(24))
    ->sum('amount_local');

if ($velocity + $newAmount > 50000) {
    // Alert: Potential structuring
}
```

### 4.3 Compliance Module

#### 4.3.1 CDD/EDD Logic

**Hard-coded Thresholds:**

| Amount (RM) | Requirement | System Action |
|-------------|-------------|---------------|
| < 3,000 | Simplified CDD | Capture Name & ID Number only |
| ≥ 3,000 | Full CDD | Capture Name, ID Type, Nationality, DOB, Purpose |
| ≥ 50,000 | Enhanced Due Diligence | Freeze transaction, require SOF + Manager approval |
| Any amount + PEP | Immediate Alert | Route to Compliance Officer |
| Any amount + Sanction | Immediate Alert | Block transaction, manual clearance |

**Implementation:**
```php
public function determineCDDLevel($amount, $customer) {
    if ($customer->pep_status || $this->checkSanctionMatch($customer)) {
        return 'Enhanced';
    }
    
    if ($amount >= 50000 || $customer->risk_rating === 'High') {
        return 'Enhanced';
    }
    
    if ($amount >= 3000) {
        return 'Standard';
    }
    
    return 'Simplified';
}
```

#### 4.3.2 Sanction List Screening

**Fuzzy Matching Algorithm:**
- Levenshtein distance for name similarity
- Threshold: 80% match triggers alert
- Sample lists for development (UNSCR, MOHA)
- Production: CSV upload interface

**Screening Process:**
1. Extract customer name components
2. Compare against sanction_entries
3. Calculate similarity score
4. If >80%, create sanction match alert
5. Block transaction pending manual review

#### 4.3.3 Compliance Officer Portal

**Features:**
- Flagged Queue: All transactions "On Hold"
- Sanction Screening Tool: Upload CSV, fuzzy search
- Investigation Notes: Attach documents to flagged items
- Data Erasure Tool: Mask PII after 7-year retention (PDPA 2024)
- Incident Reporting: 72-hour breach notification template

### 4.4 Risk-Based Approach Module

#### 4.4.1 Customer Risk Rating

**Risk Score Calculation (0-100):**

| Factor | Points | Condition |
|--------|--------|-----------|
| PEP Status | +40 | If pep_status = 1 |
| High-Risk Jurisdiction | +30 | If nationality in high_risk_countries |
| Complex Ownership | +25 | If customer type = 'Corporate' with shell indicators |
| Cash-Intensive Pattern | +20 | If >3 cash transactions > RM 10,000 in 30 days |
| Unusual Pattern | +10 | If transaction deviates 200% from customer's average |

**Classification:**
- Low: 0-30
- Medium: 31-60
- High: 61-100

**Auto-Reassessment:**
- Low risk: Every 3 years
- Medium risk: Every 2 years
- High risk: Every year + ongoing monitoring

#### 4.4.2 Geographic Risk

**High-Risk Countries:**
- FATF blacklist/greylist countries
- BNM specified jurisdictions
- Sanctioned nations

**Implementation:**
- Table: high_risk_countries with country_code, risk_level
- Auto-flag customer on creation if nationality matches
- Enhanced CDD automatically triggered

#### 4.4.3 Transaction Monitoring Rules

| Rule | Threshold | Risk Action |
|------|-----------|-------------|
| 24h Velocity | > RM 50,000 | Alert + Score +10 |
| Structuring | 3+ transactions < RM 3,000 within 1h | Alert + Block |
| Unusual Pattern | >200% deviation from 90-day avg | Score +10 |
| Cross-Border Wire | To high-risk country | Enhanced screening |
| Rapid Succession | 5+ transactions in 10 minutes | Alert + Hold |

**Alert Workflow:**
1. Rule triggered → Create flagged_transaction
2. Assign to compliance officer based on workload
3. Officer reviews, adds notes
4. Decision: Approve, Reject, or Escalate
5. Update customer risk score if pattern confirmed

### 4.5 Accounting Module

#### 4.5.1 Currency Position Tracking

**Real-time Updates:**
- Every transaction updates currency_positions
- Buy: Increase foreign currency balance
- Sell: Decrease foreign currency balance
- Running average cost calculation using BCMath

**Average Cost Formula:**
```php
$newAvgCost = bcdiv(
    bcadd(
        bcmul($oldBalance, $oldAvgCost, 6),
        bcmul($transactionAmount, $transactionRate, 6),
        6
    ),
    bcadd($oldBalance, $transactionAmount, 4),
    6
);
```

#### 4.5.2 Automatic Monthly Revaluation

**Process:**
1. System fetches month-end rates from Free Tier API on last day of month
2. Calculates unrealized P&L per currency position
3. Formula: `(new_rate - avg_cost_rate) × position_amount`
4. Creates revaluation_entries record
5. Updates currency_positions.unrealized_pnl
6. Generates revaluation report

**Schedule:**
- Automated via Laravel Scheduler (cron)
- Runs at 23:59 on last day of month
- Email notification to accounting team

#### 4.5.3 Till Management

**Daily Workflow:**
1. Morning: Open till, record opening balances per currency
2. Throughout day: Transactions update till balances
3. End of day: Enter closing balances
4. System calculates variance (expected vs actual)
5. If variance > threshold, alert manager
6. Generate till reconciliation report

**Multi-Currency Support:**
- Each till tracks all active currencies
- Individual variance per currency
- Cashier accountability

#### 4.5.4 Chart of Accounts

**Standard MSB Accounts:**
- 1000-Cash-MYR
- 1100-Cash-USD
- 1200-Cash-EUR
- ... (per currency)
- 4000-Revenue-Forex
- 5000-Expense-Revaluation-Loss
- 5100-Revenue-Revaluation-Gain

### 4.6 Reporting Module

#### 4.6.1 LCTR (Large Cash Transaction Report)

**Requirements:**
- Monthly CSV export
- All transactions ≥ RM 25,000
- Fields per BNM format
- Customer identification
- Transaction details

**Fields:**
- Transaction ID, Date, Time
- Customer ID, Name (partially masked), ID Type
- Amount (Local + Foreign), Currency
- Transaction Type (Buy/Sell)
- Branch/Till ID
- Teller ID

#### 4.6.2 Daily MSB(2) Report

**Requirements:**
- Daily summary
- Total Buy/Sell volumes per currency
- Statistical submission format
- Aggregated data (no customer PII)

**Format:**
```csv
Date,Currency,Buy_Volume,Buy_Count,Sell_Volume,Sell_Count
2025-03-31,USD,150000.00,25,85000.00,18
2025-03-31,EUR,45000.00,8,32000.00,5
```

#### 4.6.3 Accounting Reports

- **Currency Position Report**: Real-time balances per currency
- **Revaluation Summary**: Monthly gains/losses by currency
- **Till Reconciliation**: Daily variance reports
- **Customer Risk Distribution**: Pie chart of Low/Medium/High
- **Compliance Metrics**: Flagged transactions, resolution times

### 4.7 Security & PDPA Module

#### 4.7.1 Data Breach Detection

**Rules:**
- >1,000 PII records accessed within 1 minute
- Mass export detected (>500 records in single query)
- Failed login attempts from same IP (>20 in 5 minutes)
- Unauthorized access attempt to encrypted documents

**Response:**
1. Create data_breach_alerts record (severity: Critical)
2. Email alert to admin + compliance officer
3. Log incident details
4. Prepare 72-hour incident report template
5. Optional: Auto-suspend suspicious user account

#### 4.7.2 PDPA 2024 Compliance

**Right to be Forgotten:**
- Data retention: 7 years from last transaction
- After 7 years: Mask PII (name → "[MASKED]", ID → hash)
- Keep transaction data for compliance, anonymized
- Document erasure in system_logs

**Data Protection:**
- AES_ENCRYPT() for sensitive fields (MyKad/Passport)
- Documents stored above web root
- No PII in URLs or logs
- SSL/TLS required for all connections

#### 4.7.3 Encrypted Document Storage

**Storage:**
- Path: `/var/secure/cems/documents/{customer_id}/`
- Not accessible via web server
- File names: SHA256 hash of content
- AES-256 encryption at file level
- Access only via PHP (not direct URL)

---

## 5. API Specifications

### 5.1 Slim API Endpoints

**Rate API:**
```
GET /api/rates/live
Response: {
    "USD": {"buy": 4.7200, "sell": 4.7500, "timestamp": "2025-03-31T12:00:00Z"},
    "EUR": {"buy": 5.1100, "sell": 5.1400, "timestamp": "2025-03-31T12:00:00Z"}
}
```

**Transaction Engine:**
```
POST /api/transactions/calculate
Body: {"amount": 1000, "currency": "USD", "type": "Buy"}
Response: {"local_amount": 4750.00, "rate": 4.7500, "p_l": 12.50}

POST /api/transactions/execute
Body: {...transaction details...}
Response: {"transaction_id": 12345, "status": "Completed"}
```

**Risk Scoring:**
```
POST /api/risk/calculate
Body: {"customer_id": 123}
Response: {"score": 45, "rating": "Medium", "factors": [...]}
```

### 5.2 Laravel Web Routes

**Authentication:**
- `GET /login` - Login form
- `POST /login` - Authenticate
- `GET /mfa/setup` - MFA QR code
- `POST /mfa/verify` - Verify TOTP

**Dashboard:**
- `GET /dashboard` - Trading dashboard
- `GET /dashboard/rates` - Live rates (AJAX)
- `POST /dashboard/transaction` - Create transaction

**Compliance:**
- `GET /compliance/flagged` - Flagged queue
- `GET /compliance/customers/{id}/risk` - Risk details
- `POST /compliance/flagged/{id}/resolve` - Resolve flag
- `GET /compliance/sanctions` - Sanction search
- `POST /compliance/sanctions/upload` - Upload CSV

**Accounting:**
- `GET /accounting/positions` - Currency positions
- `GET /accounting/revaluation` - Revaluation report
- `POST /accounting/revaluation/run` - Trigger revaluation
- `GET /accounting/tills` - Till management

**Reporting:**
- `GET /reports/lctr` - LCTR report
- `GET /reports/msb2` - MSB(2) report
- `GET /reports/export/{type}` - CSV export

---

## 6. Frontend Specifications

### 6.1 Trading Dashboard UI

**Layout:**
- Left sidebar: Customer search, recent customers
- Center: Rate display (large, prominent)
- Right panel: Transaction form
- Bottom: Recent transactions list

**Rate Display:**
- Large font for major currencies (USD, EUR, GBP)
- Color coding: Buy (green), Sell (red)
- Last updated timestamp
- Auto-refresh indicator

**Transaction Form:**
- Customer: Search dropdown with autocomplete
- Amount: Numeric input with formatting
- Currency: Dropdown of active currencies
- Rate: Auto-populated, editable by manager
- P&L Preview: Real-time calculation
- Submit: Disabled until all validations pass

**Alerts:**
- Velocity warning banner
- CDD requirement popup
- Hold reason display
- Success/error toast messages

### 6.2 Compliance Portal UI

**Flagged Queue:**
- Table: ID, Customer, Amount, Reason, Age, Assigned To
- Filters: By type, status, date range
- Bulk actions: Assign, Export
- Click to view details

**Risk Dashboard:**
- Pie chart: Risk distribution
- Bar chart: Transactions by risk level
- Line chart: Risk scores over time
- Top 10 high-risk customers table

**Sanction Screening:**
- Upload area for CSV files
- Search box: Name search with fuzzy results
- Results: Match score, list source, entity details
- Quick flag button

### 6.3 Accounting UI

**Currency Positions:**
- Cards per currency showing:
  - Current balance
  - Average cost rate
  - Last valuation rate
  - Unrealized P&L (color-coded)

**Revaluation:**
- Month selector
- Table: Currency, Position, Old Rate, New Rate, Gain/Loss
- Total unrealized P&L summary
- Post button (restricted to accounting role)

**Till Reconciliation:**
- Calendar view
- Per-till status indicators
- Open/close forms
- Variance highlighting

---

## 7. Security Specifications

### 7.1 Encryption

**Database:**
- MyKad/Passport numbers: `AES_ENCRYPT(value, @encryption_key)`
- Encryption key stored in environment variable
- Never log encrypted values

**File Storage:**
- Documents encrypted with AES-256 before storage
- Keys rotated annually
- Old documents re-encrypted during rotation

### 7.2 Authentication

**Password Policy:**
- Minimum 12 characters
- At least one uppercase, lowercase, number, special character
- No dictionary words
- Password history (last 5)
- Force change every 90 days (admin/manager)

**MFA:**
- TOTP-based (Google Authenticator compatible)
- Required for: Admin, Manager, Compliance Officer
- Optional for: Teller
- Backup codes generated on setup

**Session Management:**
- Redis storage
- 8-hour timeout
- Single session per user (logout others)
- IP binding optional

### 7.3 Audit Trail

**Logged Actions:**
- Login/logout (success and failure)
- All transaction CRUD
- Customer data changes
- Compliance decisions
- Rate changes
- User management

**Log Retention:**
- System logs: 7 years (compliance)
- Login logs: 2 years
- Failed attempts: 1 year

### 7.4 Access Control

**Role Permissions:**

| Feature | Teller | Manager | Compliance | Admin |
|---------|--------|---------|------------|-------|
| Create Transaction | Yes | Yes | Yes | Yes |
| Approve >RM 50k | No | Yes | Yes | Yes |
| View Compliance | No | No | Yes | Yes |
| Manage Users | No | No | No | Yes |
| Run Reports | No | Yes | Yes | Yes |
| System Config | No | No | No | Yes |
| Delete Data | No | No | No | Yes |

---

## 8. Implementation Timeline

### Phase 1: Infrastructure (Week 1)
- [ ] MySQL schema creation
- [ ] Docker Compose setup (PHP, MySQL, Redis, Nginx)
- [ ] Laravel + Slim project initialization
- [ ] Environment configuration
- [ ] SSL/TLS certificate setup
- [ ] Database encryption key generation

### Phase 2: Core Engine (Week 2)
- [ ] User authentication (Laravel)
- [ ] MFA implementation
- [ ] Rate API integration (Slim)
- [ ] Transaction engine (Slim)
- [ ] BCMath integration
- [ ] Basic trading dashboard (Laravel)

### Phase 3: Compliance (Week 3)
- [ ] CDD/EDD triggers
- [ ] Customer risk rating
- [ ] Sanction list fuzzy match
- [ ] Flagged transaction workflow
- [ ] High-risk country management
- [ ] Compliance officer portal

### Phase 4: Accounting & Risk (Week 4)
- [ ] Currency position tracking
- [ ] Till management
- [ ] Automatic revaluation
- [ ] Transaction monitoring rules
- [ ] Risk-based CDD tiers
- [ ] Compliance reporting

### Phase 5: Reporting & Hardening (Week 5)
- [ ] LCTR export
- [ ] MSB(2) report
- [ ] Accounting reports
- [ ] Data breach detection
- [ ] Penetration testing
- [ ] Staff training module
- [ ] Documentation (English/BM)

---

## 9. Testing Strategy

### 9.1 Unit Tests

**Financial Calculations:**
- BCMath accuracy for all currency operations
- Exchange rate calculations
- Revaluation gain/loss formulas
- Average cost calculations

**Risk Scoring:**
- Customer risk calculation accuracy
- Threshold boundary testing
- Risk factor weighting

### 9.2 Integration Tests

**Transaction Flow:**
- End-to-end transaction creation
- CDD trigger accuracy
- Velocity check functionality
- Position updates

**Compliance:**
- Sanction screening accuracy
- Flag creation workflow
- Approval/rejection flows

**API:**
- Rate fetching and caching
- Authentication middleware
- Error handling

### 9.3 Security Tests

**Penetration Testing:**
- SQL injection attempts
- XSS vulnerability testing
- CSRF protection
- Session hijacking
- File upload security

**Compliance Tests:**
- Data encryption at rest
- PII masking in logs
- Access control enforcement
- Audit trail completeness

---

## 10. Deployment Checklist

### Pre-deployment
- [ ] All PHP code follows PSR-12 coding standards
- [ ] Unit tests passing (financial calculations verified)
- [ ] Integration tests passing
- [ ] Security audit completed
- [ ] SSL/TLS certificates configured
- [ ] Database backup script tested
- [ ] Environment variables configured (no secrets in code)
- [ ] PDPA compliance review completed
- [ ] BNM requirements validation
- [ ] Staff training materials prepared

### Post-deployment
- [ ] Smoke tests on production
- [ ] Rate API connectivity verified
- [ ] Database encryption working
- [ ] MFA setup for admin users
- [ ] Initial user accounts created
- [ ] Sample data loaded (if needed)
- [ ] Monitoring alerts configured
- [ ] Backup schedule activated
- [ ] Documentation distributed
- [ ] Go-live sign-off from compliance

---

## 11. Maintenance & Operations

### 11.1 Monitoring

**Metrics to Track:**
- Transaction volume per day/hour
- Failed transactions rate
- Average risk score trends
- Compliance flag resolution time
- System performance (response times)
- Database connection pool usage
- API rate limit usage

**Alerts:**
- Error rate > 1%
- Response time > 2 seconds
- Database connections > 80%
- Failed login attempts spike
- Data breach detection

### 11.2 Backup Strategy

**Daily:**
- Full database backup at 02:00
- Encrypted backups stored offsite
- 30-day retention

**Weekly:**
- Document storage backup
- Application code backup
- Configuration backup

**Monthly:**
- Archive logs older than 7 years
- Verify backup restoration
- Test disaster recovery

### 11.3 Updates & Patches

**Security Patches:**
- Apply critical patches within 24 hours
- Test in staging environment first
- Scheduled maintenance windows

**Feature Updates:**
- Monthly release cycle
- Regression testing required
- User training for major features

---

## 12. Appendices

### Appendix A: BNM Compliance Mapping

| BNM Requirement | Implementation |
|-----------------|----------------|
| CDD for MSB | Section 4.3.1 - Threshold triggers |
| EDD for high-risk | Section 4.3.1 - EDD freeze |
| PEP screening | Section 4.3.2 - Sanction lists |
| Transaction monitoring | Section 4.4.3 - Monitoring rules |
| Risk-based approach | Section 4.4 - Risk module |
| Record keeping (7 years) | Section 4.7.2 - Retention |
| Suspicious transaction reporting | Section 4.3.3 - Compliance portal |
| Staff training | Week 5 deliverable |

### Appendix B: PDPA Compliance Mapping

| PDPA Requirement | Implementation |
|------------------|----------------|
| Consent management | Customer registration workflow |
| Purpose limitation | Transaction purpose capture |
| Data minimization | Simplified vs Full CDD tiers |
| Right to access | Customer data export |
| Right to correction | Customer update workflows |
| Right to erasure | Section 4.7.2 - Masking after 7 years |
| Data security | Section 7 - Encryption |
| Breach notification | Section 4.7.1 - 72-hour alert |

### Appendix C: Sample Sanction List Format

```csv
name,entity_type,nationality,date_of_birth,aliases,details
John Doe,Individual,ABC,1980-01-15,"John D., J. Doe",Wanted for fraud
ABC Corp,Entity,XYZ,,,"Shell company"
```

### Appendix D: Rate API Integration

**ExchangeRate-API Free Tier:**
- Endpoint: `https://api.exchangerate-api.com/v4/latest/MYR`
- Rate limit: 1,500 requests/month
- Update frequency: Daily (sufficient for demo)
- Caching: 60 seconds in Redis

**Fallback:**
- Manual rate entry interface
- Last known rates
- Manager override capability

---

## 13. Glossary

- **BNM**: Bank Negara Malaysia (Central Bank)
- **CDD**: Customer Due Diligence
- **EDD**: Enhanced Due Diligence
- **LCTR**: Large Cash Transaction Report
- **MSB**: Money Services Business
- **PEP**: Politically Exposed Person
- **PDPA**: Personal Data Protection Act
- **SOF**: Source of Funds
- **UNSCR**: United Nations Security Council Resolution
- **MOHA**: Ministry of Home Affairs (Malaysia)
- **FATF**: Financial Action Task Force

---

**Document Owner:** Development Team  
**Review Cycle:** Quarterly  
**Last Updated:** 2025-03-31  
**Version:** 1.0
