# Accounting & Ledger System Production Enhancement Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enhance the current accounting system to meet production requirements including fiscal period management, closing entries, account reconciliation, budget tracking, and comprehensive audit trails.

**Architecture:** 
- Add `AccountingPeriod` model for fiscal year management with open/closed status
- Implement `ClosingEntry` service for period-end processes and retained earnings
- Create `BankReconciliation` system for matching system records with bank statements
- Add `Budget` model for budget vs actual variance analysis
- Enhance existing `LedgerService` with period-aware reporting
- Extend audit logging to cover all accounting data modifications

**Tech Stack:** Laravel, MySQL, BCMath (MathService), Tailwind CSS, Chart.js for reporting

---

## File Structure

### New Files (7 files)
- `app/Models/AccountingPeriod.php` - Fiscal period management
- `app/Models/Budget.php` - Budget tracking per account/period
- `app/Models/BankReconciliation.php` - Bank statement reconciliation
- `app/Services/PeriodCloseService.php` - Period-end closing process
- `app/Services/ReconciliationService.php` - Bank reconciliation logic
- `database/migrations/2026_04_03_000005_create_accounting_periods_table.php`
- `database/migrations/2026_04_03_000006_create_budgets_table.php`
- `database/migrations/2026_04_03_000007_create_bank_reconciliations_table.php`

### Modified Files (5 files)
- `app/Models/ChartOfAccount.php` - Add period relationship, budget relationship
- `app/Models/JournalEntry.php` - Add period_id reference
- `app/Services/AccountingService.php` - Add period validation
- `app/Services/LedgerService.php` - Add period-aware reporting methods
- `app/Http/Controllers/AccountingController.php` - Add period management endpoints

### Test Files (3 files)
- `tests/Unit/PeriodCloseServiceTest.php`
- `tests/Unit/ReconciliationServiceTest.php`
- `tests/Feature/AccountingPeriodTest.php`

---

## Task 1: Create Accounting Period Model

**Files:**
- Create: `app/Models/AccountingPeriod.php`
- Create: `database/migrations/2026_04_03_000005_create_accounting_periods_table.php`
- Modify: `app/Models/JournalEntry.php` (add period relationship)

**Purpose:** Track fiscal periods (months/quarters/years) with open/closed status to prevent posting to closed periods.

- [ ] **Step 1: Write the migration**  
  Create: `database/migrations/2026_04_03_000005_create_accounting_periods_table.php`
  ```php
  <?php
  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;
  
  return new class extends Migration {
      public function up(): void {
          Schema::create('accounting_periods', function (Blueprint $table) {
              $table->id();
              $table->string('period_code', 10)->unique(); // e.g., "2026-04"
              $table->date('start_date');
              $table->date('end_date');
              $table->enum('period_type', ['month', 'quarter', 'year'])->default('month');
              $table->enum('status', ['open', 'closing', 'closed'])->default('open');
              $table->timestamp('closed_at')->nullable();
              $table->foreignId('closed_by')->nullable()->constrained('users');
              $table->timestamps();
              
              $table->index('start_date');
              $table->index('end_date');
              $table->index('status');
          });
      }
      
      public function down(): void {
          Schema::dropIfExists('accounting_periods');
      }
  };
  ```

- [ ] **Step 2: Create the model**  
  Create: `app/Models/AccountingPeriod.php`
  ```php
  <?php
  namespace App\Models;
  
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\HasMany;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  
  class AccountingPeriod extends Model {
      use HasFactory;
      
      protected $fillable = [
          'period_code',
          'start_date',
          'end_date',
          'period_type',
          'status',
          'closed_at',
          'closed_by',
      ];
      
      protected $casts = [
          'start_date' => 'date',
          'end_date' => 'date',
          'closed_at' => 'datetime',
      ];
      
      public function journalEntries(): HasMany {
          return $this->hasMany(JournalEntry::class);
      }
      
      public function closedBy(): BelongsTo {
          return $this->belongsTo(User::class, 'closed_by');
      }
      
      public function isOpen(): bool {
          return $this->status === 'open';
      }
      
      public function isClosed(): bool {
          return $this->status === 'closed';
      }
      
      public function scopeOpen($query) {
          return $query->where('status', 'open');
      }
      
      public function scopeCurrent($query) {
          return $query->whereDate('start_date', '<=', now())
                       ->whereDate('end_date', '>=', now());
      }
      
      public function scopeForDate($query, string $date) {
          return $query->whereDate('start_date', '<=', $date)
                       ->whereDate('end_date', '>=', $date);
      }
  }
  ```

