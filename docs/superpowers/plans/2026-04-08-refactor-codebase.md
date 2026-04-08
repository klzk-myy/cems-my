# Codebase Refactoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split 3 large controllers into focused units, add API versioning (v1), and consolidate 66 migrations into ~20 logically grouped ones.

**Architecture:**
- **Controllers**: Split by responsibility (CRUD, Approvals, Batch, Reports, KYC, Analytics, Regulatory) into focused files
- **API Versioning**: URI prefix `/api/v1/` with controller namespace `App\Http\Controllers\Api\V1\`
- **Migrations**: Consolidate by table grouping - combine sequential adds/alters into logical "table setup" migrations

**Tech Stack:** Laravel 10.x, PHP 8.1+

---

## PART 1: SPLIT LARGE CONTROLLERS

### Task 1: Split TransactionController (1142 lines → 5 files)

**Files:**
- Modify: `app/Http/Controllers/TransactionController.php` (keep core CRUD + receipt)
- Create: `app/Http/Controllers/Transaction/TransactionBatchController.php`
- Create: `app/Http/Controllers/Transaction/TransactionApprovalController.php`
- Create: `app/Http/Controllers/Transaction/TransactionCancellationController.php`
- Create: `app/Http/Controllers/Transaction/TransactionReportController.php`
- Modify: `routes/web.php` (update route references)

**Route Updates:**
```php
// Current routes being moved:
POST   /transactions/{transaction}/approve       → TransactionApprovalController@approve
POST   /transactions/{transaction}/cancel          → TransactionCancellationController@cancel
GET    /transactions/{transaction}/cancel         → TransactionCancellationController@showCancel
POST   /transactions/{transaction}/confirm         → TransactionApprovalController@confirm
GET    /transactions/{transaction}/confirm         → TransactionApprovalController@showConfirm
GET    /transactions/batch-upload                 → TransactionBatchController@showBatchUpload
POST   /transactions/batch-upload                 → TransactionBatchController@processBatchUpload
GET    /transactions/import-results                → TransactionBatchController@showImportResults
GET    /transactions/template-download            → TransactionBatchController@downloadTemplate
GET    /transactions/customer/{customer}/history  → TransactionReportController@customerHistory
GET    /transactions/customer/{customer}/history-export → TransactionReportController@exportCustomerHistory
GET    /transactions/{transaction}/receipt         → TransactionController@receipt (stay)
```

- [ ] **Step 1: Create TransactionApprovalController**

```php
<?php
// app/Http/Controllers/Transaction/TransactionApprovalController.php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\CurrencyPositionService;
use App\Services\AccountingService;
use App\Services\CounterService;
use App\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionApprovalController extends Controller
{
    public function __construct(
        protected CurrencyPositionService $positionService,
        protected AccountingService $accountingService,
        protected CounterService $counterService,
    ) {}

    public function approve(Transaction $transaction)
    {
        $this->authorize('approve', $transaction);

        DB::transaction(function () use ($transaction) {
            $transaction->approve(Auth::user());

            $this->positionService->updateTillBalance(
                $transaction,
                'approve'
            );

            $this->accountingService->createAccountingEntries(
                $transaction,
                'approve'
            );
        });

        return redirect()->back()->with('success', 'Transaction approved.');
    }

    public function showConfirm(Transaction $transaction)
    {
        if (!$transaction->requiresConfirmation()) {
            abort(403);
        }
        return view('transactions.confirm', compact('transaction'));
    }

    public function confirm(Transaction $transaction, Request $request)
    {
        $this->authorize('confirm', $transaction);

        $request->validate([
            'confirmation_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($transaction, $request) {
            $transaction->confirm(Auth::user(), $request->confirmation_notes);

            $this->positionService->updateTillBalance($transaction, 'confirm');
            $this->accountingService->createAccountingEntries($transaction, 'confirm');
        });

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction confirmed.');
    }

    protected function requiresConfirmation(Transaction $transaction): bool
    {
        return $transaction->amount >= 50000;
    }
}
```

- [ ] **Step 2: Create TransactionCancellationController**

```php
<?php
// app/Http/Controllers/Transaction/TransactionCancellationController.php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\CurrencyPositionService;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionCancellationController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
        protected CurrencyPositionService $positionService,
    ) {}

    public function showCancel(Transaction $transaction)
    {
        if (!$transaction->canCancel()) {
            abort(403);
        }
        return view('transactions.cancel', compact('transaction'));
    }

    public function cancel(Transaction $transaction, Request $request)
    {
        $this->authorize('cancel', $transaction);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($transaction, $request) {
            $refundTransaction = $this->createRefundTransaction($transaction);
            $this->reverseStockPosition($transaction);
            $this->createReversingJournalEntries($transaction, $refundTransaction);

            $transaction->cancel(
                Auth::user(),
                $request->cancellation_reason,
                $refundTransaction->id
            );
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction cancelled and reversed.');
    }

    protected function createRefundTransaction(Transaction $original): Transaction
    {
        return Transaction::create([
            'customer_id' => $original->customer_id,
            'counter_session_id' => $original->counter_session_id,
            'type' => $original->type,
            'currency_code' => $original->currency_code,
            'currency_amount' => $original->currency_amount,
            'exchange_rate' => $original->exchange_rate,
            'myr_amount' => $original->myr_amount,
            'base_amount' => $original->base_amount,
            'spread_amount' => $original->spread_amount,
            'status' => TransactionStatus::CANCELLED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'original_transaction_id' => $original->id,
            'notes' => "Reversal of transaction #{$original->id}",
        ]);
    }

    protected function reverseStockPosition(Transaction $transaction): void
    {
        $this->positionService->reversePosition(
            $transaction->currency_code,
            $transaction->currency_amount,
            'transaction_cancellation',
            $transaction->id
        );
    }

    protected function createReversingJournalEntries(Transaction $original, Transaction $refund): void
    {
        $this->accountingService->reverseTransactionEntries($original, $refund);
    }

    protected function canCancel(Transaction $transaction): bool
    {
        $user = Auth::user();
        $role = UserRole::from($user->role);

        return in_array($transaction->status, [
            TransactionStatus::APPROVED,
            TransactionStatus::CONFIRMED,
        ]) && ($role->canApproveLargeTransactions() || $user->id === $transaction->created_by);
    }
}
```

- [ ] **Step 3: Create TransactionBatchController**

```php
<?php
// app/Http/Controllers/Transaction/TransactionBatchController.php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Imports\TransactionImport;
use App\Models\Customer;
use App\Services\TransactionImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransactionBatchController extends Controller
{
    public function __construct(
        protected TransactionImportService $importService,
    ) {}

    public function showBatchUpload()
    {
        return view('transactions.batch-upload');
    }

    public function processBatchUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $import = new TransactionImport($this->importService);
        Excel::import($import, $request->file('file'));

        $results = $import->getResults();

        return redirect()
            ->route('transactions.import-results')
            ->with('results', $results);
    }

    public function showImportResults(Request $request)
    {
        $results = $request->session()->get('results', [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ]);

        return view('transactions.import-results', compact('results'));
    }

    public function downloadTemplate()
    {
        $headers = [
            'customer_id',
            'type',
            'currency_code',
            'currency_amount',
            'exchange_rate',
            'myr_amount',
            'notes',
        ];

        $csv = implode(',', $headers) . "\n";

        return response()->streamDownload(
            fn () => echo $csv,
            'transaction_import_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}
```

- [ ] **Step 4: Create TransactionReportController**

```php
<?php
// app/Http/Controllers/Transaction/TransactionReportController.php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionReportController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    public function customerHistory(Customer $customer, Request $request)
    {
        $dateFrom = $request->query('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $transactions = Transaction::where('customer_id', $customer->id)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->with(['counterSession.counter', 'reviewedBy'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(50);

        $stats = $this->calculateHistoryStats($customer->id, $dateFrom, $dateTo);

        return view('transactions.customer-history', [
            'customer' => $customer,
            'transactions' => $transactions,
            'stats' => $stats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function exportCustomerHistory(Customer $customer, Request $request): StreamedResponse
    {
        $dateFrom = $request->query('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        return $this->transactionService->exportCustomerHistory($customer, $dateFrom, $dateTo);
    }

    protected function calculateHistoryStats(int $customerId, string $dateFrom, string $dateTo): array
    {
        return DB::table('transactions')
            ->where('customer_id', $customerId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->selectRaw("
                COUNT(*) as total_transactions,
                SUM(currency_amount) as total_volume,
                AVG(exchange_rate) as avg_rate,
                SUM(myr_amount) as total_myr
            ")
            ->first();
    }
}
```

- [ ] **Step 5: Slim down TransactionController**

Remove methods that moved to other controllers. Keep: `index`, `create`, `store`, `show`, `receipt`, `approve` (move to TransactionApprovalController), `customerTransactions` (move to TransactionReportController).

After slimming, TransactionController should be ~350 lines.

- [ ] **Step 6: Update web.php routes**

Update route references to use new controllers.

- [ ] **Step 7: Run tests**

```bash
php artisan test --filter=TransactionWorkflowTest
```

Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Transaction/ routes/web.php
git commit -m "refactor: split TransactionController into focused controllers"
```

---

### Task 2: Split CustomerController (727 lines → 2 files)

**Files:**
- Modify: `app/Http/Controllers/CustomerController.php` (keep CRUD only)
- Create: `app/Http/Controllers/Customer/CustomerKycController.php`
- Modify: `routes/web.php`

**Route Updates:**
```
GET    /customers/{customer}/kyc                → CustomerKycController@kyc
POST   /customers/{customer}/kyc                 → CustomerKycController@uploadDocument
POST   /customers/{customer}/verify-document     → CustomerKycController@verifyDocument
DELETE /customers/{customer}/documents/{doc}     → CustomerKycController@deleteDocument
```

- [ ] **Step 1: Create CustomerKycController**

```php
<?php
// app/Http/Controllers/Customer/CustomerKycController.php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Services\CustomerDocumentService;
use App\Services\RiskRatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomerKycController extends Controller
{
    public function __construct(
        protected CustomerDocumentService $documentService,
        protected RiskRatingService $riskRatingService,
    ) {}

    public function kyc(Customer $customer)
    {
        $documents = $customer->documents()->with('uploadedBy')->get();
        $verificationStatus = $this->documentService->getVerificationStatus($customer);

        return view('customers.kyc', [
            'customer' => $customer,
            'documents' => $documents,
            'verificationStatus' => $verificationStatus,
        ]);
    }

    public function uploadDocument(Customer $customer, Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'expiry_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($customer, $request) {
            $path = $request->file('file')->store('customer-documents', 's3');

            $document = $customer->documents()->create([
                'document_type' => $request->document_type,
                'file_path' => $path,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize(),
                'mime_type' => $request->file('file')->getMimeType(),
                'expiry_date' => $request->expiry_date,
                'uploaded_by' => Auth::id(),
                'notes' => $request->notes,
                'is_verified' => false,
            ]);

            $this->documentService->processUploadedDocument($document);
        });

        return redirect()
            ->route('customers.kyc', $customer)
            ->with('success', 'Document uploaded successfully.');
    }

    public function verifyDocument(Customer $customer, Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:customer_documents,id',
            'is_verified' => 'required|boolean',
            'verification_notes' => 'nullable|string|max:500',
        ]);

        $document = $customer->documents()->findOrFail($request->document_id);

        DB::transaction(function () use ($document, $request) {
            $document->update([
                'is_verified' => $request->is_verified,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'verification_notes' => $request->verification_notes,
            ]);

            if ($request->is_verified) {
                $this->riskRatingService->recalculate($document->customer);
            }
        });

        return redirect()
            ->route('customers.kyc', $customer)
            ->with('success', 'Document verification updated.');
    }

    public function deleteDocument(Customer $customer, Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:customer_documents,id',
        ]);

        $document = $customer->documents()->findOrFail($request->document_id);

        if ($document->is_verified) {
            return redirect()
                ->route('customers.kyc', $customer)
                ->with('error', 'Cannot delete verified documents.');
        }

        Storage::disk('s3')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('customers.kyc', $customer)
            ->with('success', 'Document deleted.');
    }
}
```

- [ ] **Step 2: Slim down CustomerController**

Remove KYC-related methods. Keep: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.

CustomerController should be ~400 lines.

- [ ] **Step 3: Update web.php routes**

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=CustomerTest
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Customer/ app/Http/Controllers/CustomerController.php routes/web.php
git commit -m "refactor: extract KYC management from CustomerController"
```

---

### Task 3: Split ReportController (852 lines → 3 files)

**Files:**
- Modify: `app/Http/Controllers/ReportController.php` (keep download, export, history, compare)
- Create: `app/Http/Controllers/Report/RegulatoryReportController.php`
- Create: `app/Http/Controllers/Report/AnalyticsController.php`
- Modify: `routes/api.php`

**Route Updates:**
```
// Moves to RegulatoryReportController:
POST /reports/lctr, lctr/status, lctr/generate
POST /reports/msb2, msb2/status, msb2/generate
POST /reports/lmca, lmca/status
POST /reports/quarterly-lvr, quarterly-lvr/generate
POST /reports/position-limit, position-limit/generate

// Moves to AnalyticsController:
GET  /reports/monthly-trends
GET  /reports/profitability
GET  /reports/customer-analysis
GET  /reports/compliance-summary

// Stays in ReportController:
GET  /reports/download/{filename}
POST /reports/export
GET  /reports/history
GET  /reports/compare
```

- [ ] **Step 1: Create RegulatoryReportController**

Extract all BNM regulatory report methods: `lctr`, `lctrGenerate`, `generateLCTR`, `updateLCTRStatus`, `msb2`, `msb2Generate`, `generateMSB2`, `updateMSB2Status`, `lmca`, `lmcaGenerate`, `updateLMCAStatus`, `quarterlyLvr`, `quarterlyLvrGenerate`, `positionLimit`, `positionLimitGenerate`, plus helpers `getQuarterStart`, `getQuarterEnd`.

- [ ] **Step 2: Create AnalyticsController**

Extract: `monthlyTrends`, `profitability`, `customerAnalysis`, `complianceSummary`, plus helpers `calculateTrends`, `calculateCurrencyProfitability`, `getCurrentRate`.

- [ ] **Step 3: Slim down ReportController**

Keep: `download`, `export`, `history`, `compare`, `requireManagerOrAdmin`.

- [ ] **Step 4: Update api.php routes**

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=ReportTest
```

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Report/ app/Http/Controllers/ReportController.php routes/api.php
git commit -m "refactor: split ReportController into regulatory and analytics"
```

---

## PART 2: ADD API VERSIONING

### Task 4: Create API v1 Structure

**Files:**
- Create: `routes/api_v1.php`
- Create: `app/Http/Controllers/Api/V1/` directory structure
- Create: `app/Http/Controllers/Api/V1/TransactionController.php` (moved from `TransactionController`)
- Create: `app/Http/Controllers/Api/V1/CustomerController.php` (moved from `CustomerController`)
- Create: `app/Http/Controllers/Api/V1/StrController.php`
- Create: `app/Http/Controllers/Api/V1/SanctionController.php`
- Create: `app/Http/Controllers/Api/V1/ReportController.php`
- Create: `app/Http/Controllers/Api/V1/Compliance/` (already exists - leave as-is, add versioning)
- Modify: `app/Providers/RouteServiceProvider.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Update RouteServiceProvider to register v1 routes**

Modify `boot()` method to include:
```php
Route::prefix('api/v1')->group(function () {
    $this->loadRoutesFrom(base_path('routes/api_v1.php'));
});
```

- [ ] **Step 2: Create routes/api_v1.php with versioning**

Copy all routes from `routes/api.php` into `api_v1.php` with `api/v1/` prefix.

- [ ] **Step 3: Create Api/V1 controller stubs**

For each API controller, create a versioned wrapper in `App\Http\Controllers\Api\V1\`:

```php
<?php
// app/Http/Controllers/Api/V1/TransactionController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;

class TransactionController extends ApiController
{
    // Delegates to existing TransactionController or service
}
```

Actually, refactor to call service layer directly from API controllers:
```php
<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $transactions = $this->transactionService->list($request->all());
        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $transaction = $this->transactionService->create($request->all());
        return response()->json($transaction, 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($this->transactionService->show($transaction));
    }

    public function approve(Transaction $transaction): JsonResponse
    {
        $this->transactionService->approve($transaction);
        return response()->json(['message' => 'Approved']);
    }
}
```

- [ ] **Step 4: Move Compliance API controllers to V1 namespace**

Move `app/Http/Controllers/Api/Compliance/*.php` to `app/Http/Controllers/Api/V1/Compliance/`

- [ ] **Step 5: Keep routes/api.php for backward compat (deprecated)**

Add deprecation notice comment. Keep it working but don't add new endpoints.

- [ ] **Step 6: Run tests**

```bash
php artisan test --filter=ApiTest
php artisan route:list --path=api
```

Expected: Routes show `api/v1/` prefix

- [ ] **Step 7: Commit**

```bash
git add routes/api_v1.php app/Http/Controllers/Api/V1/ app/Providers/RouteServiceProvider.php
git commit -m "feat: add API v1 versioning"
```

---

## PART 3: CONSOLIDATE MIGRATIONS

### Task 5: Migration Consolidation Strategy

**Goal:** Reduce 66 migrations to ~20 logically grouped ones.

**Groups to consolidate:**

| New Migration | Original Migrations | Files |
|--------------|---------------------|-------|
| `2025_03_31_000001_create_core_tables.sql` | users, customers, currencies, exchange_rates, transactions, system_logs, sanction_lists, sanction_entries, flagged_transactions, high_risk_countries | All 10 core table creates |
| `2025_03_31_000002_create_till_and_counters.sql` | till_balances, counters, counter_sessions, counter_handovers | 5 files |
| `2025_03_31_000003_create_accounting_tables.sql` | chart_of_accounts, journal_entries, account_ledger, departments, cost_centers, accounting_periods, fiscal_years | 7 files |
| `2025_03_31_000004_create_compliance_tables.sql` | compliance_findings, compliance_cases, compliance_case_notes, compliance_case_documents, compliance_case_links, customer_risk_profiles, customer_behavioral_baselines, edd_questionnaire_templates, edd_document_requests | 10 files |
| `2026_04_02_000001_add_transaction_cancellation_fields.sql` | All transaction enhancements | 1 file (combine all 3 transaction alters into 1) |
| `2026_04_02_000002_enhance_system_logs.sql` | All system_logs enhancements | 1 file (combine all 4 system_logs alters into 1) |
| `2026_04_03_000001_create_banks_and_recon.sql` | bank_reconciliations (create + check fields) | 1 file |
| `2026_04_03_000002_create_aml_and_rules.sql` | aml_rules (create + alter on same day) | 1 file |
| `2026_04_03_000003_create_reports_tables.sql` | report_templates, reports_generated, status columns | 1 file |
| `2026_04_05_000001_add_customer_enhancements.sql` | customers mfa, sanctions screened, document verification | 1 file |
| `2026_04_05_000002_add_journal_enhancements.sql` | journal_entries enhancements | 1 file |
| `2026_04_05_000003_add_chart_of_accounts_enhancements.sql` | chart_of_accounts enhancements | 1 file |
| `2026_04_05_000004_add_foreign_keys_and_cascade.sql` | All FK fix migrations | 1 file |
| `2026_04_05_000005_add_transactions_enhancements.sql` | transaction safeguards, rate override | 1 file |
| `2026_04_08_000001_add_compliance_findings.sql` | All findings/monitoring tables | 1 file |
| `2026_04_08_000002_add_transaction_receipts.sql` | transaction_receipts, alerts tables | 1 file |

**Files to DELETE:** 46 old migration files (consolidated into 16)

**Files to CREATE:** 16 new consolidated migration files

**Files to MODIFY:** None (backward compatibility maintained)

- [ ] **Step 1: Create consolidated core tables migration**

Create `database/migrations/2025_03_31_000001_create_core_tables.php` combining all 10 core table creates.

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('branch_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('mfa_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index(['email', 'is_active']);
        });

        // currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('name');
            $table->string('symbol', 10);
            $table->integer('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // INSERT INTO currencies VALUES ... (seed data from original migration)

        // transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('counter_session_id')->nullable()->constrained()->onDelete('restrict');
            $table->string('type');
            $table->string('currency_code');
            $table->decimal('currency_amount', 20, 4);
            $table->decimal('exchange_rate', 20, 8);
            $table->decimal('myr_amount', 20, 4);
            $table->decimal('base_amount', 20, 4)->nullable();
            $table->decimal('spread_amount', 20, 4)->nullable();
            $table->string('status')->default('pending');
            $table->string('transaction_ref')->unique();
            $table->timestamp('transaction_date');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'transaction_date']);
            $table->index(['customer_id', 'transaction_date']);
        });

        // ... ALL other core tables combined
    }
};
```

- [ ] **Step 2: Create consolidated system_logs migration**

- [ ] **Step 3: Create consolidated transaction enhancement migration**

- [ ] **Step 4: Create consolidated compliance tables migration (all 2026_04_08 batch)**

- [ ] **Step 5: Create remaining consolidated migrations**

- [ ] **Step 6: DELETE old migration files**

```bash
# Remove 46 files from old_migrations_to_delete.txt
git rm database/migrations/2025_03_31_000002_create_customers_table.php
git rm database/migrations/2025_03_31_000003_create_currencies_table.php
# ... etc for all 46 files
```

- [ ] **Step 7: Test fresh migration**

```bash
php artisan migrate:fresh --seed
php artisan test
```

Expected: All tests pass with fresh DB

- [ ] **Step 8: Commit**

```bash
git add database/migrations/
git rm database/migrations/2025_03_31_000002_create_customers_table.php # etc
git commit -m "refactor: consolidate 66 migrations into 16 logical groups"
```

---

## Task Order

**Recommended execution order:**
1. Task 1 (TransactionController split) - Most complex, establishes pattern
2. Task 2 (CustomerController split) - Similar pattern, smaller scope
3. Task 3 (ReportController split) - Similar pattern
4. Task 4 (API versioning) - Can parallelize after Task 1
5. Task 5 (Migration consolidation) - Independent but risky; do last with full test suite

**Total estimated tasks:** 5 major tasks, ~30 sub-steps

---

## Verification Checklist

After all tasks:
- [ ] `php artisan route:list` shows v1 routes with `api/v1/` prefix
- [ ] `php artisan test` passes (65+ tests)
- [ ] `php artisan migrate:fresh` works (all 16 new migrations apply)
- [ ] Controllers are split with clear responsibilities
- [ ] No remaining controller over 500 lines
