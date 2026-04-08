# Unused Modules Integration Design

**Date:** 2026-04-09
**Status:** Draft

---

## Overview

This spec covers integration of five unused/incomplete modules into the CEMS-MY codebase:

1. **DataBreachDetection** middleware — assign to sensitive routes
2. **RiskScoreUpdated** event — wire handler in `ComplianceEventListener`
3. **StockTransfer + StockTransferItem** — create service + controller + routes
4. **ComplianceCaseDocument + ComplianceCaseLink** — extend `CaseManagementService`
5. **Navigation updates** — sidebar links for new sections

---

## Phase 1: Security & Observability

### 1. DataBreachDetection Middleware

**File:** `app/Http/Middleware/DataBreachDetection.php`

**Current state:** Registered in `Http\Kernel.php` as `$middlewareAliases` but not assigned to any route.

**Changes:**

Add to each sensitive route's middleware array:

```php
Route::get('/customers/{customer}', [CustomerController::class, 'show'])
    ->middleware(['auth', 'role:teller', 'data.breach']);
```

**Routes to protect with `data.breach` middleware:**

| Route | Reason |
|-------|--------|
| `GET /customers/{customer}` | Customer PII data view |
| `GET /customers/export` | Bulk PII export |
| `GET /transactions` (with `limit > 500`) | Mass transaction query |
| `GET /users/{user}` | User data view |
| `GET /audit` | Audit log bulk access |

**Middleware alias already registered in `Http\Kernel.php`:**
```php
'data.breach' => \App\Http\Middleware\DataBreachDetection::class,
```

---

### 2. RiskScoreUpdated Event Handler

**File:** `app/Listeners/ComplianceEventListener.php`

**Current state:** Event is dispatched by `CustomerRiskScoringService::calculateAndSnapshot()` but `ComplianceEventListener` has no `handleRiskScoreUpdated` method.

**Changes:**

Add new method to `ComplianceEventListener`:

```php
public function handleRiskScoreUpdated(RiskScoreUpdated $event): void
{
    $snapshot = $event->snapshot;
    $customer = $snapshot->customer;

    // Log all score changes to audit trail
    app(AuditService::class)->logSystemEvent([
        'event_type' => 'risk_score_updated',
        'customer_id' => $customer->id,
        'old_score' => $snapshot->previous_score,
        'new_score' => $snapshot->overall_score,
        'old_rating' => $snapshot->previous_rating,
        'new_rating' => $snapshot->overall_rating_label,
    ]);

    // Alert compliance officer if score crossed HIGH/CRITICAL threshold
    $highRiskRatings = ['high_risk', 'critical_risk'];
    $oldWasHighRisk = in_array($snapshot->previous_rating, $highRiskRatings);
    $newIsHighRisk = in_array($snapshot->overall_rating_label, $highRiskRatings);

    if (!$oldWasHighRisk && $newIsHighRisk) {
        $this->alertComplianceOfficer($customer, $snapshot);
    }
}
```

**Register the listener event binding** (already registered in `EventServiceProvider`, just needs the handler method):

```php
// In EventServiceProvider $subscribe:
RiskScoreUpdated::class => 'handleRiskScoreUpdated',
```

**Alert behavior:**
- Create an `Alert` with type `RiskScoreChange`, priority based on new rating
- Assign to compliance officer or case officer
- Use existing notification channels

---

## Phase 2: Feature Completions

### 3. StockTransfer Service + Controller

#### 3.1 StockTransferService

**File:** `app/Services/StockTransferService.php`

```php
class StockTransferService
{
    public function __construct(
        protected User $requester,
    ) {}

    public function createRequest(array $data): StockTransfer;
    public function approveByBranchManager(StockTransfer $transfer): void;
    public function approveByHQ(StockTransfer $transfer): void;
    public function dispatch(StockTransfer $transfer): void;
    public function receiveItems(StockTransfer $transfer, array $items): void;
    public function complete(StockTransfer $transfer): void;
    public function cancel(StockTransfer $transfer, string $reason): void;

    // Query scopes
    public function getPendingTransfers(): Collection;
    public function getInTransitTransfers(): Collection;
    public function getTransfersByBranch(string $branchName): Collection;
}
```

**Workflow states:**
```
Requested → BranchManagerApproved → HQApproved → InTransit → PartiallyReceived → Completed
                                              ↘Rejected↗
                         → Cancelled (any stage before Completed)
```

**Approval rules:**
- `approveByBranchManager`: Requires `UserRole::Manager` or higher
- `approveByHQ`: Requires `UserRole::Admin` only
- `dispatch`: Admin only
- `complete`: Admin only
- `cancel`: Manager+ with reason required

#### 3.2 StockTransferController

**File:** `app/Http/Controllers/StockTransferController.php`

**Routes:**

| Method | URI | Role | Description |
|--------|-----|------|-------------|
| GET | `/stock-transfers` | Teller+ | List transfers (filterable by status, branch) |
| POST | `/stock-transfers` | Manager+ | Create new transfer request |
| GET | `/stock-transfers/{id}` | Teller+ | View transfer details with items |
| POST | `/stock-transfers/{id}/approve-bm` | Manager+ | BM approval |
| POST | `/stock-transfers/{id}/approve-hq` | Admin | HQ approval |
| POST | `/stock-transfers/{id}/dispatch` | Admin | Mark as dispatched |
| POST | `/stock-transfers/{id}/receive` | Admin | Record received quantities |
| POST | `/stock-transfers/{id}/complete` | Admin | Mark as completed |
| POST | `/stock-transfers/{id}/cancel` | Manager+ | Cancel with reason |

