<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Add missing foreign keys to str_reports
        Schema::table('str_reports', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('alert_id')->references('id')->on('flagged_transactions')->onDelete('set null');
        });

        // Only modify cascade delete on MySQL (SQLite doesn't support dropForeign)
        if ($driver === 'mysql') {
            // Add cascade delete to flagged_transactions.transaction_id
            Schema::table('flagged_transactions', function (Blueprint $table) {
                $table->dropForeign(['transaction_id']);
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            });

            // Add cascade delete to enhanced_diligence_records.flagged_transaction_id
            Schema::table('enhanced_diligence_records', function (Blueprint $table) {
                $table->dropForeign(['flagged_transaction_id']);
                $table->foreign('flagged_transaction_id')->references('id')->on('flagged_transactions')->onDelete('cascade');
            });

            // Add cascade delete to journal_lines.journal_entry_id
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->dropForeign(['journal_entry_id']);
                $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Revert str_reports foreign keys
        Schema::table('str_reports', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['alert_id']);
        });

        // Only revert cascade delete on MySQL
        if ($driver === 'mysql') {
            // Revert flagged_transactions cascade
            Schema::table('flagged_transactions', function (Blueprint $table) {
                $table->dropForeign(['transaction_id']);
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('restrict');
            });

            // Revert enhanced_diligence_records cascade
            Schema::table('enhanced_diligence_records', function (Blueprint $table) {
                $table->dropForeign(['flagged_transaction_id']);
                $table->foreign('flagged_transaction_id')->references('id')->on('flagged_transactions')->onDelete('restrict');
            });

            // Revert journal_lines cascade
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->dropForeign(['journal_entry_id']);
                $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('restrict');
            });
        }
    }
};
