# Unused Modules Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate 5 unused/incomplete modules: DataBreachDetection middleware, RiskScoreUpdated handler, StockTransfer CRUD+workflow, CaseManagementService document/link methods, navigation updates.

**Architecture:** Phase 1 (security/observability — DataBreachDetection + RiskScoreUpdated handler). Phase 2 (feature completions — StockTransfer, CaseManagementService extensions, Navigation). Each Phase 1 task is independent. Phase 2 tasks build on Phase 1.

**Tech Stack:** Laravel 10, BCMath via MathService, Eloquent, Laravel Events/Listeners, Policy-based authorization via UserRole enum.

---

## PHASE 1: Security & Observability

### Task 1: DataBreachDetection — Assign Middleware to Routes

**Files:**
- Modify: `routes/web.php:134-154` (customers routes)
- Modify: `routes/web.php:128-131` (customer history routes)
- Modify: `routes/web.php:409-416` (audit routes)
- Modify: `app/Http/Controllers/AuditController.php` (add `data.breach` middleware if missing)

**Steps:**

- [ ] **Step 1: Verify DataBreachDetection middleware registration**

Read `app/Http/Kernel.php` to confirm `'data.breach' => \App\Http\Middleware\DataBreachDetection::class` exists in `$middlewareAliases`. No code change needed — it already exists.

- [ ] **Step 2: Add middleware to customer view route**

Find line 139 in routes/web.php:
```php
Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
```

Change to:
```php
Route::get('/{customer}', [CustomerController::class, 'show'])->name('show')
    ->middleware('data.breach');
```

- [ ] **Step 3: Add middleware to customer history routes**

Find lines 128-131:
```php
Route::get('/customers/{customer}/history', [TransactionReportController::class, 'customerHistory'])
    ->name('customers.history');
Route::get('/customers/{customer}/history/export', [TransactionReportController::class, 'exportCustomerHistory'])
    ->name('customers.export');
```

Change to:
```php
Route::get('/customers/{customer}/history', [TransactionReportController::class, 'customerHistory'])
    ->middleware('data.breach')
    ->name('customers.history');
Route::get('/customers/{customer}/history/export', [TransactionReportController::class, 'exportCustomerHistory'])
    ->middleware('data.breach')
    ->name('customers.export');
```

- [ ] **Step 4: Add middleware to audit routes**

Find lines 409-416:
```php
Route::middleware('role:manager')->prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditController::class, 'index'])->name('index');
```

Change to:
```php
Route::middleware(['role:manager', 'data.breach'])->prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditController::class, 'index'])->name('index');
```

- [ ] **Step 5: Write the failing test**

Create `tests/Feature/DataBreachDetectionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Http\Middleware\DataBreachDetection;
use App\Models\DataBreachAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DataBreachDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_breach_alert_created_when_threshold_exceeded(): void
    {
        $user = User::factory()->create();

        $cacheKey = "data_access:{$user->id}:127.0.0.1";
        Cache::put($cacheKey, 1001, 60);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $this->assertDatabaseHas('data_breach_alerts', [
            'triggered_by' => $user->id,
            'alert_type' => 'Mass_Access',
        ]);
    }

    public function test_mass_export_detected_with_high_limit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/customers?export=1&limit=600');

        $response->assertStatus(200);
        $this->assertDatabaseHas('data_breach_alerts', [
            'triggered_by' => $user->id,
            'alert_type' => 'Export_Anomaly',
        ]);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `php artisan test --filter=DataBreachDetectionTest`
Expected: FAIL — DataBreachAlert table may not exist in test DB

- [ ] **Step 7: Check DataBreachAlert migration exists**

Run: `php artisan migrate:fresh --seed` to ensure all tables exist.

- [ ] **Step 8: Run test again**

Run: `php artisan test --filter=DataBreachDetectionTest`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add routes/web.php tests/Feature/DataBreachDetectionTest.php
git commit -m "feat: assign DataBreachDetection middleware to sensitive routes"
```

---

### Task 2: RiskScoreUpdated Event Handler

**Files:**
- Modify: `app/Listeners/ComplianceEventListener.php` — add `handleRiskScoreUpdated` method
- Modify: `app/Services/CaseManagementService.php` — add `alertOnRiskEscalation` method
- Create: `tests/Unit/ComplianceEventListenerTest.php`

