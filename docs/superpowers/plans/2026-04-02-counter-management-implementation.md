# Counter Management System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a complete Counter Management System for single-branch operations with 2-5 identical counters, including session lifecycle, formal handovers with supervisor verification, and full audit trail.

**Architecture:** Laravel 10 MVC with service layer. New tables (counters, counter_sessions, counter_handovers) integrate with existing User, Transaction, and TillBalance models. Controllers handle HTTP requests, services contain business logic, models manage data access.

**Tech Stack:** PHP 8.2+, Laravel 10, MySQL 8.0, PHPUnit, Bootstrap 5

---

## Phase 1: Core Counter Management

### Task 1: Create Counters Table Migration

**Files:**
- Create: `database/migrations/2026_04_02_000001_create_counters_table.php`

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration successful, counters table created

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_02_000001_create_counters_table.php
git commit -m "feat: create counters table migration"
```

---

### Task 2: Create Counter Model

**Files:**
- Create: `app/Models/Counter.php`
- Test: `tests/Unit/CounterModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_can_be_created()
    {
        $counter = Counter::create([
            'code' => 'C01',
            'name' => 'Counter 1',
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('counters', [
            'code' => 'C01',
            'name' => 'Counter 1'
        ]);
    }

    public function test_counter_code_must_be_unique()
    {
        Counter::create([
            'code' => 'C01',
            'name' => 'Counter 1'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Counter::create([
            'code' => 'C01',
            'name' => 'Counter 2'
        ]);
    }

    public function test_counter_has_active_scope()
    {
        Counter::create(['code' => 'C01', 'name' => 'Counter 1', 'status' => 'active']);
        Counter::create(['code' => 'C02', 'name' => 'Counter 2', 'status' => 'inactive']);
        
        $activeCounters = Counter::active()->get();
        
        $this->assertCount(1, $activeCounters);
        $this->assertEquals('C01', $activeCounters->first()->code);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterModelTest.php`
Expected: FAIL with "Class 'App\Models\Counter' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function sessions()
    {
        return $this->hasMany(CounterSession::class);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterModelTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Counter.php tests/Unit/CounterModelTest.php
git commit -m "feat: create Counter model with tests"
```

---

### Task 3: Create Counter Sessions Table Migration

**Files:**
- Create: `database/migrations/2026_04_02_000002_create_counter_sessions_table.php`

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained('counters')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('session_date');
            $table->datetime('opened_at');
            $table->datetime('closed_at')->nullable();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'closed', 'handed_over'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['counter_id', 'session_date']);
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_sessions');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration successful, counter_sessions table created

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_02_000002_create_counter_sessions_table.php
git commit -m "feat: create counter_sessions table migration"
```

---

### Task 4: Create CounterSession Model

**Files:**
- Create: `app/Models/CounterSession.php`
- Test: `tests/Unit/CounterSessionModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterSessionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_can_be_created()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);

        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_session_belongs_to_counter()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id
        ]);

        $this->assertEquals($counter->id, $session->counter->id);
    }

    public function test_session_belongs_to_user()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id
        ]);

        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_has_open_scope()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);
        
        CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'closed_at' => now()->subHour(),
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'status' => 'closed'
        ]);

        $openSessions = CounterSession::open()->get();
        
        $this->assertCount(1, $openSessions);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterSessionModelTest.php`
Expected: FAIL with "Class 'App\Models\CounterSession' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_id',
        'user_id',
        'session_date',
        'opened_at',
        'closed_at',
        'opened_by',
        'closed_by',
        'status',
        'notes'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'session_date' => 'date'
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function openedByUser()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForCounter($query, $counterId)
    {
        return $query->where('counter_id', $counterId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('session_date', $date);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterSessionModelTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/CounterSession.php tests/Unit/CounterSessionModelTest.php
git commit -m "feat: create CounterSession model with tests"
```

---

### Task 5: Create CounterService

**Files:**
- Create: `app/Services/CounterService.php`
- Test: `tests/Unit/CounterServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use App\Services\CounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterServiceTest extends TestCase
{
    use RefreshDatabase;

    private CounterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CounterService();
    }

    public function test_can_open_counter_session()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        $openingFloats = [
            ['currency_id' => 1, 'amount' => 10000.00]
        ];

        $session = $this->service->openSession($counter, $user, $openingFloats);

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_cannot_open_if_already_open()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        $this->service->openSession($counter, $user, []);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Counter is already open today');
        
        $this->service->openSession($counter, $user, []);
    }

    public function test_cannot_open_if_user_at_another_counter()
    {
        $counter1 = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $counter2 = Counter::create(['code' => 'C02', 'name' => 'Counter 2']);
        $user = User::factory()->create(['role' => 'teller']);
        
        $this->service->openSession($counter1, $user, []);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is already at another counter');
        
        $this->service->openSession($counter2, $user, []);
    }

    public function test_can_close_counter_session()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $closingFloats = [
            ['currency_id' => 1, 'amount' => 10000.00]
        ];

        $this->service->closeSession($session, $user, $closingFloats, 'No variance');

        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'closed_by' => $user->id
        ]);
    }

    public function test_calculates_variance_correctly()
    {
        $opening = 10000.00;
        $closing = 10200.00;

        $variance = $this->service->calculateVariance($opening, $closing);

        $this->assertEquals(200.00, $variance);
    }

    public function test_requires_supervisor_for_large_variance()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $closingFloats = [
            ['currency_id' => 1, 'amount' => 15000.00] // Large variance
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Variance exceeds threshold, requires supervisor approval');
        
        $this->service->closeSession($session, $user, $closingFloats, 'Large variance');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterServiceTest.php`
Expected: FAIL with "Class 'App\Services\CounterService' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use Exception;

class CounterService
{
    private const VARIANCE_THRESHOLD_YELLOW = 100.00;
    private const VARIANCE_THRESHOLD_RED = 500.00;

    public function openSession(Counter $counter, User $user, array $openingFloats): CounterSession
    {
        // Check if counter is already open today
        $existingSession = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            throw new Exception('Counter is already open today');
        }

        // Check if user is already at another counter
        $userSession = CounterSession::where('user_id', $user->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->first();

        if ($userSession) {
            throw new Exception('User is already at another counter');
        }

        // Create session
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        // TODO: Update till balances with opening floats

        return $session;
    }

