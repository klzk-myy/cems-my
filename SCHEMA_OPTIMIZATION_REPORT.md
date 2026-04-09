# Database Schema Optimization Report
## CEMS-MY Currency Exchange Management System

**Generated:** 2026-04-09  
**Scope:** database/migrations/*.php, app/Models/*.php  
**Database Type:** MySQL (Laravel 10.x)

---

## Executive Summary

This report identifies critical database schema optimizations needed for the CEMS-MY system to ensure:
- **Performance**: Proper indexing on foreign keys and query patterns
- **Data Integrity**: Unique constraints, check constraints, and cascade rules
- **Compliance**: Audit trails for BNM AML/CFT requirements
- **Reliability**: Soft deletes and optimistic locking for data safety

---

## 1. Missing Indexes on Foreign Keys

### Critical Priority

| Table | Column | Current Status | Impact | Recommendation |
|-------|--------|----------------|--------|----------------|
| **transactions** | `approved_by` | No index | Slow approval lookups | Add index |
| **transactions** | `branch_id` | No index | Branch filtering slow | Add index |
| **transactions** | `original_transaction_id` | No index | Refund lookups slow | Add index |
| **transactions** | `cancelled_by` | No index | Cancellation audit slow | Add index |
| **journal_entries** | `reversed_by` | No index | Reversal queries slow | Add index |
| **journal_entries** | `created_by` | No index | Creator filtering slow | Add index |
| **journal_entries** | `approved_by` | No index | Approval workflow slow | Add index |
| **str_reports** | `branch_id` | No index | Branch STR reports slow | Add index |
| **str_reports** | `alert_id` | No index | Alert linkage queries | Add index |
| **compliance_cases** | `primary_flag_id` | No index | Case-flag linkage | Add index |
| **compliance_cases** | `primary_finding_id` | No index | Case-finding linkage | Add index |
| **customer_documents** | `uploaded_by` | No index | Document audit slow | Add index |
| **customer_documents** | `verified_by` | No index | Verification queries | Add index |

### Migration Scripts

```php
// Create migration: 2026_04_09_000100_add_missing_foreign_key_indexes.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transaction table indexes
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('approved_by', 'idx_transactions_approved_by');
            $table->index('branch_id', 'idx_transactions_branch_id');
            $table->index('original_transaction_id', 'idx_transactions_original_txn');
            $table->index('cancelled_by', 'idx_transactions_cancelled_by');
            $table->index(['customer_id', 'status', 'created_at'], 'idx_transactions_compliance_lookup');
        });

        // Journal entry indexes
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('reversed_by', 'idx_journal_entries_reversed_by');
            $table->index('created_by', 'idx_journal_entries_created_by');
            $table->index('approved_by', 'idx_journal_entries_approved_by');
            $table->index(['period_id', 'status'], 'idx_journal_entries_period_status');
        });

        // STR reports indexes
        Schema::table('str_reports', function (Blueprint $table) {
            $table->index('branch_id', 'idx_str_reports_branch_id');
            $table->index('alert_id', 'idx_str_reports_alert_id');
            $table->index(['status', 'filing_deadline'], 'idx_str_reports_deadline');
        });

        // Compliance cases indexes
        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->index('primary_flag_id', 'idx_cases_primary_flag');
            $table->index('primary_finding_id', 'idx_cases_primary_finding');
        });

        // Customer documents indexes
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->index('uploaded_by', 'idx_documents_uploaded_by');
            $table->index('verified_by', 'idx_documents_verified_by');
            $table->index(['status', 'expiry_date'], 'idx_documents_status_expiry');
        });

        // Compliance case documents
        Schema::table('compliance_case_documents', function (Blueprint $table) {
            $table->index('uploaded_by', 'idx_case_docs_uploaded_by');
            $table->index('verified_by', 'idx_case_docs_verified_by');
        });
    }

    public function down(): void
    {
        // Remove indexes in reverse order
        Schema::table('compliance_case_documents', function (Blueprint $table) {
            $table->dropIndex('idx_case_docs_verified_by');
            $table->dropIndex('idx_case_docs_uploaded_by');
        });

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_status_expiry');
            $table->dropIndex('idx_documents_verified_by');
            $table->dropIndex('idx_documents_uploaded_by');
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->dropIndex('idx_cases_primary_finding');
            $table->dropIndex('idx_cases_primary_flag');
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->dropIndex('idx_str_reports_deadline');
            $table->dropIndex('idx_str_reports_alert_id');
            $table->dropIndex('idx_str_reports_branch_id');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_journal_entries_period_status');
            $table->dropIndex('idx_journal_entries_approved_by');
            $table->dropIndex('idx_journal_entries_created_by');
            $table->dropIndex('idx_journal_entries_reversed_by');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_compliance_lookup');
            $table->dropIndex('idx_transactions_cancelled_by');
            $table->dropIndex('idx_transactions_original_txn');
            $table->dropIndex('idx_transactions_branch_id');
            $table->dropIndex('idx_transactions_approved_by');
        });
    }
};
```

---

## 2. Missing Unique Constraints

### Critical Priority

| Table | Column(s) | Current Status | Risk | Recommendation |
|-------|-----------|----------------|------|----------------|
| **compliance_cases** | `case_number` | No unique constraint | Duplicate case numbers | Add unique |
| **str_reports** | `str_number` | Has unique ✓ | - | Verified |
| **customer_documents** | `file_hash` | No unique | Duplicate document storage | Add unique |
| **compliance_case_documents** | `file_hash` | No unique | Duplicate uploads | Add unique |
| **exchange_rates** | `currency_code` + `fetched_at` | No unique | Duplicate rate entries | Add unique |
| **currencies** | `code` | Is primary ✓ | - | Verified |
| **chart_of_accounts** | `account_code` | Is primary ✓ | - | Verified |
| **high_risk_countries** | `country_code` | Is primary ✓ | - | Verified |
| **currency_positions** | `currency_code` + `branch_id` | Has unique ✓ | - | Verified |
| **branches** | `code` | Has unique ✓ | - | Verified |
| **users** | `username` | Has unique ✓ | - | Verified |
| **users** | `email` | Has unique ✓ | - | Verified |
| **counters** | `code` | Has unique ✓ | - | Verified |
| **till_balances** | `till_id` + `date` + `currency_code` | Has unique ✓ | - | Verified |

### Migration Script

```php
// Create migration: 2026_04_09_000200_add_missing_unique_constraints.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compliance cases - unique case number
        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->unique('case_number', 'uniq_case_number');
        });

        // Customer documents - prevent duplicate file storage
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->unique('file_hash', 'uniq_customer_doc_hash');
        });

        // Compliance case documents - prevent duplicate uploads
        Schema::table('compliance_case_documents', function (Blueprint $table) {
            $table->unique('file_hash', 'uniq_case_doc_hash');
        });

        // Exchange rates - prevent duplicate rate entries for same time
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->unique(['currency_code', 'fetched_at'], 'uniq_rate_fetch');
        });

        // Sanction entries - prevent duplicates
        Schema::table('sanction_entries', function (Blueprint $table) {
            $table->unique(['list_id', 'entity_name', 'date_of_birth'], 'uniq_sanction_entry');
        });
    }

    public function down(): void
    {
        Schema::table('sanction_entries', function (Blueprint $table) {
            $table->dropUnique('uniq_sanction_entry');
        });

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique('uniq_rate_fetch');
        });

        Schema::table('compliance_case_documents', function (Blueprint $table) {
            $table->dropUnique('uniq_case_doc_hash');
        });

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropUnique('uniq_customer_doc_hash');
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->dropUnique('uniq_case_number');
        });
    }
};
```

---

## 3. Soft Deletes Not Implemented

### Critical Priority

All major transaction tables lack soft delete support. This is **critical** for BNM compliance audit trails.

| Table | Current Status | Risk | Priority |
|-------|----------------|------|----------|
| **transactions** | No soft deletes | Permanent deletion - compliance violation | CRITICAL |
| **customers** | No soft deletes | Permanent deletion - compliance violation | CRITICAL |
| **journal_entries** | No soft deletes | Cannot reverse without history | CRITICAL |
| **str_reports** | No soft deletes | STR deletion - serious compliance risk | CRITICAL |
| **compliance_cases** | No soft deletes | Case deletion - audit trail broken | CRITICAL |
| **compliance_findings** | No soft deletes | Finding deletion - compliance violation | HIGH |
| **flagged_transactions** | No soft deletes | Flag deletion - AML violation | HIGH |
| **users** | No soft deletes | User deletion breaks audit chain | HIGH |
| **customers** | No soft deletes | KYC data loss | HIGH |
| **currencies** | No soft deletes | Referential integrity issues | MEDIUM |
| **exchange_rates** | No soft deletes | Rate history loss | MEDIUM |

### Migration Script with Model Updates

```php
// Create migration: 2026_04_09_000300_add_soft_deletes.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Critical compliance tables
        Schema::table('transactions', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable(); // For BNM compliance
            $table->text('deactivation_reason')->nullable();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('compliance_findings', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Supporting tables
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('account_ledger', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('currency_positions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('currency_positions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('account_ledger', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('compliance_findings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('deactivation_reason');
            $table->dropColumn('deactivated_at');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });
    }
};
```

### Model Updates Required

Update the following models to add `SoftDeletes` trait:

```php
// app/Models/Transaction.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;
    
    // Add to $fillable:
    'deleted_by',
}

// app/Models/Customer.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    
    // Add to $fillable:
    'deleted_by',
    'deactivated_at',
    'deactivation_reason',
}

// app/Models/JournalEntry.php
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;
    
    // Add to $fillable:
    'deleted_by',
}

// Repeat for: StrReport, ComplianceCase, ComplianceFinding, 
// FlaggedTransaction, User, JournalLine, AccountLedger, CurrencyPosition
```

---

## 4. Audit Trails Missing

### Critical Priority

BNM compliance requires comprehensive audit trails for all financial and compliance operations.

### Missing Audit Fields

| Table | Missing Fields | Compliance Impact |
|-------|----------------|-------------------|
| **transactions** | `created_by`, `updated_by` | Cannot trace transaction origin | CRITICAL |
| **customers** | `created_by`, `updated_by` | KYC audit trail incomplete | CRITICAL |
| **journal_entries** | `updated_by` | Entry modification untracked | CRITICAL |
| **compliance_cases** | `created_by`, `updated_by` | Case management audit gap | CRITICAL |
| **str_reports** | `updated_by` | STR modification untracked | CRITICAL |
| **flagged_transactions** | `created_by`, `updated_by` | Flag audit trail missing | HIGH |
| **compliance_findings** | `created_by`, `updated_by` | Finding origin unknown | HIGH |
| **currency_positions** | `created_by`, `updated_by` | Position changes untracked | MEDIUM |
| **exchange_rates** | `created_by`, `updated_by` | Rate changes untracked | MEDIUM |

### Migration Script

```php
// Create migration: 2026_04_09_000400_add_audit_fields.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions audit
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Customers audit
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
            $table->foreignId('kyc_verified_by')->nullable()->after('cdd_level')->constrained('users')->nullOnDelete();
            $table->timestamp('kyc_verified_at')->nullable();
        });

        // Journal entries audit
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Compliance cases audit
        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // STR reports audit
        Schema::table('str_reports', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Flagged transactions audit
        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Compliance findings audit
        Schema::table('compliance_findings', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Currency positions audit
        Schema::table('currency_positions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        // Exchange rates audit
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('currency_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('compliance_findings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('kyc_verified_at');
            $table->dropConstrainedForeignId('kyc_verified_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
```

### Create Audit Log Model and Migration

```php
// Create migration: 2026_04_09_000500_create_audit_logs_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->string('action', 50); // INSERT, UPDATE, DELETE, RESTORE
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 128)->nullable();
            $table->timestamp('created_at');
            
            $table->index(['table_name', 'record_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

---

## 5. Data Integrity Issues

### 5.1 Missing Check Constraints

```php
// Create migration: 2026_04_09_000600_add_check_constraints.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Transaction amounts must be positive
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_positive_amount_local CHECK (amount_local > 0)');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_positive_amount_foreign CHECK (amount_foreign > 0)');
        
        // Transaction rates must be positive
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_positive_rate CHECK (rate > 0)');
        
        // Customer risk score must be 0-100
        DB::statement('ALTER TABLE customers ADD CONSTRAINT chk_risk_score_range CHECK (risk_score >= 0 AND risk_score <= 100)');
        
        // Journal lines - either debit or credit must be 0 (not both)
        DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT chk_debit_credit_not_both CHECK ((debit > 0 AND credit = 0) OR (debit = 0 AND credit > 0) OR (debit = 0 AND credit = 0))');
        
        // Currency positions - balance tracking
        DB::statement('ALTER TABLE currency_positions ADD CONSTRAINT chk_non_negative_balance CHECK (balance >= 0)');
        
        // STR reports - filing deadline must be in future
        DB::statement('ALTER TABLE str_reports ADD CONSTRAINT chk_filing_deadline CHECK (filing_deadline > suspicion_date)');
        
        // Compliance cases - SLA deadline must be in future
        DB::statement('ALTER TABLE compliance_cases ADD CONSTRAINT chk_sla_deadline CHECK (sla_deadline > created_at)');
        
        // Till balances - variance calculation
        DB::statement('ALTER TABLE till_balances ADD CONSTRAINT chk_variance_calculated CHECK (variance = closing_balance - opening_balance)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE till_balances DROP CONSTRAINT chk_variance_calculated');
        DB::statement('ALTER TABLE compliance_cases DROP CONSTRAINT chk_sla_deadline');
        DB::statement('ALTER TABLE str_reports DROP CONSTRAINT chk_filing_deadline');
        DB::statement('ALTER TABLE currency_positions DROP CONSTRAINT chk_non_negative_balance');
        DB::statement('ALTER TABLE journal_lines DROP CONSTRAINT chk_debit_credit_not_both');
        DB::statement('ALTER TABLE customers DROP CONSTRAINT chk_risk_score_range');
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_positive_rate');
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_positive_amount_foreign');
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_positive_amount_local');
    }
};
```

### 5.2 Missing Cascade Delete Rules

```php
// Create migration: 2026_04_09_000700_fix_cascade_deletes.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix compliance_findings cascade
        Schema::table('compliance_findings', function (Blueprint $table) {
            $table->dropForeign(['primary_finding_id']);
            $table->foreign('primary_finding_id')
                ->references('id')
                ->on('compliance_findings')
                ->onDelete('cascade');
        });

        // Ensure proper cascade for customer documents
        Schema::table('customer_documents', function (Blueprint $table) {
            // Already has cascade onDelete, verify
        });

        // Ensure proper cascade for compliance case related tables
        Schema::table('compliance_case_notes', function (Blueprint $table) {
            // Already has cascade onDelete, verify
        });

        Schema::table('compliance_case_documents', function (Blueprint $table) {
            // Already has cascade onDelete, verify
        });

        Schema::table('compliance_case_links', function (Blueprint $table) {
            // Already has cascade onDelete, verify
        });
    }

    public function down(): void
    {
        // Revert changes if needed
    }
};
```

### 5.3 Optimistic Locking

Most tables lack version columns for optimistic locking:

```php
// Create migration: 2026_04_09_000800_add_version_columns.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions already has version
        // Add to other critical tables
        
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        Schema::table('currency_positions', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('str_reports', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('compliance_cases', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('currency_positions', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });
    }
};
```

---

## 6. Additional Optimizations

### 6.1 Missing JSON Index Support (MySQL 5.7+)

```php
// For tables with JSON columns used in WHERE clauses

// compliance_findings.details
DB::statement('ALTER TABLE compliance_findings ADD INDEX idx_details_json ((CAST(details AS CHAR(255) ARRAY)))');

// compliance_cases.metadata
DB::statement('ALTER TABLE compliance_cases ADD INDEX idx_metadata_json ((CAST(metadata AS CHAR(255) ARRAY)))');
```

### 6.2 Partitioning Recommendations

```sql
-- For high-volume tables, implement partitioning:

-- Partition transactions by year
ALTER TABLE transactions 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Partition system_logs by month
ALTER TABLE system_logs 
PARTITION BY RANGE (TO_DAYS(created_at)) (
    PARTITION p202601 VALUES LESS THAN (TO_DAYS('2026-02-01')),
    PARTITION p202602 VALUES LESS THAN (TO_DAYS('2026-03-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### 6.3 Full-Text Search Indexes

```sql
-- For compliance search functionality
ALTER TABLE compliance_cases ADD FULLTEXT INDEX ft_case_summary (case_summary);
ALTER TABLE str_reports ADD FULLTEXT INDEX ft_narrative (narrative);
ALTER TABLE customers ADD FULLTEXT INDEX ft_customer_name (full_name);
```

---

## Implementation Priority

### Phase 1: Critical (Immediate - Blocker for Production)
1. ✅ Add missing indexes on foreign keys (transactions, customers, compliance)
2. ✅ Add soft deletes to transaction tables
3. ✅ Add audit fields (created_by, updated_by)
4. ✅ Add unique constraints on business keys

### Phase 2: High Priority (Within 1 Week)
5. Add check constraints for data integrity
6. Add version columns for optimistic locking
7. Create comprehensive audit_logs table

### Phase 3: Medium Priority (Within 1 Month)
8. Implement table partitioning for large tables
9. Add full-text search indexes
10. Optimize JSON column queries

---

## Testing Recommendations

After implementing each migration:

```php
// Test migration rollback
php artisan migrate:rollback --step=1

// Test with production-sized data
php artisan db:seed --class=ProductionLikeDataSeeder

// Run explain analyze on critical queries
EXPLAIN ANALYZE SELECT * FROM transactions 
WHERE customer_id = 123 AND status = 'Completed' 
ORDER BY created_at DESC LIMIT 100;

// Verify index usage
EXPLAIN SELECT * FROM transactions WHERE approved_by = 5;
-- Should show: type: ref, key: idx_transactions_approved_by
```

---

## Compliance Notes

All schema changes must maintain:
- **BNM AML/CFT Guidelines**: All transaction data must be retained (soft deletes)
- **Audit Trail**: created_by, updated_by, deleted_by for accountability
- **Data Integrity**: Check constraints prevent invalid states
- **Performance**: Indexes ensure query performance for regulatory reporting

---

## Files Modified

This report references the following migration files:
- `database/migrations/2025_03_31_000001_create_users_table.php`
- `database/migrations/2025_03_31_000002_create_customers_table.php`
- `database/migrations/2025_03_31_000005_create_transactions_table.php`
- `database/migrations/2026_04_01_000002_create_journal_entries_table.php`
- `database/migrations/2026_04_10_000004_create_compliance_tables.php`
- `database/migrations/2026_04_10_000006_create_customer_tables.php`
- `database/migrations/2026_04_10_000007_create_position_and_str_tables.php`
- `database/migrations/2026_04_08_000001_create_compliance_findings_table.php`
- `database/migrations/2026_04_08_000002_create_compliance_cases_table.php`
- `database/migrations/2025_03_31_000006_create_system_logs_table.php`
- And 50+ additional migration files analyzed

And the following model files:
- `app/Models/Transaction.php`
- `app/Models/Customer.php`
- `app/Models/User.php`
- `app/Models/JournalEntry.php`
- `app/Models/CurrencyPosition.php`
- `app/Models/StrReport.php`
- `app/Models/FlaggedTransaction.php`
- `app/Models/Alert.php`
- And 40+ additional model files analyzed
