<?php

use App\Enums\AmlRuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delete legacy aml_rules rows whose rule_type is not part of
     * App\Enums\AmlRuleType.
     *
     * The historical create migration seeded 'threshold' / 'aggregation'
     * rule_type values that the AmlRule model enum-casts, so they resolve to
     * null in the evaluator and are dead rows on every fresh install.
     * Equivalent coverage ships via AmlRuleSeeder (AMT-001 / STR-001).
     */
    public function up(): void
    {
        if (! Schema::hasTable('aml_rules')) {
            return;
        }

        DB::table('aml_rules')
            ->whereNotIn('rule_type', AmlRuleType::values())
            ->delete();
    }

    public function down(): void
    {
        // Legacy rows are intentionally removed and have no equivalent
        // schema to restore; nothing to do.
    }
};