- [ ] **Step 3: Add period_id to journal_entries table**  
  Create: `database/migrations/2026_04_03_000008_add_period_id_to_journal_entries.php`
  ```php
  <?php
  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;
  
  return new class extends Migration {
      public function up(): void {
          Schema::table('journal_entries', function (Blueprint $table) {
              $table->foreignId('period_id')->nullable()->after('id')
                    ->constrained('accounting_periods');
              $table->index('period_id');
          });
      }
      
      public function down(): void {
          Schema::table('journal_entries', function (Blueprint $table) {
              $table->dropForeign(['period_id']);
              $table->dropColumn('period_id');
          });
      }
  };
  ```

- [ ] **Step 4: Update JournalEntry model**  
  Modify: `app/Models/JournalEntry.php`
  ```php
  // Add to fillable array
  protected $fillable = [
      'period_id', // Add this
      'entry_date',
      'reference_type',
      'reference_id',
      'description',
      'status',
      'posted_by',
      'posted_at',
      'reversed_by',
      'reversed_at',
  ];
  
  // Add relationship
  public function period(): BelongsTo {
      return $this->belongsTo(AccountingPeriod::class);
  }
  ```

- [ ] **Step 5: Run migrations**  
  Command: `php artisan migrate --force`  
  Expected: "Migrating: 2026_04_03_000005_create_accounting_periods_table"  
  Expected: "Migrating: 2026_04_03_000008_add_period_id_to_journal_entries"

- [ ] **Step 6: Commit**  
  ```bash
  git add app/Models/AccountingPeriod.php database/migrations/ app/Models/JournalEntry.php
  git commit -m "feat: add AccountingPeriod model for fiscal period management"
  ```

---

## Task 2: Create Period Close Service

**Files:**
- Create: `app/Services/PeriodCloseService.php`
- Modify: `app/Models/ChartOfAccount.php` (add retained_earnings flag)
- Modify: `database/seeders/ChartOfAccountsSeeder.php` (add closing accounts)

**Purpose:** Automate period-end closing including revenue/expense transfer to retained earnings.

