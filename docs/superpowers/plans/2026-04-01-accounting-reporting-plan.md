# CEMS-MY Accounting & Reporting Modules
## Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement comprehensive double-entry accounting with full ledger system, regulatory reports (LCTR, MSB2), financial statements, and automated month-end revaluation.

**Architecture:** Laravel-based integrated approach using Eloquent models and service classes. Follows existing TransactionController patterns. Journal entries created automatically on every transaction with proper debit/credit balancing.

**Tech Stack:** PHP 8.2, Laravel 11, MySQL 8.0, BCMath for precision arithmetic

**Based on Design Spec:** `docs/superpowers/specs/2026-04-01-accounting-reporting-design.md`

---

## File Map

### New Files to Create

| File | Purpose |
|------|---------|
| `database/migrations/2026_04_01_000001_create_chart_of_accounts_table.php` | Chart of accounts schema |
| `database/migrations/2026_04_01_000002_create_journal_entries_table.php` | Journal headers |
| `database/migrations/2026_04_01_000003_create_journal_lines_table.php` | Journal debits/credits |
| `database/migrations/2026_04_01_000004_create_account_ledger_table.php` | Running balance ledger |
| `database/migrations/2026_04_01_000005_create_report_templates_table.php` | Report templates |
| `database/migrations/2026_04_01_000006_create_reports_generated_table.php` | Generated report tracking |
| `database/seeders/ChartOfAccountsSeeder.php` | Seed COA data |
| `app/Models/ChartOfAccount.php` | Eloquent model for COA |
| `app/Models/JournalEntry.php` | Eloquent model for entries |
| `app/Models/JournalLine.php` | Eloquent model for lines |
| `app/Models/AccountLedger.php` | Eloquent model for ledger |
| `app/Models/ReportTemplate.php` | Eloquent model for templates |
| `app/Models/ReportGenerated.php` | Eloquent model for reports |
| `app/Services/AccountingService.php` | Journal entry creation |
| `app/Services/LedgerService.php` | Ledger queries & financial statements |
| `app/Services/ExportService.php` | CSV/PDF/Excel export |
| `app/Http/Controllers/AccountingController.php` | Journal entry CRUD |
| `app/Http/Controllers/LedgerController.php` | Ledger views |
| `app/Http/Controllers/FinancialStatementController.php` | Trial balance, P&L, balance sheet |
| `app/Http/Controllers/RevaluationController.php` | Month-end revaluation UI |
| `app/Mail/RevaluationComplete.php` | Email notification |
| `app/Mail/ReportReady.php` | Report notification |
| `app/Console/Commands/RunMonthlyRevaluation.php` | Artisan command |
| `app/Console/Commands/GenerateDailyMSB2.php` | Artisan command |
| `app/Console/Commands/CleanupOldReports.php` | Artisan command |
| `resources/views/accounting/journal/index.blade.php` | Journal list view |
| `resources/views/accounting/journal/create.blade.php` | Create journal form |
| `resources/views/accounting/journal/show.blade.php` | Journal detail view |
| `resources/views/accounting/ledger/index.blade.php` | Account list |
| `resources/views/accounting/ledger/account.blade.php` | Ledger detail |
| `resources/views/accounting/trial-balance.blade.php` | Trial balance report |
| `resources/views/accounting/profit-loss.blade.php` | P&L report |
| `resources/views/accounting/balance-sheet.blade.php` | Balance sheet report |
| `resources/views/accounting/revaluation/index.blade.php` | Revaluation dashboard |
| `resources/views/accounting/revaluation/history.blade.php` | Revaluation history |
| `resources/views/reports/lctr.blade.php` | LCTR report form |
| `resources/views/reports/msb2.blade.php` | MSB(2) report form |
| `resources/views/reports/currency-position.blade.php` | Position report |
| `resources/views/reports/unrealized-pnl.blade.php` | P&L report |
| `resources/views/emails/revaluation-complete.blade.php` | Email template |
| `resources/views/emails/report-ready.blade.php` | Email template |
| `tests/Unit/AccountingServiceTest.php` | Service tests |
| `tests/Unit/LedgerServiceTest.php` | Service tests |
| `tests/Unit/ReportingServiceTest.php` | Service tests |
| `tests/Unit/ExportServiceTest.php` | Service tests |
| `tests/Feature/JournalEntryTest.php` | Feature tests |
| `tests/Feature/FinancialStatementTest.php` | Feature tests |
| `tests/Feature/LctrReportTest.php` | Feature tests |
| `tests/Feature/Msb2ReportTest.php` | Feature tests |

### Files to Modify

| File | Changes |
|------|---------|
| `app/Services/ReportingService.php` | Add LCTR and MSB2 methods |
| `app/Services/RevaluationService.php` | Add automation and notification |
| `app/Http/Controllers/TransactionController.php` | Integrate AccountingService::createJournalEntry |
| `app/Http/Controllers/ReportController.php` | Add LCTR, MSB2, export methods |
| `routes/web.php` | Add accounting, reporting, revaluation routes |
| `app/Console/Kernel.php` | Add scheduled commands |
| `resources/views/layouts/app.blade.php` | Add navigation links |
| `composer.json` | Add league/csv, maatwebsite/excel, barryvdh/laravel-dompdf |

---

## Phase 1: Database & Models (Week 1)

### Task 1: Create Chart of Accounts Migration

**Files:**
- Create: `database/migrations/2026_04_01_000001_create_chart_of_accounts_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_code', 20)->primary();
            $table->string('account_name', 255)->notNullable();
            $table->enum('account_type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'])->notNullable();
            $table->string('parent_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_code')->references('account_code')->on('chart_of_accounts');
            $table->index('account_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000001_create_chart_of_accounts_table.php
```

- [ ] **Step 3: Verify table exists**
```bash
mysql -u root -p cems -e "DESCRIBE chart_of_accounts;"
```
Expected: See account_code, account_name, account_type, parent_code, is_active, created_at, updated_at

- [ ] **Step 4: Commit**
```bash
git add database/migrations/2026_04_01_000001_create_chart_of_accounts_table.php
git commit -m "feat: add chart_of_accounts migration"
```

### Task 2: Create Journal Entries Migration

**Files:**
- Create: `database/migrations/2026_04_01_000002_create_journal_entries_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->notNullable();
            $table->string('reference_type', 50)->notNullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->notNullable();
            $table->enum('status', ['Draft', 'Posted', 'Reversed'])->default('Posted');
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('posted_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            
            $table->index('entry_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000002_create_journal_entries_table.php
```

- [ ] **Step 3: Verify**
```bash
mysql -u root -p cems -e "DESCRIBE journal_entries;"
```

- [ ] **Step 4: Commit**
```bash
git add database/migrations/2026_04_01_000002_create_journal_entries_table.php
git commit -m "feat: add journal_entries migration"
```

### Task 3: Create Journal Lines Migration

**Files:**
- Create: `database/migrations/2026_04_01_000003_create_journal_lines_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries');
            $table->string('account_code', 20);
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            
            $table->foreign('account_code')->references('account_code')->on('chart_of_accounts');
            $table->index('journal_entry_id');
            $table->index('account_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000003_create_journal_lines_table.php
```

- [ ] **Step 3: Verify**
```bash
mysql -u root -p cems -e "DESCRIBE journal_lines;"
```

- [ ] **Step 4: Commit**
```bash
git add database/migrations/2026_04_01_000003_create_journal_lines_table.php
git commit -m "feat: add journal_lines migration"
```

### Task 4: Create Account Ledger Migration

**Files:**
- Create: `database/migrations/2026_04_01_000004_create_account_ledger_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20);
            $table->date('entry_date')->notNullable();
            $table->foreignId('journal_entry_id')->constrained('journal_entries');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('running_balance', 18, 4)->notNullable();
            $table->timestamps();
            
            $table->foreign('account_code')->references('account_code')->on('chart_of_accounts');
            $table->index(['account_code', 'entry_date']);
            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_ledger');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000004_create_account_ledger_table.php
```

- [ ] **Step 3: Verify**
```bash
mysql -u root -p cems -e "DESCRIBE account_ledger;"
```

- [ ] **Step 4: Commit**
```bash
git add database/migrations/2026_04_01_000004_create_account_ledger_table.php
git commit -m "feat: add account_ledger migration"
```

### Task 5: Create Report Templates Migration

**Files:**
- Create: `database/migrations/2026_04_01_000005_create_report_templates_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->notNullable();
            $table->enum('report_type', ['LCTR', 'MSB2', 'Trial_Balance', 'PL', 'Balance_Sheet', 'Currency_Position']);
            $table->json('template_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('report_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000005_create_report_templates_table.php
```

