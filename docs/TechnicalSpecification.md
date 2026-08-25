# CEMS-MY — Technical Specification

> **Project:** CEMS-MY — Currency Exchange Management System (Malaysia)
> **Framework:** Laravel 12.x (PHP 8.3)
> **Compliance:** Bank Negara Malaysia (BNM) AML/CFT, PDPA
> **Scope:** Foreign-currency trading, till management, double-entry accounting, regulatory reporting, sanctions screening
> **Test baseline (current):** 1 746 tests / 4 247 assertions / 6 skipped / exit 0 — Pint PASS 1 410 files

---

## 1. Executive Summary & Objectives

### 1.1 System Overview
CEMS-MY is a single-tenant (per-deployment) web + REST API platform that operates money-services business (MSB) currency-exchange desks. It manages the full lifecycle of a foreign-currency trade — from teller entry, through manager approval, to EOD till close and BNM statutory reporting — while enforcing BNM AML/CFT controls and Bank Negara Malaysia (BNM) PDPA data-protection rules.

The system runs as a standard Laravel 12 application:
- **Frontend:** Server-rendered Blade + Tailwind CSS v4, Alpine.js for interactivity. No SPA/Vite runtime.
- **Backend:** REST API (Sanctum-authenticated) + web routes with identical service layer.
- **Queue:** Laravel queues (Horizon-monitored) for async compliance jobs.
- **Cache/State:** Redis (local dev has no auth; production must configure `requirepass`).

### 1.2 Core Business Logic Solved
- **Foreign-currency buy/sell transactions** with BCMath precision (`MathService`, never `float`).
- **Till (counter) lifecycle** — open with floats, transact, handover, emergency close, EOD reconciliation.
- **Tiered approval workflow** — teller creates, manager approves/rejects/cancels, compliance reviews high-risk.
- **Double-entry accounting** — every transaction produces journal entries in a `ChartOfAccount` / `JournalEntry` / `JournalLine` / `AccountLedger` / `AccountingPeriod` / `FiscalYear` structure, including month-end revaluation.
- **Sanctions & PEP screening** — OFAC/UN-listed entity screening on customers and transaction counterparties, with monthly rescreening.
- **CDD determination** — `Simplified` (< RM 3 000), `Specific` (RM 3 000–10 000), `Standard` (≥ RM 10 000), `Enhanced` (risk-based: PEP, sanction match, High risk) per BNM pd-00.md 14C.12/14C.13.
- **Regulatory reporting** — MSB(2) daily, EOD reconciliation daily, LMCA monthly, Quarterly LVR, STR submission.
- **Risk scoring** — composite customer risk score with behavioral baselines, velocity/structuring/geographic/pattern/amount sub-scores.
- **Customer KYC** — encrypted identity data, blind-index lookup (`id_number_hash` + HMAC), masked display accessor (`id_number_masked`).
- **Audit log** — tamper-evident hash chain (`SystemLog` with `previous_hash` + SHA-256; async sealing via `SealAuditHashJob`).

### 1.3 Out of Scope
This codebase does **not** handle:
- Multi-currency treasury or FX hedging (only retail exchange, single position per branch per currency).
- Cross-border wire transfers or correspondent banking (transactions are OTC cash trades).
- CRM/Marketing beyond operational KYC case management.
- Mobile banking / customer-facing portal (all UI is internal staff).
- Multi-tenant / SaaS deployment (one deployment = one MSB entity).
- Card issuing / interchange / BIN processing.
- Corporate loan/credit facilities.
- Integration with specific BNM STR submission API (STR is drafted and exported; submission is manual or via separate system).
- Real-time blockchain/crypto screening.
- Multi-bank account aggregation (bank reconciliation is manual).
- POS / e-commerce integration (POS module present is a lightweight receipt/daily-rate ledger, not e-commerce).

---

## 2. Architectural Overview

### 2.1 Layered Architecture

```
┌────────────────────────────────────────────────────────────┐
│  Presentation Layer                                         │
│  Blade views (resources/views) + Tailwind v4 + Alpine.js   │
├────────────────────────────────────────────────────────────┤
│  Route Layer                                                │
│  routes/web.php  ·  routes/api_v1.php  ·  routes/auth.php  │
│  routes/webhooks.php  ·  routes/console.php  · channels.php│
├────────────────────────────────────────────────────────────┤
│  Middleware Layer (registered in bootstrap/app.php)         │
│  Authenticate · CheckRole · EnsureMfaVerified ·             │
│  SessionTimeout · StrictRateLimit · IpBlocker ·             │
│  SecurityHeaders · QueryLogging · PerformanceTracking ·     │
│  EnsureBranchScope · EnsureSetupAccessible · ValidateSig.   │
├────────────────────────────────────────────────────────────┤
│  Controller Layer (61 controllers across 10 namespaces)     │
│  Web: Transaction/Counter/Customer/Dashboard/...            │
│  Api/V1: + Compliance/ + Accounting/ + Report/              │
├────────────────────────────────────────────────────────────┤
│  Form Request Layer (90+ requests, centralized validation)  │
├────────────────────────────────────────────────────────────┤
│  Service Layer (83+ services, constructor-injected DI)      │
│  Transaction/ · Compliance/ · Accounting/ · Risk/ ·         │
│  Customer/ · Branch/ · Audit/ · Security/ · Reporting/      │
│  DTOs/ · Contracts/ · Concerns/                            │
├────────────────────────────────────────────────────────────┤
│  Event Layer (13 events + 4 listeners)                      │
│  TransactionCreated · TransactionApproved ·                 │
│  TransactionCancelled · AlertCreated · CaseOpened · ...     │
├────────────────────────────────────────────────────────────┤
│  Queue Layer (Laravel Horizon) — 14 jobs                   │
│  Compliance/ · Accounting/ · Audit/ · Report/ · Send/       │
├────────────────────────────────────────────────────────────┤
│  Model Layer (62 Eloquent models + Bases/ + Traits/)        │
│  Transaction · Customer · Counter · JournalEntry · ...      │
├────────────────────────────────────────────────────────────┤
│  Database (MySQL) — 195 migrations, soft deletes,           │
│  hash-chain audit, DECIMAL(28,8) for money                  │
└────────────────────────────────────────────────────────────┘
```

### 2.2 Component Interaction (Transaction Lifecycle)

```
Teller (Browser)
  │
  ├── GET /transactions/create  ──►  TransactionController@create
  │       │
  │       ▼
  │   MFA re-verify required (mfa.verified middleware)
  │
  ├── POST /transactions   ──►  TransactionController@store
  │       │
  │       ▼
  │   TransactionService::create()
  │       ├── Validate form (StoreTransactionRequest)
  │       ├── Idempotency check (unique idempotency_key)
  │       ├── CddLevel::determine(amount, PEP, sanction, risk)
  │       ├── Screen customer (CustomerScreeningService)
  │       ├── Check sanctions (SanctionController/Screening)
  │       ├── Reserve stock (StockReservation)  — PendingApproval
  │       ├── Determine approval path:
  │       │     < RM 10 000  ─► Approved immediately
  │       │     RM 10 000–50 000 ─► PendingApproval (manager)
  │       │     ≥ RM 50 000 / High risk ─► Pending (compliance hold)
  │       └── Fire TransactionCreated event
  │            ├── TransactionCreatedListener → AuditService::logTransaction
  │            └── ComplianceEventListener → Velocity/Structuring monitors
  │
  ├── POST /{txn}/approve   ──►  TransactionApprovalController@approve
  │       │  (role:manager + mfa.verified)
  │       ▼
  │   TransactionApprovalService::approve()
  │       ├── Consume stock reservation
  │       ├── Debit/credit CurrencyPosition (lockForUpdate)
  │       ├── AccountingService::postJournalEntry()
  │       ├── LedgerService::credit() / ::debit()
  │       └── Fire TransactionApproved event
  │
  ├── POST /{txn}/cancel    ──►  TransactionCancellationController@cancel
  │       │  (role:manager + mfa.verified)
  │       ▼
  │   PendingCancellation → Approval required (Segregation of Duties)
  │
  └── GET /{counter}/close  ──►  CounterController@close
          │  (role:manager,admin)
          ▼
      EodReconciliationService::reconcile()
          ├── Sum transactions vs. till floats
          ├── Variance check (thresholds.variance yellow/red)
          └── Lock counter, create handover record
```

