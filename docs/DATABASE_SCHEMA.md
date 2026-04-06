# CEMS-MY Database Schema Documentation

**Version**: 1.0  
**Last Updated**: April 2026  
**Database**: MySQL 8.0+  
**Collation**: utf8mb4_unicode_ci

---

## Table of Contents

1. [Overview](#1-overview)
2. [User Management](#2-user-management)
3. [Customer Management](#3-customer-management)
4. [Currency & Exchange Rates](#4-currency--exchange-rates)
5. [Transactions](#5-transactions)
6. [Stock & Cash Management](#6-stock--cash-management)
7. [Accounting](#7-accounting)
8. [Compliance & Monitoring](#8-compliance--monitoring)
9. [Reports & Audit](#9-reports--audit)
10. [Counter Management](#10-counter-management)
11. [Database Indexes](#11-database-indexes)
12. [ER Diagram](#12-er-diagram)

---

## 1. Overview

### Database Information

| Property | Value |
|----------|-------|
| **Database Name** | `cems_my` |
| **Character Set** | utf8mb4 |
| **Collation** | utf8mb4_unicode_ci |
| **Engine** | InnoDB |
| **Total Tables** | 40+ |

### Naming Conventions

- **Tables**: snake_case, plural (e.g., `users`, `transactions`)
- **Columns**: snake_case (e.g., `created_at`, `customer_id`)
- **Foreign Keys**: `{table}_id` (e.g., `customer_id`, `user_id`)
- **Indexes**: `idx_{column}` or `idx_{table}_{column}`
- **Timestamps**: All tables include `created_at` and `updated_at`

---

## 2. User Management

### users

Stores system user accounts with role-based access control.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Unique identifier |
| `username` | varchar(50) | unique, not null | Login username |
| `email` | varchar(255) | unique, not null | Email address |
| `password_hash` | varchar(255) | not null | Bcrypt hashed password |
| `role` | enum | not null, default: 'teller' | User role |
| `mfa_enabled` | tinyint(1) | default: 0 | Multi-factor auth enabled |
| `mfa_secret` | varchar(32) | nullable | MFA secret key |
| `is_active` | tinyint(1) | default: 1 | Account active status |
| `last_login_at` | timestamp | nullable | Last login timestamp |
| `created_at` | timestamp | default: current | Record creation |
| `updated_at` | timestamp | default: current | Last update |

**Indexes:**
- `idx_role` (role)
- `idx_is_active` (is_active)

**Roles:**
- `teller`: Front-line staff, create transactions
- `manager`: Approve large transactions, manage tills
- `compliance_officer`: Review flagged transactions
- `admin`: Full system access

---

## 3. Customer Management

### customers

Stores customer information with encrypted identification data.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Customer ID |
| `full_name` | varchar(255) | not null | Customer full name |
| `id_type` | enum | not null | ID document type |
| `id_number_encrypted` | text | not null | Encrypted ID number |
| `nationality` | varchar(100) | not null | Country of citizenship |
| `date_of_birth` | date | not null | Birth date |
| `address` | text | nullable | Residential address |
| `phone` | varchar(20) | not null | Contact number |
| `email` | varchar(255) | nullable | Email address |
| `occupation` | varchar(100) | nullable | Job title |
| `employer` | varchar(255) | nullable | Employer name |
| `pep_status` | tinyint(1) | default: 0 | Politically Exposed Person |
| `risk_score` | int | default: 0 | Risk score (0-100) |
| `risk_rating` | enum | default: 'Low' | Risk level |
| `risk_assessed_at` | timestamp | nullable | Last risk assessment |
| `last_transaction_at` | timestamp | nullable | Last transaction date |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**ID Types:**
- `mykad`: Malaysian National ID
- `passport`: Passport number
- `other`: Other identification

**Risk Ratings:**
- `Low`: Score 0-30
- `Medium`: Score 31-70
- `High`: Score 71-100 or PEP

### customer_documents

Stores customer documents and verification files.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Document ID |
| `customer_id` | bigint unsigned | FK, not null | Reference to customer |
| `document_type` | enum | not null | Document type |
| `file_path` | varchar(500) | not null | Storage path |
| `file_name` | varchar(255) | not null | Original filename |
| `file_size` | int | not null | File size in bytes |
| `mime_type` | varchar(100) | not null | File MIME type |
| `verified` | tinyint(1) | default: 0 | Verification status |
| `verified_at` | timestamp | nullable | Verification timestamp |
| `verified_by` | bigint unsigned | FK, nullable | Verifier user ID |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### customer_risk_history

Tracks changes to customer risk ratings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | History ID |
| `customer_id` | bigint unsigned | FK, not null | Reference to customer |
| `old_risk_rating` | enum | nullable | Previous rating |
| `new_risk_rating` | enum | not null | New rating |
| `risk_score` | int | not null | Risk score |
| `reason` | text | nullable | Reason for change |
| `assessed_by` | bigint unsigned | FK, not null | Assessor user ID |
| `created_at` | timestamp | - | Record creation |

---

## 4. Currency & Exchange Rates

### currencies

Master table for supported currencies.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `code` | varchar(3) | PK | ISO 4217 currency code |
| `name` | varchar(100) | not null | Currency name |
| `symbol` | varchar(10) | not null | Currency symbol |
| `decimal_places` | tinyint | default: 2 | Decimal precision |
| `is_active` | tinyint(1) | default: 1 | Trading enabled |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**Supported Currencies:**
- `USD`: US Dollar
- `EUR`: Euro
- `GBP`: British Pound
- `SGD`: Singapore Dollar
- `JPY`: Japanese Yen
- `AUD`: Australian Dollar
- `CNY`: Chinese Yuan

### exchange_rates

Current exchange rates for all currencies.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Rate ID |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `rate` | decimal(18,6) | not null | Exchange rate vs MYR |
| `buy_rate` | decimal(18,6) | not null | Buy rate |
| `sell_rate` | decimal(18,6) | not null | Sell rate |
| `effective_date` | date | not null | Rate effective date |
| `source` | varchar(100) | nullable | Rate source |
| `is_active` | tinyint(1) | default: 1 | Active status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### exchange_rate_histories

Historical record of rate changes.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | History ID |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `old_rate` | decimal(18,6) | not null | Previous rate |
| `new_rate` | decimal(18,6) | not null | New rate |
| `old_buy_rate` | decimal(18,6) | not null | Previous buy rate |
| `new_buy_rate` | decimal(18,6) | not null | New buy rate |
| `old_sell_rate` | decimal(18,6) | not null | Previous sell rate |
| `new_sell_rate` | decimal(18,6) | not null | New sell rate |
| `changed_by` | bigint unsigned | FK, nullable | User who changed |
| `reason` | text | nullable | Change reason |
| `created_at` | timestamp | - | Record creation |

---

## 5. Transactions

### transactions

Core transaction table for all buy/sell operations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Transaction ID |
| `customer_id` | bigint unsigned | FK, nullable | Customer ID |
| `user_id` | bigint unsigned | FK, not null | Created by user |
| `till_id` | varchar(50) | not null | Till identifier |
| `type` | enum | not null | Transaction type |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `amount_local` | decimal(18,4) | not null | Amount in MYR |
| `amount_foreign` | decimal(18,4) | not null | Foreign amount |
| `rate` | decimal(18,6) | not null | Exchange rate applied |
| `purpose` | varchar(255) | nullable | Transaction purpose |
| `source_of_funds` | varchar(255) | nullable | Source of funds |
| `status` | enum | default: 'Pending' | Transaction status |
| `hold_reason` | varchar(500) | nullable | Reason for hold |
| `approved_by` | bigint unsigned | FK, nullable | Approver user ID |
| `approved_at` | timestamp | nullable | Approval timestamp |
| `cdd_level` | enum | default: 'Standard' | CDD level applied |
| `cancelled_at` | timestamp | nullable | Cancellation timestamp |
| `cancelled_by` | bigint unsigned | FK, nullable | Canceller user ID |
| `cancellation_reason` | varchar(500) | nullable | Cancellation reason |
| `original_transaction_id` | bigint unsigned | FK, nullable | For refunds |
| `is_refund` | tinyint(1) | default: 0 | Is this a refund |
| `idempotency_key` | varchar(100) | unique, nullable | Duplicate prevention |
| `version` | int | default: 0 | Optimistic locking |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**Transaction Types:**
- `Buy`: Purchase foreign currency from customer
- `Sell`: Sell foreign currency to customer

**Transaction Status:**
- `Pending`: Awaiting approval
- `OnHold`: Compliance hold
- `Completed`: Successfully processed
- `Cancelled`: Cancelled transaction

**CDD Levels:**
- `Simplified`: Low-risk customers
- `Standard`: Normal customers
- `Enhanced`: High-risk/PEP customers

---

## 6. Stock & Cash Management

### till_balances

Daily till opening and closing records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Balance ID |
| `till_id` | varchar(50) | not null | Till identifier |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `date` | date | not null | Transaction date |
| `opening_balance` | decimal(18,4) | not null | Opening amount |
| `closing_balance` | decimal(18,4) | nullable | Closing amount |
| `variance` | decimal(18,4) | default: 0.0000 | Cash variance |
| `variance_reason` | text | nullable | Variance explanation |
| `opened_by` | bigint unsigned | FK, not null | User who opened |
| `closed_by` | bigint unsigned | FK, nullable | User who closed |
| `closed_at` | timestamp | nullable | Closing timestamp |
| `transaction_total` | decimal(18,4) | default: 0.0000 | Total transactions |
| `foreign_total` | decimal(18,4) | default: 0.0000 | Foreign total |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### currency_positions

Real-time foreign currency inventory tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Position ID |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `till_id` | varchar(50) | not null | Till identifier |
| `balance` | decimal(18,4) | default: 0.0000 | Current balance |
| `avg_cost_rate` | decimal(18,6) | default: 0.000000 | Weighted avg cost |
| `last_valuation_rate` | decimal(18,6) | nullable | Last valuation rate |
| `unrealized_pnl` | decimal(18,4) | default: 0.0000 | Unrealized P&L |
| `last_valuation_at` | timestamp | nullable | Last valuation date |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

---

## 7. Accounting

### chart_of_accounts

Chart of accounts structure (MIA compliant).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Account ID |
| `account_code` | varchar(20) | unique, not null | Account code |
| `account_name` | varchar(255) | not null | Account name |
| `account_type` | enum | not null | Account type |
| `parent_id` | bigint unsigned | FK, nullable | Parent account |
| `is_active` | tinyint(1) | default: 1 | Active status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**Account Types:**
- `Asset`: Cash, Inventory, Receivables
- `Liability`: Payables, Accruals
- `Equity`: Capital, Retained Earnings
- `Revenue`: Trading Income, Fees
- `Expense`: Operating Costs, Losses

### accounting_periods

Fiscal periods for accounting closure.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Period ID |
| `period_code` | varchar(20) | unique, not null | Period code (e.g., 2026-04) |
| `start_date` | date | not null | Period start |
| `end_date` | date | not null | Period end |
| `status` | enum | default: 'Open' | Period status |
| `closed_by` | bigint unsigned | FK, nullable | Closer user ID |
| `closed_at` | timestamp | nullable | Closure timestamp |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**Period Status:**
- `Open`: Transactions allowed
- `Closing`: In progress
- `Closed`: No transactions allowed

### journal_entries

Double-entry bookkeeping journal entries.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Entry ID |
| `entry_date` | date | not null | Transaction date |
| `period_id` | bigint unsigned | FK, nullable | Accounting period |
| `reference_type` | varchar(50) | nullable | Source (Transaction, etc.) |
| `reference_id` | bigint unsigned | nullable | Source ID |
| `description` | varchar(500) | not null | Entry description |
| `total_amount` | decimal(18,4) | not null | Total entry amount |
| `created_by` | bigint unsigned | FK, not null | Creator user ID |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### journal_lines

Individual debit/credit lines for journal entries.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Line ID |
| `journal_entry_id` | bigint unsigned | FK, not null | Parent entry |
| `account_id` | bigint unsigned | FK, not null | Account ID |
| `debit_amount` | decimal(18,4) | default: 0.0000 | Debit amount |
| `credit_amount` | decimal(18,4) | default: 0.0000 | Credit amount |
| `description` | varchar(500) | nullable | Line description |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### revaluation_entries

Month-end revaluation entries.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Entry ID |
| `currency_code` | varchar(3) | FK, not null | Currency code |
| `old_rate` | decimal(18,6) | not null | Previous rate |
| `new_rate` | decimal(18,6) | not null | Current rate |
| `gain_loss_amount` | decimal(18,4) | not null | Revaluation P&L |
| `revaluation_date` | date | not null | Revaluation date |
| `created_by` | bigint unsigned | FK, not null | Creator user ID |
| `created_at` | timestamp | - | Record creation |

---

## 8. Compliance & Monitoring

### sanction_lists

International sanction lists.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | List ID |
| `list_name` | varchar(255) | not null | List name |
| `list_type` | varchar(50) | not null | Type (UN, OFAC, etc.) |
| `country` | varchar(100) | nullable | Issuing country |
| `last_updated` | date | not null | Last update date |
| `is_active` | tinyint(1) | default: 1 | Active status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### sanction_entries

Individual entries on sanction lists.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Entry ID |
| `list_id` | bigint unsigned | FK, not null | Sanction list |
| `name` | varchar(255) | not null | Sanctioned name |
| `aliases` | text | nullable | Alternative names |
| `nationality` | varchar(100) | nullable | Nationality |
| `date_of_birth` | date | nullable | Birth date |
| `id_number` | varchar(100) | nullable | ID number |
| `reason` | text | nullable | Sanction reason |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### flagged_transactions

Transactions flagged for compliance review.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Flag ID |
| `transaction_id` | bigint unsigned | FK, not null | Transaction ID |
| `flag_type` | enum | not null | Flag reason |
| `severity` | enum | default: 'Medium' | Flag severity |
| `description` | text | nullable | Flag details |
| `status` | enum | default: 'Open' | Review status |
| `reviewed_by` | bigint unsigned | FK, nullable | Reviewer ID |
| `reviewed_at` | timestamp | nullable | Review timestamp |
| `resolution_notes` | text | nullable | Resolution notes |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

**Flag Types:**
- `Large_Amount`: Transaction ≥ RM 50,000
- `Sanctions_Hit`: Sanctions screening match
- `Velocity`: Velocity threshold exceeded
- `Structuring`: Suspicious structuring pattern
- `EDD_Required`: Enhanced Due Diligence needed

**Severities:**
- `Low`: Informational
- `Medium`: Review required
- `High`: Immediate attention
- `Critical`: Urgent action required

---

## 9. Reports & Audit

### system_logs

Comprehensive audit trail of all system actions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Log ID |
| `user_id` | bigint unsigned | FK, nullable | User who acted |
| `action` | varchar(100) | not null | Action type |
| `entity_type` | varchar(100) | not null | Affected entity type |
| `entity_id` | bigint unsigned | nullable | Affected entity ID |
| `old_values` | json | nullable | Previous values |
| `new_values` | json | nullable | New values |
| `description` | text | nullable | Action description |
| `ip_address` | varchar(45) | nullable | IP address |
| `user_agent` | text | nullable | User agent |
| `created_at` | timestamp | - | Record creation |

**Action Types:**
- `login`, `login_failed`, `logout`
- `user_created`, `user_updated`, `user_deleted`
- `transaction_created`, `transaction_approved`, `transaction_cancelled`
- `till_opened`, `till_closed`

### report_templates

Custom report template definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Template ID |
| `name` | varchar(255) | not null | Template name |
| `description` | text | nullable | Template description |
| `query_sql` | text | not null | SQL query |
| `parameters` | json | nullable | Report parameters |
| `created_by` | bigint unsigned | FK, not null | Creator ID |
| `is_active` | tinyint(1) | default: 1 | Active status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### reports_generated

Generated reports with download tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Report ID |
| `template_id` | bigint unsigned | FK, nullable | Template used |
| `report_name` | varchar(255) | not null | Report name |
| `parameters` | json | nullable | Report parameters |
| `file_path` | varchar(500) | not null | Storage path |
| `file_format` | enum | not null | Format (PDF, CSV, etc.) |
| `generated_by` | bigint unsigned | FK, not null | Generator ID |
| `status` | enum | default: 'Pending' | Generation status |
| `download_count` | int | default: 0 | Download count |
| `expires_at` | timestamp | nullable | Expiration date |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

---

## 10. Counter Management

### counters

Physical counter/till definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Counter ID |
| `counter_code` | varchar(50) | unique, not null | Counter code |
| `counter_name` | varchar(255) | not null | Counter name |
| `location` | varchar(255) | nullable | Physical location |
| `is_active` | tinyint(1) | default: 1 | Active status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### counter_sessions

User sessions at counters.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Session ID |
| `counter_id` | bigint unsigned | FK, not null | Counter ID |
| `user_id` | bigint unsigned | FK, not null | User ID |
| `session_start` | timestamp | not null | Start time |
| `session_end` | timestamp | nullable | End time |
| `opening_balance` | decimal(18,4) | not null | Opening amount |
| `closing_balance` | decimal(18,4) | nullable | Closing amount |
| `variance` | decimal(18,4) | default: 0.0000 | Cash variance |
| `status` | enum | default: 'Active' | Session status |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

### counter_handovers

Shift handover records between users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint unsigned | PK, auto-increment | Handover ID |
| `counter_id` | bigint unsigned | FK, not null | Counter ID |
| `from_user_id` | bigint unsigned | FK, not null | Handing over user |
| `to_user_id` | bigint unsigned | FK, not null | Receiving user |
| `currency_code` | varchar(3) | FK, not null | Currency |
| `amount` | decimal(18,4) | not null | Amount transferred |
| `handover_time` | timestamp | not null | Handover timestamp |
| `confirmed` | tinyint(1) | default: 0 | Confirmation status |
| `confirmed_at` | timestamp | nullable | Confirmation timestamp |
| `notes` | text | nullable | Handover notes |
| `created_at` | timestamp | - | Record creation |
| `updated_at` | timestamp | - | Last update |

---

## 11. Database Indexes

### Performance Indexes

| Table | Index Name | Columns | Type |
|-------|------------|---------|------|
| users | `idx_role` | role | B-tree |
| users | `idx_is_active` | is_active | B-tree |
| transactions | `idx_customer_id` | customer_id | B-tree |
| transactions | `idx_user_id` | user_id | B-tree |
| transactions | `idx_currency_code` | currency_code | B-tree |
| transactions | `idx_status` | status | B-tree |
| transactions | `idx_type` | type | B-tree |
| transactions | `idx_created_at` | created_at | B-tree |
| transactions | `idx_idempotency_key` | idempotency_key | UNIQUE |
| transactions | `idx_status_created` | status, created_at | Composite |
| transactions | `idx_customer_date` | customer_id, created_at | Composite |
| currency_positions | `idx_currency_till` | currency_code, till_id | UNIQUE |
| till_balances | `idx_till_currency_date` | till_id, currency_code, date | Composite |
| system_logs | `idx_user_id` | user_id | B-tree |
| system_logs | `idx_action` | action | B-tree |
| system_logs | `idx_entity` | entity_type, entity_id | Composite |
| system_logs | `idx_created_at` | created_at | B-tree |
| journal_entries | `idx_period_id` | period_id | B-tree |
| journal_entries | `idx_entry_date` | entry_date | B-tree |

---

## 12. ER Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         DATABASE ER DIAGRAM                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌────────────┐        ┌──────────────┐        ┌────────────┐ │
│  │   users    │        │ customers    │        │ currencies │ │
│  ├────────────┤        ├──────────────┤        ├────────────┤ │
│  │ id (PK)    │        │ id (PK)      │        │ code (PK)  │ │
│  │ username   │        │ full_name    │        │ name       │ │
│  │ email      │        │ id_type      │        │ symbol     │ │
│  │ role       │        │ nationality│        └──────┬─────┘ │
│  └─────┬──────┘        │ phone        │               │       │
│        │               └──────┬───────┘               │       │
│        │                      │                       │       │
│        │     ┌───────────────┘                       │       │
│        │     │                                       │       │
│        │     │                                       │       │
│        │     │    ┌──────────────┐                   │       │
│        │     │    │ transactions │                   │       │
│        │     │    ├──────────────┤                   │       │
│        │     └───►│ id (PK)      │                   │       │
│        │          │ customer_id  │◄──────────────────┘       │
│        └─────────►│ user_id      │                          │
│                   │ currency_code│◄─────────────────────────┘
│                   │ amount_local │
│                   │ status       │
│                   └──────┬───────┘
│                          │
│          ┌───────────────┼───────────────┐
│          │               │               │
│   ┌──────▼─────┐ ┌──────▼──────┐ ┌──────▼────────┐
│   │ till_      │ │ currency_   │ │ flagged_      │
│   │ balances   │ │ positions   │ │ transactions  │
│   └────────────┘ └─────────────┘ └───────────────┘
│
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  │ journal_     │  │ chart_of_    │  │ system_logs  │
│  │ entries      │  │ accounts     │  └──────────────┘
│  └──────────────┘  └──────────────┘
│
└─────────────────────────────────────────────────────────────────┘
```

---

## Appendix

### Database Maintenance Commands

```sql
-- Optimize all tables
OPTIMIZE TABLE transactions, customers, system_logs;

-- Analyze table statistics
ANALYZE TABLE transactions;

-- Check table health
CHECK TABLE transactions;

-- Repair table (if needed)
REPAIR TABLE transactions;

-- Show table size
SELECT 
    table_name AS `Table`,
    round(((data_length + index_length) / 1024 / 1024), 2) AS `Size (MB)`
FROM information_schema.TABLES
WHERE table_schema = 'cems_my'
ORDER BY (data_length + index_length) DESC;

-- Show slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Show indexes on table
SHOW INDEX FROM transactions;

-- Explain query execution
EXPLAIN SELECT * FROM transactions 
WHERE customer_id = 123 
AND created_at > '2026-04-01';
```

### Backup SQL Script

```sql
-- Full database backup
mysqldump -u root -p cems_my > cems_my_backup_$(date +%Y%m%d).sql

-- Specific tables
mysqldump -u root -p cems_my transactions customers system_logs > critical_backup.sql

-- Structure only
mysqldump -u root -p --no-data cems_my > schema_backup.sql
```

---

**END OF DATABASE SCHEMA DOCUMENTATION**
