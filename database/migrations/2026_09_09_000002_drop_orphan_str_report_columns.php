<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sanctions_entries') && Schema::hasColumn('sanctions_entries', 'str_report_id')) {
            Schema::table('sanctions_entries', function (Blueprint $table) {
                $table->dropColumn('str_report_id');
            });
        }

        if (Schema::hasTable('sanctions_lists') && Schema::hasColumn('sanctions_lists', 'str_report_id')) {
            Schema::table('sanctions_lists', function (Blueprint $table) {
                $table->dropColumn('str_report_id');
            });
        }

        if (Schema::hasTable('sanctions_lists') && Schema::hasColumn('sanctions_lists', 'str_draft_id')) {
            Schema::table('sanctions_lists', function (Blueprint $table) {
                $table->dropColumn('str_draft_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sanctions_entries') && ! Schema::hasColumn('sanctions_entries', 'str_report_id')) {
            Schema::table('sanctions_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('str_report_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('sanctions_lists') && ! Schema::hasColumn('sanctions_lists', 'str_report_id')) {
            Schema::table('sanctions_lists', function (Blueprint $table) {
                $table->unsignedBigInteger('str_report_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('sanctions_lists') && ! Schema::hasColumn('sanctions_lists', 'str_draft_id')) {
            Schema::table('sanctions_lists', function (Blueprint $table) {
                $table->unsignedBigInteger('str_draft_id')->nullable()->after('id');
            });
        }
    }
};
