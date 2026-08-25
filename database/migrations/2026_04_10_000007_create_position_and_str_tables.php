<?php

/**
 * Consolidated Migration: Currency Positions, Revaluation, Branches
 * Replaces: 2025_03_31_000012, 2025_03_31_000013,
 *          2026_04_09_000001_create_branches_table
 *
 * Creates: currency_positions, revaluation_entries, branches
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Currency positions table
        if (! Schema::hasTable('currency_positions')) {
            Schema::create('currency_positions', function (Blueprint $table) {
                $table->id();
                $table->string('currency_code', 3);
                $table->string('branch_id', 50)->default('HQ');
                $table->decimal('quantity', 18, 4)->default(0);
                $table->decimal('average_cost', 18, 6)->default(0);
                $table->decimal('total_cost', 18, 4)->default(0);
                $table->decimal('current_rate', 18, 6)->default(0);
                $table->decimal('current_value', 18, 4)->default(0);
                $table->decimal('unrealized_gain_loss', 18, 4)->default(0);
                $table->timestamp('last_revalued_at')->nullable();
                $table->timestamps();
                $table->unique(['currency_code', 'branch_id']);
                $table->foreign('currency_code')->references('code')->on('currencies');
                $table->index(['currency_code']);
                $table->index('branch_id');
            });
        }

        // Revaluation entries table
        if (! Schema::hasTable('revaluation_entries')) {
            Schema::create('revaluation_entries', function (Blueprint $table) {
                $table->id();
                $table->date('revaluation_date');
                $table->string('currency_code', 3);
                $table->string('branch_id', 50)->default('HQ');
                $table->decimal('quantity_before', 18, 4);
                $table->decimal('rate_before', 18, 6);
                $table->decimal('value_before_myr', 18, 4);
                $table->decimal('quantity_after', 18, 4);
                $table->decimal('rate_after', 18, 6);
                $table->decimal('value_after_myr', 18, 4);
                $table->decimal('gain_loss', 18, 4);
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->index(['revaluation_date', 'currency_code']);
                $table->foreign('currency_code')->references('code')->on('currencies');
            });
        }

        // Branches table
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->string('type', 30)->default('branch');
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 50)->default('Malaysia');
                $table->string('phone', 30)->nullable();
                $table->string('email', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_main')->default(false);
                $table->timestamps();
                $table->index('code');
                $table->index(['is_active', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('revaluation_entries');
        Schema::dropIfExists('currency_positions');
    }
};