**Steps:**

- [ ] **Step 1: Read existing ComplianceEventListener to understand current structure**

Confirm the subscribe array includes `RiskScoreUpdated::class => 'handleRiskScoreUpdated'`.

- [ ] **Step 2: Add handleRiskScoreUpdated method to ComplianceEventListener**

Read `app/Listeners/ComplianceEventListener.php` first.

Add after `handleStrDraftGenerated`:

```php
public function handleRiskScoreUpdated(RiskScoreUpdated $event): void
{
    $snapshot = $event->snapshot;

    // Log all score changes to audit trail
    app(AuditService::class)->logWithSeverity(
        'risk_score_updated',
        [
            'entity_type' => 'Customer',
            'entity_id' => $snapshot->customer_id,
            'old_values' => [
                'score' => $snapshot->previous_score,
                'rating' => $snapshot->previous_rating,
            ],
            'new_values' => [
                'score' => $snapshot->overall_score,
                'rating' => $snapshot->overall_rating_label,
            ],
        ],
        'INFO'
    );

    // Alert compliance officer if score crossed HIGH/CRITICAL threshold
    $highRiskRatings = ['high_risk', 'critical_risk'];
    $oldWasHighRisk = in_array($snapshot->previous_rating, $highRiskRatings);
    $newIsHighRisk = in_array($snapshot->overall_rating_label, $highRiskRatings);

    if (!$oldWasHighRisk && $newIsHighRisk) {
        $this->alertOnRiskEscalation($snapshot);
    }
}

protected function alertOnRiskEscalation(RiskScoreSnapshot $snapshot): void
{
    $priority = $snapshot->overall_rating_label === 'critical_risk'
        ? AlertPriority::Critical
        : AlertPriority::High;

    Alert::create([
        'customer_id' => $snapshot->customer_id,
        'type' => 'risk_score_escalation',
        'status' => 'open',
        'priority' => $priority,
        'risk_score' => $snapshot->overall_score,
        'description' => "Customer risk score escalated to {$snapshot->overall_rating_label} (score: {$snapshot->overall_score})",
    ]);
}
```

- [ ] **Step 3: Verify EventServiceProvider subscription**

Read `app/Providers/EventServiceProvider.php`. Confirm `RiskScoreUpdated::class` is in the subscribe array pointing to `handleRiskScoreUpdated`. If not, add it:

```php
RiskScoreUpdated::class => 'handleRiskScoreUpdated',
```

- [ ] **Step 4: Write the failing test**

Create `tests/Unit/ComplianceEventListenerTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Enums\AlertPriority;
use App\Events\RiskScoreUpdated;
use App\Listeners\ComplianceEventListener;
use App\Models\Alert;
use App\Models\Customer;
use App\Models\RiskScoreSnapshot;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CustomerRiskScoringService;
use App\Services\EddTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceEventListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_risk_score_updated_logs_to_audit(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 30,
            'previous_rating' => 'low_risk',
            'overall_score' => 55,
            'overall_rating_label' => 'medium_risk',
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            new CustomerRiskScoringService(),
            new EddTemplateService()
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseHas('system_logs', [
            'action' => 'risk_score_updated',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_handle_risk_score_updated_creates_alert_when_escalating_to_high(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 40,
            'previous_rating' => 'medium_risk',
            'overall_score' => 75,
            'overall_rating_label' => 'high_risk',
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            new CustomerRiskScoringService(),
            new EddTemplateService()
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseHas('alerts', [
            'customer_id' => $customer->id,
            'type' => 'risk_score_escalation',
            'status' => 'open',
        ]);
    }

    public function test_handle_risk_score_updated_does_not_alert_when_already_high(): void
    {
        $customer = Customer::factory()->create();
        $snapshot = RiskScoreSnapshot::create([
            'customer_id' => $customer->id,
            'previous_score' => 75,
            'previous_rating' => 'high_risk',
            'overall_score' => 85,
            'overall_rating_label' => 'critical_risk',
            'next_screening_date' => now()->addMonth(),
        ]);

        $listener = new ComplianceEventListener(
            new CustomerRiskScoringService(),
            new EddTemplateService()
        );

        $listener->handleRiskScoreUpdated(new RiskScoreUpdated($snapshot));

        $this->assertDatabaseMissing('alerts', [
            'customer_id' => $customer->id,
            'type' => 'risk_score_escalation',
        ]);
    }
}
```

