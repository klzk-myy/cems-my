# Documentation Update Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update all CEMS-MY documentation to accurately reflect the implemented codebase

**Architecture:** Systematic review and update of 7 core documentation files to match actual implementation

**Tech Stack:** Markdown documentation, PHP 8.1+ codebase

---

## Context

### Discrepancies Found Between Code and Docs:

1. **Transaction Status** - 12-state machine in code vs 4 states in docs
2. **Transaction Flow** - Missing many states (Draft, Processing, Finalized, etc.)
3. **Navigation Structure** - Missing EDD Templates, STR Studio, Risk Dashboard, Compliance Reporting
4. **Stock Transfer Workflow** - Missing HQ approval stages
5. **Admin-only sections** - Not fully documented
6. **API Routes** - V1 routes incomplete in docs
7. **Middlewares** - New middlewares not documented
8. **Services** - Many services missing
9. **Controllers** - Missing many controllers
10. **Enums** - 27 enums not documented

### Key Source Files to Verify Against:

- `routes/web.php` - Main web routes
- `routes/api_v1.php` - API v1 routes
- `app/Enums/UserRole.php` - Role permissions
- `app/Enums/TransactionStatus.php` - 12 transaction states
- `app/Enums/CddLevel.php` - CDD level thresholds
- `app/Config/Navigation.php` - Navigation structure
- `database/seeders/UserSeeder.php` - Default credentials

---

## File Structure Mapping

| Documentation File | Purpose | Priority |
|-------------------|---------|----------|
| `docs/API.md` | Web routes and API endpoints | HIGH |
| `docs/USER_MANUAL.md` | End-user guide | HIGH |
| `docs/workflows.md` | Business workflow documentation | HIGH |
| `docs/DATABASE_SCHEMA.md` | Database schema reference | MEDIUM |
| `docs/DEPLOYMENT.md` | Production deployment guide | MEDIUM |
| `docs/TEST_SPECIFICATION.md` | Test suite documentation | LOW |
| `docs/workflows-diagrams.txt` | Visual workflow diagrams | LOW |

---

## Task 1: Update API.md - Web Routes Documentation

**Files:**
- Read: `routes/web.php`, `routes/api_v1.php`, `routes/auth.php`
- Read: `docs/API.md`
- Update: `docs/API.md`

**Context:** API.md documents web routes but is missing many routes like compliance sub-routes, stock transfers with HQ approval, and admin-only sections.

- [ ] **Step 1: Read current API.md routes section**

Run: Read first 500 lines of docs/API.md

- [ ] **Step 2: Verify all web routes from web.php**

Read `routes/web.php` lines 1-505 and cross-reference with API.md

- [ ] **Step 3: Update authentication section**

Verify `/login`, `/logout`, `/mfa/*` routes match actual routes

- [ ] **Step 4: Update transactions section**

Verify all transaction routes match:
- `/transactions` (GET/POST)
- `/transactions/create` (GET)
- `/transactions/{id}` (GET)
- `/transactions/{id}/approve` (POST, role:manager)
- `/transactions/{id}/cancel` (GET/POST, role:manager)
- `/transactions/{id}/confirm` (GET/POST, role:manager)
- `/transactions/batch-upload` (GET/POST, role:manager)

- [ ] **Step 5: Update compliance routes section**

Add missing routes:
- `/compliance/risk-dashboard/*`
- `/compliance/edd-templates/*`
- `/compliance/str-studio/*`
- `/compliance/reporting/*`

- [ ] **Step 6: Update accounting routes section**

Verify all accounting routes match:
- `/accounting/journal`
- `/accounting/ledger`
- `/accounting/trial-balance`
- `/accounting/profit-loss`
- `/accounting/balance-sheet`
- `/accounting/cash-flow`
- `/accounting/ratios`
- `/accounting/revaluation`
- `/accounting/reconciliation`
- `/accounting/budget`
- `/accounting/periods`
- `/accounting/fiscal-years`

- [ ] **Step 7: Update reports section**

Add all BNM report routes:
- `/reports/msb2`
- `/reports/lctr`
- `/reports/lmca`
- `/reports/quarterly-lvr`
- `/reports/position-limit`
- `/reports/history`

- [ ] **Step 8: Update user/branch management sections**