**Views:**
- `stock-transfers.index` — table with filters (status, source branch, dest branch, date range)
- `stock-transfers.show` — detail view with items, timeline, actions
- `stock-transfers.create` — form for requesting transfer

#### 3.3 Navigation

Add to sidebar in `config/Navigation.php` under **Operations**:

```php
[
    'label' => 'Stock Transfers',
    'route' => 'stock-transfers.index',
    'icon' => 'ArrowsRightLeftIcon',
    'permissions' => [UserRole::Teller],
],
```

#### 3.4 StockTransferItem variance tracking

When receiving items, compare `quantity_received` vs `quantity`. If mismatch:
- Record in `variance_notes`
- Alert manager if variance > 5%

---

### 4. CaseManagementService — Document + Link Methods

**File:** `app/Services/CaseManagementService.php`

**New methods:**

```php
public function addDocument(
    int $caseId,
    UploadedFile $file,
    int $uploadedBy
): ComplianceCaseDocument;

public function verifyDocument(int $documentId, int $verifiedBy): ComplianceCaseDocument;

public function addLink(int $caseId, string $linkedType, int $linkedId): ComplianceCaseLink;

public function removeLink(int $linkId): void;

public function getCaseDocuments(int $caseId): Collection;

public function getCaseLinks(int $caseId): Collection;
```

**File storage:** Store in `storage/app/compliance_cases/{case_id}/documents/`

**File naming:** `{original_filename}` prefixed with `{uuid}_` for uniqueness

**Verify document:** Sets `verified_at` and `verified_by`

---

### 5. Navigation Updates

#### 5.1 Stock Transfers link

Already described in 3.3 above.

#### 5.2 Transaction Imports sidebar link

The batch upload page exists at `GET /transactions/batch-upload` but may not have a sidebar entry. Add under **Operations**:

```php
[
    'label' => 'Transaction Imports',
    'route' => 'transactions.batch-upload',
    'icon' => 'ArrowUpTrayIcon',
    'permissions' => [UserRole::Teller],
],
```

#### 5.3 Data Breach Alerts

Create `DataBreachAlertController` at `app/Http/Controllers/DataBreachAlertController.php`:

**Routes:**

| Method | URI | Role | Description |
|--------|-----|------|-------------|
| GET | `/data-breach-alerts` | Admin | List all alerts |
| GET | `/data-breach-alerts/{id}` | Admin | View alert details |
| POST | `/data-breach-alerts/{id}/resolve` | Admin | Mark as resolved |

**Sidebar entry** under **System** (Admin only):

```php
[
    'label' => 'Data Breach Alerts',
    'route' => 'data-breach-alerts.index',
    'icon' => 'ShieldExclamationIcon',
    'permissions' => [UserRole::Admin],
],
```

---

## Testing Requirements

For each new/changed component:

### Phase 1
- `DataBreachDetectionTest`: Middleware threshold detection, mass export detection, alert creation
- `ComplianceEventListenerTest`: RiskScoreUpdated handler — audit log + compliance officer alert

### Phase 2
- `StockTransferServiceTest`: Workflow state transitions, approval permissions, variance tracking
- `StockTransferControllerTest`: Route access by role, workflow actions
- `CaseManagementServiceTest`: Document upload, link creation, verification

---

## Files to Create/Modify

### Phase 1
| File | Action |
|------|--------|
| `app/Listeners/ComplianceEventListener.php` | Add `handleRiskScoreUpdated` method |
| `routes/web.php` | Add `data.breach` middleware to sensitive routes |
| `tests/Unit/ComplianceEventListenerTest.php` | New test file |

### Phase 2
| File | Action |
|------|--------|
| `app/Services/StockTransferService.php` | New service |
| `app/Http/Controllers/StockTransferController.php` | New controller |
| `routes/web.php` | Add stock-transfer routes |
| `config/Navigation.php` | Add sidebar entries |
| `app/Services/CaseManagementService.php` | Add document/link methods |
| `app/Http/Controllers/DataBreachAlertController.php` | New controller |
| `app/Http/Controllers/Compliance/ComplianceCaseController.php` | Add document/link API routes |
| `database/migrations/*` | No changes (tables already exist) |
| `tests/Unit/StockTransferServiceTest.php` | New test file |
| `tests/Feature/StockTransferControllerTest.php` | New test file |

---

## Implementation Order

1. **DataBreachDetection middleware** — attach to routes (low risk, isolated)
2. **RiskScoreUpdated handler** — add to `ComplianceEventListener` (isolated)
3. **StockTransferService + Controller** — full CRUD + workflow (largest piece)
4. **CaseManagementService extensions** — document/link methods (small addition)
5. **Navigation + DataBreachAlertController** — sidebar + admin view (straightforward)

---

## Notes

- All monetary values use `MathService` / BCMath
- Role checks via enum methods (`UserRole::Manager->canApproveStockTransfer()`)
- No magic strings — use existing status enums where applicable
- StockTransfer uses string constants (not enum) for status — refactor to enum if one exists
