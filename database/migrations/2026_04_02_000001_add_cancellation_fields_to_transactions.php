<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite, we need to use a full table recreation approach
        if (DB::getDriverName() === 'sqlite') {
            // Get current columns and data
            $columns = DB::select('PRAGMA table_info(transactions)');
            $hasCancelled = false;
            foreach ($columns as $col) {
                if ($col->name === 'cancelled_at') {
                    $hasCancelled = true;
                    break;
                }
            }

            if (! $hasCancelled) {
                // Create new table with updated schema
                DB::statement('
                    CREATE TABLE transactions_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        customer_id INTEGER NOT NULL,
                        user_id INTEGER NOT NULL,
                        till_id VARCHAR(50) DEFAULT "MAIN",
                        type VARCHAR(255) CHECK(type IN ("Buy", "Sell")),
                        currency_code VARCHAR(3) NOT NULL,
                        amount_local DECIMAL(18,4) NOT NULL,
                        amount_foreign DECIMAL(18,4) NOT NULL,
                        rate DECIMAL(18,6) NOT NULL,
                        purpose TEXT NULL,
                        source_of_funds VARCHAR(255) NULL,
                        status VARCHAR(255) CHECK(status IN ("Pending", "Completed", "OnHold", "Rejected", "Reversed", "Cancelled")) DEFAULT "Pending",
                        hold_reason TEXT NULL,
                        approved_by INTEGER NULL,
                        approved_at DATETIME NULL,
                        cdd_level VARCHAR(255) CHECK(cdd_level IN ("Simplified", "Standard", "Enhanced")),
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL,
                        cancelled_at DATETIME NULL,
                        cancelled_by INTEGER NULL,
                        cancellation_reason TEXT NULL,
                        original_transaction_id INTEGER NULL,
                        is_refund BOOLEAN DEFAULT 0,
                        FOREIGN KEY (customer_id) REFERENCES customers(id),
                        FOREIGN KEY (user_id) REFERENCES users(id),
                        FOREIGN KEY (approved_by) REFERENCES users(id),
                        FOREIGN KEY (cancelled_by) REFERENCES users(id),
                        FOREIGN KEY (original_transaction_id) REFERENCES transactions_new(id),
                        FOREIGN KEY (currency_code) REFERENCES currencies(code)
                    )
                ');

                // Copy data from old table
                DB::statement('
                    INSERT INTO transactions_new (
                        id, customer_id, user_id, till_id, type, currency_code, amount_local, amount_foreign,
                        rate, purpose, source_of_funds, status, hold_reason, approved_by, approved_at,
                        cdd_level, created_at, updated_at, cancelled_at, cancelled_by, cancellation_reason,
                        original_transaction_id, is_refund
                    )
                    SELECT
                        id, customer_id, user_id, till_id, type, currency_code, amount_local, amount_foreign,
                        rate, purpose, source_of_funds, status, hold_reason, approved_by, approved_at,
                        cdd_level, created_at, updated_at, NULL, NULL, NULL, NULL, 0
                    FROM transactions
                ');

                // Drop old table
                DB::statement('DROP TABLE transactions');

                // Rename new table
                DB::statement('ALTER TABLE transactions_new RENAME TO transactions');

                // Recreate indexes
                DB::statement('CREATE INDEX idx_transactions_customer_created ON transactions(customer_id, created_at)');
                DB::statement('CREATE INDEX idx_transactions_status ON transactions(status)');
                DB::statement('CREATE INDEX idx_transactions_type_currency ON transactions(type, currency_code)');
                DB::statement('CREATE INDEX idx_transactions_created_at ON transactions(created_at)');
                DB::statement('CREATE INDEX idx_transactions_amount_local ON transactions(amount_local)');
                DB::statement('CREATE INDEX idx_transactions_cancelled_at ON transactions(cancelled_at)');
                DB::statement('CREATE INDEX idx_transactions_is_refund ON transactions(is_refund)');
                DB::statement('CREATE INDEX idx_transactions_original_transaction ON transactions(original_transaction_id)');
            }
        } else {
            // MySQL/PostgreSQL - modify enum
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('Pending', 'Completed', 'OnHold', 'Rejected', 'Reversed', 'Cancelled') DEFAULT 'Pending'");

            Schema::table('transactions', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable()->after('approved_at');
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users');
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
                $table->foreignId('original_transaction_id')->nullable()->after('cancellation_reason')->constrained('transactions');
                $table->boolean('is_refund')->default(false)->after('original_transaction_id');

                // Add index for cancelled transactions lookup
                $table->index('cancelled_at');
                $table->index('is_refund');
                $table->index('original_transaction_id');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, recreate table without cancellation fields
            DB::statement('
                CREATE TABLE transactions_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    till_id VARCHAR(50) DEFAULT "MAIN",
                    type VARCHAR(255) CHECK(type IN ("Buy", "Sell")),
                    currency_code VARCHAR(3) NOT NULL,
                    amount_local DECIMAL(18,4) NOT NULL,
                    amount_foreign DECIMAL(18,4) NOT NULL,
                    rate DECIMAL(18,6) NOT NULL,
                    purpose TEXT NULL,
                    source_of_funds VARCHAR(255) NULL,
                    status VARCHAR(255) CHECK(status IN ("Pending", "Completed", "OnHold", "Rejected", "Reversed")) DEFAULT "Pending",
                    hold_reason TEXT NULL,
                    approved_by INTEGER NULL,
                    approved_at DATETIME NULL,
                    cdd_level VARCHAR(255) CHECK(cdd_level IN ("Simplified", "Standard", "Enhanced")),
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (customer_id) REFERENCES customers(id),
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (approved_by) REFERENCES users(id),
                    FOREIGN KEY (currency_code) REFERENCES currencies(code)
                )
            ');

            // Copy data, converting Cancelled status to Reversed
            DB::statement('
                INSERT INTO transactions_new (
                    id, customer_id, user_id, till_id, type, currency_code, amount_local, amount_foreign,
                    rate, purpose, source_of_funds, status, hold_reason, approved_by, approved_at,
                    cdd_level, created_at, updated_at
                )
                SELECT
                    id, customer_id, user_id, till_id, type, currency_code, amount_local, amount_foreign,
                    rate, purpose, source_of_funds,
                    CASE WHEN status = "Cancelled" THEN "Reversed" ELSE status END,
                    hold_reason, approved_by, approved_at, cdd_level, created_at, updated_at
                FROM transactions
            ');

            DB::statement('DROP TABLE transactions');
            DB::statement('ALTER TABLE transactions_new RENAME TO transactions');

            // Recreate indexes
            DB::statement('CREATE INDEX idx_transactions_customer_created ON transactions(customer_id, created_at)');
            DB::statement('CREATE INDEX idx_transactions_status ON transactions(status)');
            DB::statement('CREATE INDEX idx_transactions_type_currency ON transactions(type, currency_code)');
            DB::statement('CREATE INDEX idx_transactions_created_at ON transactions(created_at)');
            DB::statement('CREATE INDEX idx_transactions_amount_local ON transactions(amount_local)');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['cancelled_by']);
                $table->dropForeign(['original_transaction_id']);
                $table->dropColumn([
                    'cancelled_at',
                    'cancelled_by',
                    'cancellation_reason',
                    'original_transaction_id',
                    'is_refund',
                ]);
            });

            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('Pending', 'Completed', 'OnHold', 'Rejected', 'Reversed') DEFAULT 'Pending'");
        }
    }
};
