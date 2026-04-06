# Accounting Production Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add production-ready features to the accounting module: Journal Entry Workflow, Enhanced Chart of Accounts, Cash Flow + Ratios, and Fiscal Year Management.

**Architecture:** Laravel 10 service layer with Eloquent models. New services handle workflow, cash flow, ratios, and fiscal year operations. Existing AccountingService and LedgerService extended with new methods. Database migrations add required columns with backward compatibility.

**Tech Stack:** Laravel 10, MySQL, BCMath (MathService), Eloquent ORM

---

## File Structure

### Database Migrations (5 new migrations)
- `2026_04_05_000001_enhance_journal_entries_table.php` - Add workflow columns
- `2026_04_05_000002_create_departments_table.php` - Department table
- `2026_04_05_000003_create_cost_centers_table.php` - Cost center table
- `2026_04_05_000004_create_fiscal_years_table.php` - Fiscal year table
- `2026_04_05_000005_enhance_chart_of_accounts_table.php` - Add account class, cost_center, department FKs

### New Models (3)
- `app/Models/Department.php`
- `app/Models/CostCenter.php`
- `app/Models/FiscalYear.php`

### Modified Models (2)
- `app/Models/JournalEntry.php` - Add workflow status, approval fields, entry number
- `app/Models/ChartOfAccount.php` - Add account_class, cost_center_id, department_id

### New Services (4)
- `app/Services/JournalEntryWorkflowService.php` - Draft/Pending/Posted workflow
- `app/Services/CashFlowService.php` - Cash flow statement
- `app/Services/FinancialRatioService.php` - Financial ratios
- `app/Services/FiscalYearService.php` - Year-end closing

### Modified Services (1)
- `app/Services/AccountingService.php` - Add cost center support, entry numbering

### New Controllers (2)
- `app/Http/Controllers/JournalEntryWorkflowController.php` - Approval actions
- `app/Http/Controllers/FiscalYearController.php` - Year management

### New Seeders (3)
- `database/seeders/DepartmentSeeder.php`
- `database/seeders/CostCenterSeeder.php`
- `database/seeders/EnhancedChartOfAccountsSeeder.php`

### Modified Seeders (1)
- `database/seeders/DatabaseSeeder.php` - Call new seeders

### New Views (4)
- `resources/views/accounting/journal/workflow.blade.php` - Entry workflow UI
- `resources/views/accounting/cash-flow.blade.php` - Cash flow statement
- `resources/views/accounting/ratios.blade.php` - Financial ratios dashboard
- `resources/views/accounting/fiscal-years.blade.php` - Fiscal year management

### Modified Views (3)
- `resources/views/accounting/journal/index.blade.php` - Show workflow status
- `resources/views/accounting/journal/create.blade.php` - Cost center/department selection
- `resources/views/accounting.blade.php` - Add links to new features

### Modified Routes (1)
- `routes/web.php` - Add workflow and fiscal year routes

### Modified Controllers (1)
- `app/Http/Controllers/AccountingController.php` - Add workflow actions

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_04_05_000001_enhance_journal_entries_table.php`
- Create: `database/migrations/2026_04_05_000002_create_departments_table.php`
- Create: `database/migrations/2026_04_05_000003_create_cost_centers_table.php`
- Create: `database/migrations/2026_04_05_000004_create_fiscal_years_table.php`
- Create: `database/migrations/2026_04_05_000005_enhance_chart_of_accounts_table.php`

- [ ] **Step 1: Create enhance_journal_entries migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('entry_number', 20)->unique()->after('id')->nullable();
            $table->string('status', 20)->default('Draft')->after('description');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('cost_center_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->index('status');
            $table->index('entry_number');
        });
    }

    public function down(): void {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'entry_number', 'status', 'created_by', 'approved_by',
                'approved_at', 'approval_notes', 'cost_center_id', 'department_id'
            ]);
        });
    }
};
```

- [ ] **Step 2: Create departments migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('departments');
    }
};
```

- [ ] **Step 3: Create cost_centers migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('department_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cost_centers');
    }
};
```

- [ ] **Step 4: Create fiscal_years migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('year_code', 10)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['Open', 'Closed', 'Archived'])->default('Open');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->foreignId('fiscal_year_id')->nullable()->after('id')->constrained();
        });
    }

    public function down(): void {
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropForeign(['fiscal_year_id']);
            $table->dropColumn('fiscal_year_id');
        });
        Schema::dropIfExists('fiscal_years');
    }
};
```

- [ ] **Step 5: Create enhance_chart_of_accounts migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_class', 50)->nullable()->after('account_type');
            $table->string('description', 255)->nullable()->after('account_name');
            $table->boolean('is_active')->default(true)->change();
            $table->boolean('allow_journal')->default(true)->after('is_active');
            $table->foreignId('cost_center_id')->nullable()->after('parent_code')->constrained();
            $table->foreignId('department_id')->nullable()->after('cost_center_id')->constrained();
        });
    }

    public function down(): void {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['account_class', 'description', 'allow_journal', 'cost_center_id', 'department_id']);
        });
    }
};
```

- [ ] **Step 6: Run migrations**

Run: `php artisan migrate`
Expected: 5 migrations run successfully

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_04_05_000001_enhance_journal_entries_table.php
git add database/migrations/2026_04_05_000002_create_departments_table.php
git add database/migrations/2026_04_05_000003_create_cost_centers_table.php
git add database/migrations/2026_04_05_000004_create_fiscal_years_table.php
git add database/migrations/2026_04_05_000005_enhance_chart_of_accounts_table.php
git commit -m "feat: add accounting production database migrations

- Add journal_entries workflow columns (entry_number, status, approval fields)
- Create departments table for organizational structure
- Create cost_centers table for cost tracking
- Create fiscal_years table for year management
- Enhance chart_of_accounts with account_class, cost_center, department

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 2: New Models

**Files:**
- Create: `app/Models/Department.php`
- Create: `app/Models/CostCenter.php`
- Create: `app/Models/FiscalYear.php`
- Modify: `app/Models/JournalEntry.php`
- Modify: `app/Models/ChartOfAccount.php`

- [ ] **Step 1: Create Department model**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }
}
```

- [ ] **Step 2: Create CostCenter model**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'is_active', 'department_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
```

- [ ] **Step 3: Create FiscalYear model**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_code', 'start_date', 'end_date', 'status', 'closed_by', 'closed_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function accountingPeriods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'Open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'Closed';
    }
}
```

- [ ] **Step 4: Update JournalEntry model**

Modify `app/Models/JournalEntry.php` - Add new fillable fields and methods:

Add to `$fillable` array:
```php
'entry_number', 'status', 'created_by', 'approved_by', 'approved_at', 'approval_notes',
'cost_center_id', 'department_id'
```

Add to `$casts` array:
```php
'approved_at' => 'datetime',
```

Add new relationships:
```php
public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function approvedBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'approved_by');
}

public function costCenter(): BelongsTo
{
    return $this->belongsTo(CostCenter::class);
}

public function department(): BelongsTo
{
    return $this->belongsTo(Department::class);
}
```

Add new status methods:
```php
public function isDraft(): bool
{
    return $this->status === 'Draft';
}

public function isPending(): bool
{
    return $this->status === 'Pending';
}

public function isApproved(): bool
{
    return $this->status === 'Approved';
}

public function canBeApproved(): bool
{
    return $this->status === 'Pending' && auth()->id() !== $this->created_by;
}

public function canBePosted(): bool
{
    return in_array($this->status, ['Pending', 'Approved']);
}
```

- [ ] **Step 5: Update ChartOfAccount model**

Modify `app/Models/ChartOfAccount.php` - Add new fillable fields:

Add to `$fillable` array:
```php
'account_class', 'description', 'allow_journal', 'cost_center_id', 'department_id'
```

Add new relationships:
```php
public function costCenter(): BelongsTo
{
    return $this->belongsTo(CostCenter::class);
}

public function department(): BelongsTo
{
    return $this->belongsTo(Department::class);
}
```

Add new methods:
```php
public function isCash(): bool
{
    return $this->account_class === 'Cash';
}

public function isBankAccount(): bool
{
    return $this->account_type === 'Asset' && $this->account_class === 'Cash';
}

public function allowsJournal(): bool
{
    return $this->allow_journal ?? true;
}
```

- [ ] **Step 6: Run tests to verify models work**

Run: `php artisan tinker --execute="echo App\Models\Department::count() . ' ' . App\Models\CostCenter::count() . ' ' . App\Models\FiscalYear::count();"`
Expected: 0 0 0 (tables exist but empty)

- [ ] **Step 7: Commit**

```bash
git add app/Models/Department.php app/Models/CostCenter.php app/Models/FiscalYear.php
git add app/Models/JournalEntry.php app/Models/ChartOfAccount.php
git commit -m "feat: add Department, CostCenter, FiscalYear models

- Department: organizational unit with cost centers relation
- CostCenter: cost tracking unit belonging to department
- FiscalYear: year entity with open/closed status
- JournalEntry: add workflow fields and status methods
- ChartOfAccount: add account_class, cost_center, department relations

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 3: Database Seeders

