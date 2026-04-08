<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default head office branch
        DB::table('branches')->updateOrInsert(
            ['code' => 'HQ'],
            [
                'name' => 'Head Office',
                'type' => 'head_office',
                'address' => 'Level 10, Menara Multi-Purpose',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan',
                'postal_code' => '50250',
                'country' => 'Malaysia',
                'phone' => '+60 3-1234 5678',
                'email' => 'hq@cems.my',
                'is_active' => true,
                'is_main' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create some default branches
        $branches = [
            ['code' => 'BR001', 'name' => 'Kuala Lumpur Branch', 'city' => 'Kuala Lumpur', 'type' => 'branch'],
            ['code' => 'BR002', 'name' => 'Petaling Jaya Branch', 'city' => 'Petaling Jaya', 'type' => 'branch'],
            ['code' => 'BR003', 'name' => 'Penang Branch', 'city' => 'George Town', 'type' => 'branch'],
        ];

        foreach ($branches as $branch) {
            DB::table('branches')->updateOrInsert(
                ['code' => $branch['code']],
                [
                    'name' => $branch['name'],
                    'type' => $branch['type'],
                    'city' => $branch['city'],
                    'country' => 'Malaysia',
                    'is_active' => true,
                    'is_main' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