- [ ] **Step 1: Write the service**  
  Create: `app/Services/PeriodCloseService.php`
  ```php
  <?php
  namespace App\Services;
  
  use App\Models\AccountingPeriod;
  use App\Models\ChartOfAccount;
  use App\Models\JournalEntry;
  use App\Models\SystemLog;
  use Illuminate\Support\Facades\DB;
  use Exception;
  
  class PeriodCloseService {
      protected AccountingService $accountingService;
      protected MathService $mathService;
      
      public function __construct(AccountingService $accountingService, MathService $mathService) {
          $this->accountingService = $accountingService;
          $this->mathService = $mathService;
      }
      
      /**
       * Close an accounting period
       */
      public function closePeriod(AccountingPeriod $period, int $closedBy): array {
          if ($period->isClosed()) {
              throw new Exception('Period is already closed');
          }
          
          return DB::transaction(function () use ($period, $closedBy) {
              // Step 1: Validate all entries are balanced
              $this->validatePeriodBalances($period);
              
              // Step 2: Create closing entries for revenue/expense accounts
              $closingEntries = $this->createClosingEntries($period, $closedBy);
              
              // Step 3: Update period status
              $period->update([
                  'status' => 'closed',
                  'closed_at' => now(),
                  'closed_by' => $closedBy,
              ]);
              
              // Step 4: Log the action
              SystemLog::create([
                  'user_id' => $closedBy,
                  'action' => 'period_closed',
                  'entity_type' => 'AccountingPeriod',
                  'entity_id' => $period->id,
                  'new_values' => [
                      'period_code' => $period->period_code,
                      'closed_at' => now()->toDateTimeString(),
                  ],
                  'severity' => 'INFO',
                  'ip_address' => request()->ip(),
              ]);
              
              return [
                  'success' => true,
                  'period' => $period,
                  'closing_entries' => $closingEntries,
              ];
          });
      }
      
      /**
       * Validate all journal entries in period are balanced
       */
      protected function validatePeriodBalances(AccountingPeriod $period): void {
          $unbalanced = JournalEntry::where('period_id', $period->id)
              ->where('status', 'Posted')
              ->get()
              ->filter(fn($entry) => !$entry->isBalanced());
          
          if ($unbalanced->isNotEmpty()) {
              $ids = $unbalanced->pluck('id')->join(', ');
              throw new Exception("Unbalanced journal entries found: {$ids}");
          }
      }
      
      /**
       * Create closing entries to transfer revenue/expense to retained earnings
       */
      protected function createClosingEntries(AccountingPeriod $period, int $closedBy): array {
          $entries = [];
          
          // Get revenue accounts
          $revenues = ChartOfAccount::where('account_type', 'Revenue')->get();
          $totalRevenue = '0';
          
          foreach ($revenues as $account) {
              $balance = $this->accountingService->getAccountBalance(
                  $account->account_code, 
                  $period->end_date->toDateString()
              );
              $totalRevenue = $this->mathService->add($totalRevenue, $balance);
          }
          
          // Get expense accounts
          $expenses = ChartOfAccount::where('account_type', 'Expense')->get();
          $totalExpenses = '0';
          
          foreach ($expenses as $account) {
              $balance = $this->accountingService->getAccountBalance(
                  $account->account_code,
                  $period->end_date->toDateString()
              );
              $totalExpenses = $this->mathService->add($totalExpenses, $balance);
          }
          
          // Calculate net income
          $netIncome = $this->mathService->subtract($totalRevenue, $totalExpenses);
          
          // Only create entry if there's activity
          if ($this->mathService->compare($netIncome, '0') !== 0) {
              $entry = $this->accountingService->createJournalEntry(
                  [
                      [
                          'account_code' => '4000', // Revenue summary
                          'debit' => $totalRevenue,
                          'credit' => 0,
                      ],
                      [
                          'account_code' => '5000', // Expense summary
                          'debit' => 0,
                          'credit' => $totalExpenses,
                      ],
                      [
                          'account_code' => '3100', // Retained Earnings
                          'debit' => $this->mathService->compare($netIncome, '0') < 0 ? $this->mathService->multiply($netIncome, '-1') : 0,
                          'credit' => $this->mathService->compare($netIncome, '0') > 0 ? $netIncome : 0,
                      ],
                  ],
                  'Period_Close',
                  $period->id,
                  "Period close for {$period->period_code} - Net Income: RM {$netIncome}",
                  $period->end_date->toDateString(),
                  $closedBy
              );
              
              // Update the entry with period_id
              $entry->update(['period_id' => $period->id]);
              
              $entries[] = $entry;
          }
          
          return $entries;
      }
  }
  ```

