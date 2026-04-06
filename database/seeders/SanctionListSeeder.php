<?php

namespace Database\Seeders;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use Illuminate\Database\Seeder;

class SanctionListSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = \App\Models\User::where('role', 'admin')->first();
        $uploadedBy = $adminUser?->id;

        // Create UN Security Council Resolutions list
        $unList = SanctionList::firstOrCreate(
            ['name' => 'UN Security Council Resolutions'],
            [
                'list_type' => 'UNSCR',
                'is_active' => true,
                'uploaded_by' => $uploadedBy,
            ]
        );

        // Create MOF (Ministry of Finance) list
        $mohaList = SanctionList::firstOrCreate(
            ['name' => 'MOF Sanctions List'],
            [
                'list_type' => 'MOHA',
                'is_active' => true,
                'uploaded_by' => $uploadedBy,
            ]
        );

        // Create Internal list
        $internalList = SanctionList::firstOrCreate(
            ['name' => 'Internal High Risk List'],
            [
                'list_type' => 'Internal',
                'is_active' => true,
                'uploaded_by' => $uploadedBy,
            ]
        );

        // Seed example entries for UNSCR list
        $unEntries = [
            [
                'entity_name' => 'John Doe Ali',
                'entity_type' => 'Individual',
                'aliases' => json_encode(['Johan Doe', 'J. Doe']),
                'nationality' => 'IR',
                'date_of_birth' => '1975-03-15',
                'details' => json_encode(['address' => 'Tehran, Iran', 'passport' => 'X1234567']),
            ],
            [
                'entity_name' => 'Kim Jong-un',
                'entity_type' => 'Individual',
                'aliases' => json_encode(['Kim Jong-un', 'Kim Jong Un']),
                'nationality' => 'KP',
                'date_of_birth' => '1984-01-08',
                'details' => json_encode(['address' => 'Pyongyang, DPRK']),
            ],
            [
                'entity_name' => 'Myanmar Military Junta Corp',
                'entity_type' => 'Entity',
                'aliases' => json_encode(['SAC', 'Tatmadaw']),
                'nationality' => 'MM',
                'date_of_birth' => null,
                'details' => json_encode(['registration_no' => '123456789']),
            ],
        ];

        foreach ($unEntries as $entry) {
            SanctionEntry::firstOrCreate(
                ['list_id' => $unList->id, 'entity_name' => $entry['entity_name']],
                $entry
            );
        }

        // Seed example entries for MOHA list
        $mohaEntries = [
            [
                'entity_name' => 'Ahmad bin Mahmud',
                'entity_type' => 'Individual',
                'aliases' => json_encode(['Ahmad M.', 'Abu Mahmud']),
                'nationality' => 'MY',
                'date_of_birth' => '1980-07-22',
                'details' => json_encode(['ic_no' => '700101-14-1234']),
            ],
            [
                'entity_name' => 'Shadow Finance Ltd',
                'entity_type' => 'Entity',
                'aliases' => json_encode(['SF Ltd', 'Shadow Fin']),
                'nationality' => 'MY',
                'date_of_birth' => null,
                'details' => json_encode(['reg_no' => '1234567-A']),
            ],
        ];

        foreach ($mohaEntries as $entry) {
            SanctionEntry::firstOrCreate(
                ['list_id' => $mohaList->id, 'entity_name' => $entry['entity_name']],
                $entry
            );
        }

        // Seed example entries for internal list
        $internalEntries = [
            [
                'entity_name' => 'Suspicious Customer XYZ',
                'entity_type' => 'Individual',
                'aliases' => json_encode(['XYZ Corp']),
                'nationality' => 'MY',
                'date_of_birth' => '1990-05-10',
                'details' => json_encode(['reason' => 'Multiple STR filed']),
            ],
        ];

        foreach ($internalEntries as $entry) {
            SanctionEntry::firstOrCreate(
                ['list_id' => $internalList->id, 'entity_name' => $entry['entity_name']],
                $entry
            );
        }

        $totalEntries = count($unEntries) + count($mohaEntries) + count($internalEntries);
        $this->command->info("Seeded 3 sanction lists with {$totalEntries} total entries");
    }
}