**Files:**
- Create: `database/seeders/DepartmentSeeder.php`
- Create: `database/seeders/CostCenterSeeder.php`
- Create: `database/seeders/EnhancedChartOfAccountsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create DepartmentSeeder**

```php
<?php
namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'EXEC', 'name' => 'Executive/Management', 'description' => 'Executive leadership and management'],
            ['code' => 'OPS', 'name' => 'Operations', 'description' => 'Core currency exchange operations'],
            ['code' => 'SALES', 'name' => 'Sales & Marketing', 'description' => 'Sales and customer acquisition'],
            ['code' => 'FIN', 'name' => 'Finance & Accounting', 'description' => 'Financial management and reporting'],
            ['code' => 'COMPLY', 'name' => 'Compliance & Risk', 'description' => 'AML/CFT compliance and risk management'],
            ['code' => 'TECH', 'name' => 'Information Technology', 'description' => 'IT systems and infrastructure'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        $this->command->info('Created ' . count($departments) . ' departments');
    }
}
```

- [ ] **Step 2: Create CostCenterSeeder**

```php
<?php
namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'code');

        $costCenters = [
            // Operations
            ['code' => 'OPS-CCY', 'name' => 'Currency Trading', 'department_code' => 'OPS'],
            ['code' => 'OPS-TILL', 'name' => 'Till Management', 'department_code' => 'OPS'],
            ['code' => 'OPS-CUST', 'name' => 'Customer Service', 'department_code' => 'OPS'],
            // Finance
            ['code' => 'FIN-ACCT', 'name' => 'Accounting', 'department_code' => 'FIN'],
            ['code' => 'FIN-TREAS', 'name' => 'Treasury', 'department_code' => 'FIN'],
            // Compliance
            ['code' => 'COMPLY-AML', 'name' => 'AML Monitoring', 'department_code' => 'COMPLY'],
            ['code' => 'COMPLY-AUD', 'name' => 'Internal Audit', 'department_code' => 'COMPLY'],
        ];

        foreach ($costCenters as $cc) {
            CostCenter::firstOrCreate(
                ['code' => $cc['code']],
                [
                    'name' => $cc['name'],
                    'department_id' => $departments[$cc['department_code']] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Created ' . count($costCenters) . ' cost centers');
    }
}
```

- [ ] **Step 3: Create EnhancedChartOfAccountsSeeder**

```php
<?php
namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class EnhancedChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $costCenters = CostCenter::pluck('id', 'code');

        $accounts = [
            // ASSET - Cash & Bank (1000-1499)
            ['code' => '1000', 'name' => 'Cash - MYR', 'type' => 'Asset', 'class' => 'Cash'],
            ['code' => '1010', 'name' => 'Cash - USD', 'type' => 'Asset', 'class' => 'Cash'],
            ['code' => '1020', 'name' => 'Cash - EUR', 'type' => 'Asset', 'class' => 'Cash'],
            ['code' => '1030', 'name' => 'Cash - GBP', 'type' => 'Asset', 'class' => 'Cash'],
            ['code' => '1040', 'name' => 'Cash - SGD', 'type' => 'Asset', 'class' => 'Cash'],
            ['code' => '1100', 'name' => 'Bank - Maybank', 'type' => 'Asset', 'class' => 'Bank'],
            ['code' => '1110', 'name' => 'Bank - CIMB', 'type' => 'Asset', 'class' => 'Bank'],
            ['code' => '1120', 'name' => 'Bank - Public Bank', 'type' => 'Asset', 'class' => 'Bank'],
            ['code' => '1130', 'name' => 'Bank - RHB', 'type' => 'Asset', 'class' => 'Bank'],
            // ASSET - Receivables (1500-1999)
            ['code' => '1500', 'name' => 'Accounts Receivable - Customers', 'type' => 'Asset', 'class' => 'Receivable'],
            ['code' => '1510', 'name' => 'Accounts Receivable - Staff', 'type' => 'Asset', 'class' => 'Receivable'],
            ['code' => '1600', 'name' => 'Prepaid Expenses', 'type' => 'Asset', 'class' => 'Prepaid'],
            // ASSET - Inventory (2000-2499)
            ['code' => '2000', 'name' => 'Foreign Currency Inventory', 'type' => 'Asset', 'class' => 'Inventory'],
            // LIABILITY - Payables (3000-3499)
            ['code' => '3000', 'name' => 'Accounts Payable - Suppliers', 'type' => 'Liability', 'class' => 'Payable'],
            ['code' => '3010', 'name' => 'Accounts Payable - BNM', 'type' => 'Liability', 'class' => 'Payable'],
            // LIABILITY - Accruals (3500-3999)
            ['code' => '3500', 'name' => 'Accrued Expenses', 'type' => 'Liability', 'class' => 'Accrued'],
            ['code' => '3600', 'name' => 'Accrued Salaries', 'type' => 'Liability', 'class' => 'Accrued'],
            // EQUITY (4000-4999)
            ['code' => '4000', 'name' => 'Paid-in Capital', 'type' => 'Equity', 'class' => 'Capital'],
            ['code' => '4100', 'name' => 'Retained Earnings', 'type' => 'Equity', 'class' => 'Retained'],
            ['code' => '4998', 'name' => 'Income Summary', 'type' => 'Equity', 'class' => 'Summary'],
            ['code' => '4999', 'name' => 'Current Year Earnings', 'type' => 'Equity', 'class' => 'Summary'],
            // REVENUE (5000-5999)
            ['code' => '5000', 'name' => 'Revenue - Forex Trading', 'type' => 'Revenue', 'class' => 'Operating'],
            ['code' => '5100', 'name' => 'Revenue - Revaluation Gain', 'type' => 'Revenue', 'class' => 'NonOperating'],
            ['code' => '5200', 'name' => 'Interest Income', 'type' => 'Revenue', 'class' => 'NonOperating'],
            // EXPENSE (6000-6999)
            ['code' => '6000', 'name' => 'Expense - Forex Loss', 'type' => 'Expense', 'class' => 'Direct'],
            ['code' => '6100', 'name' => 'Expense - Revaluation Loss', 'type' => 'Expense', 'class' => 'Direct'],
            ['code' => '6200', 'name' => 'Expense - Operating', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6300', 'name' => 'Expense - Salaries', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6400', 'name' => 'Expense - Rent', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6500', 'name' => 'Expense - Utilities', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6600', 'name' => 'Expense - Marketing', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6700', 'name' => 'Expense - Professional Fees', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6800', 'name' => 'Expense - Depreciation', 'type' => 'Expense', 'class' => 'Operating'],
            ['code' => '6900', 'name' => 'Expense - Other', 'type' => 'Expense', 'class' => 'Operating'],
        ];

        foreach ($accounts as $acct) {
            ChartOfAccount::firstOrCreate(
                ['account_code' => $acct['code']],
                [
                    'account_name' => $acct['name'],
                    'account_type' => $acct['type'],
                    'account_class' => $acct['class'],
                    'is_active' => true,
                    'allow_journal' => true,
                ]
            );
        }

        $this->command->info('Created ' . count($accounts) . ' chart of accounts');
    }
}
```

- [ ] **Step 4: Update DatabaseSeeder**

Modify `database/seeders/DatabaseSeeder.php` - Add calls to new seeders after ChartOfAccountsSeeder:

```php
// Seed Departments
$this->call(DepartmentSeeder::class);

// Seed Cost Centers
$this->call(CostCenterSeeder::class);

// Seed Enhanced Chart of Accounts (replaces basic seeder)
$this->call(EnhancedChartOfAccountsSeeder::class);

// Seed Fiscal Year for current year
$this->call(FiscalYearSeeder::class);
```

Also create `FiscalYearSeeder.php`:
```php
<?php
namespace Database\Seeders;

use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class FiscalYearSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = now()->year;
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year_code' => 'FY' . $currentYear],
            [
                'start_date' => "$currentYear-01-01",
                'end_date' => "$currentYear-12-31",
                'status' => 'Open',
            ]
        );

        $this->command->info("Fiscal year {$fiscalYear->year_code} ready");
    }
}
```

- [ ] **Step 5: Run seeders**

Run: `php artisan db:seed --force`
Expected: Departments, cost centers, chart of accounts, fiscal year created

- [ ] **Step 6: Verify counts**

Run: `php artisan tinker --execute="echo 'Depts: ' . App\Models\Department::count() . ', CostCenters: ' . App\Models\CostCenter::count() . ', Accounts: ' . App\Models\ChartOfAccount::count() . ', FY: ' . App\Models\FiscalYear::count();"`
Expected: Depts: 6, CostCenters: 8, Accounts: 35+, FY: 1

- [ ] **Step 7: Commit**

```bash
git add database/seeders/DepartmentSeeder.php database/seeders/CostCenterSeeder.php
git add database/seeders/EnhancedChartOfAccountsSeeder.php database/seeders/FiscalYearSeeder.php
git add database/seeders/DatabaseSeeder.php
git commit -m "feat: add comprehensive seeders for accounting production

- DepartmentSeeder: 6 departments (Exec, Ops, Sales, Finance, Compliance, IT)
- CostCenterSeeder: 8 cost centers for operational tracking
- EnhancedChartOfAccountsSeeder: 35+ accounts with proper classification
- FiscalYearSeeder: current fiscal year creation
- Updated DatabaseSeeder to call all new seeders

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 4: Journal Entry Workflow Service

