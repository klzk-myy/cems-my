<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['str_reports', 'report_templates', 'audit_trail_filters', 'customer_searches', 'risk_score_factors', 'report_run_audit_trails'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }

    public function down(): void
    {
        // Tables are permanently dropped.
    }
};