Verify admin-only routes documented:
- `/users/*` (Admin)
- `/branches/*` (Admin)
- `/data-breach-alerts/*` (Admin)

- [ ] **Step 9: Commit**

```bash
git add docs/API.md
git commit -m "docs: update API.md routes to match implementation"
```

---

## Task 2: Update USER_MANUAL.md - User Guide

**Files:**
- Read: `app/Config/Navigation.php`, `app/Enums/*.php`
- Read: `docs/USER_MANUAL.md`
- Update: `docs/USER_MANUAL.md`

**Context:** USER_MANUAL.md is the end-user guide but has incorrect transaction state counts and missing compliance sections.

- [ ] **Step 1: Read current USER_MANUAL.md**

Read lines 1-400 to understand current structure

- [ ] **Step 2: Verify navigation structure**

Read `app/Config/Navigation.php` and verify sidebar matches docs

- [ ] **Step 3: Update role permissions table**

Verify against `app/Enums/UserRole.php`:
- Teller permissions
- Manager permissions (includes Admin via `isManager()`)
- Compliance Officer permissions
- Admin permissions

- [ ] **Step 4: Update transaction workflow section**

Update transaction states from 4 to 12:
```
Draft → PendingApproval → Approved → Processing → Completed → Finalized
                                         ↓
Cancelled ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
                                         ↓
                                     Reversed
                                     ↓
                                   Failed/Rejected
```

- [ ] **Step 5: Update CDD level documentation**

Verify against `app/Enums/CddLevel.php`:
- Simplified: < RM 3,000
- Standard: RM 3,000 - RM 49,999
- Enhanced: ≥ RM 50,000 OR PEP OR Sanction OR High Risk

- [ ] **Step 6: Update counter management section**

Verify counter workflow matches `CounterController.php`:
- Open → Close → Handover
- Variance thresholds (RM 100 yellow, RM 500 red)

- [ ] **Step 7: Update stock transfer workflow**

Document complete workflow:
1. Create (Manager) → 2. Approve BM (Manager) → 3. Approve HQ (Admin) → 4. Dispatch (Admin) → 5. Receive (Admin) → 6. Complete (Admin)

- [ ] **Step 8: Update compliance section**

Add missing compliance sections:
- Risk Dashboard
- EDD Templates
- STR Studio
- Compliance Reporting

- [ ] **Step 9: Commit**

```bash
git add docs/USER_MANUAL.md
git commit -m "docs: update USER_MANUAL.md to match implementation"
```

---

## Task 3: Update workflows.md - Workflow Documentation

**Files:**
- Read: `app/Services/TransactionService.php`, `app/Services/ComplianceService.php`
- Read: `docs/workflows.md`
- Update: `docs/workflows.md`

**Context:** workflows.md documents business workflows but has outdated transaction states and missing monitors.

- [ ] **Step 1: Read current workflows.md**

Read first 300 lines to understand structure

- [ ] **Step 2: Update transaction workflow section**

Verify status transitions match `TransactionStatus` enum:
- Draft, PendingApproval, Approved, Processing, Completed, Finalized, Cancelled, Reversed, Failed, Rejected, Pending, OnHold

- [ ] **Step 3: Update CDD determination logic**

Verify against `app/Enums/CddLevel.php::determine()`:
```
amount >= 50000 OR PEP OR Sanction OR High Risk → Enhanced
amount >= 3000 → Standard
else → Simplified
```

- [ ] **Step 4: Update compliance monitoring section**

Document all monitors from `app/Services/Compliance/Monitors/`:
- VelocityMonitor
- StructuringMonitor
- SanctionsRescreeningMonitor
- StrDeadlineMonitor
- CustomerLocationAnomalyMonitor
- CurrencyFlowMonitor
- CounterfeitAlertMonitor

- [ ] **Step 5: Update stock transfer workflow**

Document workflow stages:
- Create → ApproveBM → ApproveHQ → Dispatch → Receive → Complete

- [ ] **Step 6: Update default users table**

Verify credentials match `database/seeders/UserSeeder.php`:
| Email | Password | Role |
|-------|----------|------|
| admin@cems.my | Admin@123456 | admin |
| teller1@cems.my | Teller@1234 | teller |
| manager1@cems.my | Manager@1234 | manager |
| compliance1@cems.my | Compliance@1234 | compliance_officer |