    public function closeSession(CounterSession $session, User $user, array $closingFloats, string $notes = null): CounterSession
    {
        if (!$session->isOpen()) {
            throw new Exception('Session is not open');
        }

        // Calculate variance
        $variances = [];
        foreach ($closingFloats as $float) {
            $openingBalance = 10000.00; // TODO: Get from till balances
            $closingBalance = $float['amount'];
            $variance = $this->calculateVariance($openingBalance, $closingBalance);
            
            if (abs($variance) > self::VARIANCE_THRESHOLD_RED) {
                throw new Exception('Variance exceeds threshold, requires supervisor approval');
            }
            
            $variances[] = [
                'currency_id' => $float['currency_id'],
                'variance' => $variance
            ];
        }

        $session->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'status' => 'closed',
            'notes' => $notes
        ]);

        // TODO: Update till balances with closing floats

        return $session;
    }

    public function calculateVariance(float $opening, float $closing): float
    {
        return $closing - $opening;
    }

    public function getCounterStatus(Counter $counter): array
    {
        $session = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->first();

        return [
            'counter' => $counter,
            'status' => $session ? 'open' : 'closed',
            'current_user' => $session ? $session->user : null,
            'session' => $session
        ];
    }

    public function getAvailableCounters(): array
    {
        $allCounters = Counter::active()->get();
        $openCounterIds = CounterSession::where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->pluck('counter_id')
            ->toArray();

        return $allCounters->filter(function ($counter) use ($openCounterIds) {
            return !in_array($counter->id, $openCounterIds);
        })->values()->all();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/CounterService.php tests/Unit/CounterServiceTest.php
git commit -m "feat: create CounterService with business logic"
```

---

### Task 6: Create CounterController

**Files:**
- Create: `app/Http/Controllers/CounterController.php`
- Test: `tests/Feature/CounterControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_counters()
    {
        $user = User::factory()->create(['role' => 'teller']);
        Counter::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/counters');

        $response->assertStatus(200);
        $response->assertViewHas('counters');
    }

    public function test_user_can_open_counter()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);

        $response = $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_user_can_close_counter()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($user)->post("/counters/{$counter->id}/close", [
            'closing_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ],
            'notes' => 'No variance'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'status' => 'closed'
        ]);
    }

    public function test_supervisor_can_override_close()
    {
        $supervisor = User::factory()->create(['role' => 'manager']);
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($supervisor)->post("/counters/{$counter->id}/close", [
            'closing_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ],
            'notes' => 'Supervisor override'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'closed_by' => $supervisor->id,
            'status' => 'closed'
        ]);
    }

    public function test_cannot_open_without_permission()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);

        $response = $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => []
        ]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/CounterControllerTest.php`
Expected: FAIL with "Route not found" or "Controller not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use App\Services\CounterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounterController extends Controller
{
    private CounterService $counterService;

    public function __construct(CounterService $counterService)
    {
        $this->counterService = $counterService;
    }

    public function index()
    {
        $counters = Counter::with(['sessions' => function ($query) {
            $query->where('session_date', now()->toDateString())
                  ->where('status', 'open');
        }])->get();

        $stats = [
            'total' => $counters->count(),
            'open' => $counters->filter(fn($c) => $c->sessions->count() > 0)->count(),
            'available' => $counters->filter(fn($c) => $c->sessions->count() === 0)->count()
        ];

        return view('counters.index', compact('counters', 'stats'));
    }

    public function open(Request $request, Counter $counter)
    {
        $request->validate([
            'opening_floats' => 'required|array',
            'opening_floats.*.currency_id' => 'required|exists:currencies,id',
            'opening_floats.*.amount' => 'required|numeric|min:0'
        ]);

        $user = Auth::user();
        $openingFloats = $request->input('opening_floats');

        try {
            $session = $this->counterService->openSession($counter, $user, $openingFloats);
            
            return redirect()->route('counters.index')
                ->with('success', "Counter {$counter->code} opened successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, Counter $counter)
    {
        $request->validate([
            'closing_floats' => 'required|array',
            'closing_floats.*.currency_id' => 'required|exists:currencies,id',
            'closing_floats.*.amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $user = Auth::user();
        $closingFloats = $request->input('closing_floats');
        $notes = $request->input('notes');

        $session = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->firstOrFail();

        try {
            $this->counterService->closeSession($session, $user, $closingFloats, $notes);
            
            return redirect()->route('counters.index')
                ->with('success', "Counter {$counter->code} closed successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function status(Counter $counter)
    {
        $status = $this->counterService->getCounterStatus($counter);
        
        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    public function history(Counter $counter)
    {
        $sessions = CounterSession::where('counter_id', $counter->id)
            ->with(['user', 'openedByUser', 'closedByUser'])
            ->orderBy('session_date', 'desc')
            ->orderBy('opened_at', 'desc')
            ->paginate(20);

        return view('counters.history', compact('counter', 'sessions'));
    }
}
```

- [ ] **Step 4: Add routes**

Modify: `routes/web.php`

Add after the existing routes:

```php
// Counter Management
Route::middleware('auth')->group(function () {
    Route::get('/counters', [CounterController::class, 'index'])->name('counters.index');
    Route::post('/counters/{counter}/open', [CounterController::class, 'open'])->name('counters.open');
    Route::post('/counters/{counter}/close', [CounterController::class, 'close'])->name('counters.close');
    Route::get('/counters/{counter}/status', [CounterController::class, 'status'])->name('counters.status');
    Route::get('/counters/{counter}/history', [CounterController::class, 'history'])->name('counters.history');
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/CounterControllerTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CounterController.php routes/web.php tests/Feature/CounterControllerTest.php
git commit -m "feat: create CounterController with routes and tests"
```

---

### Task 7: Create Counters Index View

**Files:**
- Create: `resources/views/counters/index.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Management</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Counters</h5>
                    <p class="card-text display-4">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Open Counters</h5>
                    <p class="card-text display-4 text-success">{{ $stats['open'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Available Counters</h5>
                    <p class="card-text display-4 text-primary">{{ $stats['available'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Counters Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Counters</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Current User</th>
                            <th>Session Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counters as $counter)
                            <tr>
                                <td>{{ $counter->code }}</td>
                                <td>{{ $counter->name }}</td>
                                <td>
                                    @if($counter->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($counter->sessions->count() > 0)
                                        {{ $counter->sessions->first()->user->name }}
                                    @else
                                        <em>None</em>
                                    @endif
                                </td>
                                <td>
                                    @if($counter->sessions->count() > 0)
                                        {{ $counter->sessions->first()->opened_at->format('H:i') }}
                                    @else
                                        <em>-</em>
                                    @endif
                                </td>
                                <td>
                                    @if($counter->sessions->count() > 0)
                                        <form action="{{ route('counters.close', $counter) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">Close</button>
                                        </form>
                                    @else
                                        <a href="{{ route('counters.open', $counter) }}" class="btn btn-sm btn-primary">Open</a>
                                    @endif
                                    <a href="{{ route('counters.history', $counter) }}" class="btn btn-sm btn-info">History</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/counters/index.blade.php
git commit -m "feat: create counters index view"
```

---

### Task 8: Create Counters Open View

**Files:**
- Create: `resources/views/counters/open.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Open Counter</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.open', $counter) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="counter" class="form-label">Counter</label>
                    <select class="form-select" id="counter" name="counter_id" required>
                        <option value="">Select a counter</option>
                        @foreach($availableCounters as $availableCounter)
                            <option value="{{ $availableCounter->id }}">{{ $availableCounter->code }} - {{ $availableCounter->name }}</option>
                        @endforeach
                    </select>
                </div>

                <h5 class="mt-4 mb-3">Opening Floats</h5>
                <div id="floats-container">
                    <div class="float-row mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select class="form-select currency-select" name="opening_floats[0][currency_id]" required>
                                    <option value="">Select currency</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" name="opening_floats[0][amount]" required min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-block remove-float">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-float" class="btn btn-secondary mb-3">+ Add Currency</button>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Open Counter</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('add-float').addEventListener('click', function() {
        const container = document.getElementById('floats-container');
        const rowCount = container.querySelectorAll('.float-row').length;
        
        const newRow = document.createElement('div');
        newRow.className = 'float-row mb-3';
        newRow.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <select class="form-select currency-select" name="opening_floats[${rowCount}][currency_id]" required>
                        <option value="">Select currency</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" class="form-control" name="opening_floats[${rowCount}][amount]" required min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block remove-float">Remove</button>
                </div>
            </div>
        `;
        
        container.appendChild(newRow);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-float')) {
            const row = e.target.closest('.float-row');
            if (document.querySelectorAll('.float-row').length > 1) {
                row.remove();
            }
        }
    });