**Files:**
- Create: `app/Services/JournalEntryWorkflowService.php`
- Modify: `app/Services/AccountingService.php`
- Modify: `app/Http/Controllers/AccountingController.php`

- [ ] **Step 1: Create JournalEntryWorkflowService**

```php
<?php
namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

class JournalEntryWorkflowService
{
    protected MathService $mathService;

    public function __construct()
    {
        $this->mathService = new MathService();
    }

    /**
     * Generate next entry number in format JE-YYYYMM-XXXX
     */
    public function generateEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ym') . '-';
        $lastEntry = JournalEntry::where('entry_number', 'like', $prefix . '%')
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastSeq = (int) substr($lastEntry->entry_number, -4);
            $seq = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a draft journal entry
     */
    public function createDraft(array $lines, string $description, ?string $entryDate = null, ?int $costCenterId = null, ?int $departmentId = null): JournalEntry
    {
        $this->validateLines($lines);

        return DB::transaction(function () use ($lines, $description, $entryDate, $costCenterId, $departmentId) {
            $entryDate = $entryDate ?? now()->toDateString();

            $entry = JournalEntry::create([
                'entry_date' => $entryDate,
                'entry_number' => $this->generateEntryNumber(),
                'reference_type' => 'Manual',
                'description' => $description,
                'status' => 'Draft',
                'created_by' => auth()->id(),
                'cost_center_id' => $costCenterId,
                'department_id' => $departmentId,
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code' => $line['account_code'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            $this->logAction($entry, 'created_draft');

            return $entry->fresh()->load('lines');
        });
    }

    /**
     * Submit a draft entry for approval
     */
    public function submitForApproval(JournalEntry $entry): JournalEntry
    {
        if (!$entry->isDraft()) {
            throw new \InvalidArgumentException('Only draft entries can be submitted');
        }

        $entry->update(['status' => 'Pending']);
        $this->logAction($entry, 'submitted_for_approval');

        return $entry->fresh();
    }

    /**
     * Approve and post a pending entry
     */
    public function approveAndPost(JournalEntry $entry, ?string $notes = null): JournalEntry
    {
        if (!$entry->canBeApproved()) {
            throw new \InvalidArgumentException('Entry cannot be approved by this user or is not pending');
        }

        return DB::transaction(function () use ($entry, $notes) {
            // Update entry status to Posted
            $entry->update([
                'status' => 'Posted',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $notes,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Create ledger entries
            $this->postToLedger($entry);

            $this->logAction($entry, 'approved_and_posted');

            return $entry->fresh()->load('lines');
        });
    }

    /**
     * Reject a pending entry (return to draft)
     */
    public function reject(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->status !== 'Pending') {
            throw new \InvalidArgumentException('Only pending entries can be rejected');
        }

        $entry->update([
            'status' => 'Draft',
            'approval_notes' => $reason,
        ]);

        $this->logAction($entry, 'rejected', ['reason' => $reason]);

        return $entry->fresh();
    }

    /**
     * Post a journal entry to the ledger (create account ledger entries)
     */
    protected function postToLedger(JournalEntry $entry): void
    {
        $entry->loadMissing('lines');

        foreach ($entry->lines as $line) {
            $currentBalance = $this->getAccountBalance($line->account_code);

            $isDebitNormal = in_array(
                ChartOfAccount::find($line->account_code)?->account_type,
                ['Asset', 'Expense']
            );

            if ($isDebitNormal) {
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->debit),
                    $this->mathService->multiply((string) $line->credit, '-1')
                );
            } else {
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

    /**
     * Get account balance from ledger
     */
    protected function getAccountBalance(string $accountCode): string
    {
        $lastEntry = AccountLedger::where('account_code', $accountCode)
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry ? (string) $lastEntry->running_balance : '0';
    }

    /**
     * Validate journal entry lines
     */
    protected function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException('Journal entry must have at least 2 lines');
        }

        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($lines as $line) {
            if (empty($line['account_code'])) {
                throw new \InvalidArgumentException('Each line must have an account_code');
            }

            if (!isset($line['debit']) && !isset($line['credit'])) {
                throw new \InvalidArgumentException('Each line must have either debit or credit');
            }

            $totalDebits = $this->mathService->add($totalDebits, (string) ($line['debit'] ?? 0));
            $totalCredits = $this->mathService->add($totalCredits, (string) ($line['credit'] ?? 0));
        }

        if ($this->mathService->compare($totalDebits, $totalCredits) !== 0) {
            throw new \InvalidArgumentException('Journal entry must be balanced: debits ' . $totalDebits . ' != credits ' . $totalCredits);
        }
    }

    /**
     * Log system action
     */
    protected function logAction(JournalEntry $entry, string $action, array $extra = []): void
    {
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'journal_entry_' . $action,
            'entity_type' => 'JournalEntry',
            'entity_id' => $entry->id,
            'new_values' => array_merge([
                'entry_number' => $entry->entry_number,
                'status' => $entry->status,
            ], $extra),
            'ip_address' => request()->ip(),
        ]);
    }
}
```

- [ ] **Step 2: Update AccountingController for workflow**

Modify `app/Http/Controllers/AccountingController.php` - Add new methods:

```php
use App\Services\JournalEntryWorkflowService;

public function __construct(
    AccountingService $accountingService,
    JournalEntryWorkflowService $workflowService
) {
    $this->accountingService = $accountingService;
    $this->workflowService = $workflowService;
}
```

Add new methods:

```php
/**
 * Submit entry for approval
 */
public function submitForApproval(JournalEntry $entry)
{
    $this->requireManagerOrAdmin();

    try {
        $entry = $this->workflowService->submitForApproval($entry);

        return redirect()->back()->with('success', 'Entry submitted for approval.');
    } catch (\InvalidArgumentException $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}

/**
 * Approve and post entry
 */
public function approve(Request $request, JournalEntry $entry)
{
    $this->requireManagerOrAdmin();

    try {
        $entry = $this->workflowService->approveAndPost($entry, $request->get('notes'));

        return redirect()->back()->with('success', 'Entry approved and posted to ledger.');
    } catch (\InvalidArgumentException $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}

/**
 * Reject entry
 */
public function reject(Request $request, JournalEntry $entry)
{
    $this->requireManagerOrAdmin();

    $validated = $request->validate([
        'reason' => 'required|string|min:10',
    ]);

    try {
        $entry = $this->workflowService->reject($entry, $validated['reason']);

        return redirect()->back()->with('success', 'Entry rejected and returned to draft.');
    } catch (\InvalidArgumentException $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --filter=Accounting`
Expected: All pass

- [ ] **Step 4: Commit**

```bash
git add app/Services/JournalEntryWorkflowService.php
git add app/Http/Controllers/AccountingController.php
git commit -m "feat: add journal entry workflow service

- JournalEntryWorkflowService: handles draft/pending/post workflow
- generateEntryNumber: JE-YYYYMM-XXXX format
- createDraft: creates draft entry without posting
- submitForApproval: moves draft to pending
- approveAndPost: approves and creates ledger entries
- reject: returns pending entry to draft
- All actions logged to SystemLog

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 5: Cash Flow Service

**Files:**
- Create: `app/Services/CashFlowService.php`
- Create: `resources/views/accounting/cash-flow.blade.php`
- Modify: `app/Http/Controllers/FinancialStatementController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create CashFlowService**