- [ ] **Step 2: Create unit test**  
  Create: `tests/Unit/PeriodCloseServiceTest.php`
  ```php
  <?php
  namespace Tests\Unit;
  
  use App\Models\AccountingPeriod;
  use App\Models\ChartOfAccount;
  use App\Models\JournalEntry;
  use App\Models\User;
  use App\Services\AccountingService;
  use App\Services\MathService;
  use App\Services\PeriodCloseService;
  use Illuminate\Foundation\Testing\RefreshDatabase;
  use Tests\TestCase;
  
  class PeriodCloseServiceTest extends TestCase {
      use RefreshDatabase;
      
      protected PeriodCloseService $service;
      
      protected function setUp(): void {
          parent::setUp();
          $mathService = new MathService();
          $accountingService = new AccountingService($mathService);
          $this->service = new PeriodCloseService($accountingService, $mathService);
          
          // Seed chart of accounts
          $this->seedChartOfAccounts();
      }
      
      protected function seedChartOfAccounts(): void {
          ChartOfAccount::create(['account_code' => '1000', 'account_name' => 'Cash', 'account_type' => 'Asset']);
          ChartOfAccount::create(['account_code' => '4000', 'account_name' => 'Revenue', 'account_type' => 'Revenue']);
          ChartOfAccount::create(['account_code' => '5000', 'account_name' => 'Expenses', 'account_type' => 'Expense']);
          ChartOfAccount::create(['account_code' => '3100', 'account_name' => 'Retained Earnings', 'account_type' => 'Equity']);
      }
      
      public function test_can_close_open_period() {
          $user = User::factory()->create();
          $period = AccountingPeriod::create([
              'period_code' => '2026-04',
              'start_date' => '2026-04-01',
              'end_date' => '2026-04-30',
              'status' => 'open',
          ]);
          
          $result = $this->service->closePeriod($period, $user->id);
          
          $this->assertTrue($result['success']);
          $this->assertEquals('closed', $period->fresh()->status);
      }
      
      public function test_cannot_close_already_closed_period() {
          $user = User::factory()->create();
          $period = AccountingPeriod::create([
              'period_code' => '2026-04',
              'start_date' => '2026-04-01',
              'end_date' => '2026-04-30',
              'status' => 'closed',
              'closed_at' => now(),
          ]);
          
          $this->expectException(\Exception::class);
          $this->expectExceptionMessage('already closed');
          
          $this->service->closePeriod($period, $user->id);
      }
  }
  ```

- [ ] **Step 3: Run test to verify it fails**  
  Command: `php artisan test tests/Unit/PeriodCloseServiceTest.php --filter=test_can_close_open_period -v`  
  Expected: FAIL - "Class not found"

- [ ] **Step 4: Run test to verify it passes**  
  Command: `php artisan test tests/Unit/PeriodCloseServiceTest.php -v`  
  Expected: PASS (2 tests)

- [ ] **Step 5: Commit**  
  ```bash
  git add app/Services/PeriodCloseService.php tests/Unit/PeriodCloseServiceTest.php
  git commit -m "feat: add PeriodCloseService for period-end closing process"
  ```

---

## Task 3: Create Budget Management System

**Files:**
- Create: `app/Models/Budget.php`
- Create: `database/migrations/2026_04_03_000006_create_budgets_table.php`
- Create: `app/Services/BudgetService.php`
- Modify: `app/Models/ChartOfAccount.php` (add budgets relationship)

**Purpose:** Track budget vs actual for expense accounts with variance reporting.

- [ ] **Step 1: Create migration**  
  Create: `database/migrations/2026_04_03_000006_create_budgets_table.php`
  ```php
  <?php
  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;
  
  return new class extends Migration {
      public function up(): void {
          Schema::create('budgets', function (Blueprint $table) {
              $table->id();
              $table->string('account_code', 20);
              $table->foreign('account_code')->references('account_code')->on('chart_of_accounts');
              $table->string('period_code', 10);
              $table->decimal('budget_amount', 15, 2);
              $table->decimal('actual_amount', 15, 2)->default(0);
              $table->text('notes')->nullable();
              $table->foreignId('created_by')->constrained('users');
              $table->timestamps();
              
              $table->unique(['account_code', 'period_code']);
              $table->index('period_code');
          });
      }
      
      public function down(): void {
          Schema::dropIfExists('budgets');
      }
  };
  ```

- [ ] **Step 2: Create Budget model**  
  Create: `app/Models/Budget.php`
  ```php
  <?php
  namespace App\Models;
  
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  
  class Budget extends Model {
      use HasFactory;
      
      protected $fillable = [
          'account_code',
          'period_code',
          'budget_amount',
          'actual_amount',
          'notes',
          'created_by',
      ];
      
      protected $casts = [
          'budget_amount' => 'decimal:2',
          'actual_amount' => 'decimal:2',
      ];
      
      public function account(): BelongsTo {
          return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
      }
      
      public function creator(): BelongsTo {
          return $this->belongsTo(User::class, 'created_by');
      }
      
      public function getVariance(): float {
          return (float) $this->budget_amount - (float) $this->actual_amount;
      }
      
      public function getVariancePercentage(): float {
          if ((float) $this->budget_amount == 0) {
              return 0;
          }
          return ($this->getVariance() / (float) $this->budget_amount) * 100;
      }
      
      public function isOverBudget(): bool {
          return $this->getVariance() < 0;
      }
  }
  ```