</script>
@endpush
@endsection
```

- [ ] **Step 2: Update CounterController to pass data**

Modify: `app/Http/Controllers/CounterController.php`

Add to index method:

```php
public function index()
{
    $counters = Counter::with(['sessions' => function ($query) {
        $query->where('session_date', now()->toDateString())
              ->where('status', 'open');
    }])->get();

    $stats = [
        'total' => $counters->count(),
        'open' => $counters->filter(fn($c) => $c->sessions->count() > 0)->count(),
        'available' => $counters->filter(fn($c) => $c->sessions->count() === 0)->count()
    ];

    $availableCounters = $this->counterService->getAvailableCounters();
    $currencies = \App\Models\Currency::where('is_active', true)->get();

    return view('counters.index', compact('counters', 'stats', 'availableCounters', 'currencies'));
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/counters/open.blade.php app/Http/Controllers/CounterController.php
git commit -m "feat: create counters open view with dynamic form"
```

---

### Task 9: Create Counters Close View

**Files:**
- Create: `resources/views/counters/close.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Close Counter</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Counter Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Code:</strong> {{ $counter->code }}</p>
                    <p><strong>Name:</strong> {{ $counter->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Opened By:</strong> {{ $session->openedByUser->name }}</p>
                    <p><strong>Opened At:</strong> {{ $session->opened_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.close', $counter) }}" method="POST">
                @csrf

                <h5 class="mb-3">Closing Floats</h5>
                <div id="floats-container">
                    @foreach($currencies as $index => $currency)
                        <div class="float-row mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">{{ $currency->code }} - {{ $currency->name }}</label>
                                    <input type="hidden" name="closing_floats[{{ $index }}][currency_id]" value="{{ $currency->id }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Opening Balance</label>
                                    <input type="text" class="form-control" value="{{ number_format(10000.00, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Closing Amount</label>
                                    <input type="number" step="0.01" class="form-control closing-amount" name="closing_floats[{{ $index }}][amount]" required min="0" data-opening="10000.00">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Variance</label>
                                    <input type="text" class="form-control variance-display" readonly>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Variance:</strong> <span id="total-variance">RM 0.00</span>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Required if variance > RM 100"></textarea>
                </div>

                <button type="submit" class="btn btn-warning">Close Counter</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.closing-amount').forEach(input => {
        input.addEventListener('input', function() {
            const opening = parseFloat(this.dataset.opening);
            const closing = parseFloat(this.value) || 0;
            const variance = closing - opening;
            
            const varianceDisplay = this.closest('.row').querySelector('.variance-display');
            varianceDisplay.value = 'RM ' + variance.toFixed(2);
            
            if (variance < 0) {
                varianceDisplay.classList.add('text-danger');
            } else if (variance > 0) {
                varianceDisplay.classList.add('text-success');
            } else {
                varianceDisplay.classList.remove('text-danger', 'text-success');
            }
            
            updateTotalVariance();
        });
    });

    function updateTotalVariance() {
        let total = 0;
        document.querySelectorAll('.closing-amount').forEach(input => {
            const opening = parseFloat(input.dataset.opening);
            const closing = parseFloat(input.value) || 0;
            total += (closing - opening);
        });
        
        const totalDisplay = document.getElementById('total-variance');
        totalDisplay.textContent = 'RM ' + total.toFixed(2);
        
        if (Math.abs(total) > 500) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-warning');
            totalDisplay.parentElement.classList.add('alert-danger');
        } else if (Math.abs(total) > 100) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-warning');
        } else {
            totalDisplay.parentElement.classList.remove('alert-warning', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-info');
        }
    }
</script>
@endpush
@endsection
```

- [ ] **Step 2: Update CounterController close method**

Modify: `app/Http/Controllers/CounterController.php`

```php
public function close(Request $request, Counter $counter)
{
    $request->validate([
        'closing_floats' => 'required|array',
        'closing_floats.*.currency_id' => 'required|exists:currencies,id',
        'closing_floats.*.amount' => 'required|numeric|min:0',
        'notes' => 'nullable|string'
    ]);

    $user = Auth::user();
    $closingFloats = $request->input('closing_floats');
    $notes = $request->input('notes');

    $session = CounterSession::where('counter_id', $counter->id)
        ->where('session_date', now()->toDateString())
        ->where('status', 'open')
        ->firstOrFail();

    try {
        $this->counterService->closeSession($session, $user, $closingFloats, $notes);
        
        return redirect()->route('counters.index')
            ->with('success', "Counter {$counter->code} closed successfully");
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

Add new method:

```php
public function showClose(Counter $counter)
{
    $session = CounterSession::where('counter_id', $counter->id)
        ->where('session_date', now()->toDateString())
        ->where('status', 'open')
        ->firstOrFail();

    $currencies = \App\Models\Currency::where('is_active', true)->get();

    return view('counters.close', compact('counter', 'session', 'currencies'));
}
```

- [ ] **Step 3: Add route**

Modify: `routes/web.php`

```php
Route::get('/counters/{counter}/close', [CounterController::class, 'showClose'])->name('counters.close.show');
Route::post('/counters/{counter}/close', [CounterController::class, 'close'])->name('counters.close');
```

- [ ] **Step 4: Update index view close button**

Modify: `resources/views/counters/index.blade.php`

```php
<a href="{{ route('counters.close.show', $counter) }}" class="btn btn-sm btn-warning">Close</a>
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/counters/close.blade.php app/Http/Controllers/CounterController.php routes/web.php resources/views/counters/index.blade.php
git commit -m "feat: create counters close view with variance calculation"
```

---

### Task 10: Create Counters History View

**Files:**
- Create: `resources/views/counters/history.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter History - {{ $counter->code }}</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('counters.history', $counter) }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Opened At</th>
                            <th>Closed At</th>
                            <th>Status</th>
                            <th>Total Variance (MYR)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr>
                                <td>{{ $session->session_date->format('Y-m-d') }}</td>
                                <td>{{ $session->user->name }}</td>
                                <td>{{ $session->opened_at->format('H:i:s') }}</td>
                                <td>{{ $session->closed_at ? $session->closed_at->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if($session->status === 'open')
                                        <span class="badge bg-success">Open</span>
                                    @elseif($session->status === 'closed')
                                        <span class="badge bg-secondary">Closed</span>
                                    @else
                                        <span class="badge bg-warning">Handed Over</span>
                                    @endif
                                </td>
                                <td>RM {{ number_format(0.00, 2) }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">View Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $sessions->links() }}
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('counters.index') }}" class="btn btn-secondary">Back to Counters</a>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Update CounterController history method**

Modify: `app/Http/Controllers/CounterController.php`

```php
public function history(Request $request, Counter $counter)
{
    $query = CounterSession::where('counter_id', $counter->id)
        ->with(['user', 'openedByUser', 'closedByUser']);

    if ($request->has('from_date')) {
        $query->where('session_date', '>=', $request->input('from_date'));
    }

    if ($request->has('to_date')) {
        $query->where('session_date', '<=', $request->input('to_date'));
    }

    if ($request->has('user_id')) {
        $query->where('user_id', $request->input('user_id'));
    }

    $sessions = $query->orderBy('session_date', 'desc')
        ->orderBy('opened_at', 'desc')
        ->paginate(20);

    $users = User::where('is_active', true)->get();

    return view('counters.history', compact('counter', 'sessions', 'users'));
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/counters/history.blade.php app/Http/Controllers/CounterController.php
git commit -m "feat: create counters history view with filters"
```

---

## Phase 2: Counter Handovers

### Task 11: Create Counter Handovers Table Migration

**Files:**
- Create: `database/migrations/2026_04_02_000003_create_counter_handovers_table.php`

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_session_id')->constrained('counter_sessions')->restrictOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->datetime('handover_time');
            $table->boolean('physical_count_verified')->default(true);
            $table->decimal('variance_myr', 15, 2)->default(0.00);
            $table->text('variance_notes')->nullable();
            $table->timestamps();
            
            $table->index('counter_session_id');
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('supervisor_id');
            $table->index('handover_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_handovers');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration successful, counter_handovers table created

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_02_000003_create_counter_handovers_table.php
git commit -m "feat: create counter_handovers table migration"
```

---

### Task 12: Create CounterHandover Model

**Files:**
- Create: `app/Models/CounterHandover.php`
- Test: `tests/Unit/CounterHandoverModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\CounterHandover;
use App\Models\CounterSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterHandoverModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_handover_can_be_created()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);

        $handover = CounterHandover::create([
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'handover_time' => now(),
            'variance_myr' => 0.00
        ]);

        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id
        ]);
    }

    public function test_handover_belongs_to_session()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);

        $handover = CounterHandover::create([
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'handover_time' => now()
        ]);

        $this->assertEquals($session->id, $handover->session->id);
    }

    public function test_handover_belongs_to_users()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);

        $handover = CounterHandover::create([
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'handover_time' => now()
        ]);

        $this->assertEquals($fromUser->id, $handover->fromUser->id);
        $this->assertEquals($toUser->id, $handover->toUser->id);
        $this->assertEquals($supervisor->id, $handover->supervisor->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterHandoverModelTest.php`
Expected: FAIL with "Class 'App\Models\CounterHandover' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_session_id',
        'from_user_id',
        'to_user_id',
        'supervisor_id',
        'handover_time',
        'physical_count_verified',
        'variance_myr',
        'variance_notes'
    ];

    protected $casts = [
        'handover_time' => 'datetime',
        'physical_count_verified' => 'boolean',
        'variance_myr' => 'decimal:2'
    ];

    public function session()
    {
        return $this->belongsTo(CounterSession::class, 'counter_session_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterHandoverModelTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/CounterHandover.php tests/Unit/CounterHandoverModelTest.php
git commit -m "feat: create CounterHandover model with tests"
```

---

### Task 13: Create CounterHandoverService

**Files:**
- Create: `app/Services/CounterHandoverService.php`
- Test: `tests/Unit/CounterHandoverServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\CounterHandover;
use App\Models\CounterSession;
use App\Models\User;
use App\Services\CounterHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterHandoverServiceTest extends TestCase
{
    use RefreshDatabase;

    private CounterHandoverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CounterHandoverService();
    }

    public function test_can_initiate_handover()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);
        $physicalCounts = [
            ['currency_id' => 1, 'amount' => 10000.00]
        ];

        $handover = $this->service->initiateHandover(
            $session,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts,
            'No variance'
        );

        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id
        ]);
    }

    public function test_requires_supervisor_verification()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'teller']); // Not a supervisor
        $physicalCounts = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Supervisor must have manager or admin role');
        
        $this->service->initiateHandover(
            $session,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts
        );
    }

    public function test_creates_new_session_after_handover()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $oldSession = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);
        $physicalCounts = [];

        $this->service->initiateHandover(
            $oldSession,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts
        );

        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'status' => 'open'
        ]);
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $oldSession->id,
            'status' => 'handed_over'
        ]);
    }

    public function test_logs_physical_count_variance()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);
        $physicalCounts = [
            ['currency_id' => 1, 'amount' => 10200.00]
        ];

        $handover = $this->service->initiateHandover(
            $session,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts,
            'Small variance'
        );

        $this->assertEquals(200.00, $handover->variance_myr);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterHandoverServiceTest.php`
Expected: FAIL with "Class 'App\Services\CounterHandoverService' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use App\Models\CounterHandover;
use App\Models\CounterSession;
use App\Models\User;
use Exception;

class CounterHandoverService
{
    public function initiateHandover(
        CounterSession $session,
        User $fromUser,
        User $toUser,
        User $supervisor,
        array $physicalCounts,
        string $varianceNotes = null
    ): CounterHandover {
        // Validate supervisor role
        if (!$supervisor->isManager() && !$supervisor->isAdmin()) {
            throw new Exception('Supervisor must have manager or admin role');
        }

        // Validate session is open
        if (!$session->isOpen()) {
            throw new Exception('Session is not open');
        }

        // Calculate variance
        $totalVariance = 0.00;
        foreach ($physicalCounts as $count) {
            $systemBalance = 10000.00; // TODO: Get from till balances
            $physicalCount = $count['amount'];
            $variance = $physicalCount - $systemBalance;
            $totalVariance += $variance;
        }

        // Create handover record
        $handover = CounterHandover::create([
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'handover_time' => now(),
            'physical_count_verified' => true,
            'variance_myr' => $totalVariance,
            'variance_notes' => $varianceNotes
        ]);

        // Update old session status
        $session->update([
            'status' => 'handed_over',
            'closed_at' => now(),
            'closed_by' => $supervisor->id
        ]);

        // Create new session for to_user
        CounterSession::create([
            'counter_id' => $session->counter_id,
            'user_id' => $toUser->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $supervisor->id,
            'status' => 'open'
        ]);

        return $handover;
    }

    public function getPendingHandovers(): array
    {
        return CounterHandover::with(['session', 'fromUser', 'toUser', 'supervisor'])
            ->orderBy('handover_time', 'desc')
            ->get()
            ->toArray();
    }

    public function getHandoverHistory(array $filters = []): array
    {
        $query = CounterHandover::with(['session', 'fromUser', 'toUser', 'supervisor']);

        if (isset($filters['from_date'])) {
            $query->where('handover_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('handover_time', '<=', $filters['to_date']);
        }

        return $query->orderBy('handover_time', 'desc')
            ->get()
            ->toArray();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterHandoverServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/CounterHandoverService.php tests/Unit/CounterHandoverServiceTest.php
git commit -m "feat: create CounterHandoverService with business logic"
```