- [ ] **Step 7: Commit**

```bash
git add docs/workflows.md
git commit -m "docs: update workflows.md to match implementation"
```

---

## Task 4: Update DATABASE_SCHEMA.md

**Files:**
- Read: `database/migrations/*.php`
- Read: `docs/DATABASE_SCHEMA.md`
- Update: `docs/DATABASE_SCHEMA.md`

**Context:** DATABASE_SCHEMA.md documents database tables but may be missing tables added in recent migrations.

- [ ] **Step 1: Read DATABASE_SCHEMA.md**

Read first 500 lines to understand structure

- [ ] **Step 2: List all migration files**

Run: `ls database/migrations/*.php | head -50`

- [ ] **Step 3: Verify core tables documentation**

Check against migrations:
- users
- customers
- currencies
- exchange_rates
- transactions
- counters
- counter_sessions
- till_balances

- [ ] **Step 4: Verify accounting tables documentation**

Check against migrations:
- chart_of_accounts
- journal_entries
- journal_lines
- account_ledger
- accounting_periods
- fiscal_years
- budgets
- revaluation_entries

- [ ] **Step 5: Verify compliance tables documentation**

Check against migrations:
- sanction_lists
- sanction_entries
- flagged_transactions
- compliance_findings
- compliance_cases
- alerts
- str_reports
- enhanced_diligence_records
- aml_rules

- [ ] **Step 6: Add missing tables**

Add any tables present in migrations but missing from docs

- [ ] **Step 7: Commit**

```bash
git add docs/DATABASE_SCHEMA.md
git commit -m "docs: update DATABASE_SCHEMA.md to match migrations"
```

---

## Task 5: Verify and Update DEPLOYMENT.md

**Files:**
- Read: `config/cems.php`, `config/database.php`
- Read: `docs/DEPLOYMENT.md`
- Update: `docs/DEPLOYMENT.md`

**Context:** DEPLOYMENT.md is the production deployment guide but may have outdated config references.

- [ ] **Step 1: Read DEPLOYMENT.md**

Read first 500 lines

- [ ] **Step 2: Verify server requirements**

Check `composer.json` for PHP version requirements

- [ ] **Step 3: Verify config references**

Verify all config keys in deployment guide exist in `config/cems.php`

- [ ] **Step 4: Update any outdated config references**

Fix any discrepancies between deployment guide and actual config

- [ ] **Step 5: Commit**

```bash
git add docs/DEPLOYMENT.md
git commit -m "docs: verify and update DEPLOYMENT.md"
```

---

## Task 6: Update TEST_SPECIFICATION.md

**Files:**
- Read: `tests/` directory structure
- Read: `docs/TEST_SPECIFICATION.md`
- Update: `docs/TEST_SPECIFICATION.md`

**Context:** TEST_SPECIFICATION.md documents the test suite but test counts may be outdated.

- [ ] **Step 1: Read TEST_SPECIFICATION.md**

Read first 100 lines

- [ ] **Step 2: List all test files**

Run: `ls tests/Feature/*.php tests/Unit/*.php`

- [ ] **Step 3: Verify test organization**

Compare test file list against documentation

- [ ] **Step 4: Update test statistics**

Update total test count if different from 1,061

- [ ] **Step 5: Commit**

```bash
git add docs/TEST_SPECIFICATION.md
git commit -m "docs: update TEST_SPECIFICATION.md"
```

---

## Task 7: Final Verification and Commit

- [ ] **Step 1: Run grep for known wrong credentials**

Verify no `Admin@1234` remains in docs:
```bash
grep -r "Admin@1234" docs/
```

- [ ] **Step 2: Check for placeholder text**

```bash
grep -r "TBD\|TODO\|FIXME\|XXX" docs/*.md
```

- [ ] **Step 3: Verify all docs compile/render**

Check markdown syntax is valid

- [ ] **Step 4: Final commit**

```bash
git add docs/
git commit -m "docs: comprehensive documentation update to match implementation"
```

---

## Self-Review Checklist

After completing tasks:

1. **Spec coverage:** All major codebase components documented?
2. **Placeholder scan:** No "TBD", "TODO", or placeholder text?
3. **Type consistency:** Role names, enum values, route paths consistent across docs?
