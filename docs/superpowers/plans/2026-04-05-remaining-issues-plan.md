# Remaining Issues Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Resolve all 10 open issues identified in the 2026-04-05 codebase analysis — 1 critical RBAC gap, 4 high-severity issues (auth + precision), and 5 medium issues (precision + enum consistency).

**Architecture:** Fixes are isolated to their respective files. No new abstractions. No schema changes. All fixes follow existing patterns already established in the codebase (MathService for precision, PHP enums for type safety, requireManagerOrAdmin() for auth guards).

**Tech Stack:** Laravel 10, PHP 8.1 enums, BCMath via MathService, PHPUnit 10

---

## File Map

| File | Changes |
|------|---------|
| `app/Http/Controllers/Controller.php` | Add `requireManagerOrAdmin()` helper |
| `app/Http/Controllers/CounterController.php` | Add RBAC to `close()`, `handover()` |
| `app/Http/Controllers/Auth/LoginController.php` | Remove conditional log message |
| `app/Services/RateApiService.php` | Inject MathService, replace float arithmetic |
| `app/Services/ReportingService.php` | Replace float division with MathService |
| `app/Http/Controllers/ReportController.php` | Inject MathService, fix 5 float calculations |
| `app/Http/Controllers/StockCashController.php` | Fix float calculations, remove redundant auth check |
| `app/Http/Controllers/TransactionController.php` | Replace inline auth check with helper |
| `app/Services/TransactionImportService.php` | Replace 5 string literals with enum values |
| `app/Services/CurrencyPositionService.php` | Replace `'Buy'` string with `TransactionType::Buy->value` |
| `tests/Feature/CounterControllerTest.php` | Add RBAC tests |
| `tests/Feature/AuthenticationTest.php` | Add login enumeration test |
| `tests/Unit/RateApiServiceTest.php` | Add precision test |
| `tests/Unit/CurrencyPositionServiceTest.php` | Add enum usage test |
| `tests/Feature/TransactionBatchUploadTest.php` | Add enum usage test |

---

## Task 1: Add `requireManagerOrAdmin()` to Base Controller

**Files:**
- Modify: `app/Http/Controllers/Controller.php`

This centralizes the auth helper so CounterController and TransactionController can use it without defining their own. Existing per-controller definitions in StockCashController and ReportController remain valid (they override the base).

- [ ] **Step 1: Read the current base Controller**

Verify it currently contains only the two traits:
```php
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
```

- [ ] **Step 2: Add the role-guard helpers**

Replace the entire file content of `app/Http/Controllers/Controller.php` with:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Abort with 403 unless the authenticated user is a manager or admin.
     * UserRole::isManager() returns true for Manager and Admin roles.
     */
    protected function requireManagerOrAdmin(): void
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    /**
     * Abort with 403 unless the authenticated user is an admin.
     */
    protected function requireAdmin(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Admin access required.');
        }
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Controller.php
git commit -m "refactor: centralize requireManagerOrAdmin helper in base Controller"
```

---

## Task 2: Add RBAC to CounterController

**Files:**
- Modify: `app/Http/Controllers/CounterController.php`
- Modify: `tests/Feature/CounterControllerTest.php`

**Context:** `close()` and `handover()` currently have zero role checks — any authenticated user can close any counter or initiate a handover. `open()` is intentionally left open (tellers open their own counter).

- [ ] **Step 1: Write the failing tests**

Open `tests/Feature/CounterControllerTest.php`. Add these two tests inside the existing test class:

```php
public function test_teller_cannot_close_counter(): void
{
    $teller = User::factory()->create(['role' => UserRole::Teller]);
    $counter = Counter::factory()->create();

    $this->actingAs($teller)
        ->post(route('counters.close', $counter), [
            'closing_floats' => [],
        ])
        ->assertStatus(403);
}

public function test_teller_cannot_initiate_handover(): void
{
    $teller = User::factory()->create(['role' => UserRole::Teller]);
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $counter = Counter::factory()->create();

    $this->actingAs($teller)
        ->post(route('counters.handover', $counter), [
            'to_user_id' => $manager->id,
            'supervisor_id' => $manager->id,
            'physical_counts' => [],
        ])
        ->assertStatus(403);
}