### 2.3 State Management & Data Flow Lifecycle

**No client-side state manager.** UI state is Blade-rendered; Alpine.js handles modal/inline interactions only. All state lives server-side in:
- **Session:** Laravel sessions (`session.timeout` middleware, configurable default 8 h).
- **Database:** All business state (transactions, counters, allocations, cases) persisted in MySQL.
- **Redis (Cache):** Exchange rates (cache key per branch), throttling buckets, job rate limits. No business state.
- **Queue:** Deferred work (sanctions import, monthly rescreening, deferred journal entries, notification dispatch).

**Key state machines:**

| Entity | States |
|---|---|
| `Transaction` | Draft → PendingApproval → Approved → Processing → Completed → Finalized. Alternates: Rejected, Failed, Pending → OnHold → Cancelled (PendingCancellation first), Reversed. |
| `CounterSession` | Open → HandedOver → Closed. Emergency path: EmergencyClosed. |
| `Counter` | Active / Inactive / Maintenance |
| `JournalEntry` | Draft → Pending → Posted. Rejected possible. |
| `AccountingPeriod` | Open / PendingClose / Closed / Locked |
| `FiscalYear` | Open / PendingClose / Closed |
| `StockReservation` | Pending → Consumed / Released / Expired |
| `StockTransfer` | Pending → Approved → Received. Cancelled possible. |
| `TransactionConfirmation` | Pending → Confirmed / Rejected |
| `ComplianceCase` | Open / InProgress / PendingReview / Resolved / Closed / Reopened |
| `ComplianceFinding` | Open / InProgress / Closed / FalsePositive / Escalated |
| `Alert` | Open / Assigned / InReview / Resolved / Dismissed / Escalated |

### 2.4 External Dependencies & Integrations

| Integration | Type | Service | Fallback |
|---|---|---|---|
| **Exchange-rate API** | External HTTP (configurable `RATE_API_URL`/`RATE_API_KEY`) | `RateApiService` | `RateManagementService::copyPrevious()` copies prior day's rates; manager override via `overrideRate()` |
| **OFAC/UN Sanctions lists** | Public XML/JSON feed (UN `consol_sanctions_list.xml`, OFAC SDN) | `SanctionsDownloadService` (fetch + timeout), `SanctionsImportService` (parse) | Manual file upload via `sanctions/upload`; previous list retained |
| **Laravel Horizon** | Queue dashboard | `php artisan horizon` | — |
| **Laravel Sanctum** | API token auth | `HasApiTokens` | — |
| **SMTP / Notification channels** | Mail + database notifications | `SendNotificationJob` | Graceful fallback: only failed channels logged |
| **HMAC-SHA256 blind index** | Local crypto | `EncryptionService` | Key rotation supported via `ENCRYPTION_KEY` |

No payment gateway, no third-party KYC provider, no blockchain node.

---

## 3. Data Models & Schema

> Full model catalog: **62 Eloquent models** across `app/Models/` + `app/Models/Compliance/`. Core financial models extend `TransactionModel` / `AccountingModel` / `ComplianceModel` / `SystemModel` base classes. All monetary columns are `DECIMAL(28,8)` cast via `MoneyCast`. Soft deletes on core financial tables. Hash-chain audit on `system_logs`.

### 3.1 Core Financial Models

#### 3.1.1 `Transaction`
```
Table: transactions   (SoftDeletes)
Base:   TransactionModel
```

| Field | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `customer_id` | BIGINT FK → customers | Nullable (walk-in) |
| `user_id` | BIGINT FK → users | Teller who created |
| `counter_id` | BIGINT FK → counters | |
| `till_id` | VARCHAR | Counter code string |
| `type` | ENUM (Buy/Sell) | `TransactionType` |
| `currency_code` | CHAR(3) | ISO 4217 |
| `counterparty_country` | CHAR(3) | ISO 3166-1 alpha-3 |
| `amount_local` | DECIMAL(28,8) | MYR |
| `amount_foreign` | DECIMAL(28,8) | Foreign units |
| `rate` | DECIMAL(28,8) | Applied rate |
| `base_rate` | DECIMAL(28,8) | Market rate for variance |
| `purpose` | TEXT | |
| `source_of_funds` | TEXT | KYC field |
| `source_of_wealth` | TEXT | Required for PEPs (14C.13.1(c)) |
| `status` | ENUM (13 states) | `TransactionStatus` |
| `hold_reason` | TEXT | |
| `approved_by` | BIGINT FK → users | |
| `approved_at` | DATETIME | |
| `cdd_level` | ENUM (4 levels) | `CddLevel` |
| `cancelled_at` / `cancelled_by` / `cancellation_reason` | — | |
| `original_transaction_id` / `is_refund` | — | Refund linkage |
| `idempotency_key` | VARCHAR UNIQUE | Duplicate prevention |
| `version` | INT | Optimistic locking |
| `transition_history` | JSON | State machine trace |
| `failure_reason` / `rejection_reason` / `reversal_reason` | TEXT | |
| `is_dlq` | BOOLEAN | Dead-letter queue flag |
| `created_at` / `updated_at` / `deleted_at` | DATETIME | |

**Relationships:**
- `belongsTo` Customer, User, Counter
- `hasMany` TransactionConfirmation, TransactionError, StockReservation, JournalEntry (via `reference_type`/`reference_id`)
- `hasOne` ScreeningResult (latest)

**Scopes:** `byCustomer`, `byUser`, `byStatus`, `byType`, `byDateRange`, `byBranch`, `pendingApproval`, `pendingCancellation`, `dlq`.

#### 3.1.2 `Customer`
```
Table: customers   (SoftDeletes)
```
| Field | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `full_name_encrypted` | VARCHAR | AES-256-CBC |
| `id_number_encrypted` | VARCHAR | AES-256-CBC (raw PII never stored in plaintext) |
| `id_number_hash` | VARCHAR INDEXED | HMAC-SHA256 blind index for exact-match KYC lookup |
| `phone_hash` | VARCHAR | HMAC blind index |
| `address_encrypted` | TEXT | |
| `occupation` / `employer` | TEXT | |
| `risk_rating` | ENUM (Low/Medium/High) | `RiskRating` |
| `cdd_level` | ENUM | `CddLevel` |
| `is_pep` / `is_pep_associate` | BOOLEAN | |
| `pep_type` | ENUM | `PepType` |
| `pep_cessation_date` | DATE | |
| `customer_type` | VARCHAR | Individual / Entity |
| `sanctions_screened_at` | DATETIME | |
| `frozen_at` / `freeze_reason` | — | |
| `engagement_started_at` | DATETIME | |
| `notes` | TEXT | |

