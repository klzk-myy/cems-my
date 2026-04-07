# EDD Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Enhanced Due Diligence (EDD) workflow to document source of funds, purpose of transaction, and collect supporting evidence for high-risk transactions.

**Architecture:** EDD questionnaire linked to FlaggedTransaction. Compliance officer completes checklist, uploads documents, and submits for review. EDD record is linked to STR if filed.

**Tech Stack:** Laravel 10, MySQL, Eloquent ORM, File Storage

---

## File Structure

### New Tables
- `enhanced_diligence_records` - EDD questionnaire responses

### New Models
- `EnhancedDiligenceRecord` - EDD questionnaire and documents

### New Service
- `EddService` - EDD workflow management

### New Controller
- `EnhancedDiligenceController` - EDD CRUD operations

### New Views
- `resources/views/compliance/edd/index.blade.php` - EDD records listing
- `resources/views/compliance/edd/create.blade.php` - EDD questionnaire form
- `resources/views/compliance/edd/show.blade.php` - EDD detail view

### Modified Files
- `routes/web.php` - Add EDD routes
- `resources/views/compliance.blade.php` - Add EDD menu link

---

## Task 1: Database Migration for EnhancedDiligenceRecords

**Files:**
- Create: `database/migrations/2026_04_05_000006_create_enhanced_diligence_records_table.php`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('enhanced_diligence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flagged_transaction_id')->nullable()->constrained('flagged_transactions');
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('edd_reference', 30)->unique(); // EDD-YYYYMM-XXXX
            $table->enum('status', ['Incomplete', 'Pending_Review', 'Approved', 'Rejected'])->default('Incomplete');
            $table->enum('risk_level', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');

            // Source of Funds
            $table->text('source_of_funds')->nullable();
            $table->text('source_of_funds_description')->nullable();
            $table->json('source_of_funds_documents')->nullable(); // paths to uploaded docs

            // Purpose of Transaction
            $table->text('purpose_of_transaction')->nullable();
            $table->text('business_justification')->nullable();

            // Customer Information
            $table->text('employment_status')->nullable();
            $table->string('employer_name', 200)->nullable();
            $table->string('employer_address', 500)->nullable();
            $table->text('annual_income_range')->nullable();
            $table->text('estimated_net_worth')->nullable();

            // Source of Wealth
            $table->text('source_of_wealth')->nullable();
            $table->text('source_of_wealth_description')->nullable();

            // Additional Information
            $table->text('additional_information')->nullable();
            $table->json('supporting_documents')->nullable();

            // Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index('edd_reference');
            $table->index('status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('enhanced_diligence_records');
    }
};
```

---

## Task 2: EnhancedDiligenceRecord Model

**Files:**
- Create: `app/Models/EnhancedDiligenceRecord.php`

- [ ] **Step 1: Create model**

```php
<?php

namespace App\Models;