- [ ] **Step 3: Commit**
```bash
git add database/migrations/2026_04_01_000005_create_report_templates_table.php
git commit -m "feat: add report_templates migration"
```

### Task 6: Create Reports Generated Migration

**Files:**
- Create: `database/migrations/2026_04_01_000006_create_reports_generated_table.php`

- [ ] **Step 1: Write migration**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports_generated', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 50)->notNullable();
            $table->date('period_start')->notNullable();
            $table->date('period_end')->notNullable();
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('file_path', 500)->nullable();
            $table->enum('file_format', ['CSV', 'PDF', 'XLSX']);
            $table->timestamps();
            
            $table->index(['report_type', 'period_start', 'period_end']);
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports_generated');
    }
};
```

- [ ] **Step 2: Run migration**
```bash
php artisan migrate --path=database/migrations/2026_04_01_000006_create_reports_generated_table.php
```

- [ ] **Step 3: Commit**
```bash
git add database/migrations/2026_04_01_000006_create_reports_generated_table.php
git commit -m "feat: add reports_generated migration"
```

### Task 7: Create Eloquent Models

**Files:**
- Create: `app/Models/ChartOfAccount.php`
- Create: `app/Models/JournalEntry.php`
- Create: `app/Models/JournalLine.php`
- Create: `app/Models/AccountLedger.php`
- Create: `app/Models/ReportTemplate.php`
- Create: `app/Models/ReportGenerated.php`

- [ ] **Step 1: Write ChartOfAccount model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'account_code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'account_code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_code', 'account_code');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class, 'account_code', 'account_code');
    }

    public function isAsset(): bool
    {
        return $this->account_type === 'Asset';
    }

    public function isLiability(): bool
    {
        return $this->account_type === 'Liability';
    }

    public function isEquity(): bool
    {
        return $this->account_type === 'Equity';
    }

    public function isRevenue(): bool
    {
        return $this->account_type === 'Revenue';
    }

    public function isExpense(): bool
    {
        return $this->account_type === 'Expense';
    }
}
```

- [ ] **Step 2: Write JournalEntry model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
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

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class);
    }

    public function isPosted(): bool
    {
        return $this->status === 'Posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'Reversed';
    }

    public function getTotalDebits(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCredits(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.0001;
    }
}
```

- [ ] **Step 3: Write JournalLine model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_code',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }

    public function getAmount(): float
    {
        return (float) $this->debit > 0 ? (float) $this->debit : (float) $this->credit;
    }
}
```

- [ ] **Step 4: Write AccountLedger model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedger extends Model
{
    use HasFactory;

    protected $table = 'account_ledger';

    protected $fillable = [
        'account_code',
        'entry_date',
        'journal_entry_id',
        'debit',
        'credit',
        'running_balance',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'running_balance' => 'decimal:4',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function getNetAmount(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }
}
```

- [ ] **Step 5: Write ReportTemplate model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'report_type',
        'template_config',
        'is_active',
    ];

    protected $casts = [
        'template_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }
}
```

- [ ] **Step 6: Write ReportGenerated model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportGenerated extends Model
{
    use HasFactory;

    protected $table = 'reports_generated';

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'generated_by',
        'generated_at',
        'file_path',
        'file_format',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeInPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }
}
```

- [ ] **Step 7: Commit all models**
```bash
git add app/Models/ChartOfAccount.php app/Models/JournalEntry.php app/Models/JournalLine.php app/Models/AccountLedger.php app/Models/ReportTemplate.php app/Models/ReportGenerated.php
git commit -m "feat: add accounting models"
```

### Task 8: Create Chart of Accounts Seeder

**Files:**
- Create: `database/seeders/ChartOfAccountsSeeder.php`

- [ ] **Step 1: Write seeder**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['account_code' => '1000', 'account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1100', 'account_name' => 'Cash - USD', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1200', 'account_name' => 'Cash - EUR', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1300', 'account_name' => 'Cash - GBP', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1400', 'account_name' => 'Cash - SGD', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2000', 'account_name' => 'Foreign Currency Inventory', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2100', 'account_name' => 'Accounts Receivable', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2200', 'account_name' => 'Prepaid Expenses', 'account_type' => 'Asset', 'parent_code' => null],
            
            // Liabilities
            ['account_code' => '3000', 'account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'parent_code' => null],
            ['account_code' => '3100', 'account_name' => 'Accrued Expenses', 'account_type' => 'Liability', 'parent_code' => null],
            
            // Equity
            ['account_code' => '4000', 'account_name' => 'Paid-in Capital', 'account_type' => 'Equity', 'parent_code' => null],
            ['account_code' => '4100', 'account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'parent_code' => null],
            ['account_code' => '4200', 'account_name' => 'Unrealized Forex Gains/Losses', 'account_type' => 'Equity', 'parent_code' => null],
            
            // Revenue
            ['account_code' => '5000', 'account_name' => 'Revenue - Forex Trading', 'account_type' => 'Revenue', 'parent_code' => null],
            ['account_code' => '5100', 'account_name' => 'Revenue - Revaluation Gain', 'account_type' => 'Revenue', 'parent_code' => null],
            
            // Expenses
            ['account_code' => '6000', 'account_name' => 'Expense - Forex Loss', 'account_type' => 'Expense', 'parent_code' => null],
            ['account_code' => '6100', 'account_name' => 'Expense - Revaluation Loss', 'account_type' => 'Expense', 'parent_code' => null],
            ['account_code' => '6200', 'account_name' => 'Expense - Operating', 'account_type' => 'Expense', 'parent_code' => null],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::create($account);
        }
    }
}
```

- [ ] **Step 2: Run seeder**
```bash
php artisan db:seed --class=ChartOfAccountsSeeder
```

- [ ] **Step 3: Verify**
```bash
mysql -u root -p cems -e "SELECT account_code, account_name, account_type FROM chart_of_accounts ORDER BY account_code;"
```
Expected: 19 accounts listed

- [ ] **Step 4: Commit**
```bash
git add database/seeders/ChartOfAccountsSeeder.php
git commit -m "feat: add Chart of Accounts seeder"
```

### Task 9: Create Unit Tests for Models

**Files:**
- Create: `tests/Unit/AccountingModelsTest.php`