**Relationships:**
- `hasMany` Transaction, CustomerDocument, CustomerNote, CustomerRiskHistory, RiskScoreSnapshot, CustomerRelation (as customer or related)
- `hasMany` CustomerBehavioralBaseline, CustomerRiskProfile, ComplianceCase
- **`id_number_masked`** accessor: decrypts then masks (`9001****1234`) — the **only** compliant display path.

**Key methods:**
- `findByIdNumber($idNumber)`: HMAC blind-index exact match without decrypting PII.
- `scopeForBranch($branchId)`: Branch-scoped queries.

#### 3.1.3 `Counter` & `CounterSession`
```
Table: counters    Table: counter_sessions
```

| `counters` | | | `counter_sessions` | | |
|---|---|---|---|---|---|
| `id` / `code` | VARCHAR UNIQUE | | `id` | BIGINT PK | |
| `branch_id` | FK → branches | | `counter_id` | FK → counters | |
| `status` | ENUM (Active/Inactive/Maintenance) | `CounterStatus` | `user_id` / `teller_allocation_id` | FK | |
| `assigned_teller_id` | FK → users | | `session_date` | DATE | |
| | | | `status` | ENUM (5 states) | `CounterSessionStatus` |
| | | | `opened_at` / `closed_at` | DATETIME | |
| | | | `physical_count_verified` | BOOLEAN | |
| | | | `handover_notes` / `requested_amount_myr` | — | |

**Session statuses:** Open → HandedOver → Closed. Emergency path: EmergencyClosed. New session only allowed when previous is closed/handed-over.

#### 3.1.4 `TillBalance`
```
Table: till_balances
```
Per-counter per-currency float balance. Tracks opening float, closing float, and aggregated buy/sell foreign totals for EOD reconciliation.

#### 3.1.5 `CurrencyPosition`
```
Table: currency_positions
Branch-scoped position per currency.
Total balance = sum of till balances + pool allocation.
Available balance = total - active StockReservation sum.
```

#### 3.1.6 Double-Entry Accounting
```
ChartOfAccount          →  (AccountType + AccountCode enum)
JournalEntry (Draft→Pending→Posted)  ──1:N── JournalLine (debit/credit)
AccountLedger          →  Running balance per account
AccountingPeriod       →  (Open/PendingClose/Closed/Locked)
FiscalYear             →  (Open/PendingClose/Closed)
Budget, CostCenter, Department, CostCenter
BankReconciliation, RevaluationEntry
```

**Accounting flow per transaction:**
```
Transaction → AccountingService::postJournalEntry()
  → Debit: Cash MYR (AccountCode::CASH_MYR)
  → Credit: Cash {CURRENCY} (AccountCode::CASH_FOREIGN)
  → LedgerService::debit() / ::credit()
  → RevaluationEntry created at month-end for foreign cash positions
```

#### 3.1.7 Compliance / AML Models
```
Alert                    → (priority + assignedTo + flaggedTransaction)
ComplianceCase           → (caseNumber UNIQUE, status, priority, assignedTo)
ComplianceCaseNote       → (author_id, note_type)
ComplianceCaseDocument   → (uploaded_by, file)
ComplianceCaseLink       → (case_id ↔ transaction_id)
ComplianceFinding        → (severity + finding_type + status)
ScreeningResult          → (customer_id, match_type, confidence)
EnhancedDiligenceRecord  → (edd_status + edd_risk_level)
EddQuestionnaireTemplate → (edd_template_type)
EddDocumentRequest       → (edd_document_status)
CustomerRiskProfile      → (risk dimensions)
CustomerBehavioralBaseline → (avg_transaction_size_myr + velocity + patterns)
RiskScoreSnapshot        → (score + components + trigger)
CustomerRiskHistory      → (old → new rating)
CustomerRelation         → (relation_type + engagement)
```

#### 3.1.8 Sanctions / KYC Models
```
SanctionList             → (slug + list_source + auto_update)
SanctionEntry            → (name + aliases + ids + addresses)
SanctionsAnalysis        → (customer_id + list + verdict)
SanctionImportLog        → (import metadata + row counts)
HighRiskCountry          → (country_code + risk_level)
CustomerDocument         → (document_type + verification status)
```

#### 3.1.9 Operational / Security Models
```
User                     → (role, branch_id, mfa fields, password_hash)
MfaRecoveryCode          → (user_id, code_hash, used_at)
DeviceComputations       → (trusted device fingerprint)
Branch                   → (code + name + status)
BranchPool               → (currency allocation for branch)
TellerAllocation         → (teller ↔ branch pool, approval state, rejection_reason)
StockReservation         → (transaction_id, currency, amount, status, expires_at)
StockTransfer / StockTransferItem → (status + line items)
EmergencyClosure         → (counter_id, reason, cooldown)
BranchClosureWorkflow    → (checklist tracking)
```

#### 3.1.10 Audit / System Models
```
SystemLog                → (user_id, action, entity_type, entity_id, payload, hash, previous_hash, severity)
   — Hash chain: previous_hash[i] = SHA-256(previous_hash[i-1] || payload[i-1])
   — Integrity verified by AuditService::verifyChainIntegrity()
AuditTrail               → (entity + action + diff + timestamp)
SystemHealthCheck        → (check_name, status, started_at, finished_at)
SystemAlert              → (level, resolved_at)
ReportGenerated          → (report_type, status, file_path, version)
ReportRun                → (status)
ReportSchedule           → (cron expression + report_type)
ThresholdAudit           → (category, key, old_value, new_value, changed_by, reason)
TestResult               → (test_name, status, output)
```

### 3.2 Relationships (Full Map)

| Parent | Relationship | Child | Type |
|---|---|---|---|
| User | hasMany | Transaction | 1:N |
| User | hasMany | MfaRecoveryCode | 1:N |
| User | hasMany | DeviceComputations | 1:N |
| User | hasMany | UserNotificationPreference | 1:N |
| User | hasMany | ComplianceCase (assigned_to) | 1:N |
| User | hasMany | ComplianceCaseDocument (uploaded_by) | 1:N |
| User | hasMany | ComplianceCaseNote (author_id) | 1:N |
| Customer | hasMany | Transaction | 1:N |
| Customer | hasMany | CustomerDocument, CustomerNote | 1:N |
| Customer | hasMany | CustomerRiskHistory, RiskScoreSnapshot | 1:N |
| Customer | hasMany | CustomerRelation (as customer/related) | 1:N |
| Customer | hasMany | ScreeningResult | 1:N |
| Customer | hasMany | EnhancedDiligenceRecord, BehavioralBaseline | 1:N |
| Customer | hasMany | SanctionsAnalysis | 1:N |
| Branch | hasMany | User, Counter, Transaction (via branch_id) | 1:N |
| Branch | hasMany | BranchPool | 1:N |
| Counter | hasMany | CounterSession | 1:N |
| Counter | hasOne | CounterHandover (latest) | 1:1 |
| CurrencyPosition | belongsTo | Branch, Currency | N:1 |
| Transaction | belongsTo | Customer, User, Counter | N:1 |
| Transaction | hasMany | TransactionConfirmation, TransactionError | 1:N |
| Transaction | hasOne | StockReservation (active) | 1:1 |
| Transaction | hasMany | ComplianceCaseLink | 1:N |
| JournalEntry | hasMany | JournalLine | 1:N |
| JournalEntry | belongsTo | AccountingPeriod, User | N:1 |
| ReportSchedule | hasMany | ReportRun | 1:N |
| Alert | belongsTo | Customer, User (assignedTo), Transaction | N:1 |
| ComplianceCase | belongsTo | User (assignedTo) | N:1 |

