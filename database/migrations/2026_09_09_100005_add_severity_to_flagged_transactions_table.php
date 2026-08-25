<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flagged_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('flagged_transactions', 'severity')) {
                $table->string('severity', 20)->nullable()->after('flag_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flagged_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('flagged_transactions', 'severity')) {
                $table->dropColumn('severity');
            }
        });
    }
};