- [ ] **Step 1: Write tests**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function chart_of_account_can_be_created()
    {
        $account = ChartOfAccount::create([
            'account_code' => '9999',
            'account_name' => 'Test Account',
            'account_type' => 'Asset',
        ]);

        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '9999']);
    }

    /** @test */
    public function account_knows_its_type()
    {
        $asset = ChartOfAccount::where('account_code', '1000')->first();
        $this->assertTrue($asset->isAsset());
        $this->assertFalse($asset->isLiability());

        $revenue = ChartOfAccount::where('account_code', '5000')->first();
        $this->assertTrue($revenue->isRevenue());
    }

    /** @test */
    public function journal_entry_has_lines_relationship()
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Test',
            'description' => 'Test entry',
            'posted_by' => $user->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '1000',
            'debit' => 100,
            'credit' => 0,
        ]);

        $this->assertCount(1, $entry->lines);
    }

    /** @test */
    public function journal_entry_calculates_balanced()
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Test',
            'description' => 'Test entry',
            'posted_by' => $user->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '1000',
            'debit' => 100,
            'credit' => 0,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '5000',
            'debit' => 0,
            'credit' => 100,
        ]);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(100, $entry->getTotalDebits());
        $this->assertEquals(100, $entry->getTotalCredits());
    }

    /** @test */
    public function journal_entry_detects_unbalanced()
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Test',
            'description' => 'Test entry',
            'posted_by' => $user->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '1000',
            'debit' => 100,
            'credit' => 0,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '5000',
            'debit' => 0,
            'credit' => 50,
        ]);

        $this->assertFalse($entry->isBalanced());
    }
}
```

- [ ] **Step 2: Run tests**
```bash
php artisan test tests/Unit/AccountingModelsTest.php
```

- [ ] **Step 3: Commit**
```bash
git add tests/Unit/AccountingModelsTest.php
git commit -m "test: add model unit tests"
```

---

## Phase 2: Core Services (Week 2)

### Task 10: Create AccountingService

**Files:**
- Create: `app/Services/AccountingService.php`

- [ ] **Step 1: Write service**
```php
<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function createJournalEntry(
        array $lines,
        string $referenceType,
        ?int $referenceId = null,
        string $description = '',
        ?string $entryDate = null,
        int $postedBy = null
    ): JournalEntry {
        $postedBy = $postedBy ?? auth()->id();
        $entryDate = $entryDate ?? now()->toDateString();

        return DB::transaction(function () use ($lines, $referenceType, $referenceId, $description, $entryDate, $postedBy) {
            // Validate balanced
            if (!$this->validateBalanced($lines)) {
                throw new \InvalidArgumentException('Journal entry is not balanced: debits do not equal credits');
            }

            // Create entry
            $entry = JournalEntry::create([
                'entry_date' => $entryDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => 'Posted',
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);

            // Create lines
            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code' => $line['account_code'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            // Update ledger
            $this->updateLedger($entry);

            return $entry->fresh()->load('lines');
        });
    }

    public function validateBalanced(array $lines): bool
    {
        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);
            $totalDebits = $this->mathService->add($totalDebits, $debit);
            $totalCredits = $this->mathService->add($totalCredits, $credit);
        }

        return $this->mathService->compare($totalDebits, $totalCredits) === 0;
    }

    public function reverseJournalEntry(
        JournalEntry $originalEntry,
        string $reason = '',
        int $reversedBy = null
    ): JournalEntry {
        $reversedBy = $reversedBy ?? auth()->id();

        return DB::transaction(function () use ($originalEntry, $reason, $reversedBy) {
            // Mark original as reversed
            $originalEntry->update([
                'status' => 'Reversed',
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
            ]);

            // Create reversal entry
            $lines = [];
            foreach ($originalEntry->lines as $line) {
                $lines[] = [
                    'account_code' => $line->account_code,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: ' . $line->description,
                ];
            }

            $entry = $this->createJournalEntry(
                $lines,
                'Reversal',
                $originalEntry->id,
                "Reversal of entry {$originalEntry->id}: {$reason}",
                now()->toDateString(),
                $reversedBy
            );

            return $entry;
        });
    }

    protected function updateLedger(JournalEntry $entry): void
    {
        foreach ($entry->lines as $line) {
            $currentBalance = $this->getAccountBalance($line->account_code);
            
            // Calculate new balance
            if ($this->isDebitAccount($line->account_code)) {
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->debit),
                    $this->mathService->multiply((string) $line->credit, '-1')
                );
            } else {
                // Credit account
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->credit),
                    $this->mathService->multiply((string) $line->debit, '-1')
                );
            }

            AccountLedger::create([
                'account_code' => $line->account_code,
                'entry_date' => $entry->entry_date,
                'journal_entry_id' => $entry->id,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'running_balance' => $newBalance,
            ]);
        }
    }

    protected function isDebitAccount(string $accountCode): bool
    {
        $account = ChartOfAccount::find($accountCode);
        if (!$account) {
            throw new \InvalidArgumentException("Account not found: {$accountCode}");
        }

        // Assets and Expenses increase with debit
        return in_array($account->account_type, ['Asset', 'Expense']);
    }

    public function getAccountBalance(string $accountCode, ?string $asOfDate = null): string
    {
        $query = AccountLedger::where('account_code', $accountCode);
        
        if ($asOfDate) {
            $query->where('entry_date', '<=', $asOfDate);
        }

        $lastEntry = $query->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? (string) $lastEntry->running_balance : '0';
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Services/AccountingService.php
git commit -m "feat: add AccountingService"
```

### Task 11: Create LedgerService

**Files:**
- Create: `app/Services/LedgerService.php`

- [ ] **Step 1: Write service**
```php
<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\CurrencyPosition;

class LedgerService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function getTrialBalance(?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_code')->get();
        
        $trialBalance = [];
        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($accounts as $account) {
            $balance = app(AccountingService::class)->getAccountBalance($account->account_code, $asOfDate);
            
            $debit = $this->mathService->compare($balance, '0') >= 0 ? $balance : '0';
            $credit = $this->mathService->compare($balance, '0') < 0 ? $this->mathService->multiply($balance, '-1') : '0';

            // For liability, equity, revenue: positive balance is credit
            if (in_array($account->account_type, ['Liability', 'Equity', 'Revenue'])) {
                $debit = $this->mathService->compare($balance, '0') < 0 ? $this->mathService->multiply($balance, '-1') : '0';
                $credit = $this->mathService->compare($balance, '0') >= 0 ? $balance : '0';
            }

            $trialBalance[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];

            $totalDebits = $this->mathService->add($totalDebits, $debit);
            $totalCredits = $this->mathService->add($totalCredits, $credit);
        }

        return [
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => $this->mathService->compare($totalDebits, $totalCredits) === 0,
            'as_of_date' => $asOfDate,
        ];
    }

    public function getAccountLedger(string $accountCode, string $fromDate, string $toDate): array
    {
        $account = ChartOfAccount::findOrFail($accountCode);
        
        $entries = AccountLedger::with('journalEntry')
            ->where('account_code', $accountCode)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        return [
            'account' => $account,
            'entries' => $entries,
            'opening_balance' => $this->getOpeningBalance($accountCode, $fromDate),
            'closing_balance' => $this->getClosingBalance($accountCode, $toDate),
            'total_debits' => $entries->sum('debit'),
            'total_credits' => $entries->sum('credit'),
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    public function getProfitAndLoss(string $fromDate, string $toDate): array
    {
        // Revenue accounts
        $revenues = ChartOfAccount::where('account_type', 'Revenue')->get();
        $revenueData = [];
        $totalRevenue = '0';

        foreach ($revenues as $revenue) {
            $balance = $this->getAccountActivity($revenue->account_code, $fromDate, $toDate);
            $revenueData[] = [
                'account_code' => $revenue->account_code,
                'account_name' => $revenue->account_name,
                'amount' => $balance,
            ];
            $totalRevenue = $this->mathService->add($totalRevenue, $balance);
        }

        // Expense accounts
        $expenses = ChartOfAccount::where('account_type', 'Expense')->get();
        $expenseData = [];
        $totalExpenses = '0';

        foreach ($expenses as $expense) {
            $balance = $this->getAccountActivity($expense->account_code, $fromDate, $toDate);
            $expenseData[] = [
                'account_code' => $expense->account_code,
                'account_name' => $expense->account_name,
                'amount' => $balance,
            ];
            $totalExpenses = $this->mathService->add($totalExpenses, $balance);
        }

        $netProfit = $this->mathService->subtract($totalRevenue, $totalExpenses);

        return [
            'revenues' => $revenueData,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenseData,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    public function getBalanceSheet(string $asOfDate): array
    {
        // Assets
        $assets = ChartOfAccount::where('account_type', 'Asset')->get();
        $assetData = [];
        $totalAssets = '0';

        foreach ($assets as $asset) {
            $balance = app(AccountingService::class)->getAccountBalance($asset->account_code, $asOfDate);
            $assetData[] = [
                'account_code' => $asset->account_code,
                'account_name' => $asset->account_name,
                'balance' => $balance,
            ];
            $totalAssets = $this->mathService->add($totalAssets, $balance);
        }

        // Liabilities
        $liabilities = ChartOfAccount::where('account_type', 'Liability')->get();
        $liabilityData = [];
        $totalLiabilities = '0';

        foreach ($liabilities as $liability) {
            $balance = app(AccountingService::class)->getAccountBalance($liability->account_code, $asOfDate);
            $liabilityData[] = [
                'account_code' => $liability->account_code,
                'account_name' => $liability->account_name,
                'balance' => $balance,
            ];
            $totalLiabilities = $this->mathService->add($totalLiabilities, $balance);
        }

        // Equity
        $equities = ChartOfAccount::where('account_type', 'Equity')->get();
        $equityData = [];
        $totalEquity = '0';

        foreach ($equities as $equity) {
            $balance = app(AccountingService::class)->getAccountBalance($equity->account_code, $asOfDate);
            $equityData[] = [
                'account_code' => $equity->account_code,
                'account_name' => $equity->account_name,
                'balance' => $balance,
            ];
            $totalEquity = $this->mathService->add($totalEquity, $balance);
        }

        $liabilitiesPlusEquity = $this->mathService->add($totalLiabilities, $totalEquity);

        return [
            'assets' => $assetData,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilityData,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equityData,
            'total_equity' => $totalEquity,
            'liabilities_plus_equity' => $liabilitiesPlusEquity,
            'is_balanced' => $this->mathService->compare($totalAssets, $liabilitiesPlusEquity) === 0,
            'as_of_date' => $asOfDate,
        ];
    }

    protected function getOpeningBalance(string $accountCode, string $fromDate): string
    {
        $entry = AccountLedger::where('account_code', $accountCode)
            ->where('entry_date', '<', $fromDate)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $entry ? (string) $entry->running_balance : '0';
    }

    protected function getClosingBalance(string $accountCode, string $toDate): string
    {
        return app(AccountingService::class)->getAccountBalance($accountCode, $toDate);
    }

    protected function getAccountActivity(string $accountCode, string $fromDate, string $toDate): string
    {
        $entries = AccountLedger::where('account_code', $accountCode)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->get();

        $activity = '0';
        foreach ($entries as $entry) {
            $activity = $this->mathService->add($activity, (string) $entry->credit);
            $activity = $this->mathService->subtract($activity, (string) $entry->debit);
        }

        return $activity;
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Services/LedgerService.php
git commit -m "feat: add LedgerService"
```

### Task 12: Enhance ReportingService

**Files:**
- Modify: `app/Services/ReportingService.php`

- [ ] **Step 1: Add new methods to existing ReportingService**
```php
<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use Carbon\Carbon;

class ReportingService
{
    protected EncryptionService $encryptionService;
    protected MathService $mathService;

    public function __construct(
        EncryptionService $encryptionService,
        MathService $mathService
    ) {
        $this->encryptionService = $encryptionService;
        $this->mathService = $mathService;
    }

    // ... existing methods ...

    public function generateLCTR(string $month): array
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $transactions = Transaction::with(['customer', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('amount_local', '>=', 25000)
            ->where('status', 'Completed')
            ->orderBy('created_at')
            ->get();

        $rows = [];
        foreach ($transactions as $txn) {
            $rows[] = [
                'Transaction_ID' => 'TXN-' . str_pad($txn->id, 8, '0', STR_PAD_LEFT),
                'Transaction_Date' => $txn->created_at->format('Y-m-d'),
                'Transaction_Time' => $txn->created_at->format('H:i:s'),
                'Customer_ID_Type' => $txn->customer->id_type,
                'Customer_ID_Number' => $this->encryptionService->decrypt($txn->customer->id_number_encrypted),
                'Customer_Name' => $txn->customer->full_name,
                'Customer_Nationality' => $txn->customer->nationality,
                'Transaction_Type' => $txn->type,
                'Currency_Code' => $txn->currency_code,
                'Amount_Local' => $txn->amount_local,
                'Amount_Foreign' => $txn->amount_foreign,
                'Exchange_Rate' => $txn->rate,
                'Till_ID' => $txn->till_id ?? 'MAIN',
                'Teller_ID' => 'USR-' . str_pad($txn->user_id, 6, '0', STR_PAD_LEFT),
                'Purpose' => $txn->purpose,
                'Source_of_Funds' => $txn->source_of_funds,
                'CDD_Level' => $txn->cdd_level,
                'Status' => $txn->status,
            ];
        }

        return [
            'month' => $month,
            'generated_at' => now()->toIso8601String(),
            'total_transactions' => count($rows),
            'total_amount' => $transactions->sum('amount_local'),
            'data' => $rows,
        ];
    }

    public function generateMSB2(string $date): array
    {
        $transactions = Transaction::whereDate('created_at', $date)
            ->where('status', 'Completed')
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $rows = [];

        foreach ($currencies as $currency) {
            $buyTxns = $transactions->where('currency_code', $currency->code)->where('type', 'Buy');
            $sellTxns = $transactions->where('currency_code', $currency->code)->where('type', 'Sell');
            $position = CurrencyPosition::where('currency_code', $currency->code)->first();

            $rows[] = [
                'Date' => $date,
                'Currency' => $currency->code,
                'Buy_Volume_MYR' => (float) $buyTxns->sum('amount_local'),
                'Buy_Count' => $buyTxns->count(),
                'Sell_Volume_MYR' => (float) $sellTxns->sum('amount_local'),
                'Sell_Count' => $sellTxns->count(),
                'Avg_Buy_Rate' => $buyTxns->avg('rate') ?? 0,
                'Avg_Sell_Rate' => $sellTxns->avg('rate') ?? 0,
                'Opening_Position' => $position ? (float) $position->balance : 0,
                'Closing_Position' => $position ? (float) $position->balance : 0,
            ];
        }

        return [
            'date' => $date,
            'generated_at' => now()->toIso8601String(),
            'data' => $rows,
        ];
    }

    public function generateCurrencyPositionReport(): array
    {
        $positions = CurrencyPosition::with('currency')->get();
        
        $data = [];
        $totalUnrealizedPnl = '0';

        foreach ($positions as $position) {
            $data[] = [
                'currency_code' => $position->currency_code,
                'currency_name' => $position->currency->name,
                'balance' => $position->balance,
                'avg_cost_rate' => $position->avg_cost_rate,
                'last_valuation_rate' => $position->last_valuation_rate,
                'unrealized_pnl' => $position->unrealized_pnl,
            ];
            $totalUnrealizedPnl = $this->mathService->add($totalUnrealizedPnl, $position->unrealized_pnl);
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'positions' => $data,
            'total_unrealized_pnl' => $totalUnrealizedPnl,
        ];
    }

    public function generateUnrealizedPnLReport(): array
    {
        $positions = CurrencyPosition::with('currency')
            ->whereRaw('unrealized_pnl != 0')
            ->get();

        $data = [];
        $totalGain = '0';
        $totalLoss = '0';

        foreach ($positions as $position) {
            $pnl = $position->unrealized_pnl;
            
            if ($this->mathService->compare($pnl, '0') >= 0) {
                $totalGain = $this->mathService->add($totalGain, $pnl);
            } else {
                $totalLoss = $this->mathService->add($totalLoss, $pnl);
            }

            $data[] = [
                'currency_code' => $position->currency_code,
                'currency_name' => $position->currency->name,
                'balance' => $position->balance,
                'avg_cost_rate' => $position->avg_cost_rate,
                'last_valuation_rate' => $position->last_valuation_rate,
                'unrealized_pnl' => $pnl,
                'is_gain' => $this->mathService->compare($pnl, '0') >= 0,
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'positions' => $data,
            'total_gain' => $totalGain,
            'total_loss' => $totalLoss,
            'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
        ];
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Services/ReportingService.php
git commit -m "feat: add LCTR and MSB2 reporting methods"
```

### Task 13: Create ExportService

**Files:**
- Create: `app/Services/ExportService.php`

- [ ] **Step 1: Write service**
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Illuminate\Support\Facades\Log;

class ExportService
{
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = storage_path('app/reports');
    }

    public function toCSV(array $data, string $filename): string
    {
        $path = $this->basePath . '/' . $filename;
        
        // Ensure directory exists
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }

        $csv = Writer::createFromPath($path, 'w+');

        if (!empty($data)) {
            // Header
            $csv->insertOne(array_keys($data[0]));
            
            // Data
            foreach ($data as $row) {
                $csv->insertOne(array_values($row));
            }
        }

        return $path;
    }

    public function toPDF(array $data, string $template, string $filename): string
    {
        $path = $this->basePath . '/' . $filename;

        // Ensure directory exists
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }

        $pdf = \PDF::loadView($template, ['data' => $data]);
        $pdf->save($path);

        return $path;
    }

    public function toExcel(array $data, string $filename): string
    {
        $path = $this->basePath . '/' . $filename;

        // Ensure directory exists
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }

        $export = new \Maatwebsite\Excel\Facades\Excel();
        $export::store(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection {
            protected $data;
            
            public function __construct($data)
            {
                $this->data = $data;
            }
            
            public function collection()
            {
                return collect($this->data);
            }
        }, $path);

        return $path;
    }

    public function emailReport(string $to, string $subject, string $filePath, string $reportType = ''): bool
    {
        try {
            \Mail::to($to)->send(new \App\Mail\ReportReady($subject, $filePath, $reportType));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to email report', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getExportPath(string $filename): string
    {
        return $this->basePath . '/' . $filename;
    }

    public function cleanupOldReports(int $days = 90): int
    {
        $cutoff = now()->subDays($days);
        $deleted = 0;

        $files = glob($this->basePath . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff->timestamp) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Services/ExportService.php
git commit -m "feat: add ExportService"
```

### Task 14: Enhance RevaluationService

**Files:**
- Modify: `app/Services/RevaluationService.php`

- [ ] **Step 1: Add automation and notification methods**
```php
<?php

namespace App\Services;

use App\Models\CurrencyPosition;
use App\Models\RevaluationEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevaluationService
{
    protected MathService $mathService;
    protected RateApiService $rateApiService;
    protected AccountingService $accountingService;

    public function __construct(
        MathService $mathService,
        RateApiService $rateApiService,
        AccountingService $accountingService
    ) {
        $this->mathService = $mathService;
        $this->rateApiService = $rateApiService;
        $this->accountingService = $accountingService;
    }

    public function runRevaluation(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $positions = CurrencyPosition::all();
        $results = [];
        $totalGain = '0';
        $totalLoss = '0';

        DB::beginTransaction();

        try {
            foreach ($positions as $position) {
                if ($this->mathService->compare($position->balance, '0') <= 0) {
                    continue;
                }

                $oldRate = $position->avg_cost_rate;
                $newRate = $this->rateApiService->getRateForCurrency($position->currency_code);

                $gainLoss = $this->mathService->calculateRevaluationPnl(
                    $position->balance,
                    $oldRate,
                    $newRate
                );

                if ($this->mathService->compare($gainLoss, '0') === 0) {
                    continue;
                }

                // Create revaluation entry
                $revaluationEntry = RevaluationEntry::create([
                    'currency_code' => $position->currency_code,
                    'till_id' => $position->till_id,
                    'old_rate' => $oldRate,
                    'new_rate' => $newRate,
                    'position_amount' => $position->balance,
                    'gain_loss_amount' => $gainLoss,
                    'revaluation_date' => $date,
                    'posted_by' => auth()->id() ?? 1,
                ]);

                // Create journal entry
                $isGain = $this->mathService->compare($gainLoss, '0') > 0;
                $lines = [
                    [
                        'account_code' => '2000',
                        'debit' => $isGain ? $gainLoss : '0',
                        'credit' => $isGain ? '0' : $this->mathService->multiply($gainLoss, '-1'),
                        'description' => "Revaluation for {$position->currency_code} @ {$newRate}",
                    ],
                    [
                        'account_code' => $isGain ? '5100' : '6100',
                        'debit' => $isGain ? '0' : $this->mathService->multiply($gainLoss, '-1'),
                        'credit' => $isGain ? $gainLoss : '0',
                        'description' => "Revaluation gain/loss for {$position->currency_code}",
                    ],
                ];

                $this->accountingService->createJournalEntry(
                    $lines,
                    'Revaluation',
                    $revaluationEntry->id,
                    "Month-end revaluation: {$position->currency_code}",
                    $date
                );

                // Update position
                $position->update([
                    'unrealized_pnl' => $this->mathService->add($position->unrealized_pnl ?? '0', $gainLoss),
                    'last_valuation_rate' => $newRate,
                    'last_valuation_at' => now(),
                ]);

                if ($isGain) {
                    $totalGain = $this->mathService->add($totalGain, $gainLoss);
                } else {
                    $totalLoss = $this->mathService->add($totalLoss, $gainLoss);
                }

                $results[] = [
                    'currency_code' => $position->currency_code,
                    'gain_loss' => $gainLoss,
                    'is_gain' => $isGain,
                ];
            }

            DB::commit();

            return [
                'date' => $date,
                'positions_updated' => count($results),
                'results' => $results,
                'total_gain' => $totalGain,
                'total_loss' => $totalLoss,
                'net_pnl' => $this->mathService->add($totalGain, $totalLoss),
                'report_path' => null, // Set by caller
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Revaluation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function scheduleRevaluation(): void
    {
        // Called by scheduler, nothing to do here
        Log::info('Revaluation scheduled for month-end');
    }

    public function getRevaluationStatus(string $month): array
    {
        $startDate = now()->parse($month)->startOfMonth();
        $endDate = now()->parse($month)->endOfMonth();

        $entries = RevaluationEntry::whereBetween('revaluation_date', [$startDate, $endDate])
            ->get();

        return [
            'month' => $month,
            'has_run' => $entries->count() > 0,
            'entries_count' => $entries->count(),
            'currencies' => $entries->pluck('currency_code')->toArray(),
        ];
    }

    public function sendRevaluationNotification(array $results): void
    {
        $recipients = $this->getNotificationRecipients();

        foreach ($recipients as $recipient) {
            try {
                \Mail::to($recipient->email)
                    ->send(new \App\Mail\RevaluationComplete($results));
            } catch (\Exception $e) {
                Log::error('Failed to send revaluation notification', [
                    'recipient' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function getNotificationRecipients(): array
    {
        return \App\Models\User::whereIn('role', ['manager', 'admin'])
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Services/RevaluationService.php
git commit -m "feat: enhance RevaluationService with automation"
```

### Task 15: Write Service Unit Tests

**Files:**
- Create: `tests/Unit/AccountingServiceTest.php`
- Create: `tests/Unit/LedgerServiceTest.php`

- [ ] **Step 1: Write AccountingService tests**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AccountingService;
use App\Services\MathService;
use App\Models\User;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $this->service = new AccountingService(new MathService());
    }

    /** @test */
    public function it_creates_balanced_journal_entry()
    {
        $user = User::factory()->create();
        $lines = [
            ['account_code' => '1000', 'debit' => '1000', 'credit' => '0', 'description' => 'Test'],
            ['account_code' => '5000', 'debit' => '0', 'credit' => '1000', 'description' => 'Test'],
        ];

        $entry = $this->service->createJournalEntry(
            $lines,
            'Test',
            null,
            'Test entry',
            now()->toDateString(),
            $user->id
        );

        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $entry->id, 'account_code' => '1000']);
        $this->assertTrue($entry->isBalanced());
    }

    /** @test */
    public function it_rejects_unbalanced_entry()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry is not balanced');

        $lines = [
            ['account_code' => '1000', 'debit' => '1000', 'credit' => '0'],
            ['account_code' => '5000', 'debit' => '0', 'credit' => '500'],
        ];

        $this->service->createJournalEntry($lines, 'Test');
    }

    /** @test */
    public function it_reverses_entry_correctly()
    {
        $user = User::factory()->create();
        $original = $this->service->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '500', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '500'],
            ],
            'Original',
            null,
            'Original entry',
            now()->toDateString(),
            $user->id
        );

        $reversal = $this->service->reverseJournalEntry($original, 'Correction', $user->id);

        $this->assertEquals('Reversed', $original->fresh()->status);
        $this->assertEquals(500, $reversal->lines()->where('account_code', '1000')->first()->credit);
        $this->assertEquals(500, $reversal->lines()->where('account_code', '5000')->first()->debit);
    }

    /** @test */
    public function it_updates_account_balance()
    {
        $user = User::factory()->create();
        
        $this->service->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '1000', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '1000'],
            ],
            'Test',
            null,
            'Test',
            now()->toDateString(),
            $user->id
        );

        $balance = $this->service->getAccountBalance('1000');
        $this->assertEquals('1000', $balance);
    }
}
```

- [ ] **Step 2: Write LedgerService tests**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LedgerService;
use App\Services\AccountingService;
use App\Services\MathService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $mathService = new MathService();
        $this->ledgerService = new LedgerService($mathService);
        $this->accountingService = new AccountingService($mathService);
    }

    /** @test */
    public function trial_balance_is_always_balanced()
    {
        $user = User::factory()->create();
        
        // Create some entries
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '5000', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '5000'],
            ],
            'Test',
            null,
            'Test',
            now()->toDateString(),
            $user->id
        );

        $trialBalance = $this->ledgerService->getTrialBalance();

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals($trialBalance['total_debits'], $trialBalance['total_credits']);
    }

    /** @test */
    public function profit_and_loss_calculates_correctly()
    {
        $user = User::factory()->create();
        
        // Revenue
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '10000', 'credit' => '0'],
                ['account_code' => '5000', 'debit' => '0', 'credit' => '10000'],
            ],
            'Test',
            null,
            'Test',
            now()->toDateString(),
            $user->id
        );

        // Expense
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '6000', 'debit' => '2000', 'credit' => '0'],
                ['account_code' => '1000', 'debit' => '0', 'credit' => '2000'],
            ],
            'Test',
            null,
            'Test',
            now()->toDateString(),
            $user->id
        );

        $pl = $this->ledgerService->getProfitAndLoss(now()->toDateString(), now()->toDateString());

        $this->assertEquals('10000', $pl['total_revenue']);
        $this->assertEquals('2000', $pl['total_expenses']);
        $this->assertEquals('8000', $pl['net_profit']);
    }

    /** @test */
    public function balance_sheet_balances()
    {
        $user = User::factory()->create();
        
        // Assets = Equity
        $this->accountingService->createJournalEntry(
            [
                ['account_code' => '1000', 'debit' => '50000', 'credit' => '0'],
                ['account_code' => '4000', 'debit' => '0', 'credit' => '50000'],
            ],
            'Test',
            null,
            'Test',
            now()->toDateString(),
            $user->id
        );

        $bs = $this->ledgerService->getBalanceSheet(now()->toDateString());

        $this->assertTrue($bs['is_balanced']);
        $this->assertEquals($bs['total_assets'], $bs['liabilities_plus_equity']);
    }
}
```