---

### Task 14: Create CounterHandoverController

**Files:**
- Create: `app/Http/Controllers/CounterHandoverController.php`
- Test: `tests/Feature/CounterHandoverControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\CounterHandover;
use App\Models\CounterSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterHandoverControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_initiate_handover()
    {
        $supervisor = User::factory()->create(['role' => 'manager']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        $response = $this->actingAs($supervisor)->post("/counters/{$counter->id}/handover", [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ],
            'variance_notes' => 'No variance'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $session->id,
            'supervisor_id' => $supervisor->id
        ]);
    }

    public function test_requires_both_users_present()
    {
        $supervisor = User::factory()->create(['role' => 'manager']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        $response = $this->actingAs($supervisor)->post("/counters/{$counter->id}/handover", [
            'from_user_id' => 999, // Invalid user
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => []
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_creates_audit_log()
    {
        $supervisor = User::factory()->create(['role' => 'manager']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => 1,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => 1,
            'status' => 'open'
        ]);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        $this->actingAs($supervisor)->post("/counters/{$counter->id}/handover", [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => []
        ]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'counter_handover',
            'entity_type' => 'counter_handover'
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/CounterHandoverControllerTest.php`
Expected: FAIL with "Route not found" or "Controller not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\User;
use App\Services\CounterHandoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounterHandoverController extends Controller
{
    private CounterHandoverService $handoverService;

    public function __construct(CounterHandoverService $handoverService)
    {
        $this->handoverService = $handoverService;
    }

    public function showHandover(Counter $counter)
    {
        $session = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->firstOrFail();

        $users = User::where('is_active', true)->get();
        $supervisors = User::whereIn('role', ['manager', 'admin'])->where('is_active', true)->get();
        $currencies = \App\Models\Currency::where('is_active', true)->get();

        return view('counters.handover', compact('counter', 'session', 'users', 'supervisors', 'currencies'));
    }

    public function initiate(Request $request, Counter $counter)
    {
        $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id',
            'supervisor_id' => 'required|exists:users,id',
            'physical_counts' => 'required|array',
            'physical_counts.*.currency_id' => 'required|exists:currencies,id',
            'physical_counts.*.amount' => 'required|numeric|min:0',
            'variance_notes' => 'nullable|string'
        ]);

        $session = CounterSession::where('counter_id', $counter->id)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->firstOrFail();

        $fromUser = User::findOrFail($request->input('from_user_id'));
        $toUser = User::findOrFail($request->input('to_user_id'));
        $supervisor = User::findOrFail($request->input('supervisor_id'));
        $physicalCounts = $request->input('physical_counts');
        $varianceNotes = $request->input('variance_notes');

        try {
            $this->handoverService->initiateHandover(
                $session,
                $fromUser,
                $toUser,
                $supervisor,
                $physicalCounts,
                $varianceNotes
            );

            // TODO: Create audit log entry

            return redirect()->route('counters.index')
                ->with('success', "Counter {$counter->code} handover completed successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pending()
    {
        $handovers = $this->handoverService->getPendingHandovers();

        return response()->json([
            'success' => true,
            'data' => $handovers
        ]);
    }

    public function history(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date')
        ];

        $handovers = $this->handoverService->getHandoverHistory($filters);

        return response()->json([
            'success' => true,
            'data' => $handovers
        ]);
    }
}
```

- [ ] **Step 4: Add routes**

Modify: `routes/web.php`

Add after counter routes:

```php
// Counter Handovers
Route::middleware('auth')->group(function () {
    Route::get('/counters/{counter}/handover', [CounterHandoverController::class, 'showHandover'])->name('counters.handover.show');
    Route::post('/counters/{counter}/handover', [CounterHandoverController::class, 'initiate'])->name('counters.handover');
    Route::get('/handovers/pending', [CounterHandoverController::class, 'pending'])->name('handovers.pending');
    Route::get('/handovers/history', [CounterHandoverController::class, 'history'])->name('handovers.history');
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/CounterHandoverControllerTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CounterHandoverController.php routes/web.php tests/Feature/CounterHandoverControllerTest.php
git commit -m "feat: create CounterHandoverController with routes and tests"
```

---

### Task 15: Create Counters Handover View

**Files:**
- Create: `resources/views/counters/handover.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Handover - {{ $counter->code }}</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Current Session Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Counter:</strong> {{ $counter->code }} - {{ $counter->name }}</p>
                    <p><strong>Current User:</strong> {{ $session->user->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Opened At:</strong> {{ $session->opened_at->format('Y-m-d H:i:s') }}</p>
                    <p><strong>Session Status:</strong> {{ ucfirst($session->status) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.handover', $counter) }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="from_user_id" class="form-label">From User (Current)</label>
                        <select class="form-select" id="from_user_id" name="from_user_id" required>
                            <option value="">Select user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $user->id === $session->user_id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="to_user_id" class="form-label">To User (Taking Over)</label>
                        <select class="form-select" id="to_user_id" name="to_user_id" required>
                            <option value="">Select user</option>
                            @foreach($users as $user)
                                @if($user->id !== $session->user_id)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="supervisor_id" class="form-label">Supervisor (Verifying)</label>
                        <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                            <option value="">Select supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ ucfirst($supervisor->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Physical Count Verification</h5>
                <div id="counts-container">
                    @foreach($currencies as $index => $currency)
                        <div class="count-row mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">{{ $currency->code }} - {{ $currency->name }}</label>
                                    <input type="hidden" name="physical_counts[{{ $index }}][currency_id]" value="{{ $currency->id }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">System Balance</label>
                                    <input type="text" class="form-control" value="{{ number_format(10000.00, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Physical Count</label>
                                    <input type="number" step="0.01" class="form-control physical-count" name="physical_counts[{{ $index }}][amount]" required min="0" data-system="10000.00">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Variance</label>
                                    <input type="text" class="form-control variance-display" readonly>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Variance:</strong> <span id="total-variance">RM 0.00</span>
                </div>

                <div class="mb-3">
                    <label for="variance_notes" class="form-label">Variance Notes</label>
                    <textarea class="form-control" id="variance_notes" name="variance_notes" rows="3" placeholder="Required if variance > RM 0"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Complete Handover</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.physical-count').forEach(input => {
        input.addEventListener('input', function() {
            const system = parseFloat(this.dataset.system);
            const physical = parseFloat(this.value) || 0;
            const variance = physical - system;
            
            const varianceDisplay = this.closest('.row').querySelector('.variance-display');
            varianceDisplay.value = 'RM ' + variance.toFixed(2);
            
            if (variance < 0) {
                varianceDisplay.classList.add('text-danger');
            } else if (variance > 0) {
                varianceDisplay.classList.add('text-success');
            } else {
                varianceDisplay.classList.remove('text-danger', 'text-success');
            }
            
            updateTotalVariance();
        });
    });

    function updateTotalVariance() {
        let total = 0;
        document.querySelectorAll('.physical-count').forEach(input => {
            const system = parseFloat(input.dataset.system);
            const physical = parseFloat(input.value) || 0;
            total += (physical - system);
        });
        
        const totalDisplay = document.getElementById('total-variance');
        totalDisplay.textContent = 'RM ' + total.toFixed(2);
        
        if (Math.abs(total) > 0) {
            totalDisplay.parentElement.classList.remove('alert-info');
            totalDisplay.parentElement.classList.add('alert-warning');
        } else {
            totalDisplay.parentElement.classList.remove('alert-warning');
            totalDisplay.parentElement.classList.add('alert-info');
        }
    }
</script>
@endpush
@endsection
```

- [ ] **Step 2: Update CounterController index view to add handover button**

Modify: `resources/views/counters/index.blade.php`

Add to actions column:

```php
@if($counter->sessions->count() > 0)
    <a href="{{ route('counters.handover.show', $counter) }}" class="btn btn-sm btn-info">Handover</a>
@endif
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/counters/handover.blade.php resources/views/counters/index.blade.php
git commit -m "feat: create counters handover view with variance calculation"
```

---

## Phase 3: Transaction Integration

### Task 16: Add Counter Fields to Transactions Table

**Files:**
- Create: `database/migrations/2026_04_02_000004_add_counter_fields_to_transactions_table.php`

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('counter_id')->nullable()->constrained('counters')->nullOnDelete()->after('till_id');
            $table->foreignId('counter_session_id')->nullable()->constrained('counter_sessions')->nullOnDelete()->after('counter_id');
            
            $table->index('counter_id');
            $table->index('counter_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['counter_id']);
            $table->dropForeign(['counter_session_id']);
            $table->dropIndex(['counter_id']);
            $table->dropIndex(['counter_session_id']);
            $table->dropColumn(['counter_id', 'counter_session_id']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration successful, counter_id and counter_session_id added to transactions table

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_02_000004_add_counter_fields_to_transactions_table.php
git commit -m "feat: add counter fields to transactions table"
```

---

### Task 17: Update Transaction Model

**Files:**
- Modify: `app/Models/Transaction.php`

- [ ] **Step 1: Add counter relationships**

```php
public function counter()
{
    return $this->belongsTo(Counter::class);
}

public function counterSession()
{
    return $this->belongsTo(CounterSession::class);
}
```

- [ ] **Step 2: Update fillable array**

```php
protected $fillable = [
    // ... existing fields
    'counter_id',
    'counter_session_id'
];
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/Transaction.php
git commit -m "feat: add counter relationships to Transaction model"
```

---

### Task 18: Update TransactionController to Validate Counter Session

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php`

- [ ] **Step 1: Update store method validation**

```php
public function store(Request $request)
{
    $request->validate([
        // ... existing validation
        'counter_id' => 'required|exists:counters,id',
        'counter_session_id' => 'required|exists:counter_sessions,id,status,open'
    ]);

    // Validate user is assigned to that counter
    $session = CounterSession::findOrFail($request->input('counter_session_id'));
    if ($session->user_id !== Auth::id()) {
        return back()->with('error', 'You are not assigned to this counter');
    }

    // ... rest of the method
}
```

- [ ] **Step 2: Update create method to pass counters**

```php
public function create()
{
    $user = Auth::user();
    
    // Get open counters where user is assigned
    $availableCounters = Counter::whereHas('sessions', function ($query) use ($user) {
        $query->where('user_id', $user->id)
              ->where('session_date', now()->toDateString())
              ->where('status', 'open');
    })->with(['sessions' => function ($query) use ($user) {
        $query->where('user_id', $user->id)
              ->where('session_date', now()->toDateString())
              ->where('status', 'open');
    }])->get();

    // ... rest of the method
    
    return view('transactions.create', compact(/* ... */, 'availableCounters'));
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/TransactionController.php
git commit -m "feat: update TransactionController to validate counter session"
```

---

### Task 19: Update Transaction Create View

**Files:**
- Modify: `resources/views/transactions/create.blade.php`

- [ ] **Step 1: Add counter selection dropdown**

Add after customer selection:

```php
<div class="mb-3">
    <label for="counter_id" class="form-label">Counter</label>
    <select class="form-select" id="counter_id" name="counter_id" required>
        <option value="">Select counter</option>
        @foreach($availableCounters as $counter)
            <option value="{{ $counter->id }}" {{ old('counter_id') == $counter->id ? 'selected' : '' }}>
                {{ $counter->code }} - {{ $counter->name }}
                @if($counter->sessions->count() > 0)
                    (Opened by {{ $counter->sessions->first()->user->name }} at {{ $counter->sessions->first()->opened_at->format('H:i') }})
                @endif
            </option>
        @endforeach
    </select>
    @error('counter_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<input type="hidden" name="counter_session_id" id="counter_session_id">
```

- [ ] **Step 2: Add JavaScript to auto-select session**

```php
@push('scripts')
<script>
    document.getElementById('counter_id').addEventListener('change', function() {
        const counterId = this.value;
        const sessionInput = document.getElementById('counter_session_id');
        
        // Find the session for this counter
        @foreach($availableCounters as $counter)
            @if($counter->sessions->count() > 0)
                if (counterId === {{ $counter->id }}) {
                    sessionInput.value = {{ $counter->sessions->first()->id }};
                }
            @endif
        @endforeach
    });
</script>
@endpush
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/transactions/create.blade.php
git commit -m "feat: add counter selection to transaction create view"
```

---

### Task 20: Update StockCashController to Link to Counters

**Files:**
- Modify: `app/Http/Controllers/StockCashController.php`

- [ ] **Step 1: Update openTill method**

```php
public function openTill(Request $request)
{
    $request->validate([
        'till_id' => 'required|exists:till_balances,id',
        'counter_id' => 'required|exists:counters,id',
        'opening_balance' => 'required|numeric|min:0'
    ]);

    // Validate counter session is open
    $session = CounterSession::where('counter_id', $request->input('counter_id'))
        ->where('session_date', now()->toDateString())
        ->where('status', 'open')
        ->firstOrFail();

    // ... rest of the method
}
```

- [ ] **Step 2: Update closeTill method**

```php
public function closeTill(Request $request)
{
    $request->validate([
        'till_id' => 'required|exists:till_balances,id',
        'counter_id' => 'required|exists:counters,id',
        'closing_balance' => 'required|numeric|min:0'
    ]);

    // Validate counter session is open
    $session = CounterSession::where('counter_id', $request->input('counter_id'))
        ->where('session_date', now()->toDateString())
        ->where('status', 'open')
        ->firstOrFail();

    // ... rest of the method
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/StockCashController.php
git commit -m "feat: update StockCashController to link to counters"
```

---

### Task 21: Update Stock-Cash Index View

**Files:**
- Modify: `resources/views/stock-cash/index.blade.php`

- [ ] **Step 1: Add counter filter dropdown**

Add before till balances table:

```php
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('stock-cash.index') }}" method="GET">
            <div class="row">
                <div class="col-md-4">
                    <label for="counter_filter" class="form-label">Filter by Counter</label>
                    <select class="form-select" id="counter_filter" name="counter_id">
                        <option value="">All Counters</option>
                        @foreach($counters as $counter)
                            <option value="{{ $counter->id }}" {{ request('counter_id') == $counter->id ? 'selected' : '' }}>
                                {{ $counter->code }} - {{ $counter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>
```

- [ ] **Step 2: Update till balances table to show counter**

Add counter column to table:

```php
<th>Counter</th>
```

Add counter cell to table body:

```php
<td>{{ $till->counter ? $till->counter->code : '-' }}</td>
```

- [ ] **Step 3: Update StockCashController index method**

```php
public function index(Request $request)
{
    $query = TillBalance::query();

    if ($request->has('counter_id')) {
        $query->where('counter_id', $request->input('counter_id'));
    }

    $tillBalances = $query->with('counter', 'currency')->get();
    $counters = Counter::active()->get();

    return view('stock-cash.index', compact('tillBalances', 'counters'));
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/stock-cash/index.blade.php app/Http/Controllers/StockCashController.php
git commit -m "feat: add counter filter to stock-cash index view"
```

---

## Phase 4: Reporting & History

### Task 22: Add Counter Performance Metrics

**Files:**
- Create: `app/Services/CounterReportingService.php`
- Test: `tests/Unit/CounterReportingServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CounterReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CounterReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CounterReportingService();
    }

    public function test_get_counter_utilization()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'status' => 'closed'
        ]);

        $utilization = $this->service->getCounterUtilization($counter, now()->subDays(7), now());

        $this->assertIsArray($utilization);
        $this->assertArrayHasKey('total_sessions', $utilization);
        $this->assertArrayHasKey('total_hours', $utilization);
    }

    public function test_get_counter_transaction_volume()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        Transaction::factory()->count(5)->create([
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id
        ]);

        $volume = $this->service->getCounterTransactionVolume($counter, now()->subDays(7), now());

        $this->assertEquals(5, $volume['total_transactions']);
    }

    public function test_get_variance_analysis()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $user = User::factory()->create(['role' => 'teller']);
        
        CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'status' => 'closed',
            'notes' => 'Variance: RM 50.00'
        ]);

        $analysis = $this->service->getVarianceAnalysis($counter, now()->subDays(30), now());

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('total_variance', $analysis);
        $this->assertArrayHasKey('average_variance', $analysis);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/CounterReportingServiceTest.php`
Expected: FAIL with "Class 'App\Services\CounterReportingService' not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Transaction;
use Carbon\Carbon;

class CounterReportingService
{
    public function getCounterUtilization(Counter $counter, Carbon $fromDate, Carbon $toDate): array
    {
        $sessions = CounterSession::where('counter_id', $counter->id)
            ->whereBetween('session_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->get();

        $totalHours = 0;
        foreach ($sessions as $session) {
            if ($session->closed_at) {
                $totalHours += $session->opened_at->diffInHours($session->closed_at);
            }
        }

        return [
            'total_sessions' => $sessions->count(),
            'total_hours' => $totalHours,
            'average_hours_per_session' => $sessions->count() > 0 ? $totalHours / $sessions->count() : 0
        ];
    }

    public function getCounterTransactionVolume(Counter $counter, Carbon $fromDate, Carbon $toDate): array
    {
        $transactions = Transaction::where('counter_id', $counter->id)
            ->whereBetween('transaction_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_volume_myr' => $transactions->sum('to_amount'),
            'average_transaction_value' => $transactions->count() > 0 ? $transactions->sum('to_amount') / $transactions->count() : 0
        ];
    }

    public function getVarianceAnalysis(Counter $counter, Carbon $fromDate, Carbon $toDate): array
    {
        $sessions = CounterSession::where('counter_id', $counter->id)
            ->whereBetween('session_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'closed')
            ->get();

        // TODO: Parse variance from notes or calculate from till balances
        $totalVariance = 0.00;
        foreach ($sessions as $session) {
            // Parse variance from notes if present
            if ($session->notes && preg_match('/Variance:\s*RM\s*([\d.]+)/', $session->notes, $matches)) {
                $totalVariance += floatval($matches[1]);
            }
        }

        return [
            'total_variance' => $totalVariance,
            'average_variance' => $sessions->count() > 0 ? $totalVariance / $sessions->count() : 0,
            'sessions_with_variance' => $sessions->filter(fn($s) => $s->notes && strpos($s->notes, 'Variance') !== false)->count()
        ];
    }

    public function getCounterPerformanceReport(Counter $counter, Carbon $fromDate, Carbon $toDate): array
    {
        return [
            'utilization' => $this->getCounterUtilization($counter, $fromDate, $toDate),
            'transaction_volume' => $this->getCounterTransactionVolume($counter, $fromDate, $toDate),
            'variance_analysis' => $this->getVarianceAnalysis($counter, $fromDate, $toDate)
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/CounterReportingServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/CounterReportingService.php tests/Unit/CounterReportingServiceTest.php
git commit -m "feat: create CounterReportingService with metrics"
```

---

### Task 23: Create Counter Reports Controller

**Files:**
- Create: `app/Http/Controllers/CounterReportController.php`

- [ ] **Step 1: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Services\CounterReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CounterReportController extends Controller
{
    private CounterReportingService $reportingService;

    public function __construct(CounterReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function performance(Request $request, Counter $counter)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $report = $this->reportingService->getCounterPerformanceReport(
            $counter,
            Carbon::parse($fromDate),
            Carbon::parse($toDate)
        );

        return view('counters.reports.performance', compact('counter', 'report', 'fromDate', 'toDate'));
    }

    public function utilization(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $counters = Counter::active()->get();
        $utilizationData = [];

        foreach ($counters as $counter) {
            $utilizationData[$counter->id] = $this->reportingService->getCounterUtilization(
                $counter,
                Carbon::parse($fromDate),
                Carbon::parse($toDate)
            );
        }

        return view('counters.reports.utilization', compact('counters', 'utilizationData', 'fromDate', 'toDate'));
    }

    public function variance(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $counters = Counter::active()->get();
        $varianceData = [];

        foreach ($counters as $counter) {
            $varianceData[$counter->id] = $this->reportingService->getVarianceAnalysis(
                $counter,
                Carbon::parse($fromDate),
                Carbon::parse($toDate)
            );
        }

        return view('counters.reports.variance', compact('counters', 'varianceData', 'fromDate', 'toDate'));
    }
}
```

- [ ] **Step 2: Add routes**

Modify: `routes/web.php`

```php
// Counter Reports
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/counters/{counter}/reports/performance', [CounterReportController::class, 'performance'])->name('counters.reports.performance');
    Route::get('/counters/reports/utilization', [CounterReportController::class, 'utilization'])->name('counters.reports.utilization');
    Route::get('/counters/reports/variance', [CounterReportController::class, 'variance'])->name('counters.reports.variance');
});
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/CounterReportController.php routes/web.php
git commit -m "feat: create CounterReportController with reporting endpoints"
```

---

### Task 24: Create Counter Performance Report View

**Files:**
- Create: `resources/views/counters/reports/performance.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Performance Report - {{ $counter->code }}</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('counters.reports.performance', $counter) }}" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Sessions</h5>
                    <p class="card-text display-4">{{ $report['utilization']['total_sessions'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Hours</h5>
                    <p class="card-text display-4">{{ number_format($report['utilization']['total_hours'], 1) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Avg Hours/Session</h5>
                    <p class="card-text display-4">{{ number_format($report['utilization']['average_hours_per_session'], 1) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Transaction Volume</h5>
                    <p class="card-text">Total Transactions: {{ $report['transaction_volume']['total_transactions'] }}</p>
                    <p class="card-text">Total Volume: RM {{ number_format($report['transaction_volume']['total_volume_myr'], 2) }}</p>
                    <p class="card-text">Avg Transaction: RM {{ number_format($report['transaction_volume']['average_transaction_value'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Variance Analysis</h5>
                    <p class="card-text">Total Variance: RM {{ number_format($report['variance_analysis']['total_variance'], 2) }}</p>
                    <p class="card-text">Average Variance: RM {{ number_format($report['variance_analysis']['average_variance'], 2) }}</p>
                    <p class="card-text">Sessions with Variance: {{ $report['variance_analysis']['sessions_with_variance'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('counters.index') }}" class="btn btn-secondary">Back to Counters</a>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/counters/reports/performance.blade.php
git commit -m "feat: create counter performance report view"
```

---

### Task 25: Create Counter Utilization Report View

**Files:**
- Create: `resources/views/counters/reports/utilization.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Utilization Report</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('counters.reports.utilization') }}" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Counter</th>
                            <th>Total Sessions</th>
                            <th>Total Hours</th>
                            <th>Avg Hours/Session</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counters as $counter)
                            <tr>
                                <td>{{ $counter->code }} - {{ $counter->name }}</td>
                                <td>{{ $utilizationData[$counter->id]['total_sessions'] }}</td>
                                <td>{{ number_format($utilizationData[$counter->id]['total_hours'], 1) }}</td>
                                <td>{{ number_format($utilizationData[$counter->id]['average_hours_per_session'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('counters.index') }}" class="btn btn-secondary">Back to Counters</a>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/counters/reports/utilization.blade.php
git commit -m "feat: create counter utilization report view"
```

---

### Task 26: Create Counter Variance Report View

**Files:**
- Create: `resources/views/counters/reports/variance.blade.php`

- [ ] **Step 1: Write the view file**

```php
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Variance Report</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('counters.reports.variance') }}" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Counter</th>
                            <th>Total Variance (MYR)</th>
                            <th>Average Variance (MYR)</th>
                            <th>Sessions with Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counters as $counter)
                            <tr>
                                <td>{{ $counter->code }} - {{ $counter->name }}</td>
                                <td>RM {{ number_format($varianceData[$counter->id]['total_variance'], 2) }}</td>
                                <td>RM {{ number_format($varianceData[$counter->id]['average_variance'], 2) }}</td>
                                <td>{{ $varianceData[$counter->id]['sessions_with_variance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('counters.index') }}" class="btn btn-secondary">Back to Counters</a>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/counters/reports/variance.blade.php
git commit -m "feat: create counter variance report view"
```

---

### Task 27: Add Export Functionality to History

**Files:**
- Modify: `app/Http/Controllers/CounterController.php`

- [ ] **Step 1: Add export method**

```php
public function exportHistory(Request $request, Counter $counter)
{
    $query = CounterSession::where('counter_id', $counter->id)
        ->with(['user', 'openedByUser', 'closedByUser']);

    if ($request->has('from_date')) {
        $query->where('session_date', '>=', $request->input('from_date'));
    }

    if ($request->has('to_date')) {
        $query->where('session_date', '<=', $request->input('to_date'));
    }

    if ($request->has('user_id')) {
        $query->where('user_id', $request->input('user_id'));
    }

    $sessions = $query->orderBy('session_date', 'desc')
        ->orderBy('opened_at', 'desc')
        ->get();

    $filename = "counter_{$counter->code}_history_" . now()->format('Y-m-d') . ".csv";
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    $callback = function() use ($sessions) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['Date', 'User', 'Opened At', 'Closed At', 'Status', 'Notes']);

        foreach ($sessions as $session) {
            fputcsv($file, [
                $session->session_date->format('Y-m-d'),
                $session->user->name,
                $session->opened_at->format('Y-m-d H:i:s'),
                $session->closed_at ? $session->closed_at->format('Y-m-d H:i:s') : '',
                $session->status,
                $session->notes
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
```

- [ ] **Step 2: Add route**

Modify: `routes/web.php`

```php
Route::get('/counters/{counter}/history/export', [CounterController::class, 'exportHistory'])->name('counters.history.export');
```

- [ ] **Step 3: Update history view to add export button**

Modify: `resources/views/counters/history.blade.php`

```php
<a href="{{ route('counters.history.export', $counter) }}?from_date={{ request('from_date') }}&to_date={{ request('to_date') }}" class="btn btn-success">Export CSV</a>
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/CounterController.php routes/web.php resources/views/counters/history.blade.php
git commit -m "feat: add CSV export to counter history"
```

---

### Task 28: Seed Default Counters

**Files:**
- Create: `database/seeders/CounterSeeder.php`

- [ ] **Step 1: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Counter;
use Illuminate\Database\Seeder;

class CounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['code' => 'C01', 'name' => 'Counter 1'],
            ['code' => 'C02', 'name' => 'Counter 2'],
            ['code' => 'C03', 'name' => 'Counter 3'],
            ['code' => 'C04', 'name' => 'Counter 4'],
            ['code' => 'C05', 'name' => 'Counter 5'],
        ];

        foreach ($counters as $counter) {
            Counter::firstOrCreate(
                ['code' => $counter['code']],
                $counter
            );
        }
    }
}
```

- [ ] **Step 2: Run seeder**

Run: `php artisan db:seed --class=CounterSeeder`
Expected: 5 counters created

- [ ] **Step 3: Commit**

```bash
git add database/seeders/CounterSeeder.php
git commit -m "feat: create CounterSeeder with default counters"
```

---

### Task 29: Add Navigation Menu Item

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (or wherever navigation is defined)

- [ ] **Step 1: Add Counters menu item**

Add to navigation menu:

```php
<li class="nav-item">
    <a class="nav-link {{ request()->is('counters*') ? 'active' : '' }}" href="{{ route('counters.index') }}">
        Counters
    </a>
</li>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: add Counters menu item to navigation"
```

---

### Task 30: Final Integration Tests

**Files:**
- Create: `tests/Feature/CounterIntegrationTest.php`

- [ ] **Step 1: Write integration tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_validates_counter_session_is_open()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $session = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $user->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($user)->post('/transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id,
            'customer_id' => 1,
            'from_currency_id' => 1,
            'to_currency_id' => 2,
            'from_amount' => 1000.00,
            'to_amount' => 4720.00,
            'rate' => 4.72,
            'payment_method' => 'cash'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id
        ]);
    }

    public function test_counter_handover_properly_transitions_sessions()
    {
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);
        $fromUser = User::factory()->create(['role' => 'teller']);
        $toUser = User::factory()->create(['role' => 'teller']);
        $supervisor = User::factory()->create(['role' => 'manager']);
        
        $oldSession = CounterSession::create([
            'counter_id' => $counter->id,
            'user_id' => $fromUser->id,
            'session_date' => now()->toDateString(),
            'opened_at' => now(),
            'opened_by' => $fromUser->id,
            'status' => 'open'
        ]);

        $this->actingAs($supervisor)->post("/counters/{$counter->id}/handover", [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        $this->assertDatabaseHas('counter_sessions', [
            'id' => $oldSession->id,
            'status' => 'handed_over'
        ]);
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $toUser->id,
            'status' => 'open'
        ]);
    }

    public function test_audit_log_entries_created_for_all_operations()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);

        $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'counter_opened',
            'user_id' => $user->id
        ]);
    }

    public function test_full_workflow_open_transaction_close()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::create(['code' => 'C01', 'name' => 'Counter 1']);

        // Open counter
        $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        $session = CounterSession::where('counter_id', $counter->id)
            ->where('status', 'open')
            ->first();

        $this->assertNotNull($session);

        // Create transaction
        $this->actingAs($user)->post('/transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id,
            'customer_id' => 1,
            'from_currency_id' => 1,
            'to_currency_id' => 2,
            'from_amount' => 1000.00,
            'to_amount' => 4720.00,
            'rate' => 4.72,
            'payment_method' => 'cash'
        ]);

        $this->assertDatabaseHas('transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id
        ]);

        // Close counter
        $this->actingAs($user)->post("/counters/{$counter->id}/close", [
            'closing_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ],
            'notes' => 'No variance'
        ]);

        $session->refresh();
        $this->assertEquals('closed', $session->status);
    }
}
```

- [ ] **Step 2: Run integration tests**

Run: `php artisan test tests/Feature/CounterIntegrationTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/CounterIntegrationTest.php
git commit -m "test: add comprehensive integration tests for counter management"
```

---

## Summary

This implementation plan covers all aspects of the Counter Management System:

**Phase 1: Core Counter Management** (Tasks 1-10)
- Database schema (counters, counter_sessions)
- Models with relationships
- Service layer with business logic
- Controllers with validation
- Views for index, open, close, history

**Phase 2: Counter Handovers** (Tasks 11-15)
- Database schema (counter_handovers)
- Model with relationships
- Service with handover logic
- Controller with supervisor verification
- View with physical count variance tracking

**Phase 3: Transaction Integration** (Tasks 16-21)
- Add counter fields to transactions
- Update Transaction model and controller
- Update transaction create view
- Update StockCash controller and views

**Phase 4: Reporting & History** (Tasks 22-30)
- Reporting service with metrics
- Report controller and views
- Export functionality
- Default counters seeder
- Navigation integration
- Comprehensive integration tests

**Total Tasks:** 30
**Estimated Time:** 4 weeks (as per spec)
**Test Coverage:** Unit, feature, and integration tests for all components