### 3.3 Caching Strategy

| Cache Key Pattern | Content | TTL | Invalidation |
|---|---|---|---|
| `rates:branch:{id}:all` | Full rate set per branch | `config(thresholds.rates.cache_duration)` | On override / fetch |
| `rates:branch:{id}:{currency}` | Per-currency rate | Same | Same |
| `cems:dashboard:{user}:{hash}` | Dashboard aggregates | Tag-based | `Cache::tags(['dashboard'])->flush()` on transaction create/approve |
| `cems:compliance:kpis` | Compliance dashboard KPIs | 5 min | On case/alert changes |
| Rate limit buckets | `throttle:*` | Per-window | Automatic |
| Session data | Redis | Session TTL | `session.timeout` middleware |

**No aggressive business-state caching.** Financial figures are always read from DB to avoid stale-balance risks. Only rates and dashboard aggregates are cached.

---

## 4. Interface & API Specifications

### 4.1 Route Files

| File | Lines | Purpose |
|---|---|---|
| `routes/web.php` | 430 | All Blade-rendered staff UI routes |
| `routes/api_v1.php` | 418 | Sanctum-authenticated REST API |
| `routes/auth.php` | 12 | Login / logout |
| `routes/webhooks.php` | 18 | Sanctions feed webhook |
| `routes/console.php` | 19 | Artisan command registration |
| `routes/channels.php` | 14 | Broadcast channels (User.{id}) |

### 4.2 Middleware Stack (Global + Aliases)

**Global web middleware** (`bootstrap/app.php`):
- `EncryptCookies`, `TrustProxies`, `PreventRequestsDuringMaintenance`, `VerifyCsrfToken`
- `SecurityHeaders` (HSTS + CSP + X-Frame-Options + X-Content-Type-Options)
- `QueryLogging`, `PerformanceTrackingMiddleware`

**Global API middleware:**
- `EnsureFrontendRequestsAreStateful` (Sanctum session cookie support)

**Aliases (route-level):**

| Alias | Middleware | Purpose |
|---|---|---|
| `auth` | `Authenticate` | Session auth |
| `role:X` | `CheckRole` | Enum-based RBAC (manager/admin/compliance/teller) |
| `mfa.verified` | `EnsureMfaVerified` | 15-min re-verification for sensitive ops |
| `session.timeout` | `SessionTimeout` | Idle session expiry (default 8 h) |
| `strict.ratelimit` | `StrictRateLimit` | BNM-compliant rate limiting |
| `ip.blocker` | `IpBlocker` | 10-fail/5-min → 1 h block |
| `branch.scope` | `EnsureBranchScope` | Branch-scoped data isolation |
| `setup.accessible` | `EnsureSetupAccessible` | Prevent writes after initial setup |
| `throttle:X,Y` | `ThrottleRequests` | Per-minute IP/user limits |
| `signed` | `ValidateSignature` | Signed URL validation |

### 4.3 Web Routes (Summary)

All web routes require `auth` + `session.timeout` except:
- `/` — `HomeController@__invoke` (home)
- `/login` — guest
- `/health` — auth + `throttle:60,1`
- `/test/query-log` — auth + `role:admin`
- `/setup/*` — `setup.accessible` gate

**Transaction routes** (require `mfa.verified` for create/approve/cancel):
```
GET     /transactions                              → index
GET     /transactions/create                       → create      [+mfa.verified]
POST    /transactions                              → store       [+mfa.verified]
GET     /transactions/{transaction}                → show
GET     /transactions/{transaction}/receipt        → receipt
POST    /transactions/{transaction}/approve        → approve     [role:manager + mfa.verified]
POST    /transactions/{transaction}/reject         → reject      [role:manager + mfa.verified]
GET     /transactions/{transaction}/cancel         → showCancel  [role:manager + mfa.verified]
POST    /transactions/{transaction}/cancel         → cancel.store [role:manager + mfa.verified]
POST    /transactions/{transaction}/approve-cancellation → [role:manager + mfa.verified]
POST    /transactions/{transaction}/reject-cancellation  → [role:manager + mfa.verified]
POST    /transactions/batch-upload                 → [role:manager]
```

**Counter routes:**
```
GET/POST  /counters/{counter}/open    [role:teller,manager,admin]
GET       /counters/{counter}/status  [role:teller,manager,admin]
GET/POST  /counters/{counter}/handover [role:teller,manager,admin]
POST      /counters/{counter}/handover/acknowledge [role:teller,manager,admin]
GET/POST  /counters/{counter}/close   [role:manager,admin]
GET/POST  /counters/{counter}/emergency [role:manager,admin]
```

**Customer routes:**
```
GET/POST  /customers                 → index / store
GET       /customers/search          → search (CustomerSearchController)
POST      /customers/quick-create    → quickCreate
GET/PUT   /customers/{customer}      → show / update
POST      /customers/{customer}/notes → storeNote
GET       /customers/exchange-rates  → getExchangeRates
```

**MFA routes** (all require `auth`):
```
GET/POST /mfa/setup, /mfa/verify, /mfa/recovery/verify
POST     /mfa/disable
GET      /mfa/recovery-codes, /mfa/trusted-devices
DELETE   /mfa/trusted-devices/{deviceId}
```

### 4.4 REST API v1 Routes

**All routes under `auth:sanctum`.** Branch-scoped under `branch.scope`.

| Method | Endpoint | Auth | Role | Rate Limit |
|---|---|---|---|---|
| GET | `/api/v1/user` | Sanctum | — | 60/min |
| GET | `/api/v1/transactions` | Sanctum | branch.scope | 60/min |
| POST | `/api/v1/transactions` | Sanctum | mfa.verified | **10/min** |
| GET | `/api/v1/transactions/{id}` | Sanctum | — | 60/min |
| POST | `/api/v1/transactions/{id}/approve` | Sanctum | manager + mfa.verified | 20/min |
| POST | `/api/v1/transactions/{id}/request-cancellation` | Sanctum | manager + mfa.verified | 10/min |
| POST | `/api/v1/transactions/{id}/approve-cancellation` | Sanctum | manager/compliance + mfa.verified | **5/min** |
| POST | `/api/v1/transactions/{id}/reject-cancellation` | Sanctum | manager/compliance + mfa.verified | 10/min |
| POST | `/api/v1/wizard/transactions/step1-3` | Sanctum | teller | 30/min |
| GET/POST/PUT/DELETE | `/api/v1/customers*` | Sanctum | — | 10–60/min |
| POST | `/api/v1/sanctions/search` | Sanctum | — | — |
| POST | `/api/v1/sanctions/upload` | Sanctum | **admin only** | — |
| POST | `/api/v1/reports/msb2` | Sanctum | manager/admin | — |
| POST | `/api/v1/reports/msb2/status` | Sanctum | manager/admin | — |
| GET | `/api/v1/reports/download/{file}` | Sanctum | manager/admin | — |
| GET/POST | `/api/v1/compliance/findings*` | Sanctum | compliance/admin | — |
| GET | `/api/v1/compliance/dashboard/kpis` | Sanctum | compliance/admin | — |
| GET/POST | `/api/v1/compliance/alerts*` | Sanctum | compliance | — |
| GET/POST/PATCH | `/api/v1/compliance/cases*` | Sanctum | compliance | — |

