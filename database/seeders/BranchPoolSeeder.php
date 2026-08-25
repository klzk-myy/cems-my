<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchPool;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class BranchPoolSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $currencies = Currency::where('code', '!=', 'MYR')->where('is_active', true)->get();

        $initialBalances = [
            'USD' => '50000.0000',
            'EUR' => '40000.0000',
            'GBP' => '30000.0000',
            'SGD' => '35000.0000',
            'AUD' => '25000.0000',
            'JPY' => '2000000.0000',
            'CHF' => '20000.0000',
            'CAD' => '25000.0000',
            'HKD' => '80000.0000',
            'CNY' => '100000.0000',
        ];

        foreach ($branches as $branch) {
            foreach ($currencies as $currency) {
                $initialAmount = $initialBalances[$currency->code] ?? '10000.0000';

                BranchPool::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'currency_code' => $currency->code,
                    ],
                    [
                        'available_balance' => $initialAmount,
                        'allocated_balance' => '0.0000',
                    ]
                );

                $this->command->info("Seeded branch pool for {$branch->code} - {$currency->code}: {$initialAmount}");
            }
        }

        $this->command->info('Branch pool seeding completed');
    }
}