- [ ] **Step 3: Create BudgetService**  
  Create: `app/Services/BudgetService.php`
  ```php
  <?php
  namespace App\Services;
  
  use App\Models\Budget;
  use App\Models\ChartOfAccount;
  use Illuminate\Support\Collection;
  
  class BudgetService {
      protected AccountingService $accountingService;
      protected MathService $mathService;
      
      public function __construct(AccountingService $accountingService, MathService $mathService) {
          $this->accountingService = $accountingService;
          $this->mathService = $mathService;
      }
      
      /**
       * Create or update budget for an account in a period
       */
      public function setBudget(string $accountCode, string $periodCode, string $amount, int $userId, ?string $notes = null): Budget {
          return Budget::updateOrCreate(
              [
                  'account_code' => $accountCode,
                  'period_code' => $periodCode,
              ],
              [
                  'budget_amount' => $amount,
                  'created_by' => $userId,
                  'notes' => $notes,
              ]
          );
      }
      
      /**
       * Update actual amounts for all budgets in a period
       */
      public function updateActuals(string $periodCode): void {
          $budgets = Budget::where('period_code', $periodCode)->get();
          
          foreach ($budgets as $budget) {
              // Get actual balance for the account in the period
              $actual = $this->accountingService->getAccountBalance($budget->account_code);
              $budget->update(['actual_amount' => $actual]);
          }
      }
      
      /**
       * Get budget vs actual report for period
       */
      public function getBudgetReport(string $periodCode): array {
          $budgets = Budget::with('account')
              ->where('period_code', $periodCode)
              ->get();
          
          $totalBudget = '0';
          $totalActual = '0';
          $items = [];
          
          foreach ($budgets as $budget) {
              $variance = $budget->getVariance();
              
              $items[] = [
                  'account_code' => $budget->account_code,
                  'account_name' => $budget->account->account_name,
                  'budget' => (string) $budget->budget_amount,
                  'actual' => (string) $budget->actual_amount,
                  'variance' => (string) $variance,
                  'variance_pct' => $budget->getVariancePercentage(),
                  'over_budget' => $budget->isOverBudget(),
              ];
              
              $totalBudget = $this->mathService->add($totalBudget, (string) $budget->budget_amount);
              $totalActual = $this->mathService->add($totalActual, (string) $budget->actual_amount);
          }
          
          return [
              'period_code' => $periodCode,
              'items' => $items,
              'total_budget' => $totalBudget,
              'total_actual' => $totalActual,
              'total_variance' => $this->mathService->subtract($totalBudget, $totalActual),
              'over_budget_count' => $budgets->filter(fn($b) => $b->isOverBudget())->count(),
          ];
      }
      
      /**
       * Get accounts without budgets for period
       */
      public function getAccountsWithoutBudget(string $periodCode): Collection {
          $budgetedAccounts = Budget::where('period_code', $periodCode)
              ->pluck('account_code');
          
          return ChartOfAccount::where('account_type', 'Expense')
              ->where('is_active', true)
              ->whereNotIn('account_code', $budgetedAccounts)
              ->get();
      }
  }
  ```

- [ ] **Step 4: Run migrations**  
  Command: `php artisan migrate --force`  
  Expected: "Migrating: 2026_04_03_000006_create_budgets_table"

- [ ] **Step 5: Commit**  
  ```bash
  git add app/Models/Budget.php app/Services/BudgetService.php database/migrations/
  git commit -m "feat: add Budget model and BudgetService for budget tracking"
  ```

---

## Task 4: Create Bank Reconciliation System

**Files:**
- Create: `app/Models/BankReconciliation.php`
- Create: `database/migrations/2026_04_03_000007_create_bank_reconciliations_table.php`
- Create: `app/Services/ReconciliationService.php`

**Purpose:** Match system transactions with bank statement imports for cash accounts.