### 4.5 Webhook Endpoints

| Method | Endpoint | Auth | Rate Limit |
|---|---|---|---|
| POST | `/sanctions/update` | HMAC token (`hash_equals`) | **10/min** |
| GET | `/sanctions/health` | None (public) | **30/min** |

`SanctionsWebhookController` validates a constant-time shared secret token. If token is unconfigured, all requests are rejected. Rate-limited to prevent brute-force.

### 4.6 Webhook + Broadcast Channels

```php
Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int)$user->id === (int)$id);
```
Authenticated users can listen on their own channel. Used by `SendNotificationJob` for in-app/database notifications.

### 4.7 Key Request/Response Signatures

**`POST /transactions` (web & API v1)**
- **Form Request:** `StoreTransactionRequest` — validates `type:enum(Buy,Sell)`, `currency_code:required`, `amount_local:required|numeric`, `amount_foreign:required|numeric`, `rate:required|numeric`, `purpose:required|string`, `idempotency_key:required|unique`.
- **Success (201):** Transaction resource — id, status, amounts, cdd_level, idempotency_key.
- **Error codes:** `422` (validation), `409` (idempotency conflict / insufficient stock via `InsufficientStockException`), `403` (MFA not verified), `429` (rate limit), `401` (unauthenticated).

**`POST /transactions/{id}/approve`**
- **Middleware:** `role:manager` + `mfa.verified`.
- **Form Request:** `ConfirmTransactionApprovalRequest`.
- **Success (200):** Transaction resource, status `Completed`/`Approved`.
- **Error codes:** `409` (`InsufficientStockException` — reservation consumed failed), `422` (validation), `403` (self-approval blocked by `SelfApprovalException`).

**`POST /transactions/{id}/cancel`**
- **Middleware:** `role:manager` + `mfa.verified`.
- **Form Request:** `CancelTransactionRequest` with `cancellation_reason:required`.
- **Result:** Status → `PendingCancellation`. Second manager/compliance approval finalizes.

**`POST /counters/{id}/open`**
- **Form Request:** `OpenCounterRequest` (opening floats per currency).
- **Error codes:** `409` (`TillAlreadyOpenException` / `UserAlreadyAtCounterException` / `TillBalanceMissingException`).

**`POST /counters/{id}/close`**
- **Form Request:** `CloseCounterRequest` (closing floats).
- **Error codes:** `409` (`TillClosedException` / `VarianceThresholdException` if outside threshold).

**`POST /customers/{id}/kyc`**
- **Multipart upload.** Sanitizes filename with `basename()` or UUID.

**Sanctions webhook (`POST /sanctions/update`)**
- **Header:** `X-Sanctions-Token: {shared_secret}` (constant-time `hash_equals` verify).
- **Body:** JSON payload → `SanctionsImportService::import($filepath)`.

### 4.8 API Resource Transformers

All API responses serialized through `app/Http/Resources/Api/V1/*` resource classes. Customer resources use `id_number_masked` accessor (PDPA-compliant). Transaction resources include `cdd_level`, `approval_status`, `compliance_flag`.

---

## 5. Error Handling & Edge Cases

### 5.1 Failure Points & Recovery

| Failure Point | Location | Exception | Recovery |
|---|---|---|---|
| Stock reservation expired at approval | `TransactionApprovalService` | `StockReservationExpiredException` | Release old reservation; re-reserve or reject |
| Insufficient foreign currency at sell | `CurrencyPositionService` | `InsufficientStockException` | Reject transaction; notify teller |
| Till balance missing | `CounterService::open` | `TillBalanceMissingException` | Manager must initialize floats first |
| Till already open | `CounterService::open` | `TillAlreadyOpenException` | Close first, then open |
| User at another counter | `CounterService::open` | `UserAlreadyAtCounterException` | Close other counter first |
| Variance exceeds threshold at close | `EodReconciliationService` | `VarianceThresholdException` | Block close; flag for manager review |
| Self-approval | `TransactionApprovalService` | `SelfApprovalException` | Reject; must approve another teller's txn |
| Segregation of duties violated | `TransactionApprovalService` | `SegregationOfDutiesException` | Reject; supervisor required |
| Rate deviation exceeds role limit | `RateManagementService::validateTransactionRate` | `InvalidRateException` | Teller: ±0.5%; Manager: ±2%; Admin: unlimited |
| External rate API unreachable | `RateApiService::fetchLatestRates` | `InvalidRateException` | Fallback to `RateManagementService::copyPrevious()` |
| Sanctions feed parse failure | `SanctionsImportService` | `SanctionsImportException` | Previous list retained; manual upload available |
| XML parse error | `SanctionsImportService` | `SanctionsImportException` | `libxml_use_internal_errors` captures and rethrows |
| MFA not verified for sensitive op | `EnsureMfaVerified` middleware | `MfaValidationException` | 403; user redirected to MFA verify |
| Session idle timeout | `SessionTimeout` middleware | — | 419; user re-auth required |
| IP blocked after 10 failed logins | `IpBlocker` middleware | — | 403; 1 h block duration |
| Hash chain broken | `AuditService::verifyChainIntegrity` | `AuditIntegrityException` | Flag for forensic review |
| Unbalanced journal entry | `AccountingService` | `UnbalancedJournalEntriesException` | Reject posting; debit must equal credit |
| Closed accounting period | `AccountingService` | `ClosedPeriodException` | Cannot post; must reopen period |
| Duplicate transaction | `TransactionService` | `DuplicateTransactionException` | Unique `idempotency_key` constraint rejects |
| Optimistic locking conflict | `TransactionModel` | `TransactionException` | `version` column; stale writer gets conflict |
| Fiscal year closed | `FiscalYearService` | `FiscalYearClosedException` | Must close or carry forward |
| DLQ stuck transaction | `DlqController` | — | Admin can retry or purge |
| Queue job failure | Horizon | — | `ProcessTransactionRetry` job; `RetryFailedJobs` command |

### 5.2 Validation & Sanitization

- **Centralized validation:** 90+ Form Request classes under `app/Http/Requests/`. Controllers inject typed requests — no inline `validate()` calls.
- **Money:** Always `string` from DB (`DECIMAL` → cast via `MoneyCast`); all arithmetic via `MathService` (BCMath wrappers: `add`, `subtract`, `multiply`, `divide`, `compare`, `round`). Never `float`.
- **Rate precision:** 4 decimal places, configurable via `RATE_PRECISION`.
- **PII masking:** `id_number_masked` accessor — decrypts AES-256-CBC value, masks middle digits (`9001****1234`). Raw `id_number` column does not exist; no accessor exposes it.
- **Blind index:** `id_number_hash` = HMAC-SHA256 of `id_number`. Enables exact-match KYC without decrypting PII.
- **Filename sanitization:** `basename()` or `Str::uuid()` on all uploads.
- **Blade escaping:** All views use `{{ }}` (auto-escaped). Zero `|raw` usages in the codebase.
- **Rate limiting:** Per-endpoint (login: 5/min, API: 30/min, transactions: 10/min, STR: 3/min, bulk: 1/5min). Burst protection allows small bursts but enforces average rates.
- **CSRF:** Enabled globally on web routes. API uses Sanctum token auth (no CSRF).
- **Input whitelist:** `DB::raw` usage in `TransactionReportQuery` validates column names against a whitelist (`['amount_local','amount_foreign']`) before interpolation. `DbDate::monthColumn` wraps identifiers with `QueryGrammar::wrap()` before embedding in `DB::raw`.