use App\Enums\EddStatus;
use App\Enums\EddRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnhancedDiligenceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'flagged_transaction_id',
        'customer_id',
        'edd_reference',
        'status',
        'risk_level',
        'source_of_funds',
        'source_of_funds_description',
        'source_of_funds_documents',
        'purpose_of_transaction',
        'business_justification',
        'employment_status',
        'employer_name',
        'employer_address',
        'annual_income_range',
        'estimated_net_worth',
        'source_of_wealth',
        'source_of_wealth_description',
        'additional_information',
        'supporting_documents',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'source_of_funds_documents' => 'array',
        'supporting_documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function flaggedTransaction(): BelongsTo
    {
        return $this->belongsTo(FlaggedTransaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isComplete(): bool
    {
        return $this->status !== 'Incomplete';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'Pending_Review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}
```

---

## Task 3: EddStatus and EddRiskLevel Enums

**Files:**
- Create: `app/Enums/EddStatus.php`
- Create: `app/Enums/EddRiskLevel.php`

- [ ] **Step 1: Create EddStatus enum**

```php
<?php

namespace App\Enums;

enum EddStatus: string
{
    case Incomplete = 'Incomplete';
    case PendingReview = 'Pending_Review';
    case Approved = 'Approved';
    case Rejected = 'Rejected';

    public function label(): string
    {
        return match($this) {
            self::Incomplete => 'Incomplete',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Incomplete => 'secondary',
            self::PendingReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
```

- [ ] **Step 2: Create EddRiskLevel enum**

```php
<?php

namespace App\Enums;

enum EddRiskLevel: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
    case Critical = 'Critical';

    public function label(): string
    {
        return match($this) {
            self::Low => 'Low Risk',
            self::Medium => 'Medium Risk',
            self::High => 'High Risk',
            self::Critical => 'Critical Risk',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Low => 'success',
            self::Medium => 'info',
            self::High => 'warning',
            self::Critical => 'danger',
        };
    }
}
```

---

## Task 4: EddService

**Files:**
- Create: `app/Services/EddService.php`

- [ ] **Step 1: Create service**

```php
<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\FlaggedTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EddService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function createEddRecord(FlaggedTransaction $flag, array $data = []): EnhancedDiligenceRecord
    {
        return DB::transaction(function () use ($flag, $data) {
            $eddReference = $this->generateEddReference();

            $record = EnhancedDiligenceRecord::create([
                'flagged_transaction_id' => $flag->id,
                'customer_id' => $flag->customer_id,
                'edd_reference' => $eddReference,
                'status' => 'Incomplete',
                'risk_level' => $data['risk_level'] ?? 'Medium',
            ]);

            return $record;
        });
    }

    public function updateEddRecord(EnhancedDiligenceRecord $record, array $data): EnhancedDiligenceRecord
    {
        $record->update($data);

        if ($this->isRecordComplete($record)) {
            $record->update(['status' => 'Pending_Review']);
        }

        return $record->fresh();
    }

    public function submitForReview(EnhancedDiligenceRecord $record): EnhancedDiligenceRecord
    {
        if (!$this->isRecordComplete($record)) {
            throw new \InvalidArgumentException('EDD record must be complete before submission');
        }

        $record->update(['status' => 'Pending_Review']);

        return $record;
    }

    public function approve(EnhancedDiligenceRecord $record, User $reviewer, ?string $notes = null): EnhancedDiligenceRecord
    {
        $record->update([
            'status' => 'Approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return $record;
    }

    public function reject(EnhancedDiligenceRecord $record, User $reviewer, string $reason): EnhancedDiligenceRecord
    {
        $record->update([
            'status' => 'Rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        return $record;
    }

    public function isRecordComplete(EnhancedDiligenceRecord $record): bool
    {
        $required = [
            $record->source_of_funds,
            $record->purpose_of_transaction,
        ];

        return !in_array(null, $required, true) && !empty($record->source_of_funds);
    }

    protected function generateEddReference(): string
    {
        $prefix = 'EDD-' . date('Ym') . '-';
        $lastRecord = EnhancedDiligenceRecord::where('edd_reference', 'like', $prefix . '%')
            ->orderBy('edd_reference', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->edd_reference, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
```

---

## Task 5: EnhancedDiligenceController

**Files:**
- Create: `app/Http/Controllers/EnhancedDiligenceController.php`

- [ ] **Step 1: Create controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\FlaggedTransaction;
use App\Services\EddService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnhancedDiligenceController extends Controller
{
    protected EddService $eddService;

    public function __construct(EddService $eddService)
    {
        $this->eddService = $eddService;
    }

    public function index(Request $request)
    {
        $query = EnhancedDiligenceRecord::with(['customer', 'reviewer', 'flaggedTransaction']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->risk_level) {
            $query->where('risk_level', $request->risk_level);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('compliance.edd.index', compact('records'));
    }

    public function create(Request $request)
    {
        $flagId = $request->flagged_transaction_id;
        $flag = null;
        $customer = null;

        if ($flagId) {
            $flag = FlaggedTransaction::findOrFail($flagId);
            $customer = $flag->customer;
        }

        $customers = Customer::where('risk_rating', 'High Risk')->orWhere('is_pep', true)->get();

        return view('compliance.edd.create', compact('flag', 'customer', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'flagged_transaction_id' => 'nullable|exists:flagged_transactions,id',
            'risk_level' => 'required|in:Low,Medium,High,Critical',
            'source_of_funds' => 'required|string',
            'source_of_funds_description' => 'nullable|string',
            'purpose_of_transaction' => 'required|string',
            'business_justification' => 'nullable|string',
            'employment_status' => 'nullable|string',
            'employer_name' => 'nullable|string|max:200',
            'employer_address' => 'nullable|string|max:500',
            'annual_income_range' => 'nullable|string',
            'estimated_net_worth' => 'nullable|string',
            'source_of_wealth' => 'nullable|string',
            'source_of_wealth_description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $flag = null;
        if ($request->flagged_transaction_id) {
            $flag = FlaggedTransaction::find($request->flagged_transaction_id);
        }

        $record = $this->eddService->createEddRecord($flag ?? new FlaggedTransaction(['customer_id' => $validated['customer_id']]), [
            'risk_level' => $validated['risk_level'],
        ]);

        $record = $this->eddService->updateEddRecord($record, $validated);

        return redirect()->route('compliance.edd.show', $record)
            ->with('success', 'EDD record created successfully.');
    }

    public function show(EnhancedDiligenceRecord $record)
    {
        $record->load(['customer', 'reviewer', 'flaggedTransaction']);

        return view('compliance.edd.show', compact('record'));
    }

    public function edit(EnhancedDiligenceRecord $record)
    {
        if (!$record->isComplete()) {
            return redirect()->route('compliance.edd.show', $record)
                ->with('error', 'Cannot edit a pending review or approved record.');
        }

        $record->load(['customer', 'flaggedTransaction']);

        return view('compliance.edd.edit', compact('record'));
    }

    public function update(Request $request, EnhancedDiligenceRecord $record)
    {
        if ($record->status === 'Approved') {
            return redirect()->back()->with('error', 'Cannot update an approved EDD record.');
        }

        $validated = $request->validate([
            'source_of_funds' => 'required|string',
            'source_of_funds_description' => 'nullable|string',
            'purpose_of_transaction' => 'required|string',
            'business_justification' => 'nullable|string',
            'employment_status' => 'nullable|string',
            'employer_name' => 'nullable|string|max:200',
            'employer_address' => 'nullable|string|max:500',
            'annual_income_range' => 'nullable|string',
            'estimated_net_worth' => 'nullable|string',
            'source_of_wealth' => 'nullable|string',
            'source_of_wealth_description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $record = $this->eddService->updateEddRecord($record, $validated);

        return redirect()->route('compliance.edd.show', $record)
            ->with('success', 'EDD record updated successfully.');
    }

    public function submitReview(EnhancedDiligenceRecord $record)
    {
        try {
            $record = $this->eddService->submitForReview($record);
            return redirect()->back()->with('success', 'EDD record submitted for review.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, EnhancedDiligenceRecord $record)
    {
        if (!$record->isPendingReview()) {
            return redirect()->back()->with('error', 'Only pending records can be approved.');
        }

        $record = $this->eddService->approve($record, Auth::user(), $request->notes);

        return redirect()->back()->with('success', 'EDD record approved.');
    }

    public function reject(Request $request, EnhancedDiligenceRecord $record)
    {
        if (!$record->isPendingReview()) {
            return redirect()->back()->with('error', 'Only pending records can be rejected.');
        }

        $request->validate(['reason' => 'required|string']);

        $record = $this->eddService->reject($record, Auth::user(), $request->reason);

        return redirect()->back()->with('success', 'EDD record rejected.');
    }
}
```

---

## Task 6: EDD Views

**Files:**
- Create: `resources/views/compliance/edd/index.blade.php`
- Create: `resources/views/compliance/edd/create.blade.php`
- Create: `resources/views/compliance/edd/show.blade.php`
- Create: `resources/views/compliance/edd/edit.blade.php`

- [ ] **Step 1: Create index view**

```blade
@extends('layouts.app')

@section('title', 'Enhanced Due Diligence - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>Enhanced Due Diligence (EDD)</h2>
    <p>Document source of funds and transaction purpose for high-risk customers</p>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>EDD Records</h4>
        <a href="{{ route('compliance.edd.create') }}" class="btn btn-primary">New EDD Record</a>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>EDD Reference</th>
                    <th>Customer</th>
                    <th>Risk Level</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td><strong>{{ $record->edd_reference }}</strong></td>
                    <td>{{ $record->customer->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-{{ $record->risk_level === 'Critical' ? 'danger' : ($record->risk_level === 'High' ? 'warning' : 'info') }}">
                            {{ $record->risk_level }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $record->status === 'Approved' ? 'success' : ($record->status === 'Pending_Review' ? 'warning' : 'secondary') }}">
                            {{ str_replace('_', ' ', $record->status) }}
                        </span>
                    </td>
                    <td>{{ $record->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('compliance.edd.show', $record) }}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-muted">No EDD records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $records->links() }}
    </div>
</div>
@endsection
```

- [ ] **Step 2: Create show view**

```blade
@extends('layouts.app')

@section('title', 'EDD Detail - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>EDD Record: {{ $record->edd_reference }}</h2>
    <p>Enhanced Due Diligence Documentation</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h4>Customer Information</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Customer:</strong> {{ $record->customer->name ?? 'N/A' }}</p>
                <p><strong>Risk Level:</strong> <span class="badge bg-{{ $record->risk_level === 'Critical' ? 'danger' : 'info' }}">{{ $record->risk_level }}</span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-{{ $record->status === 'Approved' ? 'success' : 'warning' }}">{{ str_replace('_', ' ', $record->status) }}</span></p>
                <p><strong>Created:</strong> {{ $record->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Source of Funds</h4>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <strong>Source:</strong>
                <p>{{ $record->source_of_funds ?? 'Not provided' }}</p>
            </div>
        </div>
        @if($record->source_of_funds_description)
        <div class="row mb-3">
            <div class="col-md-12">
                <strong>Description:</strong>
                <p>{{ $record->source_of_funds_description }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Purpose of Transaction</h4>
    </div>
    <div class="card-body">
        <p><strong>Purpose:</strong> {{ $record->purpose_of_transaction ?? 'Not provided' }}</p>
        @if($record->business_justification)
            <p><strong>Business Justification:</strong> {{ $record->business_justification }}</p>
        @endif
    </div>
</div>

@if($record->employment_status)
<div class="card">
    <div class="card-header">
        <h4>Employment Information</h4>
    </div>
    <div class="card-body">
        <p><strong>Status:</strong> {{ $record->employment_status }}</p>
        @if($record->employer_name)
            <p><strong>Employer:</strong> {{ $record->employer_name }}</p>
        @endif
        @if($record->employer_address)
            <p><strong>Address:</strong> {{ $record->employer_address }}</p>
        @endif
        @if($record->annual_income_range)
            <p><strong>Annual Income Range:</strong> {{ $record->annual_income_range }}</p>
        @endif
        @if($record->estimated_net_worth)
            <p><strong>Estimated Net Worth:</strong> {{ $record->estimated_net_worth }}</p>
        @endif
    </div>
</div>
@endif

@if($record->source_of_wealth)
<div class="card">
    <div class="card-header">
        <h4>Source of Wealth</h4>
    </div>
    <div class="card-body">
        <p><strong>Source:</strong> {{ $record->source_of_wealth }}</p>
        @if($record->source_of_wealth_description)
            <p><strong>Description:</strong> {{ $record->source_of_wealth_description }}</p>
        @endif
    </div>
</div>
@endif

@if($record->reviewed_by)
<div class="card">
    <div class="card-header">
        <h4>Review</h4>
    </div>
    <div class="card-body">
        <p><strong>Reviewed By:</strong> {{ $record->reviewer->username ?? 'N/A' }}</p>
        <p><strong>Reviewed At:</strong> {{ $record->reviewed_at->format('Y-m-d H:i') }}</p>
        @if($record->review_notes)
            <p><strong>Notes:</strong> {{ $record->review_notes }}</p>
        @endif
    </div>
</div>
@endif

<div class="card">
    <div class="card-body">
        @if($record->status === 'Incomplete')
            <a href="{{ route('compliance.edd.edit', $record) }}" class="btn btn-primary">Complete EDD</a>
        @elseif($record->status === 'Pending_Review')
            <form action="{{ route('compliance.edd.approve', $record) }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="notes" value="">
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
            <form action="{{ route('compliance.edd.reject', $record) }}" method="POST" style="display: inline;">
                @csrf
                <input type="text" name="reason" placeholder="Rejection reason" required>
                <button type="submit" class="btn btn-danger">Reject</button>
            </form>
        @endif
    </div>
</div>
@endsection
```

- [ ] **Step 3: Create create view**

```blade
@extends('layouts.app')

@section('title', 'New EDD Record - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>New EDD Record</h2>
    <p>Enhanced Due Diligence for High-Risk Customer</p>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('compliance.edd.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="customer_id" class="form-label">Customer *</label>
                <select name="customer_id" id="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->risk_rating }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="risk_level" class="form-label">Risk Level *</label>
                <select name="risk_level" id="risk_level" class="form-control" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <hr>

            <h5>Source of Funds</h5>

            <div class="mb-3">
                <label for="source_of_funds" class="form-label">Source of Funds *</label>
                <select name="source_of_funds" id="source_of_funds" class="form-control" required>
                    <option value="">Select Source</option>
                    <option value="Salary">Salary / Employment Income</option>
                    <option value="Business">Business Income</option>
                    <option value="Investment">Investment Returns</option>
                    <option value="Inheritance">Inheritance</option>
                    <option value="Gift">Gift / Donation</option>
                    <option value="Sale of Asset">Sale of Asset</option>
                    <option value="Loan">Loan / Borrowed Funds</option>
                    <option value="Savings">Savings</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="source_of_funds_description" class="form-label">Source of Funds Description</label>
                <textarea name="source_of_funds_description" id="source_of_funds_description" class="form-control" rows="3"></textarea>
            </div>

            <hr>

            <h5>Purpose of Transaction</h5>

            <div class="mb-3">
                <label for="purpose_of_transaction" class="form-label">Purpose of Transaction *</label>
                <select name="purpose_of_transaction" id="purpose_of_transaction" class="form-control" required>
                    <option value="">Select Purpose</option>
                    <option value="Business Payment">Business Payment</option>
                    <option value="Personal Transaction">Personal Transaction</option>
                    <option value="Investment">Investment</option>
                    <option value="Education">Education</option>
                    <option value="Travel">Travel</option>
                    <option value="Remittance">Remittance / Money Transfer</option>
                    <option value="Import/Export">Import/Export Payment</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="business_justification" class="form-label">Business Justification</label>
                <textarea name="business_justification" id="business_justification" class="form-control" rows="3"></textarea>
            </div>

            <hr>

            <h5>Employment Information</h5>

            <div class="mb-3">
                <label for="employment_status" class="form-label">Employment Status</label>
                <input type="text" name="employment_status" id="employment_status" class="form-control">
            </div>

            <div class="mb-3">
                <label for="employer_name" class="form-label">Employer Name</label>
                <input type="text" name="employer_name" id="employer_name" class="form-control">
            </div>

            <div class="mb-3">
                <label for="annual_income_range" class="form-label">Annual Income Range</label>
                <input type="text" name="annual_income_range" id="annual_income_range" class="form-control">
            </div>

            <div class="mb-3">
                <label for="estimated_net_worth" class="form-label">Estimated Net Worth</label>
                <input type="text" name="estimated_net_worth" id="estimated_net_worth" class="form-control">
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Create EDD Record</button>
                <a href="{{ route('compliance.edd.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
```

---

## Task 7: Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add EDD routes**

Add to compliance routes section:
```php
// Enhanced Due Diligence
Route::get('/compliance/edd', [EnhancedDiligenceController::class, 'index'])->name('compliance.edd.index');
Route::get('/compliance/edd/create', [EnhancedDiligenceController::class, 'create'])->name('compliance.edd.create');
Route::post('/compliance/edd', [EnhancedDiligenceController::class, 'store'])->name('compliance.edd.store');
Route::get('/compliance/edd/{record}', [EnhancedDiligenceController::class, 'show'])->name('compliance.edd.show');
Route::get('/compliance/edd/{record}/edit', [EnhancedDiligenceController::class, 'edit'])->name('compliance.edd.edit');
Route::put('/compliance/edd/{record}', [EnhancedDiligenceController::class, 'update'])->name('compliance.edd.update');
Route::post('/compliance/edd/{record}/submit', [EnhancedDiligenceController::class, 'submitReview'])->name('compliance.edd.submit');
Route::post('/compliance/edd/{record}/approve', [EnhancedDiligenceController::class, 'approve'])->name('compliance.edd.approve');
Route::post('/compliance/edd/{record}/reject', [EnhancedDiligenceController::class, 'reject'])->name('compliance.edd.reject');
```

---

## Task 8: Add EDD Link to Compliance Menu

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Add EDD to compliance submenu**

Add under the compliance nav group in sidebar:
```blade
<a href="/compliance/edd">📋 EDD Records</a>
```

---

## Task 9: Tests

**Files:**
- Create: `tests/Feature/EddWorkflowTest.php`

- [ ] **Step 1: Create test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\FlaggedTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EddWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Password@123'),
            'role' => 'compliance_officer',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'risk_rating' => 'High Risk',
            'is_pep' => true,
        ]);
    }

    public function test_edd_index_accessible_by_compliance(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/compliance/edd');
        $response->assertStatus(200);
    }

    public function test_can_create_edd_record(): void
    {
        $response = $this->actingAs($this->complianceUser)->post('/compliance/edd', [
            'customer_id' => $this->customer->id,
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $this->assertDatabaseHas('enhanced_diligence_records', [
            'customer_id' => $this->customer->id,
            'status' => 'Incomplete',
        ]);
    }

    public function test_edd_record_requires_source_of_funds(): void
    {
        $response = $this->actingAs($this->complianceUser)->post('/compliance/edd', [
            'customer_id' => $this->customer->id,
            'risk_level' => 'High',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response->assertSessionHasErrors('source_of_funds');
    }

    public function test_can_update_and_submit_edd_record(): void
    {
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $this->customer->id,
            'edd_reference' => 'EDD-202604-0001',
            'status' => 'Incomplete',
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response = $this->actingAs($this->complianceUser)->put("/compliance/edd/{$record->id}", [
            'source_of_funds' => 'Business Income',
            'purpose_of_transaction' => 'Business Payment',
            'business_justification' => 'Payment for import goods',
        ]);

        $record->refresh();
        $this->assertEquals('Pending_Review', $record->status);
    }
}
```

---

## Task 10: Run Tests and Verify

- [ ] **Step 1: Run migrations**

```bash
php artisan migrate
```

- [ ] **Step 2: Run tests**

```bash
php artisan test --filter=EddWorkflowTest
```

- [ ] **Step 3: Verify all tests pass**

Expected: All tests pass
