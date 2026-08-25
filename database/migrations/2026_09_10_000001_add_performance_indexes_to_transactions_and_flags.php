<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'created_at']);
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->index(['status', 'flag_type']);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->index(['priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'created_at']);
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'flag_type']);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['priority', 'status']);
        });
    }
};