### 5.3 Error Response Envelope

Standard Laravel exception handler renders:
- **Web requests:** 403/422 with redirect + session flash error.
- **API requests:** JSON `{ message, errors?, status }`. Domain exceptions return `{ message, type, detail }` where `type` is the exception class name.

### 5.4 Domain Exception Catalog (43 types)

All under `app/Exceptions/Domain/`. Key ones:
`InsufficientStockException`, `StockReservationExpiredException`, `TillAlreadyOpenException`, `TillBalanceMissingException`, `TillClosedException`, `VarianceThresholdException`, `SessionClosedException`, `UserAlreadyAtCounterException`, `SelfApprovalException`, `SegregationOfDutiesException`, `SupervisorRequiredException`, `InvalidRateException`, `InvalidCurrencyException`, `InvalidStateException`, `TransactionAlreadyProcessedException`, `TransactionApprovalException`, `TransactionValidationException`, `TransactionBlockedException`, `DuplicateTransactionException`, `ClosedPeriodException`, `UnbalancedJournalEntriesException`, `FiscalYearClosedException`, `EmergencyCloseCooldownException`, `EmergencyCloseSessionTooNewException`, `EncryptionConfigurationException`, `AuditIntegrityException`, `MfaValidationException`, `UserManagementException`, `PermissionDeniedException`, `UnauthorizedException`, `CaseManagementException`, `EddValidationException`, `PepApprovalRequiredException`, `RiskProfileNotFoundException`, `MathValidationException`, `NotificationPreferenceException`, `PoolAllocationException`, `AllocationValidationException`, `InvalidAllocationStateException`, `InvalidIpAddressException`, `BranchDeactivationException`, `BackupException`, `ThresholdNotFoundException`, `ReportValidationException`, `MonthEndPreCheckFailedException`, `NoActiveCounterSessionException`, `AccountingPeriodException`.

---

## 6. Security & Performance Considerations

### 6.1 Authentication & Authorization

**Authentication:**
- **Web:** Laravel session auth (`EncryptCookies` + `VerifyCsrfToken`). Passwords hashed via Laravel `Hash::make` (bcrypt/argon2id).
- **API:** Laravel Sanctum (`HasApiTokens`). Stateful Sanctum for same-origin SPAs; token-based for server-to-server.
- **MFA:** TOTP (RFC 6238), 30 s period, 6 digits. Required for all roles (including Tellers) — BNM requirement.
  - `EnsureMfaEnabled` forces setup on first login.
  - `EnsureMfaVerified` requires re-verification for write/approval operations. 15-min session expiry; trusted device bypass.
  - Recovery codes (SHA-256 hashed, `MfaRecoveryCode`). Trusted devices (`DeviceComputations` table).
- **Password policy:** Min 12 chars, mixed case, number, special char. Max 5 attempts, 15-min lockout.

**Authorization (Enum-based RBAC):**

| Role | Can Approve Large Txn | Can Access Compliance | Can Cancel Txn | Can Manage Users | Can Override Rate |
|---|---|---|---|---|---|
| `Teller` | No | No | No | No | ±0.5% |
| `Manager` | Yes | No | Yes | No (tellers only) | ±2% |
| `ComplianceOfficer` | No | Yes | No | No | Unlimited |
| `Admin` | Yes | Yes | Yes | Yes | Unlimited |

**Middleware stack enforces at every endpoint:**
- `role:X` middleware checks `UserRole` enum methods (`isManager()`, `canApproveLargeTransactions()`, etc.).
- `EnsureBranchScope` restricts non-admins to their own branch data.
- `CheckRoleAny` for composite role checks.

### 6.2 Data Encryption (at Rest & in Transit)

**At rest:**
- **PII fields** (`full_name_encrypted`, `id_number_encrypted`, `address_encrypted`): AES-256-CBC via `EncryptionService`. Random IV prepended to ciphertext per encryption.
- **Passwords:** Argon2id/bcrypt via `Hash::make`.
- **MFA recovery codes:** SHA-256 hashed, stored in `MfaRecoveryCode.code_hash`.
- **Blind index:** HMAC-SHA256 for `id_number_hash` and `phone_hash`.
- **Sanctum tokens:** Encrypted at rest by framework.

**In transit:**
- **HSTS:** `SECURITY_HSTS_MAX_AGE=31536000` (1 year), include subdomains. Preload optional.
- **SecurityHeaders middleware:** CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection.
- **HTTPS enforced** via HSTS + SecurityHeaders.

**PII access control:**
- `$hidden = ['password', 'password_hash', 'mfa_secret']` on User.
- Customer PII fields hidden from mass assignment; only accessible through `CustomerService` decrypt methods with explicit role checks.
- `id_number_masked` accessor is the **only** display path. All API resources (`CustomerResource`), alert controllers, and dashboard views use this accessor.
- `id_number` raw column does **not** exist. No `getIdNumberAttribute` accessor. Accessing `$customer->id_number` returns `null` today (safe by accident, not by design).

### 6.3 Audit & Tamper Evidence

**SystemLog hash chain:**
- Each `SystemLog` entry stores `hash` (SHA-256 of payload + previous hash) and `previous_hash` (link to prior entry).
- `AuditService::logWithSeverity()` creates entries with null hash; `SealAuditHashJob` seals the chain asynchronously via Laravel queue (avoids global DB lock contention during high-throughput operations like bulk transactions).
- `AuditService::verifyChainIntegrity()` returns `{ valid: bool, broken_at: int|null, message: string }`. Broken chain triggers `AuditIntegrityException`.
- Async job `SealAuditHashJob` runs after each audit write via `dispatchAfterResponse`.

**ThresholdAudit:** All threshold changes recorded with old/new value, changed_by, reason.

**AuditTrail:** Entity-level action diff with timestamp for all significant state changes.

### 6.4 Performance Bottlenecks & Scaling Recommendations

| Area | Current Implementation | Risk / Observation |
|---|---|---|
| **Money precision** | BCMath via `MathService` (string arithmetic) | Correct. Never float. |
| **Database concurrency** | `lockForUpdate()` on position updates; `version` column optimistic locking on transactions | Correct for race conditions. |
| **Stock reservation** | 24-hour expiry (`reservation:expire` command). `getAvailableBalance()` subtracts active reservations. | Correct for concurrency. |
| **Audit hash sealing** | Async via `SealAuditHashJob` to avoid lock contention | Correct. Unsealed entries monitorable via `getUnsealedCount()`. |
| **Dashboard caching** | Tag-based `Cache::tags(['dashboard'])`, auto-invalidated on transaction changes | Correct. Avoids full recompute. |
| **N+1 prevention** | Eager loading on Alert (`with(['customer','assignedTo','flaggedTransaction'])`); CounterService pre-fetches currencies; CustomerSearchController eager loads | Verified in audit. |
| **Queue** | Laravel Horizon for monitoring; 14 job types across Compliance/Accounting/Audit/Report | Correct. |
| **Rate API cache** | `Cache::remember` per branch + per-currency | Correct. |

**Recommended scaling improvements (informational — not blocking):**