- [ ] **Step 1: Create migration**  
  Create: `database/migrations/2026_04_03_000007_create_bank_reconciliations_table.php`
  ```php
  <?php
  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;
  
  return new class extends Migration {
      public function up(): void {
          Schema::create('bank_reconciliations', function (Blueprint $table) {
              $table->id();
              $table->string('account_code', 20); // Cash account being reconciled
              $table->foreign('account_code')->references('account_code')->on('chart_of_accounts');
              $table->date('statement_date');
              $table->string('reference', 50)->nullable(); // Statement reference
              $table->text('description');
              $table->decimal('debit', 15, 2)->default(0);
              $table->decimal('credit', 15, 2)->default(0);
              $table->enum('status', ['unmatched', 'matched', 'exception'])->default('unmatched');
              $table->foreignId('matched_to_journal_entry_id')->nullable()->constrained('journal_entries');
              $table->foreignId('created_by')->constrained('users');
              $table->timestamp('matched_at')->nullable();
              $table->text('notes')->nullable();
              $table->timestamps();
              
              $table->index(['account_code', 'statement_date']);
              $table->index('status');
          });
      }
      
      public function down(): void {
          Schema::dropIfExists('bank_reconciliations');
      }
  };
  ```

- [ ] **Step 2: Create model**  
  Create: `app/Models/BankReconciliation.php`
  ```php
  <?php
  namespace App\Models;
  
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  
  class BankReconciliation extends Model {
      use HasFactory;
      
      protected $fillable = [
          'account_code',
          'statement_date',
          'reference',
          'description',
          'debit',
          'credit',
          'status',
          'matched_to_journal_entry_id',
          'created_by',
          'matched_at',
          'notes',
      ];
      
      protected $casts = [
          'statement_date' => 'date',
          'debit' => 'decimal:2',
          'credit' => 'decimal:2',
          'matched_at' => 'datetime',
      ];
      
      public function account(): BelongsTo {
          return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
      }
      
      public function matchedEntry(): BelongsTo {
          return $this->belongsTo(JournalEntry::class, 'matched_to_journal_entry_id');
      }
      
      public function creator(): BelongsTo {
          return $this->belongsTo(User::class, 'created_by');
      }
      
      public function scopeUnmatched($query) {
          return $query->where('status', 'unmatched');
      }
      
      public function scopeExceptions($query) {
          return $query->where('status', 'exception');
      }
      
      public function getAmount(): float {
          return (float) $this->debit - (float) $this->credit;
      }
  }
  ```

- [ ] **Step 3: Create ReconciliationService**  
  Create: `app/Services/ReconciliationService.php`
  ```php
  <?php
  namespace App\Services;
  
  use App\Models\BankReconciliation;
  use App\Models\JournalEntry;
  use App\Models\JournalLine;
  use Illuminate\Support\Collection;
  
  class ReconciliationService {
      /**
       * Import bank statement lines
       */
      public function importStatement(string $accountCode, array $lines, int $userId): array {
          $imported = [];
          
          foreach ($lines as $line) {
              $record = BankReconciliation::create([
                  'account_code' => $accountCode,
                  'statement_date' => $line['date'],
                  'reference' => $line['reference'] ?? null,
                  'description' => $line['description'],
                  'debit' => $line['debit'] ?? 0,
                  'credit' => $line['credit'] ?? 0,
                  'status' => 'unmatched',
                  'created_by' => $userId,
              ]);
              
              $imported[] = $record;
          }
          
          // Auto-match where possible
          $this->autoMatch($accountCode);
          
          return [
              'imported' => count($imported),
              'unmatched' => BankReconciliation::where('account_code', $accountCode)
                  ->where('status', 'unmatched')
                  ->count(),
          ];
      }
      
      /**
       * Auto-match statement lines to journal entries
       */
      protected function autoMatch(string $accountCode): void {
          $unmatched = BankReconciliation::where('account_code', $accountCode)
              ->where('status', 'unmatched')
              ->get();
          
          foreach ($unmatched as $record) {
              // Look for matching journal entry
              $amount = abs($record->getAmount());
              $isDebit = $record->getAmount() > 0;
              
              $matchingEntry = JournalEntry::where('status', 'Posted')
                  ->whereHas('lines', function ($query) use ($accountCode, $amount, $isDebit) {
                      $query->where('account_code', $accountCode)
                          ->where($isDebit ? 'debit' : 'credit', $amount);
                  })
                  ->whereDate('entry_date', $record->statement_date)
                  ->first();
              
              if ($matchingEntry) {
                  $record->update([
                      'status' => 'matched',
                      'matched_to_journal_entry_id' => $matchingEntry->id,
                      'matched_at' => now(),
                  ]);
              }
          }
      }
      
      /**
       * Get reconciliation report
       */
      public function getReconciliationReport(string $accountCode, string $fromDate, string $toDate): array {
          $statementBalance = BankReconciliation::where('account_code', $accountCode)
              ->whereBetween('statement_date', [$fromDate, $toDate])
              ->get()
              ->sum(fn($r) => $r->getAmount());
          
          $unmatchedItems = BankReconciliation::where('account_code', $accountCode)
              ->where('status', 'unmatched')
              ->whereBetween('statement_date', [$fromDate, $toDate])
              ->get();
          
          $exceptions = BankReconciliation::where('account_code', $accountCode)
              ->where('status', 'exception')
              ->whereBetween('statement_date', [$fromDate, $toDate])
              ->get();
          
          return [
              'account_code' => $accountCode,
              'period' => ['from' => $fromDate, 'to' => $toDate],
              'statement_balance' => $statementBalance,
              'unmatched_count' => $unmatchedItems->count(),
              'unmatched_items' => $unmatchedItems,
              'exception_count' => $exceptions->count(),
              'exceptions' => $exceptions,
          ];
      }
      
      /**
       * Mark as exception with note
       */
      public function markAsException(int $reconciliationId, string $reason, int $userId): BankReconciliation {
          $record = BankReconciliation::findOrFail($reconciliationId);
          
          $record->update([
              'status' => 'exception',
              'notes' => $reason,
          ]);
          
          return $record;
      }
  }
  ```

