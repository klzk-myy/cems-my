<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'status', 'created_at'], 'idx_transactions_branch_status_date');
        });

        Schema::table('till_balances', function (Blueprint $table) {
            $table->index(['updated_at'], 'idx_till_balances_updated');
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_flagged_transactions_status_date');
        });

        Schema::table('audit_trails', function (Blueprint $table) {
            $table->index(['ip_address', 'created_at'], 'idx_audit_trails_ip_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['risk_rating_changed_at'], 'idx_customers_risk_rating_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_branch_status_date');
        });

        Schema::table('till_balances', function (Blueprint $table) {
            $table->dropIndex('idx_till_balances_updated');
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_flagged_transactions_status_date');
        });

        Schema::table('audit_trails', function (Blueprint $table) {
            $table->dropIndex('idx_audit_trails_ip_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_risk_rating_changed_at');
        });
    }
};