1. **Database connection pooling.** High-throughput periods (EOD reconciliation) hit many lockForUpdate. Consider read replicas for reporting queries.
2. **Full-text index on customer names.** `full_name_encrypted` can't be indexed. Consider a searchable pseudonym column if search volume grows.
3. **Batched sanctions import.** Current import reads entire file into memory. For lists >100 k entries, switch to streaming parser.
4. **Rate-limit per-user not just per-IP.** Already partially done (throttle is per-user). Consider per-role limits.
5. **Redis auth in production.** Local dev intentionally has no `requirepass`. Production must set `REDIS_PASSWORD` and configure Redis `requirepass`. See `.env.example`.
6. **Horizontal scaling.** Laravel Horizon supervisors per queue (compliance/long vs. audit/short). Add `supervisors` config in `horizon.php`.
7. **Audit log archival.** `RotateAuditLogs` command exists. Configure retention policy (BNM typically requires 5+ years for AML records).
8. **Backup verification.** `BackupLog` model tracks backups. Add integrity check job to verify restorability.
9. **Query logging in production.** `QueryLogging` middleware is global. Disable in production (`APP_DEBUG=false` guards the output, but still writes to log).
10. **Blade template caching.** Already handled by Laravel's view compiler. No action needed.

### 6.5 Scheduled Commands (Cron)

All registered in `bootstrap/app.php` `withSchedule`:

| Command | Schedule | Purpose |
|---|---|---|
| `report:msb2` | Daily 00:05 | BNM MSB(2) daily transaction summary |
| `report:position-limit` | Daily 06:00 | Position limit utilization check |
| `report:eod` | Daily 20:00 | EOD reconciliation (after counters close) |
| `ReconcileDeferredAccountingJob` | Daily 21:00 (without overlapping) | Auto-create journal entries for Enhanced CDD transactions |
| `report:lmca` | Monthly | Monthly LMCA report |
| `report:qlvr` | Quarterly | Quarterly Large Value report |
| `compliance:rescreen` | Monthly | Sanctions rescreening of all customers |
| `reservation:expire` | Daily | Release stale 24-hour stock reservations |
| `backup:*` | Configurable | Automated backups |
| `queue:health-check` | Frequent | Queue health monitoring |

### 6.6 Configuration Files (33 configs)

| Config | Key Settings |
|---|---|
| `config/cems.php` | Transaction cancellation window, aggregate lookback days, position limits per currency, BNM reporting thresholds (EDD/STR), MFA settings |
| `config/thresholds.php` | Approval thresholds (auto: 10k, manager: 50k), CDD thresholds (3k/10k/50k), risk scoring thresholds, velocity/structuring windows, variance thresholds |
| `config/security.php` | HSTS, rate limits (login: 5/min, API: 30/min, transaction: 10/min, STR: 3/min, bulk: 1/5min), session settings |
| `config/transactions.php` | Batch upload max rows (5 000) |
| `config/accounting.php` | Accounting period settings |
| `config/sanctions.php` | Sanctions list sources (OFAC/UN), download URLs |
| `config/backup.php` | Backup retention, destinations |
| `config/queue.php` | Redis connection, queue names |
| `config/horizon.php` | Horizon supervisors, metrics retention |
| `config/hashing.php` | Driver (argon2id/bcrypt) |
| `config/session.php` | Redis driver, idle timeout |
| `config/ratelimit.php` | Rate limit definitions |

---

## Appendix A: Enum Catalog (44 enums)

All enums are PHP 8.3 backed string enums under `app/Enums/`. Key ones documented in §3.1. Full catalog:

| Enum | Values |
|---|---|
| `AccountCode` | 14 codes (CASH_MYR, CASH_FOREIGN, REVENUE, etc.) |
| `AccountingPeriodStatus` | Open, PendingClose, Closed, Locked |
| `AccountingPeriodType` | Monthly, Quarterly, Annual |
| `AccountType` | Asset, Liability, Equity, Income, Expense, OffBalance |
| `AlertPriority` | Low, Medium, High, Critical |
| `AmlRuleType` | Amount, Frequency, Pattern, Threshold |
| `ApprovalLevel` | Auto, Manager, Compliance |
| `ApprovalStatus` | Pending, Approved, Rejected |
| `BackupStatus` | Pending, InProgress, Completed, Failed |
| `BankReconciliationStatus` | Pending, Reconciled, Discrepancy |
| `BranchClosureStatus` | Pending, InProgress, Completed |
| `CaseNoteType` | Internal, External, Audit |
| `CaseResolution` | Resolved, Escalated, FalsePositive, Pending |
| `CaseStatus` | Open, InProgress, PendingReview, Resolved, Closed, Reopened |
| `CddLevel` | Simplified, Specific, Standard, Enhanced |
| `CheckStatus` | Pending, Cleared, Returned, Stopped |
| `ComplianceCasePriority` | Low, Medium, High, Critical |
| `ComplianceCaseStatus` | Open, InProgress, PendingReview, Resolved, Closed, Reopened |
| `ComplianceCaseType` | STR, EDD, PEP, Sanction, Other |
| `ComplianceFlagType` | 16 flag types |
| `CounterSessionStatus` | Open, Closed, HandedOver, PendingHandover, EmergencyClosed |
| `CounterStatus` | Active, Inactive, Maintenance |
| `DocumentType` | IC, Passport, BusinessReg, Others |
| `EddDocumentStatus` | Requested, Submitted, Verified, Rejected |
| `EddRiskLevel` | Low, Medium, High |
| `EddStatus` | Pending, InProgress, Completed, Overdue |
| `EddTemplateType` | Standard, Enhanced, PEP |
| `EmploymentStatus` | Employed, SelfEmployed, Retired, Student, Unemployed |
| `EntityType` | Individual, Business, Trust |
| `ErrorType` | 12 error types |
| `FindingSeverity` | Low, Medium, High, Critical |
| `FindingStatus` | Open, InProgress, Closed, FalsePositive, Escalated |
| `FindingType` | 8 finding types |
| `FiscalYearStatus` | Open, PendingClose, Closed |
| `FlagStatus` | Open, Acknowledged, Resolved, Dismissed |
| `HighRiskCountryRiskLevel` | High, VeryHigh |
| `IdType` | IC, Passport, DriverLicense, Others |
| `JournalEntryStatus` | Draft, Pending, Posted, Rejected |
| `MatchType` | Exact, Fuzzy, Alias |
| `NavigationPermission` | 8 permissions |
| `NotificationType` | Transaction, Alert, Report, System |
| `PepType` | HeadOfState, SeniorGov, Judge, Military, BusinessLeader, PartyOfficial, SOE, Family, CloseAssociate |
| `RecalculationTrigger` | Manual, Scheduled, TransactionChange |
| `ReferenceType` | Transaction, Customer, Counter, Report |
| `RelationType` | Family, Business, BeneficialOwner, Director |
| `ReportGeneratedStatus` | Pending, Generating, Completed, Failed, Archived |
| `ReportRunStatus` | Scheduled, Running, Completed, Failed |
| `ReportType` | 8 types (MSB2, EOD, LMCA, LVR, STR, Position, Reconciliation, TrialBalance) |
| `RiskRating` | Low, Medium, High |
| `RiskTrend` | Improving, Stable, Deteriorating |
| `SanctionListType` | OFAC, UN, Local, Custom |
| `SanctionStatus` | Active, Removed, Superseded |
| `StockReservationStatus` | Pending, Consumed, Released, Expired |
| `StockTransferStatus` | Pending, Approved, Received, Cancelled |
| `SystemAlertLevel` | Info, Warning, Critical |
| `SystemHealthCheckStatus` | Healthy, Degraded, Unhealthy |
| `TellerAllocationStatus` | Pending, Approved, Rejected |
| `TestResultStatus` | Passed, Failed, Skipped |
| `TransactionConfirmationStatus` | Pending, Confirmed, Rejected |
| `TransactionImportStatus` | Pending, Processing, Completed, Failed |
| `TransactionStatus` | 13 states (see §2.3) |
| `TransactionType` | Buy, Sell |
| `UpdateStatus` | Pending, Updated, Failed |
| `UserRole` | Teller, Manager, ComplianceOfficer, Admin |

