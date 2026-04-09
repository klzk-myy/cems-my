# CEMS-MY Web Application Routes Documentation

**Version**: 1.1
**Last Updated**: April 2026
**Authentication**: Session-based (Laravel web routes)

> **Important**: This is not a REST API. It is a traditional server-rendered web application using session-based authentication.

---

## Overview

CEMS-MY is a Laravel 10.x web application for Malaysian Money Services Businesses (MSB), compliant with Bank Negara Malaysia (BNM) AML/CFT requirements. All routes use session-based authentication via Laravel's built-in session handling. This is not a REST API - it is a traditional server-rendered web application.

**Key Differences from REST API:**
- Authentication via session cookies, not Bearer tokens
- All routes are in the web middleware group (`/`)
- No `X-RateLimit-*` headers on responses
- No SDK libraries - users interact via browser

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Transactions](#2-transactions)
3. [Customers](#3-customers)
4. [Counters (Tills)](#4-counters-tills)
5. [Accounting](#5-accounting)
6. [Compliance & AML](#6-compliance--aml)
7. [Tasks](#7-tasks)
8. [Reports](#8-reports)
9. [User Management](#9-user-management)

---

## 1. Authentication

### Login Form

**Route**: `GET /login`

Display the login form.

**Route**: `POST /login`

Authenticate user with email/password.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | User email address |
| password | string | Yes | User password |
| mfa_code | string | No | 6-digit MFA code (if MFA enabled) |

**Redirects to**: `/mfa/verify` if MFA is enabled and not yet verified.

---

### Logout

**Route**: `POST /logout`

Invalidate the current session and log out the user.

**Redirects to**: `/login`

---

### MFA Setup

**Route**: `GET /mfa/setup`

Display MFA configuration page (TOTP QR code).

**Route**: `POST /mfa/setup`

Store MFA secret and enable MFA for the user.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| mfa_code | string | Yes | 6-digit code from authenticator app to verify setup |

---

### MFA Verification

**Route**: `GET /mfa/verify`

Display MFA verification prompt.

**Route**: `POST /mfa/verify`

Verify MFA code.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| mfa_code | string | Yes | 6-digit code from authenticator app |

**Redirects to**: `/dashboard` on success.

---

### MFA Recovery

**Route**: `GET /mfa/recovery`

Display recovery codes for MFA users.

---

### MFA Trusted Devices

**Route**: `GET /mfa/trusted-devices`

List trusted devices for MFA.

**Route**: `DELETE /mfa/trusted-devices/{deviceId}`

Remove a trusted device.

---

### Disable MFA

**Route**: `POST /mfa/disable`

Disable MFA for the current user.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| mfa_code | string | Yes | Current MFA code to confirm disable |

---

## 2. Transactions

### Transaction List

**Route**: `GET /transactions`

Display paginated list of transactions. Supports filtering via query parameters.

**Query Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status (Pending, Completed, Cancelled, etc.) |
| type | string | Filter by type (Buy, Sell) |
| currency_code | string | Filter by currency (USD, EUR, etc.) |
| date_from | date | Start date (YYYY-MM-DD) |
| date_to | date | End date (YYYY-MM-DD) |
| customer_id | integer | Filter by customer |

---

### Create Transaction

**Route**: `GET /transactions/create`

Display transaction creation form.

**Route**: `POST /transactions`

Create a new foreign currency transaction.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| customer_id | integer | Yes | Customer ID |
| type | string | Yes | Transaction type: "Buy" or "Sell" |
| currency_code | string | Yes | ISO currency code (USD, EUR, GBP, etc.) |
| amount_foreign | numeric | Yes | Amount in foreign currency |
| rate | numeric | Yes | Exchange rate to MYR |
| purpose | string | No | Purpose of transaction |
| source_of_funds | string | No | Source of funds |

**Business Rules**:
- Transactions >= RM 50,000 require manager approval
- All cash transactions >= RM 10,000 require CTOS reporting
- CDD (Customer Due Diligence) level determined automatically

---

### View Transaction

**Route**: `GET /transactions/{transaction}`

Display transaction details including customer info, approval status, and audit trail.

---

### Approve Transaction

**Route**: `POST /transactions/{transaction}/approve`

Approve a pending transaction (Manager/Admin only).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| notes | string | No | Approval notes |

---

### Cancel Transaction

**Route**: `GET /transactions/{transaction}/cancel`

Display cancellation form.

**Route**: `POST /transactions/{transaction}/cancel`

Cancel a transaction.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| reason | string | Yes | Reason for cancellation |

**Note**: All cancellations require manager approval for segregation of duties.

---

### Confirm Transaction

**Route**: `GET /transactions/{transaction}/confirm`

Display large transaction confirmation form.

**Route**: `POST /transactions/{transaction}/confirm`

Confirm a large transaction (>= RM 50,000) with manager sign-off.

---

### Transaction Receipt

**Route**: `GET /transactions/{transaction}/receipt`

Generate and display transaction receipt.

---

### Batch Upload

**Route**: `GET /transactions/batch-upload`

Display batch upload form for bulk transactions.

**Route**: `POST /transactions/batch-upload`

Process batch upload file (CSV/Excel).

---

## 3. Customers

### Customer List

**Route**: `GET /customers`

Display paginated list of customers with search and filtering.

**Query Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Search by name, phone, or ID number |
| risk_rating | string | Filter by risk (Low, Medium, High) |
| date_from | date | Registration date from |
| date_to | date | Registration date to |

---

### Create Customer

**Route**: `GET /customers/create`

Display customer registration form.

**Route**: `POST /customers`

Register a new customer.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| full_name | string | Yes | Customer full name |
| id_type | string | Yes | ID type: "mykad", "passport", "police", "army" |
| id_number | string | Yes | ID number |
| nationality | string | Yes | Nationality |
| date_of_birth | date | Yes | Date of birth (YYYY-MM-DD) |
| address | string | No | Address |
| phone | string | No | Phone number |
| email | string | No | Email address |
| occupation | string | No | Occupation |
| employer | string | No | Employer name |

---

### View Customer

**Route**: `GET /customers/{customer}`

Display customer details including KYC status, risk rating, and transaction history.

---

### Edit Customer

**Route**: `PUT /customers/{customer}`

Update customer information.

---

### Customer KYC Documents

**Route**: `GET /customers/{customer}/kyc`

Display KYC document management page.

**Route**: `POST /customers/{customer}/kyc`

Upload KYC document.

**Route**: `DELETE /customers/{customer}/kyc/{document}`

Delete KYC document.

**Route**: `POST /customers/{customer}/kyc/{document}/verify`

Verify a KYC document.

---

### Customer Transaction History

**Route**: `GET /customers/{customer}/history`

Display customer's transaction history.

**Route**: `GET /customers/{customer}/history/export`

Export transaction history (CSV).

---

## 4. Counters (Tills)

### Counter List

**Route**: `GET /counters`

Display all counters with current status.

---

### Open Counter

**Route**: `GET /counters/{counter}/open`

Display counter opening form with currency selection.

**Route**: `POST /counters/{counter}/open`

Open counter with initial float amounts.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| currencies | array | Yes | Array of currency floats: `[{"currency": "USD", "amount": "1000.00"}, ...]` |
| notes | string | No | Opening notes |

---

### Close Counter

**Route**: `GET /counters/{counter}/close`

Display counter closing form.

**Route**: `POST /counters/{counter}/close`

Close counter with final counts and closing float.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| currencies | array | Yes | Array of currency counts |
| notes | string | No | Closing notes |

---

### Counter Handover

**Route**: `GET /counters/{counter}/handover`

Display handover confirmation form.

**Route**: `POST /counters/{counter}/handover`

Transfer counter custody to another user.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| target_user_id | integer | Yes | User ID of receiving teller |
| notes | string | No | Handover notes |

---

### Counter Status

**Route**: `GET /counters/{counter}/status`

Display real-time counter status including current holdings and today's transactions.

---

### Counter History

**Route**: `GET /counters/{counter}/history`

Display counter transaction history.

---

## 5. Accounting

### Journal Entries

**Route**: `GET /accounting/journal`

Display journal entries list.

**Route**: `GET /accounting/journal/create`

Display journal entry creation form.

**Route**: `POST /accounting/journal`

Create a manual journal entry.

**Route**: `GET /accounting/journal/{entry}`

Display journal entry details.

**Route**: `POST /accounting/journal/{entry}/reverse`

Reverse a journal entry.

---

### Ledger

**Route**: `GET /accounting/ledger`

Display chart of accounts / account list.

**Route**: `GET /accounting/ledger/{accountCode}`

Display account ledger detail for specific account code.

---

### Trial Balance

**Route**: `GET /accounting/trial-balance`

Display trial balance report.

---

### Profit & Loss Statement

**Route**: `GET /accounting/profit-loss`

Display P&L statement.

---

### Balance Sheet

**Route**: `GET /accounting/balance-sheet`

Display balance sheet.

---

### Currency Revaluation

**Route**: `GET /accounting/revaluation`

Display revaluation form.

**Route**: `POST /accounting/revaluation/run`

Run currency revaluation for current period.

**Route**: `GET /accounting/revaluation/history`

Display revaluation history.

---

### Accounting Periods

**Route**: `GET /accounting/periods`

Display accounting periods list.

**Route**: `POST /accounting/periods/{period}/close`

Close an accounting period.

**Note**: Periods must be closed in sequence. Closing a period prevents further journal entries from being posted to that period.

---

### Budget

**Route**: `GET /accounting/budget`

Display budget vs actual report.

**Route**: `POST /accounting/budget`

Create budget entry.

**Route**: `PUT /accounting/budget/{budget}`

Update budget entry.

**Route**: `PATCH /accounting/budget/{budget}`

Patch budget entry.

---

### Bank Reconciliation

**Route**: `GET /accounting/reconciliation`

Display bank reconciliation page.

**Route**: `POST /accounting/reconciliation/import`

Import bank statement file (CSV/Excel).

**Route**: `GET /accounting/reconciliation/report`

Display reconciliation report.

**Route**: `POST /accounting/reconciliation/{reconciliation}/exception`

Mark item as exception.

**Route**: `GET /accounting/reconciliation/export`

Export reconciliation data.

---

### Journal Entry Workflow

**Route**: `GET /accounting/journal/workflow`

Display journal entry workflow status (Draft → Pending → Posted).

**Route**: `POST /accounting/journal/{entry}/submit`

Submit journal entry for approval.

**Route**: `POST /accounting/journal/{entry}/approve`

Approve journal entry (Manager required).

---

### Cash Flow Statement

**Route**: `GET /accounting/cash-flow`

Display cash flow statement (operating, investing, financing activities).

---

### Financial Ratios

**Route**: `GET /accounting/ratios`

Display financial ratios (liquidity, profitability, leverage, efficiency).

---

### Fiscal Year Management

**Route**: `GET /accounting/fiscal-years`

Display fiscal years list.

**Route**: `POST /accounting/fiscal-years`

Create fiscal year.

**Route**: `POST /accounting/fiscal-years/{year}/close`

Close fiscal year.

**Route**: `GET /accounting/fiscal-years/{yearCode}/report`

Display fiscal year report.

---

## 6. Compliance & AML

### Compliance Dashboard

**Route**: `GET /compliance`

Display compliance overview with flags, alerts, and risk metrics.

---

### Flagged Transactions

**Route**: `GET /compliance/flagged`

Display list of flagged transactions requiring review.

**Route**: `PATCH /compliance/flags/{flaggedTransaction}/assign`

Assign flagged transaction to a reviewer.

**Route**: `PATCH /compliance/flags/{flaggedTransaction}/resolve`

Resolve a flagged transaction.

**Route**: `POST /compliance/flags/{flaggedTransaction}/generate-str`

Generate STR (Suspicious Transaction Report) from flagged transaction.

---

### AML Rules

**Route**: `GET /compliance/rules`

Display AML rule configuration.

**Route**: `POST /compliance/rules`

Create new AML rule.

**Route**: `GET /compliance/rules/create`

Display AML rule creation form.

**Route**: `GET /compliance/rules/{rule}`

Display AML rule details.

**Route**: `PUT /compliance/rules/{rule}`

Update AML rule.

**Route**: `DELETE /compliance/rules/{rule}`

Delete AML rule.

**Route**: `PATCH /compliance/rules/{rule}/toggle`

Enable/disable AML rule.

---

### STR (Suspicious Transaction Reports)

**Route**: `GET /str`

Display STR list.

**Route**: `GET /str/create`

Display STR creation form.

**Route**: `POST /str`

Create new STR.

**Route**: `GET /str/{str}`

Display STR details.

**Route**: `PUT /str/{str}`

Update STR.

**Route**: `POST /str/{str}/submit`

Submit STR for internal review.

**Route**: `POST /str/{str}/submit-review`

Submit STR for compliance officer review.

**Route**: `POST /str/{str}/submit-approval`

Submit STR for approval.

**Route**: `POST /str/{str}/approve`

Approve STR.

**Route**: `POST /str/{str}/track-acknowledgment`

Track STR acknowledgment from BNM.

---

### Enhanced Due Diligence (EDD)

**Route**: `GET /compliance/edd`

Display EDD records list (Compliance Officer required).

**Route**: `GET /compliance/edd/create`

Display EDD creation form.

**Route**: `POST /compliance/edd`

Create new EDD record.

**Route**: `GET /compliance/edd/{record}`

Display EDD record details.

**Route**: `GET /compliance/edd/{record}/edit`

Edit EDD record.

**Route**: `PUT /compliance/edd/{record}`

Update EDD record.

**Route**: `POST /compliance/edd/{record}/submit`

Submit EDD for review.

**Route**: `POST /compliance/edd/{record}/approve`

Approve EDD (Manager or Compliance required).

**Route**: `POST /compliance/edd/{record}/reject`

Reject EDD (Manager or Compliance required).

---

## 7. Tasks

### Tasks Dashboard

**Route**: `GET /tasks`

Display all tasks.

**Route**: `GET /tasks/my`

Display current user's tasks.

**Route**: `GET /tasks/overdue`

Display overdue tasks.

---

### Create Task

**Route**: `GET /tasks/create`

Display task creation form.

**Route**: `POST /tasks`

Create new task.

---

### View Task

**Route**: `GET /tasks/{task}`

Display task details.

---

### Task Actions

**Route**: `POST /tasks/{task}/acknowledge`

Acknowledge task.

**Route**: `POST /tasks/{task}/complete`

Mark task complete.

**Route**: `POST /tasks/{task}/cancel`

Cancel task.

**Route**: `POST /tasks/{task}/escalate`

Escalate task.

---

### Task Statistics API

**Route**: `GET /api/tasks/stats`

Get task statistics (JSON API).

**Note**: This route is defined in `routes/web.php`, not `routes/api.php`, and uses session-based authentication.

---

## 8. Reports

### Reports Dashboard

**Route**: `GET /reports`

Display reports menu with available report types.

---

### LCTR (Large Cash Transaction Report)

**Route**: `GET /reports/lctr`

Display LCTR report form.

**Route**: `GET /reports/lctr/generate`

Generate LCTR report for >= RM 50,000 transactions.

**BNM Requirement**: Daily report for cash transactions >= RM 50,000

---

### MSB2 Report

**Route**: `GET /reports/msb2`

Display MSB2 report form.

**Route**: `GET /reports/msb2/generate`

Generate MSB2 daily transaction summary.

**BNM Requirement**: Daily transaction summary report

---

### LMCA (Large Cash Account) Report

**Route**: `GET /reports/lmca`

Display LMCA report form.

**Route**: `GET /reports/lmca/generate`

Generate LMCA monthly report.

**BNM Requirement**: Monthly report for large cash accounts

---

### Quarterly LVR (Large Value Report)

**Route**: `GET /reports/quarterly-lvr`

Display quarterly LVR report form.

**Route**: `GET /reports/quarterly-lvr/generate`

Generate quarterly large value transactions report.

**BNM Requirement**: Quarterly large value transactions report

---

### Position Limit Report

**Route**: `GET /reports/position-limit`

Display position limit report form.

**Route**: `GET /reports/position-limit/generate`

Generate currency position limit report.

---

### Compliance Summary

**Route**: `GET /reports/compliance-summary`

Display compliance summary for date range.

---

### Export Data

**Route**: `GET /reports/export`

Export data in various formats.

---

### Report History

**Route**: `GET /reports/history`

Display previously generated reports.

---

### Compare Reports

**Route**: `GET /reports/compare`

Compare two report periods.

---

### Customer Analysis

**Route**: `GET /reports/customer-analysis`

Display customer analysis report.

---

### Profitability Report

**Route**: `GET /reports/profitability`

Display profitability analysis report.

---

### Monthly Trends

**Route**: `GET /reports/monthly-trends`

Display monthly trend analysis.

---

## 9. User Management

### User List

**Route**: `GET /users`

Display user list.

**Route**: `POST /users`

Create new user.

---

### View User

**Route**: `GET /users/{user}`

Display user details.

---

### Edit User

**Route**: `PUT /users/{user}`

Update user.

**Route**: `GET /users/{user}/edit`

Display user edit form.

---

### Toggle User Status

**Route**: `POST /users/{user}/toggle`

Activate/deactivate user.

---

### Audit Log

**Route**: `GET /audit`

Display system audit log.

**Route**: `GET /audit/dashboard`

Display audit dashboard.

**Route**: `GET /audit/{log}`

Display audit log entry details.

**Route**: `POST /audit/export`

Export audit log entries.

**Route**: `GET /audit/rotate`

Display audit log rotation controls.

---

## Appendix: REST API Routes

> **Note**: CEMS-MY primarily uses session-based web routes. The REST API uses Laravel Sanctum token authentication and is documented below.
>
> **API Versioning**: The current REST API version is `api_v1.php`. The legacy `api.php` file is deprecated and should not be used for new integrations.

---

### Compliance Alerts

**Route**: `GET /api/compliance/alerts`

List compliance alerts with filtering.

| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status (Open, Assigned, Resolved, Closed) |
| severity | string | Filter by severity (Low, Medium, High, Critical) |
| type | string | Filter by alert type |
| date_from | date | Filter from date |
| date_to | date | Filter to date |

**Route**: `GET /api/compliance/alerts/summary`

Get alert summary statistics (counts by status, severity, type).

**Route**: `GET /api/compliance/alerts/overdue`

Get overdue alerts past their SLA deadline.

**Route**: `GET /api/compliance/alerts/{id}`

Get specific alert details.

**Route**: `POST /api/compliance/alerts/bulk-assign`

Bulk assign alerts to a reviewer.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| alert_ids | array | Yes | Array of alert IDs to assign |
| assigned_to | integer | Yes | User ID of reviewer |

**Route**: `POST /api/compliance/alerts/bulk-resolve`

Bulk resolve multiple alerts.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| alert_ids | array | Yes | Array of alert IDs to resolve |
| resolution | string | Yes | Resolution notes |

**Route**: `POST /api/compliance/alerts/auto-assign`

Auto-assign alerts based on workload distribution.

---

### Compliance Findings

**Route**: `GET /api/compliance/findings`

List compliance findings with filtering.

| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status (New, Reviewed, Dismissed, CaseCreated) |
| severity | string | Filter by severity (Low, Medium, High, Critical) |
| type | string | Filter by finding type |
| date_from | date | Filter from date |
| date_to | date | Filter to date |

**Route**: `GET /api/compliance/findings/stats`

Get finding statistics.

**Route**: `GET /api/compliance/findings/{id}`

Get specific finding details.

**Route**: `POST /api/compliance/findings/{id}/dismiss`

Dismiss a finding.

---

### Compliance Cases

**Route**: `GET /api/compliance/cases`

List compliance cases with filtering.

**Route**: `POST /api/compliance/cases`

Create new compliance case.

**Route**: `GET /api/compliance/cases/{id}`

Get case details.

**Route**: `PATCH /api/compliance/cases/{id}`

Update case.

**Route**: `POST /api/compliance/cases/{id}/notes`

Add note to case.

**Route**: `POST /api/compliance/cases/{id}/close`

Close case with resolution.

**Route**: `POST /api/compliance/cases/{id}/escalate`

Escalate case.

**Route**: `GET /api/compliance/cases/{id}/timeline`

Get case event timeline.

---

### EDD (Enhanced Due Diligence)

**Route**: `GET /api/compliance/edd`

List EDD records.

**Route**: `GET /api/compliance/edd/templates`

List EDD questionnaire templates.

**Route**: `GET /api/compliance/edd/{id}`

Get EDD record details.

**Route**: `POST /api/compliance/edd/{id}/questionnaire`

Submit EDD questionnaire.

**Route**: `POST /api/compliance/edd/{id}/approve`

Approve EDD record.

**Route**: `POST /api/compliance/edd/{id}/reject`

Reject EDD record.

---

### Risk Scoring

**Route**: `GET /api/risk/portfolio`

Get risk distribution across portfolio.

**Route**: `GET /api/risk/{customerId}`

Get customer's risk profile.

**Route**: `GET /api/risk/{customerId}/history`

Get customer's risk score history.

**Route**: `POST /api/risk/{customerId}/recalculate`

Recalculate customer risk score.

**Route**: `POST /api/risk/{customerId}/lock`

Lock customer's risk score.

**Route**: `POST /api/risk/{customerId}/unlock`

Unlock customer's risk score.

---

### Exchange Rates

**Route**: `GET /api/rates/history/{currency}`

Get historical exchange rates for a currency.

| Parameter | Type | Description |
|-----------|------|-------------|
| currency | string | ISO currency code (USD, EUR, GBP, etc.) |
| date_from | date | Start date (YYYY-MM-DD) |
| date_to | date | End date (YYYY-MM-DD) |

---

### Dashboard & Reporting

**Route**: `GET /api/compliance/dashboard`

Get dashboard KPIs (case summary, STR status, EDD status, findings, risk distribution).

**Route**: `GET /api/compliance/calendar`

Get BNM regulatory filing calendar.

**Route**: `GET /api/compliance/case-aging`

Get case aging and SLA metrics.

**Route**: `GET /api/compliance/audit-trail`

Get compliance audit trail (paginated).

**Route**: `GET /api/compliance/audit-trail/export`

Export audit trail as CSV.

**Route**: `GET /api/compliance/reports/auto`

Get auto-generated reports pending approval.

---

## Appendix: Middleware Stack

All routes are protected by authentication middleware. Additional middleware applied based on route:

| Middleware | Purpose |
|-----------|---------|
| auth | All authenticated users |
| role:manager | Manager or Admin role required |
| role:compliance | Compliance Officer or Admin required |
| CheckRoleAny | Multiple role support (e.g., `role.manager,compliance`) |
| EnsureMfaEnabled | MFA enforcement (redirects to MFA setup if not enabled) |
| EnsureMfaVerified | MFA verification required (`mfa.verified`) |
| DataBreachDetection | Data breach monitoring and alerting |
| session.timeout | Idle session timeout (configurable, default 15 min) |
| CheckBranchAccess | Branch-based access control |
| throttle | Rate limiting (varies by endpoint) |

---

## Appendix: Session-Based Authentication Notes

CEMS-MY uses Laravel's session-based authentication:

1. **Login**: User submits credentials to `POST /login`, Laravel establishes session
2. **Session Cookie**: Browser receives session cookie (not Authorization header)
3. **MFA**: After password auth, MFA code required for first-time or每次 login
4. **Session Timeout**: Sessions expire after configurable idle period (default 15 minutes)
5. **CSRF Protection**: All non-GET form submissions require valid CSRF token

This is fundamentally different from REST API token-based auth:

- No `Authorization: Bearer <token>` header needed
- No `X-RateLimit-*` response headers
- Session cookie automatically included with each request
- Logout invalidates session server-side

---

## Idempotency Keys

Transaction creation supports idempotency keys to prevent duplicate submissions:
- **Header**: `X-Idempotency-Key: <unique-key>` (UUID recommended)
- If a transaction with the same idempotency key exists, returns the existing transaction
- Key is stored in `transactions.idempotency_key` column
- Valid for 24 hours after first submission

---

## Appendix: REST API (routes/api.php)

The actual REST API with Bearer token authentication (Sanctum) has these endpoints:

**Route**: `POST /api/sanctions/search`

Search sanctions list.

**Route**: `POST /api/sanctions/upload`

Upload sanctions list.

**Route**: `POST /api/reports/lctr`

Generate LCTR report.

**Route**: `POST /api/reports/lctr/status`

Update LCTR status.

**Route**: `POST /api/reports/msb2`

Generate MSB2 report.

**Route**: `POST /api/reports/msb2/status`

Update MSB2 status.

**Route**: `GET /api/reports/download/{filename}`

Download report.

---

**END OF DOCUMENTATION**
