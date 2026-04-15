<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('till_balances', function (Blueprint $table) {
            $table->foreignId('teller_allocation_id')->nullable()->after('branch_id')->constrained('teller_allocations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('till_balances', function (Blueprint $table) {
            $table->dropForeign(['teller_allocation_id']);
            $table->dropColumn('teller_allocation_id');
        });
    }
};