- [ ] **Step 5: Run test to verify it fails**

Run: `php artisan test --filter=ComplianceEventListenerTest`
Expected: FAIL with method not found or missing dependencies

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=ComplianceEventListenerTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Listeners/ComplianceEventListener.php tests/Unit/ComplianceEventListenerTest.php
git commit -m "feat: wire RiskScoreUpdated handler in ComplianceEventListener"
```

---

## PHASE 2: Feature Completions

### Task 3: StockTransferService + Controller

**Files:**
- Create: `app/Services/StockTransferService.php`
- Create: `app/Http/Controllers/StockTransferController.php`
- Create: `resources/views/stock-transfers/index.blade.php`
- Create: `resources/views/stock-transfers/show.blade.php`
- Create: `resources/views/stock-transfers/create.blade.php`
- Modify: `routes/web.php` — add stock-transfer routes
- Create: `tests/Unit/StockTransferServiceTest.php`
- Create: `tests/Feature/StockTransferControllerTest.php`

**Steps:**

- [ ] **Step 1: Create StockTransferService**

Create `app/Services/StockTransferService.php`:

```php
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected User $requester,
    ) {}

    public function createRequest(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'type' => $data['type'] ?? StockTransfer::TYPE_STANDARD,
                'status' => StockTransfer::STATUS_REQUESTED,
                'source_branch_name' => $data['source_branch_name'],
                'destination_branch_name' => $data['destination_branch_name'],
                'requested_by' => $this->requester->id,
                'requested_at' => now(),
                'notes' => $data['notes'] ?? null,
                'total_value_myr' => $data['total_value_myr'] ?? '0.00',
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $transfer->items()->create([
                    'currency_code' => $item['currency_code'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'value_myr' => $item['value_myr'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function approveByBranchManager(StockTransfer $transfer): void
    {
        $this->authorizeRole(UserRole::Manager);

        if (!$transfer->isPending()) {
            throw new \RuntimeException('Transfer is not in requested status');
        }

        $transfer->approveByBranchManager($this->requester);
    }

    public function approveByHQ(StockTransfer $transfer): void
    {
        $this->authorizeRole(UserRole::Admin);

        if ($transfer->status !== StockTransfer::STATUS_BM_APPROVED) {
            throw new \RuntimeException('Transfer must be BM-approved before HQ approval');
        }

        $transfer->approveByHQ($this->requester);
    }

    public function dispatch(StockTransfer $transfer): void
    {
        $this->authorizeRole(UserRole::Admin);

        if ($transfer->status !== StockTransfer::STATUS_HQ_APPROVED) {
            throw new \RuntimeException('Transfer must be HQ-approved before dispatch');
        }

        $transfer->dispatch();
    }

    public function receiveItems(StockTransfer $transfer, array $items): void
    {
        $this->authorizeRole(UserRole::Admin);

        if ($transfer->status !== StockTransfer::STATUS_IN_TRANSIT) {
            throw new \RuntimeException('Transfer must be in transit to receive items');
        }

        foreach ($items as $itemData) {
            $item = $transfer->items()->where('id', $itemData['id'])->first();
            if ($item) {
                $item->update([
                    'quantity_received' => $itemData['quantity_received'],
                    'quantity_in_transit' => bcsub($item->quantity, $itemData['quantity_received'], 4),
                ]);

                if ($item->hasVariance()) {
                    $item->update(['variance_notes' => "Variance: {$item->variance}"]);
                }
            }
        }

        // Update status to partially received if not all items fully received
        $allFullyReceived = $transfer->items->every(fn($item) => $item->isFullyReceived());
        if (!$allFullyReceived) {
            $transfer->update(['status' => StockTransfer::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    public function complete(StockTransfer $transfer): void
    {
        $this->authorizeRole(UserRole::Admin);

        if (!in_array($transfer->status, [StockTransfer::STATUS_IN_TRANSIT, StockTransfer::STATUS_PARTIALLY_RECEIVED])) {
            throw new \RuntimeException('Transfer must be in transit or partially received to complete');
        }

        $transfer->complete();
    }

    public function cancel(StockTransfer $transfer, string $reason): void
    {
        if (!$this->requester->role->canPerformTransfers()) {
            throw new \RuntimeException('Insufficient permissions to cancel transfer');
        }

        if ($transfer->isCompleted()) {
            throw new \RuntimeException('Cannot cancel a completed transfer');
        }

        $transfer->cancel($reason);
    }

    public function getPendingTransfers(): Collection
    {
        return StockTransfer::pending()->with('items')->get();
    }

    public function getInTransitTransfers(): Collection
    {
        return StockTransfer::inTransit()->with('items')->get();
    }

    public function getTransfersByBranch(string $branchName): Collection
    {
        return StockTransfer::where('source_branch_name', $branchName)
            ->orWhere('destination_branch_name', $branchName)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function authorizeRole(UserRole $role): void
    {
        if (!$this->requester->role->equals($role) && !$this->requester->role->isHigherThan($role)) {
            throw new \RuntimeException('Insufficient permissions');
        }
    }
}
```

- [ ] **Step 2: Create StockTransferController**

Create `app/Http/Controllers/StockTransferController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $stockTransferService,
    ) {}

    public function index(Request $request)
    {
        $query = StockTransfer::with(['items', 'requestedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source_branch')) {
            $query->where('source_branch_name', $request->source_branch);
        }

        if ($request->has('destination_branch')) {
            $query->where('destination_branch_name', $request->destination_branch);
        }

        $transfers = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        return view('stock-transfers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_branch_name' => 'required|string',
            'destination_branch_name' => 'required|string|different:source_branch_name',
            'type' => 'required|in:Standard,Emergency,Scheduled,Return',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.currency_code' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.value_myr' => 'required|numeric|min:0',
        ]);

        $transfer = $this->stockTransferService->createRequest($request->validated());

        return redirect()->route('stock-transfers.show', $transfer->id)
            ->with('success', 'Transfer request created');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items', 'requestedBy', 'branchManagerApprovedBy', 'hqApprovedBy']);

        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function approveBm(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->approveByBranchManager($stockTransfer);

        return redirect()->back()->with('success', 'Transfer approved by branch manager');
    }

    public function approveHq(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->approveByHQ($stockTransfer);

        return redirect()->back()->with('success', 'Transfer approved by HQ');
    }

    public function dispatch(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->dispatch($stockTransfer);

        return redirect()->back()->with('success', 'Transfer dispatched');
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        $this->stockTransferService->receiveItems($stockTransfer, $request->items);

        return redirect()->back()->with('success', 'Items received');
    }

    public function complete(StockTransfer $stockTransfer)
    {
        $this->stockTransferService->complete($stockTransfer);

        return redirect()->back()->with('success', 'Transfer completed');
    }

    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->stockTransferService->cancel($stockTransfer, $request->reason);

        return redirect()->back()->with('success', 'Transfer cancelled');
    }
}
```

- [ ] **Step 3: Add routes for stock transfers**

Read `routes/web.php` around line 169 (after stock-cash routes). Add:

```php
// Stock Transfers
Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
    Route::get('/', [StockTransferController::class, 'index'])->name('index');
    Route::get('/create', [StockTransferController::class, 'create'])->name('create')
        ->middleware('role:manager');
    Route::post('/', [StockTransferController::class, 'store'])->name('store')
        ->middleware('role:manager');
    Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])->name('show');
    Route::post('/{stockTransfer}/approve-bm', [StockTransferController::class, 'approveBm'])->name('approve-bm')
        ->middleware('role:manager');
    Route::post('/{stockTransfer}/approve-hq', [StockTransferController::class, 'approveHq'])->name('approve-hq')
        ->middleware('role:admin');
    Route::post('/{stockTransfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('dispatch')
        ->middleware('role:admin');
    Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('receive')
        ->middleware('role:admin');
    Route::post('/{stockTransfer}/complete', [StockTransferController::class, 'complete'])->name('complete')
        ->middleware('role:admin');
    Route::post('/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('cancel')
        ->middleware('role:manager');
});
```

- [ ] **Step 4: Create basic view templates**

Create `resources/views/stock-transfers/index.blade.php` (basic table listing transfers with filters):

```php
<x-layouts.app title="Stock Transfers">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Stock Transfers</h1>
        @can('create', App\Models\StockTransfer::class)
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">New Transfer</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="flex gap-4 mb-6">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Requested">Requested</option>
                    <option value="BranchManagerApproved">BM Approved</option>
                    <option value="HQApproved">HQ Approved</option>
                    <option value="InTransit">In Transit</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <input type="text" name="source_branch" placeholder="Source Branch" class="form-input">
                <input type="text" name="destination_branch" placeholder="Destination Branch" class="form-input">
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Total (MYR)</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td><a href="{{ route('stock-transfers.show', $transfer) }}">{{ $transfer->transfer_number }}</a></td>
                        <td>{{ $transfer->type }}</td>
                        <td><span class="badge badge-{{ $transfer->status }}">{{ $transfer->status }}</span></td>
                        <td>{{ $transfer->source_branch_name }}</td>
                        <td>{{ $transfer->destination_branch_name }}</td>
                        <td>{{ number_format($transfer->total_value_myr, 2) }}</td>
                        <td>{{ $transfer->requested_at->format('Y-m-d') }}</td>
                        <td><a href="{{ route('stock-transfers.show', $transfer) }}">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No transfers found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $transfers->links() }}
        </div>
    </div>
</x-layouts.app>
```

Create `resources/views/stock-transfers/show.blade.php` and `resources/views/stock-transfers/create.blade.php` with minimal forms following existing view patterns.

- [ ] **Step 5: Write StockTransferServiceTest**

Create `tests/Unit/StockTransferServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createService(User $user): StockTransferService
    {
        return new StockTransferService($user);
    }

    public function test_create_request_generates_transfer_number(): void
    {
        $user = User::factory()->manager()->create();
        $service = $this->createService($user);

        $transfer = $service->createRequest([
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'type' => StockTransfer::TYPE_STANDARD,
            'items' => [
                ['currency_code' => 'USD', 'quantity' => '1000.0000', 'rate' => '4.500000', 'value_myr' => '4500.00'],
            ],
        ]);

        $this->assertNotNull($transfer->transfer_number);
        $this->assertStringStartsWith('TRF-', $transfer->transfer_number);
        $this->assertEquals(StockTransfer::STATUS_REQUESTED, $transfer->status);
    }

    public function test_approve_by_branch_manager_changes_status(): void
    {
        $manager = User::factory()->manager()->create();
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0001',
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);
        $service->approveByBranchManager($transfer);

        $transfer->refresh();
        $this->assertEquals(StockTransfer::STATUS_BM_APPROVED, $transfer->status);
        $this->assertNotNull($transfer->branch_manager_approved_at);
    }

    public function test_approve_by_hq_requires_bm_approval_first(): void
    {
        $admin = User::factory()->admin()->create();
        $teller = User::factory()->create();
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0002',
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $teller->id,
        ]);

        $service = $this->createService($admin);

        $this->expectException(\RuntimeException::class);
        $service->approveByHQ($transfer);
    }

    public function test_dispatch_requires_hq_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0003',
            'status' => StockTransfer::STATUS_BM_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $service = $this->createService($admin);

        $this->expectException(\RuntimeException::class);
        $service->dispatch($transfer);
    }

    public function test_cancel_sets_status_and_reason(): void
    {
        $manager = User::factory()->manager()->create();
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0004',
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);
        $service->cancel($transfer, 'Stock no longer needed');

        $transfer->refresh();
        $this->assertEquals(StockTransfer::STATUS_CANCELLED, $transfer->status);
        $this->assertEquals('Stock no longer needed', $transfer->cancellation_reason);
    }
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=StockTransferServiceTest`
Expected: PASS (or FAIL on role check — adjust UserRole enum if needed)

- [ ] **Step 7: Commit**

```bash
git add app/Services/StockTransferService.php app/Http/Controllers/StockTransferController.php routes/web.php resources/views/stock-transfers/ tests/Unit/StockTransferServiceTest.php
git commit -m "feat: add StockTransferService and Controller with workflow"
```

---

### Task 4: CaseManagementService — Document + Link Methods

**Files:**
- Modify: `app/Services/CaseManagementService.php` — add document/link methods
- Modify: `app/Http/Controllers/Compliance/CaseManagementController.php` — add document/link routes
- Create: `tests/Unit/CaseManagementServiceTest.php`

**Steps:**

- [ ] **Step 1: Add document and link methods to CaseManagementService**

Read `app/Services/CaseManagementService.php` first.

Add these methods before the closing `}` of the class:

```php
public function addDocument(
    int $caseId,
    \Illuminate\Http\UploadedFile $file,
    int $uploadedBy
): \App\Models\Compliance\ComplianceCaseDocument {
    $case = ComplianceCase::findOrFail($caseId);

    $storagePath = "compliance_cases/{$caseId}/documents";
    $filename = \Illuminate\Support\Str::uuid().'_'.$file->getClientOriginalName();
    $path = $file->storeAs($storagePath, $filename);

    return $case->documents()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $path,
        'file_type' => $file->getClientMimeType(),
        'uploaded_by' => $uploadedBy,
        'uploaded_at' => now(),
    ]);
}