- [ ] **Step 3: Run tests**
```bash
php artisan test tests/Unit/AccountingServiceTest.php tests/Unit/LedgerServiceTest.php
```

- [ ] **Step 4: Commit**
```bash
git add tests/Unit/AccountingServiceTest.php tests/Unit/LedgerServiceTest.php
git commit -m "test: add accounting and ledger service tests"
```

---

## Phase 3: Controllers & Views (Week 3)

### Task 16: Create AccountingController

**Files:**
- Create: `app/Http/Controllers/AccountingController.php`

- [ ] **Step 1: Write controller**
```php
<?php

namespace App\Http\Controllers;

use App\Services\AccountingService;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    protected function requireManagerOrAdmin()
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function index()
    {
        $this->requireManagerOrAdmin();
        
        $entries = JournalEntry::with('postedBy')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('accounting.journal.index', compact('entries'));
    }

    public function create()
    {
        $this->requireManagerOrAdmin();
        
        $accounts = \App\Models\ChartOfAccount::where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('accounting.journal.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|exists:chart_of_accounts,account_code',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        try {
            $entry = $this->accountingService->createJournalEntry(
                $validated['lines'],
                'Manual',
                null,
                $validated['description'],
                $validated['entry_date']
            );

            return redirect()->route('accounting.journal.show', $entry)
                ->with('success', 'Journal entry created successfully.');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }
    }

    public function show(JournalEntry $entry)
    {
        $this->requireManagerOrAdmin();
        $entry->load('lines.account', 'postedBy', 'reversedBy');
        
        return view('accounting.journal.show', compact('entry'));
    }

    public function reverse(Request $request, JournalEntry $entry)
    {
        $this->requireManagerOrAdmin();

        if ($entry->isReversed()) {
            return back()->with('error', 'Entry is already reversed.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $reversal = $this->accountingService->reverseJournalEntry(
                $entry,
                $validated['reason']
            );

            return redirect()->route('accounting.journal.show', $reversal)
                ->with('success', 'Entry reversed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Reversal failed: ' . $e->getMessage());
        }
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/AccountingController.php
git commit -m "feat: add AccountingController"
```

