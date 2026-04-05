<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CurrencySeeder::class,
            ChartOfAccountsSeeder::class,
            AccountingPeriodSeeder::class,
            BudgetSeeder::class,
            HighRiskCountrySeeder::class,
            SanctionListSeeder::class,
            AmlRuleSeeder::class,
        ]);
    }
}