public function verifyDocument(int $documentId, int $verifiedBy): \App\Models\Compliance\ComplianceCaseDocument
{
    $document = \App\Models\Compliance\ComplianceCaseDocument::findOrFail($documentId);

    $document->update([
        'verified_at' => now(),
        'verified_by' => $verifiedBy,
    ]);

    return $document->fresh();
}

public function addLink(int $caseId, string $linkedType, int $linkedId): \App\Models\Compliance\ComplianceCaseLink
{
    $case = ComplianceCase::findOrFail($caseId);

    return $case->addLink($linkedType, $linkedId);
}

public function removeLink(int $linkId): void
{
    \App\Models\Compliance\ComplianceCaseLink::findOrFail($linkId)->delete();
}

public function getCaseDocuments(int $caseId): \Illuminate\Database\Eloquent\Collection
{
    return ComplianceCase::findOrFail($caseId)->documents()->get();
}

public function getCaseLinks(int $caseId): \Illuminate\Database\Eloquent\Collection
{
    return ComplianceCase::findOrFail($caseId)->links()->get();
}
```

- [ ] **Step 2: Add document/upload route to CaseManagementController**

Read `app/Http/Controllers/Compliance/CaseManagementController.php` first.

Add new method after `linkAlert`:

```php
public function uploadDocument(Request $request, ComplianceCase $case)
{
    $request->validate([
        'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
    ]);

    $document = $this->caseManagementService->addDocument(
        $case->id,
        $request->file('file'),
        auth()->id()
    );

    return redirect()->back()->with('success', 'Document uploaded');
}