---

## Appendix B: Background Job Catalog (14 jobs)

| Job | Namespace | Purpose |
|---|---|---|
| `ComplianceScreeningJob` | `Jobs` | Run compliance screening on transaction creation |
| `ImportSanctionsJob` | `Jobs` | Async sanctions list import |
| `ProcessTransactionRetry` | `Jobs` | Retry failed transactions from DLQ |
| `ReportGenerationJob` | `Jobs` | Async report generation (CSV/PDF/Excel) |
| `RescreenHighRiskCustomersJob` | `Jobs` | Monthly rescreening of High-risk customers |
| `SendNotificationJob` | `Jobs` | Dispatch notifications (mail/sms/database) |
| `ReconcileDeferredAccountingJob` | `Jobs/Accounting` | Auto-post journal entries for deferred Enhanced CDD |
| `SealAuditHashJob` | `Jobs/Audit` | Seal audit hash chain (async, avoids lock contention) |
| `CounterfeitAlertJob` | `Jobs/Compliance` | Detect counterfeit currency patterns |
| `CurrencyFlowJob` | `Jobs/Compliance` | Currency flow round-trip detection (7-day lookback) |
| `CustomerLocationAnomalyJob` | `Jobs/Compliance` | Geographic anomaly detection |
| `SanctionsRescreeningJob` | `Jobs/Compliance` | Monthly rescreening of all customers |
| `StructuringMonitorJob` | `Jobs/Compliance` | Sub-threshold aggregation detection (3000 × 3 txn / 1 hr) |
| `VelocityMonitorJob` | `Jobs/Compliance` | Velocity/structuring patterns (90-day lookback) |

---

## Appendix C: Event & Listener Catalog

| Event | Listeners | Purpose |
|---|---|---|
| `TransactionCreated` | `TransactionCreatedListener`, `ComplianceEventListener` | Audit log + compliance trigger |
| `TransactionApproved` | `TransactionApprovedListener`, `ComplianceEventListener` | Audit log + stock accounting |
| `TransactionCancelled` | `ComplianceEventListener` | Audit log + notification |
| `AlertCreated` | `ComplianceEventListener` | Case creation trigger |
| `CaseOpened` | `ComplianceEventListener` | Audit + notification |
| `CustomerRecordUpdated` | `ComplianceEventListener` | Audit + risk recalculation |
| `CustomerRelationAdded` | `CustomerRelationListener` | PEP/ownership concern check |
| `CustomerRelationRemoved` | `CustomerRelationListener` | Relation audit |
| `PendingCancellationRequested` | `ComplianceEventListener` | Supervisor notification |
| `RelatedPartyOwnershipConcern` | `ComplianceEventListener` | Escalation |
| `ReportGenerated` | `ComplianceEventListener` | Audit |
| `RiskScoreCalculated` | `ComplianceEventListener` | Snapshot |
| `RiskScoreUpdated` | `ComplianceEventListener` | Snapshot + notification |
| `SanctionsListUpdated` | `TriggerSanctionsRescreening` | Fire rescreening for all customers |

---

## Appendix D: Artisan Command Catalog (50+ commands)

| Command | Purpose |
|---|---|
| `report:msb2` | BNM MSB(2) daily report |
| `report:eod` | EOD reconciliation |
| `report:lmca` | Monthly LMCA |
| `report:qlvr` | Quarterly LVR |
| `report:position-limit` | Position limit report |
| `report:trial-balance` | Trial balance |
| `compliance:rescreen` | Monthly sanctions rescreening |
| `reservation:expire` | Expire stale stock reservations |
| `sanctions:import` | Import sanctions list |
| `sanctions:update` | Update sanctions lists from feeds |
| `sanctions:status` | Check sanctions import status |
| `alert:daily-summary` | Daily alert summary |
| `alert:dlq-transactions` | DLQ alert |
| `month-end:close` | Month-end close |
| `accounting:revaluation` | Monthly revaluation |
| `backup:*` | Automated backups |
| `queue:health-check` | Queue health |
| `clear-stuck-queues` | Clear stuck queue entries |
| `retry-failed-jobs` | Retry failed queue jobs |
| `recover-failed-transactions` | Recover failed transactions |
| `customer-risk-review` | Customer risk review |
| `user:create` | Create user |
| `setup:quick` / `setup:comprehensive` | Initial business setup |
| `ip-blocker` | IP blocking management |
| `audit:rotate` | Audit log rotation |
| `monitor:check` / `monitor:status` | System monitoring |
| `validate-routes` | Route consistency validation |
| `tests:run` | Test runner command |
| `reports:cleanup` / `reports:archive` | Report lifecycle |
| `notifications:digest` / `notifications:test` | Notification system |

---

## Appendix E: Test Organization

| Suite | Path | Tests |
|---|---|---|
| Feature | `tests/Feature/` | 740 tests / 1 907 asserts |
| Unit | `tests/Unit/` | 1 006 tests / 2 340 asserts |
| **Total** | | **1 746 tests / 4 247 asserts / 6 skip** |

**Key test files:**
- `TransactionWorkflowTest` — Transaction create/approve/cancel
- `TransactionAccountingVerificationTest` — 60 transactions (20/branch × 3 branches)
- `RealWorldTransactionWorkflowTest` — End-to-end scenarios
- `CounterHandoverTest` — Till custody transfer
- `EddWorkflowTest` — Enhanced Due Diligence
- `FiscalYearControllerTest` — Fiscal year lifecycle
- `JournalEntryWorkflowTest` — Journal draft → pending → posted
- `StrWorkflowTest` — STR creation and workflow
- `FinancialStatementControllerTest` — Trial balance, P&L, balance sheet, cash flow
- `ComplianceServiceTest` — CDD, sanctions, velocity, structuring
- `MathServiceTest` — BCMath precision
- `AuditServiceTest` — Hash chaining (`verifyChainIntegrity`)
- `CustomerBlindIndexTest` — Blind index
- `RiskRatingServiceTest` — Risk scoring
- `FinancialRatioServiceTest` — Liquidity, profitability, leverage ratios
- `CashFlowServiceTest` — Cash flow statement

---

## Appendix F: Deployment & Environment Notes

- **PHP:** 8.3.30 minimum.
- **Framework:** Laravel 12.
- **Database:** MySQL. All money columns `DECIMAL(28,8)`.
- **Redis:** Required for cache, sessions, queue. Local dev has no auth; **production must configure `requirepass` + `REDIS_PASSWORD`**.
- **Frontend:** Tailwind CSS v4 (CSS-first `@theme`, no `tailwind.config.js`). Alpine.js for interactivity. No Livewire.
- **Queue worker:** `php artisan horizon` (production), `php artisan queue:work` (dev).
- **Migrations:** 195 cumulative. Many add columns/fixes; full table creation done in early migrations.
- **Setup flow:** First boot runs `/setup/*` wizard (company info → admin user → currencies → exchange rates → initial stock → opening balance) before any transaction can occur. `setup.accessible` middleware gates this.
- **Local dev URL:** `http://localhost:8000` (standard Laravel serve). Production must enforce HTTPS via HSTS.

---

*End of Technical Specification.*