### Task 17: Create LedgerController

**Files:**
- Create: `app/Http/Controllers/LedgerController.php`

- [ ] **Step 1: Write controller**
```php
<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    protected function requireManagerOrAdmin()
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function index()
    {
        $this->requireManagerOrAdmin();

        $trialBalance = $this->ledgerService->getTrialBalance();

        return view('accounting.ledger.index', compact('trialBalance'));
    }

    public function account(Request $request, string $accountCode)
    {
        $this->requireManagerOrAdmin();

        $fromDate = $request->input('from', now()->subMonth()->toDateString());
        $toDate = $request->input('to', now()->toDateString());

        $ledger = $this->ledgerService->getAccountLedger($accountCode, $fromDate, $toDate);

        return view('accounting.ledger.account', compact('ledger', 'fromDate', 'toDate'));
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/LedgerController.php
git commit -m "feat: add LedgerController"
```

### Task 18: Create FinancialStatementController

**Files:**
- Create: `app/Http/Controllers/FinancialStatementController.php`

- [ ] **Step 1: Write controller**
```php
<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use Illuminate\Http\Request;

class FinancialStatementController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    protected function requireManagerOrAdmin()
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function trialBalance(Request $request)
    {
        $this->requireManagerOrAdmin();

        $asOfDate = $request->input('as_of', now()->toDateString());
        $trialBalance = $this->ledgerService->getTrialBalance($asOfDate);

        return view('accounting.trial-balance', compact('trialBalance', 'asOfDate'));
    }

    public function profitLoss(Request $request)
    {
        $this->requireManagerOrAdmin();

        $fromDate = $request->input('from', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to', now()->toDateString());

        $pl = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        return view('accounting.profit-loss', compact('pl', 'fromDate', 'toDate'));
    }

    public function balanceSheet(Request $request)
    {
        $this->requireManagerOrAdmin();

        $asOfDate = $request->input('as_of', now()->toDateString());
        $balanceSheet = $this->ledgerService->getBalanceSheet($asOfDate);

        return view('accounting.balance-sheet', compact('balanceSheet', 'asOfDate'));
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/FinancialStatementController.php
git commit -m "feat: add FinancialStatementController"
```

