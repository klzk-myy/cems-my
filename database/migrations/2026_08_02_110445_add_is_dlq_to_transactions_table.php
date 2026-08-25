<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_dlq')->default(false)->after('failure_reason');
            $table->index('is_dlq');
        });

        // Backfill existing DLQ transactions
        DB::statement("UPDATE transactions SET is_dlq = true WHERE failure_reason LIKE '[DLQ]%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['is_dlq']);
            $table->dropColumn('is_dlq');
        });
    }
};