```php
<?php
namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;

class CashFlowService
{
    protected AccountingService $accountingService;
    protected MathService $mathService;

    public function __construct(AccountingService $accountingService, MathService $mathService)
    {
        $this->accountingService = $accountingService;
        $this->mathService = $mathService;
    }

    /**
     * Get complete cash flow statement
     */
    public function getCashFlowStatement(string $fromDate, string $toDate): array
    {
        return [
            'operating' => $this->getOperatingCashFlow($fromDate, $toDate),
            'investing' => $this->getInvestingCashFlow($fromDate, $toDate),
            'financing' => $this->getFinancingCashFlow($fromDate, $toDate),
            'net_change' => $this->getNetCashChange($fromDate, $toDate),
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    /**
     * Calculate operating cash flow
     */
    public function getOperatingCashFlow(string $fromDate, string $toDate): array
    {
        // Cash from customers (receivables decreases = cash received)
        $cashFromCustomers = $this->calculateCashFromCustomers($fromDate, $toDate);

        // Cash paid to suppliers (payables increases = cash saved)
        $cashToSuppliers = $this->calculateCashToSuppliers($fromDate, $toDate);

        // Cash paid for salaries (expense accounts 6300)
        $cashSalaries = $this->calculateCashPaid('6300', $fromDate, $toDate);

        // Cash paid for rent (expense account 6400)
        $cashRent = $this->calculateCashPaid('6400', $fromDate, $toDate);

        // Other operating expenses
        $cashOpex = $this->calculateCashPaid(['6200', '6500', '6600', '6700', '6800', '6900'], $fromDate, $toDate);

        $total = $this->mathService->add(
            $this->mathService->add($cashFromCustomers, $cashToSuppliers),
            $this->mathService->add(
                $this->mathService->add($cashSalaries, $cashRent),
                $cashOpex
            )
        );

        return [
            'cash_from_customers' => $cashFromCustomers,
            'cash_to_suppliers' => $cashToSuppliers,
            'cash_salaries' => $cashSalaries,
            'cash_rent' => $cashRent,
            'cash_other_operating' => $cashOpex,
            'total' => $total,
        ];
    }

    /**
     * Calculate investing cash flow
     */
    public function getInvestingCashFlow(string $fromDate, string $toDate): array
    {
        // For MSB, investing activities are limited
        // Purchase/sale of fixed assets would go here
        // For now, return zeros as we don't track fixed assets

        return [
            'purchase_of_assets' => '0.00',
            'sale_of_assets' => '0.00',
            'total' => '0.00',
        ];
    }

    /**
     * Calculate financing cash flow
     */
    public function getFinancingCashFlow(string $fromDate, string $toDate): array
    {
        // For MSB, financing is limited to capital contributions and loans
        // For now, return zeros

        return [
            'proceeds_from_loans' => '0.00',
            'loan_repayments' => '0.00',
            'dividend_payments' => '0.00',
            'total' => '0.00',
        ];
    }

    /**
     * Calculate net cash change
     */
    protected function getNetCashChange(string $fromDate, string $toDate): string
    {
        $operating = $this->getOperatingCashFlow($fromDate, $toDate)['total'];
        $investing = $this->getInvestingCashFlow($fromDate, $toDate)['total'];
        $financing = $this->getFinancingCashFlow($fromDate, $toDate)['total'];

        return $this->mathService->add(
            $this->mathService->add($operating, $investing),
            $financing
        );
    }

    /**
     * Calculate cash received from customers
     */
    protected function calculateCashFromCustomers(string $fromDate, string $toDate): string
    {
        // Cash received = decreases in receivables + increases in payables (customer deposits)
        // For simplicity: look at 1500 (AR-Customers) account activity
        $arOpening = $this->accountingService->getAccountBalance('1500', $fromDate);
        $arClosing = $this->accountingService->getAccountBalance('1500', $toDate);

        // Cash received = opening - closing (if AR decreased, cash increased)
        $change = $this->mathService->subtract($arOpening, $arClosing);

        // Also consider cash sales (revenue accounts 5000)
        $revenueActivity = $this->accountingService->getAccountActivity('5000', $fromDate, $toDate);

        // Cash from customers = revenue - AR change (simplified)
        return $this->mathService->subtract($revenueActivity, $change);
    }

    /**
     * Calculate cash paid to suppliers
     */
    protected function calculateCashToSuppliers(string $fromDate, string $toDate): string
    {
        // AP activity - increases in AP = cash not yet paid
        $apOpening = $this->accountingService->getAccountBalance('3000', $fromDate);
        $apClosing = $this->accountingService->getAccountBalance('3000', $toDate);

        // If AP increased, we owe more (cash not paid)
        $apChange = $this->mathService->subtract($apClosing, $apOpening);

        // Net cash to suppliers = COGS - AP change (simplified)
        // Use expense 6000 as proxy for COGS
        $expenseActivity = $this->accountingService->getAccountActivity('6000', $fromDate, $toDate);

        return $this->mathService->subtract($expenseActivity, $apChange);
    }

    /**
     * Calculate cash paid for an expense account
     */
    protected function calculateCashPaid(string|array $accountCodes, string $fromDate, string $toDate): string
    {
        $codes = is_array($accountCodes) ? $accountCodes : [$accountCodes];
        $total = '0.00';

        foreach ($codes as $code) {
            $activity = $this->accountingService->getAccountActivity($code, $fromDate, $toDate);
            $total = $this->mathService->add($total, $activity);
        }

        return $total;
    }

    /**
     * Get cash position summary
     */
    public function getCashPosition(string $asOfDate): array
    {
        $accounts = [
            '1000' => 'Cash - MYR',
            '1010' => 'Cash - USD',
            '1020' => 'Cash - EUR',
            '1030' => 'Cash - GBP',
            '1040' => 'Cash - SGD',
            '1100' => 'Bank - Maybank',
            '1110' => 'Bank - CIMB',
            '1120' => 'Bank - Public Bank',
            '1130' => 'Bank - RHB',
        ];

        $cashAccounts = [];
        $totalCash = '0.00';

        foreach ($accounts as $code => $name) {
            $balance = $this->accountingService->getAccountBalance($code, $asOfDate);
            if ($this->mathService->compare($balance, '0') !== 0) {
                $cashAccounts[$code] = [
                    'name' => $name,
                    'balance' => $balance,
                ];
                $totalCash = $this->mathService->add($totalCash, $balance);
            }
        }

        return [
            'accounts' => $cashAccounts,
            'total_cash' => $totalCash,
            'as_of_date' => $asOfDate,
        ];
    }
}
```

- [ ] **Step 2: Create cash-flow view**

Create `resources/views/accounting/cash-flow.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Cash Flow Statement - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Cash Flow Statement</h2>
    <p>Track cash movements through operating, investing, and financing activities</p>
</div>

<form method="GET" class="card" style="padding: 1rem; margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 1rem; align-items: flex-end;">
        <div>
            <label>From Date</label>
            <input type="date" name="from" value="{{ $fromDate }}" class="form-control">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" name="to" value="{{ $toDate }}" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </div>
</form>

@if(isset($cashFlow))
<!-- Cash Position Summary -->
<div class="card">
    <h2>Cash Position as of {{ $cashPosition['as_of_date'] ?? $toDate }}</h2>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th style="text-align: right;">Balance (RM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cashPosition['accounts'] as $code => $account)
            <tr>
                <td>{{ $account['name'] }}</td>
                <td style="text-align: right;">{{ number_format((float) $account['balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background: #f7fafc; font-weight: 600;">
            <tr>
                <td>Total Cash</td>
                <td style="text-align: right;">{{ number_format((float) $cashPosition['total_cash'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Operating Activities -->
<div class="card">
    <h2>Operating Activities</h2>
    <table>
        <tbody>
            <tr>
                <td>Cash from Customers</td>
                <td style="text-align: right;">{{ number_format((float) $cashFlow['operating']['cash_from_customers'], 2) }}</td>
            </tr>
            <tr>
                <td>Cash to Suppliers</td>
                <td style="text-align: right;">( {{ number_format((float) abs((float) $cashFlow['operating']['cash_to_suppliers']), 2) }} )</td>
            </tr>
            <tr>
                <td>Cash for Salaries</td>
                <td style="text-align: right;">( {{ number_format((float) abs((float) $cashFlow['operating']['cash_salaries']), 2) }} )</td>
            </tr>
            <tr>
                <td>Cash for Rent</td>
                <td style="text-align: right;">( {{ number_format((float) abs((float) $cashFlow['operating']['cash_rent']), 2) }} )</td>
            </tr>
            <tr>
                <td>Other Operating Expenses</td>
                <td style="text-align: right;">( {{ number_format((float) abs((float) $cashFlow['operating']['cash_other_operating']), 2) }} )</td>
            </tr>
        </tbody>
        <tfoot style="background: #f7fafc; font-weight: 600;">
            <tr>
                <td>Net Cash from Operating</td>
                <td style="text-align: right;" class="{{ (float) $cashFlow['operating']['total'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ number_format((float) $cashFlow['operating']['total'], 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Investing Activities -->
<div class="card">
    <h2>Investing Activities</h2>
    <table>
        <tbody>
            <tr>
                <td>Purchase of Assets</td>
                <td style="text-align: right;">-</td>
            </tr>
            <tr>
                <td>Sale of Assets</td>
                <td style="text-align: right;">-</td>
            </tr>
        </tbody>
        <tfoot style="background: #f7fafc; font-weight: 600;">
            <tr>
                <td>Net Cash from Investing</td>
                <td style="text-align: right;">0.00</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Financing Activities -->
<div class="card">
    <h2>Financing Activities</h2>
    <table>
        <tbody>
            <tr>
                <td>Proceeds from Loans</td>
                <td style="text-align: right;">-</td>
            </tr>
            <tr>
                <td>Loan Repayments</td>
                <td style="text-align: right;">-</td>
            </tr>
            <tr>
                <td>Dividend Payments</td>
                <td style="text-align: right;">-</td>
            </tr>
        </tbody>
        <tfoot style="background: #f7fafc; font-weight: 600;">
            <tr>
                <td>Net Cash from Financing</td>
                <td style="text-align: right;">0.00</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Summary -->
<div class="card" style="background: #1a365d; color: white;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: white;">Net Change in Cash</h2>
        <span style="font-size: 1.5rem; font-weight: 700;">
            {{ number_format((float) $cashFlow['net_change'], 2) }}
        </span>
    </div>
</div>
@else
<div class="card">
    <div class="alert alert-info">
        No cash flow data available for the selected period. Create and post journal entries to see cash flow.
    </div>
</div>
@endif
@endsection
```

- [ ] **Step 3: Update FinancialStatementController**

Add to `app/Http/Controllers/FinancialStatementController.php`:

```php
use App\Services\CashFlowService;

protected CashFlowService $cashFlowService;

public function __construct(LedgerService $ledgerService, CashFlowService $cashFlowService)
{
    $this->ledgerService = $ledgerService;
    $this->cashFlowService = $cashFlowService;
}
```