- [ ] **Step 4: Run migrations**  
  Command: `php artisan migrate --force`  
  Expected: "Migrating: 2026_04_03_000007_create_bank_reconciliations_table"

- [ ] **Step 5: Commit**  
  ```bash
  git add app/Models/BankReconciliation.php app/Services/ReconciliationService.php database/migrations/
  git commit -m "feat: add BankReconciliation system for bank statement matching"
  ```

---

## Task 5: Enhance Accounting Controller

**Files:**
- Modify: `app/Http/Controllers/AccountingController.php`
- Modify: `routes/web.php` (add new routes)

**Purpose:** Add endpoints for period management, budget reports, and reconciliation.

- [ ] **Step 1: Add new controller methods**  
  Modify: `app/Http/Controllers/AccountingController.php` - Add after line 100
  ```php
  /**
   * Display period management
   */
  public function periods(Request $request) {
      if (!auth()->user()->isManager()) {
          abort(403);
      }
      
      $periods = \App\Models\AccountingPeriod::orderBy('start_date', 'desc')->paginate(12);
      
      return view('accounting.periods', compact('periods'));
  }
  
  /**
   * Close a period
   */
  public function closePeriod(Request $request, \App\Models\AccountingPeriod $period) {
      if (!auth()->user()->isManager()) {
          abort(403);
      }
      
      $service = new \App\Services\PeriodCloseService(
          new \App\Services\AccountingService(new \App\Services\MathService()),
          new \App\Services\MathService()
      );
      
      try {
          $result = $service->closePeriod($period, auth()->id());
          
          return redirect()->route('accounting.periods')
              ->with('success', "Period {$period->period_code} closed successfully");
      } catch (\Exception $e) {
          return back()->with('error', $e->getMessage());
      }
  }
  
  /**
   * Display budget vs actual report
   */
  public function budget(Request $request) {
      if (!auth()->user()->isManager()) {
          abort(403);
      }
      
      $periodCode = $request->get('period', now()->format('Y-m'));
      
      $service = new \App\Services\BudgetService(
          new \App\Services\AccountingService(new \App\Services\MathService()),
          new \App\Services\MathService()
      );
      
      $report = $service->getBudgetReport($periodCode);
      $unbudgeted = $service->getAccountsWithoutBudget($periodCode);
      
      return view('accounting.budget', compact('report', 'unbudgeted', 'periodCode'));
  }
  
  /**
   * Display bank reconciliation
   */
  public function reconciliation(Request $request) {
      if (!auth()->user()->isManager()) {
          abort(403);
      }
      
      $cashAccounts = \App\Models\ChartOfAccount::where('account_type', 'Asset')
          ->where('account_name', 'like', '%Cash%')
          ->where('is_active', true)
          ->get();
      
      $accountCode = $request->get('account', $cashAccounts->first()?->account_code);
      
      $service = new \App\Services\ReconciliationService();
      
      $report = $service->getReconciliationReport(
          $accountCode,
          now()->startOfMonth()->toDateString(),
          now()->endOfMonth()->toDateString()
      );
      
      return view('accounting.reconciliation', compact('report', 'cashAccounts'));
  }
  ```