### Task 19: Create RevaluationController

**Files:**
- Create: `app/Http/Controllers/RevaluationController.php`

- [ ] **Step 1: Write controller**
```php
<?php

namespace App\Http\Controllers;

use App\Services\RevaluationService;
use App\Models\CurrencyPosition;
use Illuminate\Http\Request;

class RevaluationController extends Controller
{
    protected RevaluationService $revaluationService;

    public function __construct(RevaluationService $revaluationService)
    {
        $this->revaluationService = $revaluationService;
    }

    protected function requireManagerOrAdmin()
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function index()
    {
        $this->requireManagerOrAdmin();

        $positions = CurrencyPosition::with('currency')->get();
        $status = $this->revaluationService->getRevaluationStatus(now()->format('Y-m'));

        return view('accounting.revaluation.index', compact('positions', 'status'));
    }

    public function run(Request $request)
    {
        $this->requireManagerOrAdmin();

        try {
            $results = $this->revaluationService->runRevaluation();
            
            return redirect()->route('accounting.revaluation.index')
                ->with('success', "Revaluation complete. {$results['positions_updated']} positions updated.");

        } catch (\Exception $e) {
            return back()->with('error', 'Revaluation failed: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $this->requireManagerOrAdmin();

        $month = $request->input('month', now()->format('Y-m'));
        $history = \App\Models\RevaluationEntry::whereMonth('revaluation_date', now()->parse($month)->month)
            ->whereYear('revaluation_date', now()->parse($month)->year)
            ->with(['currency', 'postedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('accounting.revaluation.history', compact('history', 'month'));
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/RevaluationController.php
git commit -m "feat: add RevaluationController"
```

### Task 20: Create Blade Views

**Files:**
- Create: `resources/views/accounting/journal/index.blade.php`
- Create: `resources/views/accounting/ledger/index.blade.php`
- Create: `resources/views/accounting/trial-balance.blade.php`