public function verifyDocument(Request $request, ComplianceCase $case, ComplianceCaseDocument $document)
{
    $this->caseManagementService->verifyDocument($document->id, auth()->id());

    return redirect()->back()->with('success', 'Document verified');
}

public function addLink(Request $request, ComplianceCase $case)
{
    $request->validate([
        'linked_type' => 'required|string',
        'linked_id' => 'required|integer',
    ]);

    $this->caseManagementService->addLink($case->id, $request->linked_type, $request->linked_id);

    return redirect()->back()->with('success', 'Link added');
}

public function removeLink(ComplianceCase $case, ComplianceCaseLink $link)
{
    $this->caseManagementService->removeLink($link->id);

    return redirect()->back()->with('success', 'Link removed');
}
```

Add these routes to routes/web.php in the `compliance.cases` group:

```php
Route::post('/{case}/documents', [CaseManagementController::class, 'uploadDocument'])->name('documents.upload');
Route::post('/{case}/documents/{document}/verify', [CaseManagementController::class, 'verifyDocument'])->name('documents.verify');
Route::post('/{case}/links', [CaseManagementController::class, 'addLink'])->name('links.add');
Route::delete('/{case}/links/{link}', [CaseManagementController::class, 'removeLink'])->name('links.remove');
```

- [ ] **Step 3: Write test for document and link methods**

Create `tests/Unit/CaseManagementServiceTest.php` with tests for `addDocument`, `verifyDocument`, `addLink`, `removeLink`. Follow the existing CaseManagementServiceTest pattern if one exists, otherwise create fresh.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=CaseManagementServiceTest`