Add method:
```php
public function cashFlow(Request $request)
{
    $this->requireManagerOrAdmin();

    $fromDate = $request->input('from', now()->startOfMonth()->toDateString());
    $toDate = $request->input('to', now()->toDateString());

    $cashFlow = $this->cashFlowService->getCashFlowStatement($fromDate, $toDate);
    $cashPosition = $this->cashFlowService->getCashPosition($toDate);

    return view('accounting.cash-flow', compact('cashFlow', 'cashPosition', 'fromDate', 'toDate'));
}
```

- [ ] **Step 4: Add route**

Add to `routes/web.php`:
```php
Route::get('/accounting/cash-flow', [FinancialStatementController::class, 'cashFlow'])->name('accounting.cash-flow');
```

- [ ] **Step 5: Add link to accounting menu**

Add to `resources/views/accounting.blade.php` quick links:
```blade
<a href="{{ route('accounting.cash-flow') }}" class="quick-link">
    <span class="quick-link-icon">💵</span>
    <span>Cash Flow</span>
</a>
```

- [ ] **Step 6: Test view compiles**

Run: `php artisan view:cache`
Expected: No errors

- [ ] **Step 7: Commit**

```bash
git add app/Services/CashFlowService.php
git add app/Http/Controllers/FinancialStatementController.php
git add resources/views/accounting/cash-flow.blade.php
git add routes/web.php
git add resources/views/accounting.blade.php
git commit -m "feat: add cash flow statement

- CashFlowService: calculate operating, investing, financing cash flows
- Cash position summary by account
- cash-flow.blade.php view
- Added route and menu link

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 6: Financial Ratio Service

**Files:**
- Create: `app/Services/FinancialRatioService.php`
- Create: `resources/views/accounting/ratios.blade.php`
- Modify: `app/Http/Controllers/FinancialStatementController.php`

- [ ] **Step 1: Create FinancialRatioService**

```php
<?php
namespace App\Services;

class FinancialRatioService
{
    protected AccountingService $accountingService;
    protected LedgerService $ledgerService;
    protected MathService $mathService;

    public function __construct(
        AccountingService $accountingService,
        LedgerService $ledgerService,
        MathService $mathService
    ) {
        $this->accountingService = $accountingService;
        $this->ledgerService = $ledgerService;
        $this->mathService = $mathService;
    }

    /**
     * Get all financial ratios
     */
    public function getAllRatios(string $asOfDate, string $fromDate, string $toDate): array
    {
        return [
            'liquidity' => $this->getLiquidityRatios($asOfDate),
            'profitability' => $this->getProfitabilityRatios($fromDate, $toDate),
            'leverage' => $this->getLeverageRatios($asOfDate),
            'efficiency' => $this->getEfficiencyRatios($fromDate, $toDate),
        ];
    }

    /**
     * Liquidity Ratios
     */
    public function getLiquidityRatios(string $asOfDate): array
    {
        // Current Assets
        $currentAssets = $this->sumAccountTypeBalances('Asset', $asOfDate, ['Cash', 'Receivable', 'Prepaid']);

        // Current Liabilities
        $currentLiabilities = $this->sumAccountTypeBalances('Liability', $asOfDate, ['Payable', 'Accrued']);

        // Inventory (exclude from quick assets)
        $inventory = $this->accountingService->getAccountBalance('2000', $asOfDate);

        // Cash
        $cash = $this->getCashBalance($asOfDate);

        // Current Ratio = Current Assets / Current Liabilities
        $currentRatio = $this->divide($currentAssets, $currentLiabilities);

        // Quick Ratio = (Current Assets - Inventory) / Current Liabilities
        $quickAssets = $this->mathService->subtract($currentAssets, $inventory);
        $quickRatio = $this->divide($quickAssets, $currentLiabilities);

        // Cash Ratio = Cash / Current Liabilities
        $cashRatio = $this->divide($cash, $currentLiabilities);

        return [
            'current_ratio' => $this->formatRatio($currentRatio),
            'current_ratio_value' => $currentRatio,
            'quick_ratio' => $this->formatRatio($quickRatio),
            'quick_ratio_value' => $quickRatio,
            'cash_ratio' => $this->formatRatio($cashRatio),
            'cash_ratio_value' => $cashRatio,
            'current_assets' => $currentAssets,
            'current_liabilities' => $currentLiabilities,
            'inventory' => $inventory,
            'cash' => $cash,
        ];
    }

    /**
     * Profitability Ratios
     */
    public function getProfitabilityRatios(string $fromDate, string $toDate): array
    {
        $pl = $this->ledgerService->getProfitAndLoss($fromDate, $toDate);

        $revenue = $pl['total_revenue'];
        $expenses = $pl['total_expenses'];
        $netIncome = $pl['net_profit'];

        // Total Assets (for ROA)
        $totalAssets = $this->sumAccountTypeBalances('Asset', $toDate);

        // Total Equity (for ROE)
        $totalEquity = $this->sumAccountTypeBalances('Equity', $toDate);

        // Gross Profit Margin = (Revenue - COGS) / Revenue (assume COGS = expense 6000)
        $cogs = $this->accountingService->getAccountActivity('6000', $fromDate, $toDate);
        $grossProfit = $this->mathService->subtract($revenue, $cogs);
        $grossMargin = $this->divide($grossProfit, $revenue);

        // Net Profit Margin = Net Income / Revenue
        $netMargin = $this->divide($netIncome, $revenue);

        // ROE = Net Income / Equity
        $roe = $this->divide($netIncome, $totalEquity);

        // ROA = Net Income / Total Assets
        $roa = $this->divide($netIncome, $totalAssets);

        return [
            'gross_profit_margin' => $this->formatPercent($grossMargin),
            'gross_profit_margin_value' => $grossMargin,
            'net_profit_margin' => $this->formatPercent($netMargin),
            'net_profit_margin_value' => $netMargin,
            'roe' => $this->formatPercent($roe),
            'roe_value' => $roe,
            'roa' => $this->formatPercent($roa),
            'roa_value' => $roa,
            'revenue' => $revenue,
            'net_income' => $netIncome,
            'total_assets' => $totalAssets,
            'total_equity' => $totalEquity,
        ];
    }

    /**
     * Leverage Ratios
     */
    public function getLeverageRatios(string $asOfDate): array
    {
        $totalDebt = $this->sumAccountTypeBalances('Liability', $asOfDate);
        $totalEquity = $this->sumAccountTypeBalances('Equity', $asOfDate);
        $totalAssets = $this->sumAccountTypeBalances('Asset', $asOfDate);

        // Debt-to-Equity = Total Debt / Equity
        $debtToEquity = $this->divide($totalDebt, $totalEquity);

        // Debt-to-Assets = Total Debt / Total Assets
        $debtToAssets = $this->divide($totalDebt, $totalAssets);

        return [
            'debt_to_equity' => $this->formatRatio($debtToEquity),
            'debt_to_equity_value' => $debtToEquity,
            'debt_to_assets' => $this->formatPercent($debtToAssets),
            'debt_to_assets_value' => $debtToAssets,
            'total_debt' => $totalDebt,
            'total_equity' => $totalEquity,
        ];
    }

    /**
     * Efficiency Ratios
     */
    public function getEfficiencyRatios(string $fromDate, string $toDate): array
    {
        $revenue = $this->accountingService->getAccountActivity('5000', $fromDate, $toDate);
        $totalAssets = $this->sumAccountTypeBalances('Asset', $toDate);
        $inventory = $this->accountingService->getAccountBalance('2000', $toDate);

        // Asset Turnover = Revenue / Total Assets
        $assetTurnover = $this->divide($revenue, $totalAssets);

        // Inventory Turnover = COGS / Inventory (use expense 6000 as COGS)
        $cogs = $this->accountingService->getAccountActivity('6000', $fromDate, $toDate);
        $inventoryTurnover = $this->divide($cogs, $inventory);

        return [
            'asset_turnover' => $this->formatRatio($assetTurnover),
            'asset_turnover_value' => $assetTurnover,
            'inventory_turnover' => $this->formatRatio($inventoryTurnover),
            'inventory_turnover_value' => $inventoryTurnover,
        ];
    }

