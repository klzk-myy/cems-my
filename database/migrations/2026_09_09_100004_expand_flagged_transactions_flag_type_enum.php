<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Full ComplianceFlagType value list, hardcoded for determinism.
     * Must mirror app/Enums/ComplianceFlagType.php case values exactly.
     */
    private const FLAG_TYPE_VALUES = [
        'Large_Amount',
        'Sanctions_Hit',
        'Velocity',
        'Structuring',
        'EDD_Required',
        'PEP_Status',
        'Sanction_Match',
        'High_Risk_Customer',
        'Unusual_Pattern',
        'Manual_Review',
        'High_Risk_Country',
        'Round_Amount',
        'Profile_Deviation',
        'Aml_Rule_Triggered',
        'Counterfeit_Currency',
        'Risk_Score_Escalation',
        'Related_Party_Ownership',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Normalise the pre-existing spelling ('Pep_Status') to the enum
        // value ('PEP_Status') first; under MySQL strict mode an ALTER that
        // leaves rows holding a value outside the new list fails outright.
        DB::table('flagged_transactions')
            ->where('flag_type', 'Pep_Status')
            ->update(['flag_type' => 'PEP_Status']);

        $values = implode(', ', array_map(
            fn (string $value) => "'{$value}'",
            self::FLAG_TYPE_VALUES
        ));

        DB::statement("ALTER TABLE flagged_transactions MODIFY COLUMN flag_type ENUM({$values}) NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('flagged_transactions')
            ->whereIn('flag_type', ['Risk_Score_Escalation', 'Related_Party_Ownership'])
            ->update(['flag_type' => 'Manual_Review']);

        DB::table('flagged_transactions')
            ->where('flag_type', 'PEP_Status')
            ->update(['flag_type' => 'Pep_Status']);

        DB::statement("ALTER TABLE flagged_transactions MODIFY COLUMN flag_type ENUM('Large_Amount', 'Sanctions_Hit', 'Velocity', 'Structuring', 'EDD_Required', 'Pep_Status', 'Sanction_Match', 'High_Risk_Customer', 'Unusual_Pattern', 'Manual_Review', 'High_Risk_Country', 'Round_Amount', 'Profile_Deviation', 'Aml_Rule_Triggered', 'Counterfeit_Currency') NOT NULL");
    }
};