- [ ] **Step 1: Write journal index view**
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Journal Entries</h4>
                    <a href="{{ route('accounting.journal.create') }}" class="btn btn-primary">Create Entry</a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th>Debits</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->id }}</td>
                                <td>{{ $entry->entry_date }}</td>
                                <td>{{ $entry->reference_type }} {{ $entry->reference_id }}</td>
                                <td>{{ Str::limit($entry->description, 50) }}</td>
                                <td>{{ number_format($entry->getTotalDebits(), 2) }}</td>
                                <td>{{ number_format($entry->getTotalCredits(), 2) }}</td>
                                <td>
                                    @if($entry->isPosted())
                                        <span class="badge bg-success">Posted</span>
                                    @elseif($entry->isReversed())
                                        <span class="badge bg-warning">Reversed</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('accounting.journal.show', $entry) }}" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    {{ $entries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Write ledger index view**
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Trial Balance - {{ $trialBalance['as_of_date'] }}</h4>
                </div>
                <div class="card-body">
                    @if($trialBalance['is_balanced'])
                        <div class="alert alert-success">Trial balance is balanced</div>
                    @else
                        <div class="alert alert-danger">Trial balance is NOT balanced</div>
                    @endif
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trialBalance['accounts'] as $account)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td>{{ $account['account_type'] }}</td>
                                <td class="text-end">{{ number_format($account['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($account['credit'], 2) }}</td>
                                <td>
                                    <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="btn btn-sm btn-info">Ledger</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">TOTAL</th>
                                <th class="text-end">{{ number_format($trialBalance['total_debits'], 2) }}</th>
                                <th class="text-end">{{ number_format($trialBalance['total_credits'], 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 3: Write trial balance view**
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Trial Balance</h4>
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" name="as_of" value="{{ $asOfDate }}" class="form-control">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </form>
                </div>
                <div class="card-body">
                    @if($trialBalance['is_balanced'])
                        <div class="alert alert-success">Trial balance is balanced</div>
                    @else
                        <div class="alert alert-danger">Trial balance is NOT balanced</div>
                    @endif
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trialBalance['accounts'] as $account)
                            @if($account['debit'] > 0 || $account['credit'] > 0)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td>{{ $account['account_type'] }}</td>
                                <td class="text-end">{{ number_format($account['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($account['credit'], 2) }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">TOTAL</th>
                                <th class="text-end">{{ number_format($trialBalance['total_debits'], 2) }}</th>
                                <th class="text-end">{{ number_format($trialBalance['total_credits'], 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 4: Commit**
```bash
git add resources/views/accounting/
git commit -m "feat: add accounting views"
```

---

## Phase 4: Reporting Integration (Week 4)

### Task 21: Enhance ReportController

**Files:**
- Modify: `app/Http/Controllers/ReportController.php`

- [ ] **Step 1: Add new methods**
```php
<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportingService $reportingService;
    protected ExportService $exportService;

    public function __construct(
        ReportingService $reportingService,
        ExportService $exportService
    ) {
        $this->reportingService = $reportingService;
        $this->exportService = $exportService;
    }

    protected function requireManagerOrAdmin()
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    // ... existing methods ...

    public function lctr(Request $request)
    {
        $this->requireManagerOrAdmin();
        
        $month = $request->input('month', now()->format('Y-m'));
        
        return view('reports.lctr', compact('month'));
    }

    public function lctrGenerate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $month = $request->input('month', now()->format('Y-m'));
        $report = $this->reportingService->generateLCTR($month);

        // Store report
        \App\Models\ReportGenerated::create([
            'report_type' => 'LCTR',
            'period_start' => now()->parse($month)->startOfMonth(),
            'period_end' => now()->parse($month)->endOfMonth(),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json($report);
    }

    public function msb2(Request $request)
    {
        $this->requireManagerOrAdmin();
        
        $date = $request->input('date', now()->subDay()->toDateString());
        
        return view('reports.msb2', compact('date'));
    }

    public function msb2Generate(Request $request)
    {
        $this->requireManagerOrAdmin();

        $date = $request->input('date', now()->subDay()->toDateString());
        $report = $this->reportingService->generateMSB2($date);

        // Store report
        \App\Models\ReportGenerated::create([
            'report_type' => 'MSB2',
            'period_start' => $date,
            'period_end' => $date,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'file_format' => 'CSV',
        ]);

        return response()->json($report);
    }

    public function export(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'report_type' => 'required|in:lctr,msb2,trial_balance,pl,balance_sheet',
            'period' => 'required|string',
            'format' => 'required|in:CSV,PDF,XLSX',
        ]);

        // Generate report data
        $data = match($validated['report_type']) {
            'lctr' => $this->reportingService->generateLCTR($validated['period']),
            'msb2' => $this->reportingService->generateMSB2($validated['period']),
            default => [],
        };

        // Export
        $filename = "{$validated['report_type']}_{$validated['period']}." . strtolower($validated['format']);
        
        switch($validated['format']) {
            case 'CSV':
                $path = $this->exportService->toCSV($data['data'], $filename);
                return response()->download($path);
            
            case 'PDF':
                $path = $this->exportService->toPDF($data, 'reports.pdf', $filename);
                return response()->download($path);
            
            case 'XLSX':
                $path = $this->exportService->toExcel($data['data'], $filename);
                return response()->download($path);
        }
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/ReportController.php
git commit -m "feat: add LCTR and MSB2 report methods"
```

### Task 22: Create Report Views

**Files:**
- Create: `resources/views/reports/lctr.blade.php`
- Create: `resources/views/reports/msb2.blade.php`

- [ ] **Step 1: Write LCTR view**
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Large Cash Transaction Report (LCTR)</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.lctr.generate') }}">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Month</label>
                                <input type="month" name="month" value="{{ $month }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary form-control">Generate</button>
                            </div>
                        </div>
                    </form>
                    
                    <p class="text-muted">
                        This report includes all transactions ≥ RM 25,000 for BNM compliance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**
```bash
git add resources/views/reports/
git commit -m "feat: add report views"
```

---

## Phase 5: Automation & Integration (Week 5)

### Task 23: Create Console Commands

**Files:**
- Create: `app/Console/Commands/RunMonthlyRevaluation.php`
- Create: `app/Console/Commands/GenerateDailyMSB2.php`
- Create: `app/Console/Commands/CleanupOldReports.php`

- [ ] **Step 1: Write RunMonthlyRevaluation command**
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RevaluationService;
use App\Services\ExportService;

class RunMonthlyRevaluation extends Command
{
    protected $signature = 'revaluation:run {--force : Force run even if not month-end}';
    protected $description = 'Run monthly currency revaluation';

    public function handle(RevaluationService $service, ExportService $exportService)
    {
        $isMonthEnd = now()->isLastOfMonth();
        
        if (!$isMonthEnd && !$this->option('force')) {
            $this->info('Not month-end. Use --force to run manually.');
            return 0;
        }

        $this->info('Running month-end revaluation...');

        try {
            $results = $service->runRevaluation();

            // Generate report
            $filename = 'Revaluation_' . now()->format('Y-m') . '.csv';
            $path = $exportService->toCSV($results['results'], $filename);
            $results['report_path'] = $path;

            // Send notification
            $service->sendRevaluationNotification($results);

            $this->info("Revaluation complete. {$results['positions_updated']} positions updated.");
            $this->info("Net P&L: {$results['net_pnl']}");
            
            return 0;

        } catch (\Exception $e) {
            $this->error('Revaluation failed: ' . $e->getMessage());
            return 1;
        }
    }
}
```

- [ ] **Step 2: Write GenerateDailyMSB2 command**
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportingService;
use App\Services\ExportService;

class GenerateDailyMSB2 extends Command
{
    protected $signature = 'report:msb2 {--date= : Specific date (Y-m-d)}';
    protected $description = 'Generate daily MSB(2) report';

    public function handle(ReportingService $reportingService, ExportService $exportService)
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();
        
        $this->info("Generating MSB(2) report for {$date}...");

        try {
            $report = $reportingService->generateMSB2($date);
            
            $filename = "MSB2_{$date}.csv";
            $path = $exportService->toCSV($report['data'], $filename);

            $this->info("MSB(2) report generated: {$path}");
            $this->info("Total currencies: " . count($report['data']));
            
            return 0;

        } catch (\Exception $e) {
            $this->error('Report generation failed: ' . $e->getMessage());
            return 1;
        }
    }
}
```

- [ ] **Step 3: Write CleanupOldReports command**
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExportService;

class CleanupOldReports extends Command
{
    protected $signature = 'reports:cleanup {--days=90 : Delete reports older than N days}';
    protected $description = 'Clean up old generated report files';

    public function handle(ExportService $exportService)
    {
        $days = $this->option('days');
        
        $this->info("Cleaning up reports older than {$days} days...");

        $deleted = $exportService->cleanupOldReports($days);

        $this->info("Deleted {$deleted} old report files.");
        
        return 0;
    }
}
```

- [ ] **Step 4: Commit**
```bash
git add app/Console/Commands/RunMonthlyRevaluation.php app/Console/Commands/GenerateDailyMSB2.php app/Console/Commands/CleanupOldReports.php
git commit -m "feat: add console commands for automation"
```

### Task 24: Update Kernel Schedule

**Files:**
- Modify: `app/Console/Kernel.php`

- [ ] **Step 1: Add scheduled commands**
```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Month-end revaluation at 23:59 on last day
        $schedule->command('revaluation:run')
            ->monthlyOn(1, '23:59')
            ->when(fn() => now()->isLastOfMonth())
            ->onSuccess(function () {
                \Log::info('Monthly revaluation completed');
            })
            ->onFailure(function () {
                \Log::error('Monthly revaluation failed');
            });

        // Daily MSB(2) at 00:05 for previous day
        $schedule->command('report:msb2')
            ->dailyAt('00:05')
            ->onSuccess(function () {
                \Log::info('Daily MSB(2) report generated');
            });

        // Weekly trial balance backup
        $schedule->command('report:trial-balance')
            ->weekly()
            ->sundays()
            ->at('01:00');

        // Monthly cleanup
        $schedule->command('reports:cleanup --days=90')
            ->monthly()
            ->onFirstOfMonth()
            ->at('02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Console/Kernel.php
git commit -m "feat: add scheduled commands to kernel"
```

### Task 25: Create Mail Classes

**Files:**
- Create: `app/Mail/RevaluationComplete.php`
- Create: `app/Mail/ReportReady.php`

- [ ] **Step 1: Write RevaluationComplete mail**
```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevaluationComplete extends Mailable
{
    use Queueable, SerializesModels;

    public array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Monthly Revaluation Complete - ' . now()->format('F Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revaluation-complete',
            with: [
                'results' => $this->results,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($this->results['report_path'])
                ->as('revaluation_report.csv'),
        ];
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Mail/RevaluationComplete.php app/Mail/ReportReady.php
git commit -m "feat: add email notification classes"
```

### Task 26: Integrate with TransactionController

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php`

- [ ] **Step 1: Update createAccountingEntries method**
```php
protected function createAccountingEntries(Transaction $transaction): void
{
    $accountingService = app(AccountingService::class);
    
    if ($transaction->type === 'Buy') {
        // Buy: Dr Inventory, Cr Cash
        $lines = [
            [
                'account_code' => '2000',
                'debit' => (string) $transaction->amount_local,
                'credit' => '0',
                'description' => "Buy {$transaction->amount_foreign} {$transaction->currency_code} @ {$transaction->rate}",
            ],
            [
                'account_code' => '1000',
                'debit' => '0',
                'credit' => (string) $transaction->amount_local,
                'description' => "Cash out for {$transaction->currency_code} purchase",
            ],
        ];
    } else {
        // Sell: Calculate gain/loss
        $position = $this->positionService->getPosition($transaction->currency_code);
        $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
        $costBasis = $this->mathService->multiply(
            (string) $transaction->amount_foreign,
            $avgCost
        );
        $gainLoss = $this->mathService->subtract(
            (string) $transaction->amount_local,
            $costBasis
        );

        $lines = [
            [
                'account_code' => '1000',
                'debit' => (string) $transaction->amount_local,
                'credit' => '0',
                'description' => "Sale of {$transaction->amount_foreign} {$transaction->currency_code}",
            ],
            [
                'account_code' => '2000',
                'debit' => '0',
                'credit' => $costBasis,
                'description' => "Cost of {$transaction->currency_code} sold",
            ],
        ];

        if ($this->mathService->compare($gainLoss, '0') >= 0) {
            $lines[] = [
                'account_code' => '5000',
                'debit' => '0',
                'credit' => $gainLoss,
                'description' => "Gain on {$transaction->currency_code} sale",
            ];
        } else {
            $lines[] = [
                'account_code' => '6000',
                'debit' => $this->mathService->multiply($gainLoss, '-1'),
                'credit' => '0',
                'description' => "Loss on {$transaction->currency_code} sale",
            ];
        }
    }

    $accountingService->createJournalEntry(
        $lines,
        'Transaction',
        $transaction->id,
        "Transaction #{$transaction->id} - {$transaction->type} {$transaction->currency_code}"
    );
}
```

- [ ] **Step 2: Commit**
```bash
git add app/Http/Controllers/TransactionController.php
git commit -m "feat: integrate AccountingService with TransactionController"
```

### Task 27: Update Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add new routes**
```php
<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\RevaluationController;

// ... existing routes ...

// Accounting Routes (Manager/Admin only)
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/accounting/journal', [AccountingController::class, 'index'])->name('accounting.journal');
    Route::get('/accounting/journal/create', [AccountingController::class, 'create'])->name('accounting.journal.create');
    Route::post('/accounting/journal', [AccountingController::class, 'store'])->name('accounting.journal.store');
    Route::get('/accounting/journal/{entry}', [AccountingController::class, 'show'])->name('accounting.journal.show');
    Route::post('/accounting/journal/{entry}/reverse', [AccountingController::class, 'reverse'])->name('accounting.journal.reverse');
    
    Route::get('/accounting/ledger', [LedgerController::class, 'index'])->name('accounting.ledger');
    Route::get('/accounting/ledger/{accountCode}', [LedgerController::class, 'account'])->name('accounting.ledger.account');
    
    Route::get('/accounting/trial-balance', [FinancialStatementController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', [FinancialStatementController::class, 'profitLoss'])->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', [FinancialStatementController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    
    Route::get('/accounting/revaluation', [RevaluationController::class, 'index'])->name('accounting.revaluation');
    Route::post('/accounting/revaluation/run', [RevaluationController::class, 'run'])->name('accounting.revaluation.run');
    Route::get('/accounting/revaluation/history', [RevaluationController::class, 'history'])->name('accounting.revaluation.history');
});

// Reporting Routes (Manager/Admin only)
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/reports/lctr', [ReportController::class, 'lctr'])->name('reports.lctr');
    Route::get('/reports/lctr/generate', [ReportController::class, 'lctrGenerate'])->name('reports.lctr.generate');
    Route::get('/reports/msb2', [ReportController::class, 'msb2'])->name('reports.msb2');
    Route::get('/reports/msb2/generate', [ReportController::class, 'msb2Generate'])->name('reports.msb2.generate');
    Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});
```

- [ ] **Step 2: Commit**
```bash
git add routes/web.php
git commit -m "feat: add accounting and reporting routes"
```

---

## Phase 6: Testing & Final Integration (Week 6)

### Task 28: Create Feature Tests

**Files:**
- Create: `tests/Feature/JournalEntryTest.php`
- Create: `tests/Feature/LctrReportTest.php`

- [ ] **Step 1: Write JournalEntry feature test**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function manager_can_create_manual_journal_entry()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        
        $response = $this->actingAs($manager)
            ->post(route('accounting.journal.store'), [
                'entry_date' => '2026-01-15',
                'description' => 'Test entry',
                'lines' => [
                    ['account_code' => '1000', 'debit' => 100, 'credit' => 0, 'description' => ''],
                    ['account_code' => '5000', 'debit' => 0, 'credit' => 100, 'description' => ''],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['description' => 'Test entry']);
    }

    /** @test */
    public function teller_cannot_access_journal_entry()
    {
        $teller = User::factory()->create(['role' => 'teller']);
        
        $response = $this->actingAs($teller)
            ->get(route('accounting.journal'));

        $response->assertForbidden();
    }

    /** @test */
    public function unbalanced_entry_is_rejected()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        
        $response = $this->actingAs($manager)
            ->post(route('accounting.journal.store'), [
                'entry_date' => '2026-01-15',
                'description' => 'Test entry',
                'lines' => [
                    ['account_code' => '1000', 'debit' => 100, 'credit' => 0, 'description' => ''],
                    ['account_code' => '5000', 'debit' => 0, 'credit' => 50, 'description' => ''],
                ],
            ]);

        $response->assertSessionHasErrors();
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add tests/Feature/JournalEntryTest.php tests/Feature/LctrReportTest.php
git commit -m "test: add feature tests for journal and reports"
```

### Task 29: Update Layout Navigation

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (or navigation partial)

- [ ] **Step 1: Add navigation links**
```php
<!-- In navigation section -->
@if(auth()->user()->isManager())
<div class="nav-section">
    <h3>Accounting</h3>
    <a href="{{ route('accounting.journal') }}">Journal Entries</a>
    <a href="{{ route('accounting.ledger') }}">General Ledger</a>
    <a href="{{ route('accounting.trial-balance') }}">Trial Balance</a>
    <a href="{{ route('accounting.profit-loss') }}">Profit & Loss</a>
    <a href="{{ route('accounting.balance-sheet') }}">Balance Sheet</a>
    <a href="{{ route('accounting.revaluation') }}">Revaluation</a>
</div>

<div class="nav-section">
    <h3>Reports</h3>
    <a href="{{ route('reports.lctr') }}">LCTR (BNM)</a>
    <a href="{{ route('reports.msb2') }}">MSB(2) Daily</a>
</div>
@endif
```

- [ ] **Step 2: Commit**
```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: add accounting navigation"
```

### Task 30: Final Integration Test

**Files:**
- Run all tests

- [ ] **Step 1: Run full test suite**
```bash
php artisan test
```
Expected: All tests pass

- [ ] **Step 2: Run migrations**
```bash
php artisan migrate
```
Expected: All migrations complete successfully

- [ ] **Step 3: Seed data**
```bash
php artisan db:seed --class=ChartOfAccountsSeeder
```

- [ ] **Step 4: Commit**
```bash
git commit -m "chore: final integration complete"
```

---

## Verification Checklist

### Database
- [ ] All 6 migrations created and run
- [ ] Chart of Accounts seeded (19 accounts)
- [ ] Foreign keys properly configured

### Services
- [ ] AccountingService creates balanced entries
- [ ] LedgerService generates correct trial balance
- [ ] ReportingService generates LCTR/MSB2
- [ ] ExportService exports to CSV/PDF/Excel
- [ ] RevaluationService runs month-end automation

### Controllers
- [ ] AccountingController CRUD operations work
- [ ] LedgerController displays ledgers
- [ ] FinancialStatementController generates reports
- [ ] RevaluationController runs revaluation
- [ ] ReportController handles exports

### Views
- [ ] Journal entry list, create, show views
- [ ] Ledger and trial balance views
- [ ] P&L and balance sheet views
- [ ] Revaluation dashboard and history
- [ ] Report generation forms

### Automation
- [ ] Console commands registered
- [ ] Kernel schedule configured
- [ ] Email notifications configured

### Tests
- [ ] Unit tests pass (95%+ coverage)
- [ ] Feature tests pass
- [ ] All accounting entries balanced
- [ ] Role-based access enforced

---

**Plan Created:** 2026-04-01
**Total Tasks:** 30
**Estimated Duration:** 6 weeks
**Priority:** Critical for BNM Compliance