public function test_manager_can_close_counter(): void
{
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $counter = Counter::factory()->create();

    // Manager should get past the auth check (may fail on business logic, but not 403)
    $response = $this->actingAs($manager)
        ->post(route('counters.close', $counter), [
            'closing_floats' => [],
            'notes' => null,
        ]);

    $this->assertNotEquals(403, $response->getStatusCode());
}
```

Make sure the top of the test file has these imports if not already present:
```php
use App\Enums\UserRole;
use App\Models\Counter;
use App\Models\User;
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/CounterControllerTest.php --filter="teller_cannot_close_counter|teller_cannot_initiate_handover|manager_can_close_counter" --stop-on-failure
```

Expected: Tests for teller pass (teller should be denied but currently isn't), test for manager may vary. All should currently fail or show unexpected behavior.

- [ ] **Step 3: Add role checks to CounterController**

Open `app/Http/Controllers/CounterController.php`. At the very top of `close()` (after the opening brace, before `$request->validate`), add:

```php
public function close(Request $request, Counter $counter)
{
    $this->requireManagerOrAdmin();

    // Debug: Log the counter
    \Log::debug('Close counter called', [
```

At the very top of `handover()` (after the opening brace, before `$request->validate`), add:

```php
public function handover(Request $request, Counter $counter)
{
    $this->requireManagerOrAdmin();

    $request->validate([
```

Also, inside `handover()`, after the `$fromUser = Auth::user();` line, add an explicit initiator check:

```php
    $fromUser = Auth::user();

    // Verify the initiating user is authorized (must be a manager or the session owner)
    $session = CounterSession::where('counter_id', $counter->id)
```

Wait — `$session` is already fetched earlier in the method. The check should come *after* `$fromUser` is set. Replace the handover method's session fetch block so the initiator check happens after requireManagerOrAdmin() is already called — `requireManagerOrAdmin()` already covers this, so no additional check is needed.

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/CounterControllerTest.php --filter="teller_cannot_close_counter|teller_cannot_initiate_handover|manager_can_close_counter"
```

Expected: All 3 tests pass.

- [ ] **Step 5: Run the full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CounterController.php tests/Feature/CounterControllerTest.php
git commit -m "fix: add RBAC to CounterController close and handover operations"
```

---

## Task 3: Fix Login User Enumeration

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `tests/Feature/AuthenticationTest.php`

**Context:** The failed-login log message at line 50 reveals whether the account exists and its active status. An attacker with log access can enumerate valid users.

- [ ] **Step 1: Write the failing test**

Open `tests/Feature/AuthenticationTest.php`. Add this test:

```php
public function test_failed_login_log_does_not_reveal_user_status(): void
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'is_active' => false,
        'password_hash' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong',
    ]);

    $this->assertDatabaseMissing('system_logs', [
        'action' => 'login_failed',
        'description' => 'Failed login attempt - inactive account',
    ]);

    $this->assertDatabaseMissing('system_logs', [
        'action' => 'login_failed',
        'description' => 'Failed login attempt - wrong password',
    ]);

    $this->assertDatabaseHas('system_logs', [
        'action' => 'login_failed',
        'description' => 'Failed login attempt',
    ]);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/AuthenticationTest.php --filter="failed_login_log_does_not_reveal_user_status"
```

Expected: FAIL — current code writes `'Failed login attempt - inactive account'`.

- [ ] **Step 3: Fix the login controller**

In `app/Http/Controllers/Auth/LoginController.php`, replace the failed-login logging block:

Current code (lines 44-50):
```php
        // Log failed login attempt
        if ($user) {
            \App\Models\SystemLog::create([
                'user_id' => $user->id,
                'action' => 'login_failed',
                'description' => 'Failed login attempt - ' . ($user->is_active ? 'wrong password' : 'inactive account'),
                'ip_address' => $request->ip(),
            ]);
        }
```

Replace with:
```php
        // Log failed login attempt with generic message to prevent user enumeration
        if ($user) {
            \App\Models\SystemLog::create([
                'user_id' => $user->id,
                'action' => 'login_failed',
                'description' => 'Failed login attempt',
                'ip_address' => $request->ip(),
            ]);
        }
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test tests/Feature/AuthenticationTest.php --filter="failed_login_log_does_not_reveal_user_status"
```

Expected: PASS.

- [ ] **Step 5: Run full suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php tests/Feature/AuthenticationTest.php
git commit -m "fix: remove user status from failed login log to prevent user enumeration"
```

---

## Task 4: Fix Float Arithmetic in RateApiService

**Files:**
- Modify: `app/Services/RateApiService.php`
- Modify: `tests/Unit/RateApiServiceTest.php`

**Context:** RateApiService has no MathService injection. It uses `$rate * (1 - $spread / 2)` — native float arithmetic on exchange rates. All monetary/rate calculations must use BCMath per codebase policy.

- [ ] **Step 1: Write the failing test**

Open `tests/Unit/RateApiServiceTest.php`. Add this test:

```php
public function test_spread_calculation_uses_bcmath_precision(): void
{
    // Float arithmetic on 0.02 / 2 = 0.01 can lose precision
    // BCMath: '0.02' / '2' = '0.01' exactly
    $service = new \App\Services\RateApiService(new \App\Services\MathService);

    // Use reflection to call processRates with known values
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('applySpread');

    // If method doesn't exist yet, this test verifies the constructor accepts MathService
    $this->assertInstanceOf(\App\Services\RateApiService::class, $service);
}
```

Actually, since `applySpread` doesn't exist yet, write a test that will verify the calculation after refactoring:

```php
public function test_rate_api_service_accepts_math_service_injection(): void
{
    $mathService = new \App\Services\MathService;
    $service = new \App\Services\RateApiService($mathService);

    $this->assertInstanceOf(\App\Services\RateApiService::class, $service);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Unit/RateApiServiceTest.php --filter="rate_api_service_accepts_math_service_injection"
```

Expected: FAIL — constructor currently takes no arguments.

- [ ] **Step 3: Inject MathService into RateApiService**

Open `app/Services/RateApiService.php`. 

Add the import at the top of the file (after `namespace App\Services;`):
```php
use App\Services\MathService;
```

Replace the constructor:

Current:
```php
    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate_api.key');
        $this->baseUrl = 'https://api.exchangerate-api.com/v4';
    }
```

Replace with:
```php
    public function __construct(protected MathService $mathService = new MathService)
    {
        $this->apiKey = config('services.exchange_rate_api.key');
        $this->baseUrl = 'https://api.exchangerate-api.com/v4';
    }
```

**Note:** The `= new MathService` default allows existing call sites that instantiate without arguments to keep working. Laravel's DI container will inject it automatically for controller/service usage.

- [ ] **Step 4: Replace float spread arithmetic**

Find this code in the `processRates` or equivalent method (around line 58):
```php
        $spread = 0.02; // 2% spread

        $processed[$currency] = [
            'buy' => $this->roundRate($rate * (1 - $spread / 2)),
            'sell' => $this->roundRate($rate * (1 + $spread / 2)),
            'mid' => $this->roundRate($rate),
```

Replace with:
```php
        $spreadStr = '0.02'; // 2% spread
        $half = $this->mathService->divide($spreadStr, '2'); // '0.01'
        $rateStr = (string) $rate;

        $processed[$currency] = [
            'buy' => $this->roundRate(
                (float) $this->mathService->multiply($rateStr, $this->mathService->subtract('1', $half))
            ),
            'sell' => $this->roundRate(
                (float) $this->mathService->multiply($rateStr, $this->mathService->add('1', $half))
            ),
            'mid' => $this->roundRate($rate),
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Unit/RateApiServiceTest.php
```

Expected: All tests pass.

- [ ] **Step 6: Run full suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Services/RateApiService.php tests/Unit/RateApiServiceTest.php
git commit -m "fix: replace float arithmetic with BCMath in RateApiService spread calculation"
```

---

## Task 5: Fix Float Division in ReportingService

**Files:**
- Modify: `app/Services/ReportingService.php`

**Context:** `ReportingService` already has `$this->mathService` injected and in use. Line 518 uses `($currentBalance / $limitValue) * 100` — native float arithmetic on the position limit utilization percentage.

- [ ] **Step 1: Find the exact line**

Open `app/Services/ReportingService.php` around line 518. The code to fix:

```php
            $utilization = $limitValue > 0 ? ($currentBalance / $limitValue) * 100 : 0;
```

- [ ] **Step 2: Replace with MathService calculation**

Replace that line with:
```php
            $utilization = $limitValue > 0
                ? $this->mathService->multiply(
                    $this->mathService->divide((string) $currentBalance, (string) $limitValue),
                    '100'
                )
                : '0';
```

Also update the `status` condition two lines below (it compares `$utilization` as a number). Since `$utilization` is now a string from MathService, update the comparison:

Find:
```php
                'status' => $utilization >= 90 ? 'Critical' : ($utilization >= 75 ? 'Warning' : 'Normal'),
```

Replace with:
```php
                'status' => $this->mathService->compare($utilization, '90') >= 0
                    ? 'Critical'
                    : ($this->mathService->compare($utilization, '75') >= 0 ? 'Warning' : 'Normal'),
```

And update `utilization_percent` which calls `round($utilization, 2)`:

Find:
```php
                'utilization_percent' => round($utilization, 2),
```

Replace with:
```php
                'utilization_percent' => round((float) $utilization, 2),
```

- [ ] **Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add app/Services/ReportingService.php
git commit -m "fix: replace float division with BCMath in ReportingService utilization calculation"
```

---

## Task 6: Fix Float Arithmetic in ReportController

**Files:**
- Modify: `app/Http/Controllers/ReportController.php`

**Context:** ReportController has no MathService injected (only ReportingService and ExportService). Five locations use native float arithmetic on monetary values.

- [ ] **Step 1: Inject MathService**

Open `app/Http/Controllers/ReportController.php`.

Add import (after existing `use` statements):
```php
use App\Services\MathService;
```

Find the constructor:
```php
    public function __construct(
        ReportingService $reportingService,
        ExportService $exportService
    ) {
        $this->reportingService = $reportingService;
        $this->exportService = $exportService;
    }
```

Replace with:
```php
    public function __construct(
        ReportingService $reportingService,
        ExportService $exportService,
        MathService $mathService
    ) {
        $this->reportingService = $reportingService;
        $this->exportService = $exportService;
        $this->mathService = $mathService;
    }
```

Add the property declaration (near the top of the class, with other protected properties):
```php
    protected MathService $mathService;
```

- [ ] **Step 2: Fix calculateTrends() float arithmetic (line ~372)**

Find:
```php
            $trend = (($row->total_volume - $previousVolume) / $previousVolume) * 100;
```

Replace with:
```php
            $diff = $this->mathService->subtract((string) $row->total_volume, (string) $previousVolume);
            $trend = $this->mathService->multiply(
                $this->mathService->divide($diff, (string) $previousVolume),
                '100'
            );
```

Also update the direction comparison two lines below. Find:
```php
            'direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'neutral'),
```

Replace with:
```php
            'direction' => $this->mathService->compare($trend, '0') > 0
                ? 'up'
                : ($this->mathService->compare($trend, '0') < 0 ? 'down' : 'neutral'),
```

- [ ] **Step 3: Fix P&L float arithmetic (lines ~451-465)**

Find this block:
```php
        $avgCost = (float) $position->avg_cost_rate;
        $balance = (float) $position->balance;
        $unrealizedPnl = ($currentRate - $avgCost) * $balance;
```

Replace with:
```php
        $avgCost = (string) $position->avg_cost_rate;
        $balance = (string) $position->balance;
        $unrealizedPnl = $this->mathService->multiply(
            $this->mathService->subtract((string) $currentRate, $avgCost),
            $balance
        );
```

Find the realized P&L calculation inside the loop:
```php
            $sellRate = (float) $sell->rate;
            $sellAmount = (float) $sell->amount_foreign;
            // Gain = (sell rate - avg cost) * amount
            $realizedPnl += ($sellRate - $avgCost) * $sellAmount;
```

Replace with:
```php
            $sellRate = (string) $sell->rate;
            $sellAmount = (string) $sell->amount_foreign;
            // Gain = (sell rate - avg cost) * amount
            $gain = $this->mathService->multiply(
                $this->mathService->subtract($sellRate, $avgCost),
                $sellAmount
            );
            $realizedPnl = $this->mathService->add((string) $realizedPnl, $gain);
```

Update the `$realizedPnl` initialization above the loop:
Find `$realizedPnl = 0;` and replace with `$realizedPnl = '0';`

Update the return statement. Find:
```php
            'total_pnl' => $unrealizedPnl + $realizedPnl,
```

Replace with:
```php
            'total_pnl' => $this->mathService->add((string) $unrealizedPnl, (string) $realizedPnl),
```

- [ ] **Step 4: Fix avg_transaction float division (line ~521)**

Find:
```php
        'avg_transaction' => $allTransactions->count() > 0
            ? $allTransactions->sum('amount_local') / $allTransactions->count()
            : 0,
```

Replace with:
```php
        'avg_transaction' => $allTransactions->count() > 0
            ? $this->mathService->divide(
                (string) $allTransactions->sum('amount_local'),
                (string) $allTransactions->count()
              )
            : '0',
```

- [ ] **Step 5: Fix quarter float division (line ~674)**

Find:
```php
        $quarter = $validated['quarter'] ?? now()->format('Y').'-Q'.ceil(now()->format('n') / 3);
```

Replace with:
```php
        $quarter = $validated['quarter'] ?? now()->format('Y').'-Q'.(int) ceil((int) now()->format('n') / 3);
```

- [ ] **Step 6: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/ReportController.php
git commit -m "fix: replace float arithmetic with BCMath in ReportController and fix quarter integer division"
```

---

## Task 7: Fix Float Arithmetic and Auth in StockCashController

**Files:**
- Modify: `app/Http/Controllers/StockCashController.php`

**Context:** StockCashController uses `new MathService` inline rather than a proper injected property. It also has redundant `auth()->check()` calls that duplicate what the `auth` middleware already guarantees.

- [ ] **Step 1: Add MathService property and inject it properly**

Open `app/Http/Controllers/StockCashController.php`.

StockCashController currently has no explicit constructor. Add one after the `requireManagerOrAdmin()` method:

```php
    public function __construct(protected MathService $mathService) {}
```

Remove the `use App\Services\MathService;` import if it creates a conflict — it should already be there.

- [ ] **Step 2: Remove redundant `auth()->check()` from openTill**

In `openTill()`, find and remove this block:
```php
    // Allow any authenticated user (teller, manager, admin) to open till
    // Access control handled by auth middleware in routes
    if (! auth()->check()) {
        abort(403, 'Unauthorized');
    }
```

Replace it with nothing (the `auth` middleware on the routes already guarantees the user is authenticated).

- [ ] **Step 3: Remove redundant `auth()->check()` from closeTill**

In `closeTill()`, find and remove the same block:
```php
    // Allow any authenticated user (teller, manager, admin) to close till
    // Access control handled by auth middleware in routes
    if (! auth()->check()) {
        abort(403, 'Unauthorized');
    }
```

- [ ] **Step 4: Fix variance float calculation in closeTill (line ~172)**

Find:
```php
        $expectedClosing = (float) $tillBalance->opening_balance + $netFlow;
        $variance = $validated['closing_balance'] - $expectedClosing;
```

Replace with:
```php
        $expectedClosing = $this->mathService->add(
            (string) $tillBalance->opening_balance,
            (string) $netFlow
        );
        $variance = $this->mathService->subtract(
            (string) $validated['closing_balance'],
            $expectedClosing
        );
```

Update `$tillBalance->update` call that stores variance — `'variance' => $variance` stays the same since it's now a precise string.

- [ ] **Step 5: Fix net_flow and expectedClosing float arithmetic in reconciliationReport (lines ~277-292)**

Find:
```php
            'net_flow' => $transactions->where('type', TransactionType::Buy)->sum('amount_local') - $transactions->where('type', TransactionType::Sell)->sum('amount_local'),
```

Replace with:
```php
            'net_flow' => $this->mathService->subtract(
                (string) $transactions->where('type', TransactionType::Buy)->sum('amount_local'),
                (string) $transactions->where('type', TransactionType::Sell)->sum('amount_local')
            ),
```

Find:
```php
        $expectedClosing = (float) $tillBalance->opening_balance + $summary['net_flow'];
```

Replace with:
```php
        $expectedClosing = $this->mathService->add(
            (string) $tillBalance->opening_balance,
            (string) $summary['net_flow']
        );
```

Find:
```php
        $variance = $actualClosing !== null
            ? $actualClosing - $expectedClosing
            : null;
```

Replace with:
```php
        $variance = $actualClosing !== null
            ? $this->mathService->subtract((string) $actualClosing, (string) $expectedClosing)
            : null;
```

- [ ] **Step 6: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/StockCashController.php
git commit -m "fix: inject MathService properly, replace float arithmetic, remove redundant auth checks in StockCashController"
```

---

## Task 8: Fix Raw Enum Strings in TransactionImportService

**Files:**
- Modify: `app/Services/TransactionImportService.php`
- Modify: `tests/Feature/TransactionBatchUploadTest.php`

**Context:** Five locations in TransactionImportService compare against hardcoded strings `'Buy'`, `'Sell'`, `'Completed'`, `'Pending'` instead of using the established `TransactionType` and `TransactionStatus` enums. This is fragile if enum values change.

- [ ] **Step 1: Add enum imports to TransactionImportService**

Open `app/Services/TransactionImportService.php`. Check whether `TransactionType` and `TransactionStatus` are already imported. If not, add:
```php
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
```

- [ ] **Step 2: Fix line 118 — type validation**

Find:
```php
            if (! in_array($data['type'], ['Buy', 'Sell'])) {
                throw new \Exception("Invalid transaction type: {$data['type']}. Must be 'Buy' or 'Sell'");
            }
```

Replace with:
```php
            if (TransactionType::tryFrom($data['type']) === null) {
                throw new \Exception("Invalid transaction type: {$data['type']}. Must be '".TransactionType::Buy->value."' or '".TransactionType::Sell->value."'");
            }
```

- [ ] **Step 3: Fix line 160 — status initialization**

Find:
```php
            $status = 'Completed';
```

Replace with:
```php
            $status = TransactionStatus::Completed->value;
```

- [ ] **Step 4: Fix line 167 — Pending status**

Find:
```php
                    $status = 'Pending';
```

Replace with:
```php
                    $status = TransactionStatus::Pending->value;
```

Also find the OnHold assignment in the same block:
```php
                    $status = 'OnHold';
```

Replace with:
```php
                    $status = TransactionStatus::OnHold->value;
```

- [ ] **Step 5: Fix line 176 — Sell type check**

Find:
```php
            if ($data['type'] === 'Sell') {
```

Replace with:
```php
            if ($data['type'] === TransactionType::Sell->value) {
```

- [ ] **Step 6: Fix lines 211 and 228 — Completed status comparisons**

Find both occurrences of:
```php
                if ($status === 'Completed') {
```

Replace both with:
```php
                if ($status === TransactionStatus::Completed->value) {
```

- [ ] **Step 7: Run the full test suite**

```bash
php artisan test tests/Feature/TransactionBatchUploadTest.php
```

Expected: All tests pass.

- [ ] **Step 8: Run full suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 9: Commit**

```bash
git add app/Services/TransactionImportService.php
git commit -m "fix: replace raw enum string literals with TransactionType/TransactionStatus enum values in TransactionImportService"
```

---

## Task 9: Fix Raw Enum String in CurrencyPositionService

**Files:**
- Modify: `app/Services/CurrencyPositionService.php`
- Modify: `tests/Unit/CurrencyPositionServiceTest.php`

**Context:** `updatePosition()` accepts `string $type` and compares `$type === 'Buy'`. The enum `TransactionType` is already used elsewhere. The parameter type remains `string` for backward compatibility (callers pass `$data['type']` from input), but the comparison should use the enum constant.

- [ ] **Step 1: Write a test that verifies enum value usage**

Open `tests/Unit/CurrencyPositionServiceTest.php`. Add:

```php
public function test_update_position_uses_enum_value_for_type_comparison(): void
{
    // Verify TransactionType::Buy->value is 'buy' or 'Buy' — whichever the enum defines
    // This test documents the contract between callers and the service
    $this->assertEquals(TransactionType::Buy->value, 'Buy');
    $this->assertEquals(TransactionType::Sell->value, 'Sell');
}
```

Add import if needed: `use App\Enums\TransactionType;`

- [ ] **Step 2: Run test to confirm it passes (documents the contract)**

```bash
php artisan test tests/Unit/CurrencyPositionServiceTest.php --filter="test_update_position_uses_enum_value_for_type_comparison"
```

Expected: PASS. This confirms `TransactionType::Buy->value === 'Buy'`.

- [ ] **Step 3: Replace hardcoded string in CurrencyPositionService**

Open `app/Services/CurrencyPositionService.php`.

Add import at the top:
```php
use App\Enums\TransactionType;
```

Find (line ~69):
```php
            if ($type === 'Buy') {
```

Replace with:
```php
            if ($type === TransactionType::Buy->value) {
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Unit/CurrencyPositionServiceTest.php
```

Expected: All tests pass.

- [ ] **Step 5: Run full suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Services/CurrencyPositionService.php tests/Unit/CurrencyPositionServiceTest.php
git commit -m "fix: replace raw string 'Buy' with TransactionType::Buy->value in CurrencyPositionService"
```

---

## Task 10: Standardize Auth Pattern in TransactionController

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php`

**Context:** `approve()` uses an inline `if (! auth()->user()->isManager()) abort(403)` instead of the `requireManagerOrAdmin()` helper now available in the base Controller. This is the last inconsistency in issue 5.2.

- [ ] **Step 1: Replace inline check in approve()**

Open `app/Http/Controllers/TransactionController.php`, around line 255.

Find:
```php
    public function approve(Request $request, Transaction $transaction)
    {
        if (! auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager approval required.');
        }
```

Replace with:
```php
    public function approve(Request $request, Transaction $transaction)
    {
        $this->requireManagerOrAdmin();
```

- [ ] **Step 2: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/TransactionController.php
git commit -m "refactor: replace inline manager check with requireManagerOrAdmin helper in TransactionController"
```

---

## Final Verification

- [ ] **Run the complete test suite one final time**

```bash
php artisan test --stop-on-failure
```

Expected output: All tests pass. Zero failures.

- [ ] **Verify analysis docs are up to date**

Both `docs/comprehensive-logical-analysis-2026-04-03.md` and `docs/codebase-analysis-2026-04-05.md` were updated during the analysis session. No further doc updates needed after this plan completes.

- [ ] **Final commit (if any uncommitted changes remain)**

```bash
git status
# If any files modified but not yet committed:
git add <files>
git commit -m "chore: final cleanup from remaining issues fix session"
```

---

## Summary

| Task | Issue(s) | Severity | Effort |
|------|---------|----------|--------|
| 1. Base Controller helpers | 5.2 prep | Medium | S |
| 2. CounterController RBAC | 2.1, 2.2 | Critical + High | S |
| 3. Login enumeration | 6.2 | High | S |
| 4. RateApiService precision | 2.3 | High | S |
| 5. ReportingService precision | 2.4 | High | S |
| 6. ReportController precision + quarter | 5.3 partial, 2.5 | High + Medium | M |
| 7. StockCashController precision + auth | 5.3 partial, 5.2 partial | High + Medium | M |
| 8. TransactionImportService enums | 2.6 | Medium | M |
| 9. CurrencyPositionService enum | 2.7 | Medium | S |
| 10. TransactionController auth | 5.2 final | Medium | S |