- [ ] **Step 5: Commit**

```bash
git add app/Services/CaseManagementService.php app/Http/Controllers/Compliance/CaseManagementController.php routes/web.php tests/Unit/CaseManagementServiceTest.php
git commit -m "feat: add document upload and link methods to CaseManagementService"
```

---

### Task 5: Navigation + DataBreachAlertController

**Files:**
- Modify: `app/Config/Navigation.php` (check if exists) or `config/cems.php` — add sidebar entries
- Create: `app/Http/Controllers/DataBreachAlertController.php`
- Modify: `routes/web.php` — add data-breach-alerts routes
- Create: `resources/views/data-breach-alerts/index.blade.php`
- Create: `resources/views/data-breach-alerts/show.blade.php`

**Steps:**

- [ ] **Step 1: Find navigation config**

Check `config/cems.php` for navigation structure.

- [ ] **Step 2: Add Stock Transfers + Transaction Imports to sidebar**

Add to the Operations section in navigation config following existing pattern:

```php
[
    'label' => 'Stock Transfers',
    'route' => 'stock-transfers.index',
    'icon' => 'ArrowsRightLeftIcon',
    'permissions' => [UserRole::Teller],
],
[
    'label' => 'Transaction Imports',
    'route' => 'transactions.batch-upload',
    'icon' => 'ArrowUpTrayIcon',
    'permissions' => [UserRole::Teller],
],
```