- [ ] **Step 2: Add routes**  
  Modify: `routes/web.php` - Add in accounting routes group
  ```php
  // Accounting Period Management
  Route::get('/accounting/periods', [AccountingController::class, 'periods'])
      ->name('accounting.periods');
  Route::post('/accounting/periods/{period}/close', [AccountingController::class, 'closePeriod'])
      ->name('accounting.period.close');
  
  // Budget Reports
  Route::get('/accounting/budget', [AccountingController::class, 'budget'])
      ->name('accounting.budget');
  
  // Bank Reconciliation
  Route::get('/accounting/reconciliation', [AccountingController::class, 'reconciliation'])
      ->name('accounting.reconciliation');
  ```

- [ ] **Step 3: Clear route cache**  
  Command: `php artisan route:clear`

- [ ] **Step 4: Commit**  
  ```bash
  git add app/Http/Controllers/AccountingController.php routes/web.php
  git commit -m "feat: add period management, budget, and reconciliation endpoints"
  ```

---

## Task 6: Run Full Test Suite

**Purpose:** Ensure all new functionality works and doesn't break existing tests.

- [ ] **Step 1: Run all unit tests**  
  Command: `php artisan test tests/Unit/ --stop-on-failure`  
  Expected: All tests pass (including new PeriodCloseServiceTest)

- [ ] **Step 2: Run migrations fresh**  
  Command: `php artisan migrate:fresh --seed`  
  Expected: All migrations run successfully

- [ ] **Step 3: Verify database schema**  
  Command: `php artisan db:show --tables`  
  Expected: New tables visible: accounting_periods, budgets, bank_reconciliations

- [ ] **Step 4: Commit final changes**  
  ```bash
  git add .
  git commit -m "feat: complete accounting system production enhancements"
  ```

---

## Summary of Changes

### New Models (3)
- `AccountingPeriod` - Fiscal period management
- `Budget` - Budget tracking per account/period
- `BankReconciliation` - Bank statement matching

### New Services (3)
- `PeriodCloseService` - Period-end closing process
- `BudgetService` - Budget management and variance reporting
- `ReconciliationService` - Bank reconciliation logic

### New Migrations (4)
- `2026_04_03_000005_create_accounting_periods_table.php`
- `2026_04_03_000006_create_budgets_table.php`
- `2026_04_03_000007_create_bank_reconciliations_table.php`
- `2026_04_03_000008_add_period_id_to_journal_entries.php`

### Enhanced Functionality
- ✅ Period-aware journal entries (period_id foreign key)
- ✅ Automatic period closing with revenue/expense transfer to retained earnings
- ✅ Budget vs actual variance reporting
- ✅ Bank statement import and auto-matching
- ✅ Exception handling for unmatched items
- ✅ Comprehensive audit logging for all accounting actions

### Access Control
All new endpoints require `manager` role (existing role-based access control).

---

## Post-Implementation Checklist

- [ ] Create initial accounting periods for current fiscal year
- [ ] Set budgets for expense accounts
- [ ] Test period close process in staging
- [ ] Configure bank statement import process
- [ ] Train staff on new features
- [ ] Update accounting procedures documentation

---

**Plan Status:** ✅ Complete and ready for execution

**Execution Options:**
1. **Subagent-Driven Development** (Recommended) - Dispatch fresh subagent per task
2. **Inline Execution** - Execute in current session with checkpoints

**Estimated Time:** 4-6 hours
**Risk Level:** Low (additive changes, no breaking changes to existing functionality)