    /**
     * Helper: Sum account type balances
     */
    protected function sumAccountTypeBalances(string $type, string $asOfDate, array $classes = []): string
    {
        $query = \App\Models\ChartOfAccount::where('account_type', $type);

        if (!empty($classes)) {
            $query->whereIn('account_class', $classes);
        }

        $accounts = $query->get();
        $total = '0.00';

        foreach ($accounts as $account) {
            $balance = $this->accountingService->getAccountBalance($account->account_code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Helper: Get total cash balance
     */
    protected function getCashBalance(string $asOfDate): string
    {
        $cashAccounts = ['1000', '1010', '1020', '1030', '1040', '1100', '1110', '1120', '1130'];
        $total = '0.00';

        foreach ($cashAccounts as $code) {
            $balance = $this->accountingService->getAccountBalance($code, $asOfDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Helper: Safe division
     */
    protected function divide(string $numerator, string $denominator): string
    {
        if ($this->mathService->compare($denominator, '0') === 0) {
            return '0.00';
        }

        return $this->mathService->divide($numerator, $denominator, 4);
    }

    /**
     * Helper: Format as ratio (e.g., 1.50:1)
     */
    protected function formatRatio(string $value): string
    {
        return number_format((float) $value, 2) . ':1';
    }

    /**
     * Helper: Format as percentage
     */
    protected function formatPercent(string $value): string
    {
        return number_format((float) $value * 100, 2) . '%';
    }
}
```

- [ ] **Step 2: Create ratios view**

Create `resources/views/accounting/ratios.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Financial Ratios - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Financial Ratios Analysis</h2>
    <p>Key financial metrics for assessing business performance</p>
</div>

<form method="GET" class="card" style="padding: 1rem; margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 1rem; align-items: flex-end;">
        <div>
            <label>From Date</label>
            <input type="date" name="from" value="{{ $fromDate }}" class="form-control">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" name="to" value="{{ $toDate }}" class="form-control">
        </div>
        <div>
            <label>As of Date</label>
            <input type="date" name="as_of" value="{{ $asOfDate }}" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </div>
</form>

@if(isset($ratios))
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">

<!-- Liquidity Ratios -->
<div class="card">
    <h2 style="border-left: 4px solid #3182ce; padding-left: 0.5rem;">Liquidity Ratios</h2>
    <p style="color: #718096; font-size: 0.875rem; margin-bottom: 1rem;">
        Measure ability to meet short-term obligations
    </p>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Current Ratio</span>
            <strong class="{{ (float) $ratios['liquidity']['current_ratio_value'] >= 1 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $ratios['liquidity']['current_ratio'] }}
            </strong>
        </div>
        <small style="color: #718096;">Assets / Liabilities (ideal: > 1.5)</small>
    </div>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Quick Ratio</span>
            <strong class="{{ (float) $ratios['liquidity']['quick_ratio_value'] >= 1 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $ratios['liquidity']['quick_ratio'] }}
            </strong>
        </div>
        <small style="color: #718096;">(Assets - Inv) / Liabilities (ideal: > 1.0)</small>
    </div>

    <div>
        <div style="display: flex; justify-content: space-between;">
            <span>Cash Ratio</span>
            <strong>{{ $ratios['liquidity']['cash_ratio'] }}</strong>
        </div>
        <small style="color: #718096;">Cash / Liabilities (ideal: > 0.2)</small>
    </div>
</div>

<!-- Profitability Ratios -->
<div class="card">
    <h2 style="border-left: 4px solid #38a169; padding-left: 0.5rem;">Profitability Ratios</h2>
    <p style="color: #718096; font-size: 0.875rem; margin-bottom: 1rem;">
        Measure ability to generate earnings
    </p>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Gross Margin</span>
            <strong class="pnl-positive">{{ $ratios['profitability']['gross_profit_margin'] }}</strong>
        </div>
        <small style="color: #718096;">(Revenue - COGS) / Revenue</small>
    </div>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Net Profit Margin</span>
            <strong class="{{ (float) $ratios['profitability']['net_profit_margin_value'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $ratios['profitability']['net_profit_margin'] }}
            </strong>
        </div>
        <small style="color: #718096;">Net Income / Revenue</small>
    </div>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>ROE</span>
            <strong class="{{ (float) $ratios['profitability']['roe_value'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $ratios['profitability']['roe'] }}
            </strong>
        </div>
        <small style="color: #718096;">Return on Equity</small>
    </div>

    <div>
        <div style="display: flex; justify-content: space-between;">
            <span>ROA</span>
            <strong class="{{ (float) $ratios['profitability']['roa_value'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $ratios['profitability']['roa'] }}
            </strong>
        </div>
        <small style="color: #718096;">Return on Assets</small>
    </div>
</div>

<!-- Leverage Ratios -->
<div class="card">
    <h2 style="border-left: 4px solid #dd6b20; padding-left: 0.5rem;">Leverage Ratios</h2>
    <p style="color: #718096; font-size: 0.875rem; margin-bottom: 1rem;">
        Measure debt levels and financial leverage
    </p>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Debt-to-Equity</span>
            <strong>{{ $ratios['leverage']['debt_to_equity'] }}</strong>
        </div>
        <small style="color: #718096;">Total Debt / Equity (ideal: < 2.0)</small>
    </div>

    <div>
        <div style="display: flex; justify-content: space-between;">
            <span>Debt-to-Assets</span>
            <strong>{{ $ratios['leverage']['debt_to_assets'] }}</strong>
        </div>
        <small style="color: #718096;">Total Debt / Total Assets (ideal: < 0.5)</small>
    </div>
</div>

<!-- Efficiency Ratios -->
<div class="card">
    <h2 style="border-left: 4px solid #805ad5; padding-left: 0.5rem;">Efficiency Ratios</h2>
    <p style="color: #718096; font-size: 0.875rem; margin-bottom: 1rem;">
        Measure how effectively assets are used
    </p>

    <div style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between;">
            <span>Asset Turnover</span>
            <strong>{{ $ratios['efficiency']['asset_turnover'] }}</strong>
        </div>
        <small style="color: #718096;">Revenue / Total Assets (higher is better)</small>
    </div>

    <div>
        <div style="display: flex; justify-content: space-between;">
            <span>Inventory Turnover</span>
            <strong>{{ $ratios['efficiency']['inventory_turnover'] }}</strong>
        </div>
        <small style="color: #718096;">COGS / Inventory (higher is better)</small>
    </div>
</div>

</div>

<!-- Summary -->
<div class="card">
    <h2>Key Figures</h2>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: #1a365d;">
                RM {{ number_format((float) $ratios['liquidity']['current_assets'], 0) }}
            </div>
            <small style="color: #718096;">Current Assets</small>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: #1a365d;">
                RM {{ number_format((float) $ratios['liquidity']['current_liabilities'], 0) }}
            </div>
            <small style="color: #718096;">Current Liabilities</small>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: #38a169;">
                RM {{ number_format((float) $ratios['profitability']['revenue'], 0) }}
            </div>
            <small style="color: #718096;">Revenue</small>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; {{ (float) $ratios['profitability']['net_income'] >= 0 ? 'color: #38a169;' : 'color: #e53e3e;' }}">
                RM {{ number_format((float) $ratios['profitability']['net_income'], 0) }}
            </div>
            <small style="color: #718096;">Net Income</small>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="alert alert-info">
        No financial data available. Create journal entries to see ratio analysis.
    </div>
</div>
@endif
@endsection
```

- [ ] **Step 3: Update FinancialStatementController**

Add method:
```php
use App\Services\FinancialRatioService;

protected FinancialRatioService $ratioService;

public function __construct(
    LedgerService $ledgerService,
    CashFlowService $cashFlowService,
    FinancialRatioService $ratioService
) {
    $this->ledgerService = $ledgerService;
    $this->cashFlowService = $cashFlowService;
    $this->ratioService = $ratioService;
}

public function ratios(Request $request)
{
    $this->requireManagerOrAdmin();

    $asOfDate = $request->input('as_of', now()->toDateString());
    $fromDate = $request->input('from', now()->startOfMonth()->toDateString());
    $toDate = $request->input('to', now()->toDateString());

    $ratios = $this->ratioService->getAllRatios($asOfDate, $fromDate, $toDate);

    return view('accounting.ratios', compact('ratios', 'asOfDate', 'fromDate', 'toDate'));
}
```

- [ ] **Step 4: Add route**

Add to `routes/web.php`:
```php
Route::get('/accounting/ratios', [FinancialStatementController::class, 'ratios'])->name('accounting.ratios');
```

- [ ] **Step 5: Add link to accounting menu**

Add to `resources/views/accounting.blade.php` quick links:
```blade
<a href="{{ route('accounting.ratios') }}" class="quick-link">
    <span class="quick-link-icon">📊</span>
    <span>Financial Ratios</span>
</a>
```

- [ ] **Step 6: Test view compiles**

Run: `php artisan view:cache`
Expected: No errors

- [ ] **Step 7: Commit**

```bash
git add app/Services/FinancialRatioService.php
git add app/Http/Controllers/FinancialStatementController.php
git add resources/views/accounting/ratios.blade.php
git add routes/web.php
git add resources/views/accounting.blade.php
git commit -m "feat: add financial ratio analysis

- FinancialRatioService: liquidity, profitability, leverage, efficiency ratios
- ratios.blade.php: dashboard view with key metrics
- Added route and menu link

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 7: Fiscal Year Service

**Files:**
- Create: `app/Services/FiscalYearService.php`
- Create: `resources/views/accounting/fiscal-years.blade.php`
- Create: `app/Http/Controllers/FiscalYearController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create FiscalYearService**

```php
<?php
namespace App\Services;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

class FiscalYearService
{
    protected MathService $mathService;
    protected AccountingService $accountingService;

    public function __construct(MathService $mathService, AccountingService $accountingService)
    {
        $this->mathService = $mathService;
        $this->accountingService = $accountingService;
    }

    /**
     * Create a new fiscal year
     */
    public function createFiscalYear(string $yearCode, string $startDate, string $endDate): FiscalYear
    {
        $fiscalYear = FiscalYear::create([
            'year_code' => $yearCode,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'Open',
        ]);

        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'fiscal_year_created',
            'entity_type' => 'FiscalYear',
            'entity_id' => $fiscalYear->id,
            'new_values' => ['year_code' => $yearCode],
            'ip_address' => request()->ip(),
        ]);

        return $fiscalYear;
    }

    /**
     * Close a fiscal year (year-end closing)
     */
    public function closeFiscalYear(FiscalYear $fiscalYear): array
    {
        if ($fiscalYear->isClosed()) {
            throw new \InvalidArgumentException('Fiscal year is already closed');
        }

        // Check all periods are closed
        $openPeriods = $fiscalYear->accountingPeriods()->where('status', 'open')->count();
        if ($openPeriods > 0) {
            throw new \InvalidArgumentException("Cannot close fiscal year with {$openPeriods} open periods");
        }

        return DB::transaction(function () use ($fiscalYear) {
            $yearEndDate = $fiscalYear->end_date->toDateString();

            // Get P&L balances
            $revenues = $this->getRevenueBalances($fiscalYear->start_date->toDateString(), $yearEndDate);
            $expenses = $this->getExpenseBalances($fiscalYear->start_date->toDateString(), $yearEndDate);

            // Net Income = Total Revenue - Total Expenses
            $netIncome = $this->mathService->subtract($revenues, $expenses);

            // Create closing entries
            $closingEntries = [];

            // 1. Close Revenue accounts to Income Summary (4998)
            $revenueAccounts = ChartOfAccount::where('account_type', 'Revenue')->get();
            foreach ($revenueAccounts as $account) {
                $balance = $this->accountingService->getAccountBalance($account->account_code, $yearEndDate);
                if ($this->mathService->compare($balance, '0') !== 0) {
                    $closingEntries[] = [
                        'account_code' => $account->account_code,
                        'credit' => $balance,
                        'debit' => '0',
                    ];
                }
            }

            // 2. Close Expense accounts to Income Summary (4998)
            $expenseAccounts = ChartOfAccount::where('account_type', 'Expense')->get();
            foreach ($expenseAccounts as $account) {
                $balance = $this->accountingService->getAccountBalance($account->account_code, $yearEndDate);
                if ($this->mathService->compare($balance, '0') !== 0) {
                    $closingEntries[] = [
                        'account_code' => $account->account_code,
                        'debit' => $balance,
                        'credit' => '0',
                    ];
                }
            }

            // 3. Close Income Summary (4998) to Retained Earnings (4999)
            $closingEntries[] = [
                'account_code' => '4998',
                'debit' => $revenues,
                'credit' => $expenses,
            ];
            $closingEntries[] = [
                'account_code' => '4999',
                'credit' => $netIncome,
                'debit' => '0',
            ];

            // Create the closing journal entry
            $entry = JournalEntry::create([
                'entry_date' => $yearEndDate,
                'entry_number' => 'CL-' . $fiscalYear->year_code . '-001',
                'reference_type' => 'Year-End Closing',
                'description' => 'Year-End Closing Entry for ' . $fiscalYear->year_code,
                'status' => 'Posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($closingEntries as $line) {
                \App\Models\JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code' => $line['account_code'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            // Post to ledger
            $this->postClosingEntries($entry);

            // Update fiscal year status
            $fiscalYear->update([
                'status' => 'Closed',
                'closed_by' => auth()->id(),
                'closed_at' => now(),
            ]);

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'fiscal_year_closed',
                'entity_type' => 'FiscalYear',
                'entity_id' => $fiscalYear->id,
                'new_values' => [
                    'year_code' => $fiscalYear->year_code,
                    'net_income' => $netIncome,
                    'closing_entry_id' => $entry->id,
                ],
                'ip_address' => request()->ip(),
            ]);

            return [
                'fiscal_year' => $fiscalYear->fresh(),
                'closing_entry' => $entry->fresh()->load('lines'),
                'net_income' => $netIncome,
                'total_revenue' => $revenues,
                'total_expenses' => $expenses,
            ];
        });
    }

    /**
     * Get year-end report
     */
    public function getYearEndReport(FiscalYear $fiscalYear): array
    {
        $balanceSheet = app(LedgerService::class)->getBalanceSheet($fiscalYear->end_date->toDateString());
        $pl = $this->getYearP&L($fiscalYear->start_date->toDateString(), $fiscalYear->end_date->toDateString());

        return [
            'fiscal_year' => $fiscalYear,
            'balance_sheet' => $balanceSheet,
            'profit_loss' => $pl,
            'as_of_date' => $fiscalYear->end_date->toDateString(),
        ];
    }

    /**
     * Get year P&L summary
     */
    protected function getYearP&L(string $fromDate, string $toDate): array
    {
        $revenues = $this->getRevenueBalances($fromDate, $toDate);
        $expenses = $this->getExpenseBalances($fromDate, $toDate);
        $netIncome = $this->mathService->subtract($revenues, $expenses);

        return [
            'total_revenue' => $revenues,
            'total_expenses' => $expenses,
            'net_income' => $netIncome,
            'period' => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    /**
     * Get total revenue for period
     */
    protected function getRevenueBalances(string $fromDate, string $toDate): string
    {
        $accounts = ChartOfAccount::where('account_type', 'Revenue')->get();
        $total = '0.00';

        foreach ($accounts as $account) {
            $balance = $this->accountingService->getAccountBalance($account->account_code, $toDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Get total expenses for period
     */
    protected function getExpenseBalances(string $fromDate, string $toDate): string
    {
        $accounts = ChartOfAccount::where('account_type', 'Expense')->get();
        $total = '0.00';

        foreach ($accounts as $account) {
            $balance = $this->accountingService->getAccountBalance($account->account_code, $toDate);
            $total = $this->mathService->add($total, $balance);
        }

        return $total;
    }

    /**
     * Post closing entries to ledger
     */
    protected function postClosingEntries(JournalEntry $entry): void
    {
        $entry->loadMissing('lines');

        foreach ($entry->lines as $line) {
            $currentBalance = $this->accountingService->getAccountBalance($line->account_code);
            $isDebitNormal = in_array(
                ChartOfAccount::find($line->account_code)?->account_type,
                ['Asset', 'Expense']
            );

            if ($isDebitNormal) {
                $newBalance = $this->mathService->add(
                    $this->mathService->add($currentBalance, (string) $line->debit),
                    $this->mathService->multiply((string) $line->credit, '-1')
                );
            } else {
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
}
```

- [ ] **Step 2: Create FiscalYearController**

```php
<?php
namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\FiscalYearService;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    protected FiscalYearService $fiscalYearService;

    public function __construct(FiscalYearService $fiscalYearService)
    {
        $this->fiscalYearService = $fiscalYearService;
    }

    protected function requireManagerOrAdmin(): void
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Manager or Admin access required.');
        }
    }

    public function index()
    {
        $this->requireManagerOrAdmin();

        $fiscalYears = FiscalYear::orderBy('year_code', 'desc')->paginate(10);

        return view('accounting.fiscal-years', compact('fiscalYears'));
    }

    public function show(FiscalYear $fiscalYear)
    {
        $this->requireManagerOrAdmin();

        $report = $this->fiscalYearService->getYearEndReport($fiscalYear);

        return view('accounting.fiscal-year-show', compact('fiscalYear', 'report'));
    }

    public function close(FiscalYear $fiscalYear)
    {
        $this->requireManagerOrAdmin();

        try {
            $result = $this->fiscalYearService->closeFiscalYear($fiscalYear);

            return redirect()->back()->with('success', sprintf(
                'Fiscal year %s closed successfully. Net Income: RM %s',
                $fiscalYear->year_code,
                number_format((float) $result['net_income'], 2)
            ));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'year_code' => 'required|string|max:10|unique:fiscal_years,year_code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $fiscalYear = $this->fiscalYearService->createFiscalYear(
            $validated['year_code'],
            $validated['start_date'],
            $validated['end_date']
        );

        return redirect()->back()->with('success', "Fiscal year {$fiscalYear->year_code} created.");
    }
}
```

- [ ] **Step 3: Create fiscal-years view**

Create `resources/views/accounting/fiscal-years.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Fiscal Years - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Fiscal Year Management</h2>
    <p>Manage annual accounting periods and year-end closing</p>
</div>

<div class="card">
    <h2>Create New Fiscal Year</h2>
    <form method="POST" action="{{ route('accounting.fiscal-years.store') }}">
        @csrf
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
            <div>
                <label>Year Code</label>
                <input type="text" name="year_code" placeholder="FY2027" class="form-control" required>
            </div>
            <div>
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div>
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Fiscal Years</h2>
    <table>
        <thead>
            <tr>
                <th>Year Code</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Closed By</th>
                <th>Closed At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fiscalYears as $fy)
            <tr>
                <td><strong>{{ $fy->year_code }}</strong></td>
                <td>{{ $fy->start_date->format('Y-m-d') }}</td>
                <td>{{ $fy->end_date->format('Y-m-d') }}</td>
                <td>
                    @if($fy->isClosed())
                        <span class="status-badge status-inactive">Closed</span>
                    @else
                        <span class="status-badge status-active">Open</span>
                    @endif
                </td>
                <td>{{ $fy->closedBy?->username ?? '-' }}</td>
                <td>{{ $fy->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                <td>
                    <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="btn btn-sm btn-info">Report</a>
                    @if($fy->isOpen())
                        <form action="{{ route('accounting.fiscal-years.close', $fy) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Close this fiscal year? All periods must be closed first.')">Close Year</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #718096;">No fiscal years found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{ $fiscalYears->links() }}
</div>
@endsection
```

Also create `resources/views/accounting/fiscal-year-show.blade.php`:

```blade
@extends('layouts.app')

@section('title', "Fiscal Year Report - {$fiscalYear->year_code}")

@section('content')
<div class="accounting-header">
    <h2>Fiscal Year Report: {{ $fiscalYear->year_code }}</h2>
    <p>
        Period: {{ $fiscalYear->start_date->format('Y-m-d') }} to {{ $fiscalYear->end_date->format('Y-m-d') }}
        @if($fiscalYear->isClosed())
            <span class="status-badge status-inactive">Closed</span>
        @else
            <span class="status-badge status-active">Open</span>
        @endif
    </p>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
    <div class="card summary-box">
        <div class="summary-label">Total Revenue</div>
        <div class="summary-value pnl-positive">RM {{ number_format((float) $report['profit_loss']['total_revenue'], 2) }}</div>
    </div>
    <div class="card summary-box">
        <div class="summary-label">Total Expenses</div>
        <div class="summary-value pnl-negative">RM {{ number_format((float) $report['profit_loss']['total_expenses'], 2) }}</div>
    </div>
    <div class="card summary-box">
        <div class="summary-label">Net Income</div>
        <div class="summary-value {{ (float) $report['profit_loss']['net_income'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
            RM {{ number_format((float) $report['profit_loss']['net_income'], 2) }}
        </div>
    </div>
</div>

<div class="card">
    <h2>Balance Sheet as of {{ $report['as_of_date'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" style="font-weight: 600; background: #f7fafc;">ASSETS</td>
            </tr>
            @foreach($report['balance_sheet']['assets'] as $asset)
            <tr>
                <td style="padding-left: 2rem;">{{ $asset['account_code'] }} - {{ $asset['account_name'] }}</td>
                <td style="text-align: right;">{{ number_format((float) $asset['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: 600; background: #f7fafc;">
                <td>Total Assets</td>
                <td style="text-align: right;">{{ number_format((float) $report['balance_sheet']['total_assets'], 2) }}</td>
            </tr>

            <tr>
                <td colspan="2" style="font-weight: 600; background: #f7fafc;">LIABILITIES</td>
            </tr>
            @foreach($report['balance_sheet']['liabilities'] as $liability)
            <tr>
                <td style="padding-left: 2rem;">{{ $liability['account_code'] }} - {{ $liability['account_name'] }}</td>
                <td style="text-align: right;">{{ number_format((float) $liability['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: 600; background: #f7fafc;">
                <td>Total Liabilities</td>
                <td style="text-align: right;">{{ number_format((float) $report['balance_sheet']['total_liabilities'], 2) }}</td>
            </tr>

            <tr>
                <td colspan="2" style="font-weight: 600; background: #f7fafc;">EQUITY</td>
            </tr>
            @foreach($report['balance_sheet']['equity'] as $eq)
            <tr>
                <td style="padding-left: 2rem;">{{ $eq['account_code'] }} - {{ $eq['account_name'] }}</td>
                <td style="text-align: right;">{{ number_format((float) $eq['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: 600; background: #f7fafc;">
                <td>Total Equity</td>
                <td style="text-align: right;">{{ number_format((float) $report['balance_sheet']['total_equity'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem;">
    <a href="{{ route('accounting.fiscal-years.index') }}" class="btn btn-secondary">Back to List</a>
</div>
@endsection
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:
```php
// Fiscal Years
Route::get('/accounting/fiscal-years', [FiscalYearController::class, 'index'])->name('accounting.fiscal-years');
Route::post('/accounting/fiscal-years', [FiscalYearController::class, 'store'])->name('accounting.fiscal-years.store');
Route::get('/accounting/fiscal-years/{fiscalYear}', [FiscalYearController::class, 'show'])->name('accounting.fiscal-years.show');
Route::post('/accounting/fiscal-years/{fiscalYear}/close', [FiscalYearController::class, 'close'])->name('accounting.fiscal-years.close');
```

- [ ] **Step 5: Add link to accounting menu**

Add to `resources/views/accounting.blade.php` quick links:
```blade
<a href="{{ route('accounting.fiscal-years.index') }}" class="quick-link">
    <span class="quick-link-icon">📅</span>
    <span>Fiscal Years</span>
</a>
```

- [ ] **Step 6: Test view compiles**

Run: `php artisan view:cache`
Expected: No errors

- [ ] **Step 7: Commit**

```bash
git add app/Services/FiscalYearService.php
git add app/Http/Controllers/FiscalYearController.php
git add resources/views/accounting/fiscal-years.blade.php
git add resources/views/accounting/fiscal-year-show.blade.php
git add routes/web.php
git add resources/views/accounting.blade.php
git commit -m "feat: add fiscal year management

- FiscalYearService: create, close fiscal years, year-end closing
- FiscalYearController: CRUD + close action
- fiscal-years.blade.php: list and manage fiscal years
- fiscal-year-show.blade.php: year-end report
- Added routes and menu link

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 8: Update Journal Entry Views

**Files:**
- Modify: `resources/views/accounting/journal/index.blade.php`
- Modify: `resources/views/accounting/journal/create.blade.php`
- Create: `resources/views/accounting/journal/workflow.blade.php`

- [ ] **Step 1: Update journal index view**

Modify to show workflow status and add workflow actions:

```blade
<!-- Add workflow status badges and action buttons -->
<td>
    @if($entry->isPosted())
        <span class="badge bg-success">Posted</span>
    @elseif($entry->isReversed())
        <span class="badge bg-warning">Reversed</span>
    @elseif($entry->isPending())
        <span class="badge bg-info">Pending</span>
    @else
        <span class="badge bg-secondary">Draft</span>
    @endif
</td>
<td>
    <a href="{{ route('accounting.journal.show', $entry) }}" class="btn btn-sm btn-info">View</a>
    @if($entry->isPending())
        <form action="{{ route('accounting.journal.approve', $entry) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">Approve</button>
        </form>
        <form action="{{ route('accounting.journal.reject', $entry) }}" method="POST" style="display:inline;">
            @csrf
            <input type="hidden" name="reason" value="Rejected for review">
            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
        </form>
    @endif
    @if($entry->isDraft())
        <form action="{{ route('accounting.journal.submit', $entry) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">Submit</button>
        </form>
    @endif
</td>
```

- [ ] **Step 2: Update journal create view**

Add cost center and department selection:

```blade
<!-- Add after description field -->
<div class="mb-3">
    <label for="cost_center_id">Cost Center (Optional)</label>
    <select name="cost_center_id" id="cost_center_id" class="form-control">
        <option value="">-- None --</option>
        @foreach(\App\Models\CostCenter::where('is_active', true)->get() as $cc)
            <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="department_id">Department (Optional)</label>
    <select name="department_id" id="department_id" class="form-control">
        <option value="">-- None --</option>
        @foreach(\App\Models\Department::where('is_active', true)->get() as $dept)
            <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
        @endforeach
    </select>
</div>
```

- [ ] **Step 3: Add workflow routes**

Add to `routes/web.php`:
```php
Route::post('/accounting/journal/{entry}/submit', [AccountingController::class, 'submitForApproval'])->name('accounting.journal.submit');
Route::post('/accounting/journal/{entry}/approve', [AccountingController::class, 'approve'])->name('accounting.journal.approve');
Route::post('/accounting/journal/{entry}/reject', [AccountingController::class, 'reject'])->name('accounting.journal.reject');
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/accounting/journal/index.blade.php
git add resources/views/accounting/journal/create.blade.php
git add routes/web.php
git commit -m "feat: add journal workflow UI

- Updated journal index with workflow status badges
- Added approve/reject/submit action buttons
- Added cost center and department selection to create form
- Added workflow routes

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 9: Final Tests and Verification

**Files:**
- Run all tests
- Verify views compile
- Check routes

- [ ] **Step 1: Run migrations fresh (if needed for testing)**

Run: `php artisan migrate:refresh --seed`
Expected: All migrations run, seeders populate data

- [ ] **Step 2: Run full test suite**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 3: Verify views compile**

Run: `php artisan view:cache`
Expected: Views cached successfully

- [ ] **Step 4: List routes to verify**

Run: `php artisan route:list | grep accounting`
Expected: All new routes listed

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat: complete accounting production features

Implemented all 4 features:
1. Journal Entry Workflow (Draft/Pending/Posted with approvals)
2. Enhanced Chart of Accounts (35+ accounts, cost centers, departments)
3. Cash Flow Statement + Financial Ratios
4. Fiscal Year Management (year-end closing)

New Services:
- JournalEntryWorkflowService
- CashFlowService
- FinancialRatioService
- FiscalYearService

New Models:
- Department
- CostCenter
- FiscalYear

New Views:
- accounting/cash-flow.blade.php
- accounting/ratios.blade.php
- accounting/fiscal-years.blade.php
- accounting/fiscal-year-show.blade.php

Database:
- 5 new migrations
- 3 new seeders
- Enhanced chart of accounts

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Self-Review Checklist

- [ ] Spec coverage: All 4 features implemented?
- [ ] No placeholder code (TODO, TBD, etc.)
- [ ] All method signatures consistent
- [ ] All new routes added
- [ ] All new views created
- [ ] Tests pass
- [ ] Views compile
- [ ] Seeders run successfully