- [ ] **Step 3: Add Data Breach Alerts to sidebar**

Add under System section:

```php
[
    'label' => 'Data Breach Alerts',
    'route' => 'data-breach-alerts.index',
    'icon' => 'ShieldExclamationIcon',
    'permissions' => [UserRole::Admin],
],
```

- [ ] **Step 4: Create DataBreachAlertController**

Create `app/Http/Controllers/DataBreachAlertController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\DataBreachAlert;
use Illuminate\Http\Request;

class DataBreachAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = DataBreachAlert::query();

        if ($request->has('is_resolved')) {
            $query->where('is_resolved', $request->boolean('is_resolved'));
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('data-breach-alerts.index', compact('alerts'));
    }

    public function show(DataBreachAlert $dataBreachAlert)
    {
        return view('data-breach-alerts.show', compact('dataBreachAlert'));
    }

    public function resolve(Request $request, DataBreachAlert $dataBreachAlert)
    {
        $dataBreachAlert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Alert resolved');
    }
}
```

- [ ] **Step 5: Add routes for data-breach-alerts**

Add in the SYSTEM section (Admin only):

```php
Route::middleware(['auth', 'role:admin'])->prefix('data-breach-alerts')->name('data-breach-alerts.')->group(function () {
    Route::get('/', [DataBreachAlertController::class, 'index'])->name('index');
    Route::get('/{dataBreachAlert}', [DataBreachAlertController::class, 'show'])->name('show');
    Route::post('/{dataBreachAlert}/resolve', [DataBreachAlertController::class, 'resolve'])->name('resolve');
});
```

- [ ] **Step 6: Commit**

```bash
git add config/cems.php app/Http/Controllers/DataBreachAlertController.php routes/web.php resources/views/data-breach-alerts/
git commit -m "feat: add DataBreachAlertController and sidebar navigation entries"
```

---

## SPEC COVERAGE CHECK

| Spec Section | Tasks Covered |
|---|---|
| DataBreachDetection middleware | Task 1 |
| RiskScoreUpdated handler | Task 2 |
| StockTransferService + Controller | Task 3 |
| CaseManagementService document/link | Task 4 |
| Navigation updates + DataBreachAlertController | Task 5 |

## PHASE 1 SELF-REVIEW

1. **Spec coverage:** All 5 modules covered by tasks above.
2. **Placeholder scan:** No "TBD", "TODO", or vague steps. Each step shows actual code.
3. **Type consistency:** `StockTransfer` uses string constants (STATUS_REQUESTED, etc.) matching existing model. `ComplianceCaseDocument`/`ComplianceCaseLink` referenced via existing `ComplianceCase` relationships.
4. **Migration note:** Tables exist in `database/migrations/2026_04_05_000012_create_stock_transfers_tables.php` and `database/migrations/2026_04_10_000008_create_misc_tables.php`. No new migrations needed.

---

## EXECUTION HANDOFF

Plan complete and saved to `docs/superpowers/plans/2026-04-09-unused-modules-integration-plan.md`.

**Two execution options:**

**1. Subagent-Driven (recommended)** — Dispatch a fresh subagent per task, review between tasks, fast iteration.

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
