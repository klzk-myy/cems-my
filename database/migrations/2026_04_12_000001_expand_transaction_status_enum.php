<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to handle the CHECK constraint differently
            // The enum constraint is emulated via CHECK with list of values

            // Clean up any existing backup table first
            DB::statement('DROP TABLE IF EXISTS transactions_old');
            DB::statement('DROP TABLE IF EXISTS transactions');

            // Create new table with expanded status constraint (PascalCase values)
            DB::statement('
                CREATE TABLE transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    branch_id INTEGER DEFAULT NULL,
                    till_id VARCHAR(50) DEFAULT "MAIN",
                    type VARCHAR(255) CHECK(type IN ("Buy", "Sell")),
                    currency_code VARCHAR(3) NOT NULL,
                    amount_local DECIMAL(18,4) NOT NULL,
                    amount_foreign DECIMAL(18,4) NOT NULL,
                    rate DECIMAL(18,6) NOT NULL,
                    base_rate DECIMAL(18,6) DEFAULT NULL,
                    rate_override BOOLEAN DEFAULT 0,
                    rate_override_approved_by INTEGER DEFAULT NULL,
                    rate_override_approved_at DATETIME DEFAULT NULL,
                    purpose TEXT DEFAULT NULL,
                    source_of_funds VARCHAR(255) DEFAULT NULL,
                    status VARCHAR(255) CHECK(status IN ("Draft", "PendingApproval", "Approved", "Processing", "Completed", "Finalized", "Cancelled", "Reversed", "Failed", "Rejected", "Pending", "OnHold")) DEFAULT "Draft",
                    hold_reason TEXT DEFAULT NULL,
                    approved_by INTEGER DEFAULT NULL,
                    approved_at DATETIME DEFAULT NULL,
                    cdd_level VARCHAR(255) CHECK(cdd_level IN ("Simplified", "Standard", "Enhanced")),
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    cancelled_at DATETIME DEFAULT NULL,
                    cancelled_by INTEGER DEFAULT NULL,
                    cancellation_reason TEXT DEFAULT NULL,
                    original_transaction_id INTEGER DEFAULT NULL,
                    is_refund BOOLEAN DEFAULT 0,
                    idempotency_key VARCHAR(255) DEFAULT NULL,
                    version INTEGER DEFAULT 1,
                    transition_history TEXT DEFAULT NULL,
                    failure_reason TEXT DEFAULT NULL,
                    rejection_reason TEXT DEFAULT NULL,
                    reversal_reason TEXT DEFAULT NULL,
                    FOREIGN KEY (customer_id) REFERENCES customers(id),
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (branch_id) REFERENCES branches(id),
                    FOREIGN KEY (approved_by) REFERENCES users(id),
                    FOREIGN KEY (cancelled_by) REFERENCES users(id),
                    FOREIGN KEY (original_transaction_id) REFERENCES transactions(id),
                    FOREIGN KEY (currency_code) REFERENCES currencies(code)
                )
            ');

            // Create indexes
            DB::statement('CREATE INDEX idx_transactions_customer_created ON transactions(customer_id, created_at)');
            DB::statement('CREATE INDEX idx_transactions_status ON transactions(status)');
            DB::statement('CREATE INDEX idx_transactions_type_currency ON transactions(type, currency_code)');
            DB::statement('CREATE INDEX idx_transactions_created_at ON transactions(created_at)');
            DB::statement('CREATE INDEX idx_transactions_amount_local ON transactions(amount_local)');
            DB::statement('CREATE INDEX idx_transactions_cancelled_at ON transactions(cancelled_at)');
            DB::statement('CREATE INDEX idx_transactions_is_refund ON transactions(is_refund)');
            DB::statement('CREATE INDEX idx_transactions_original_transaction ON transactions(original_transaction_id)');
        } else {
            // MySQL/PostgreSQL - modify enum to include all new statuses (PascalCase)
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('Draft', 'PendingApproval', 'Approved', 'Processing', 'Completed', 'Finalized', 'Cancelled', 'Reversed', 'Failed', 'Rejected') DEFAULT 'Draft'");

            // Add new columns if they don't exist (check for both column existence and table existence)
            if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'transition_history')) {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->json('transition_history')->nullable()->after('version');
                    $table->string('failure_reason')->nullable()->after('transition_history');
                    $table->string('rejection_reason')->nullable()->after('failure_reason');
                    $table->string('reversal_reason')->nullable()->after('rejection_reason');
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, recreate table with old schema
            DB::statement('DROP TABLE IF EXISTS transactions');
            DB::statement('DROP TABLE IF EXISTS transactions_old');

            DB::statement('
                CREATE TABLE transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    branch_id INTEGER DEFAULT NULL,
                    till_id VARCHAR(50) DEFAULT "MAIN",
                    type VARCHAR(255) CHECK(type IN ("Buy", "Sell")),
                    currency_code VARCHAR(3) NOT NULL,
                    amount_local DECIMAL(18,4) NOT NULL,
                    amount_foreign DECIMAL(18,4) NOT NULL,
                    rate DECIMAL(18,6) NOT NULL,
                    base_rate DECIMAL(18,6) DEFAULT NULL,
                    rate_override BOOLEAN DEFAULT 0,
                    rate_override_approved_by INTEGER DEFAULT NULL,
                    rate_override_approved_at DATETIME DEFAULT NULL,
                    purpose TEXT DEFAULT NULL,
                    source_of_funds VARCHAR(255) DEFAULT NULL,
                    status VARCHAR(255) CHECK(status IN ("Draft", "PendingApproval", "Approved", "Processing", "Completed", "Finalized", "Cancelled", "Reversed", "Failed", "Rejected", "Pending", "OnHold")) DEFAULT "Draft",
                    hold_reason TEXT DEFAULT NULL,
                    approved_by INTEGER DEFAULT NULL,
                    approved_at DATETIME DEFAULT NULL,
                    cdd_level VARCHAR(255) CHECK(cdd_level IN ("Simplified", "Standard", "Enhanced")),
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    cancelled_at DATETIME DEFAULT NULL,
                    cancelled_by INTEGER DEFAULT NULL,
                    cancellation_reason TEXT DEFAULT NULL,
                    original_transaction_id INTEGER DEFAULT NULL,
                    is_refund BOOLEAN DEFAULT 0,
                    idempotency_key VARCHAR(255) DEFAULT NULL,
                    version INTEGER DEFAULT 1,
                    FOREIGN KEY (customer_id) REFERENCES customers(id),
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (branch_id) REFERENCES branches(id),
                    FOREIGN KEY (approved_by) REFERENCES users(id),
                    FOREIGN KEY (cancelled_by) REFERENCES users(id),
                    FOREIGN KEY (original_transaction_id) REFERENCES transactions(id),
                    FOREIGN KEY (currency_code) REFERENCES currencies(code)
                )
            ');

            // Recreate indexes
            DB::statement('CREATE INDEX idx_transactions_customer_created ON transactions(customer_id, created_at)');
            DB::statement('CREATE INDEX idx_transactions_status ON transactions(status)');
            DB::statement('CREATE INDEX idx_transactions_type_currency ON transactions(type, currency_code)');
            DB::statement('CREATE INDEX idx_transactions_created_at ON transactions(created_at)');
            DB::statement('CREATE INDEX idx_transactions_amount_local ON transactions(amount_local)');
            DB::statement('CREATE INDEX idx_transactions_cancelled_at ON transactions(cancelled_at)');
            DB::statement('CREATE INDEX idx_transactions_is_refund ON transactions(is_refund)');
            DB::statement('CREATE INDEX idx_transactions_original_transaction ON transactions(original_transaction_id)');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $columnsToDrop = ['transition_history', 'failure_reason', 'rejection_reason', 'reversal_reason'];
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });

            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('Pending', 'Completed', 'OnHold', 'Rejected', 'Reversed', 'Cancelled') DEFAULT 'Pending'");
        }
    }
};
