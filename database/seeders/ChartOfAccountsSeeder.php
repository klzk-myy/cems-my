<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['account_code' => '1000', 'account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1100', 'account_name' => 'Cash - USD', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1200', 'account_name' => 'Cash - EUR', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1300', 'account_name' => 'Cash - GBP', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '1400', 'account_name' => 'Cash - SGD', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2000', 'account_name' => 'Foreign Currency Inventory', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2100', 'account_name' => 'Accounts Receivable', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '2200', 'account_name' => 'Prepaid Expenses', 'account_type' => 'Asset', 'parent_code' => null],
            ['account_code' => '3000', 'account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'parent_code' => null],
            ['account_code' => '3100', 'account_name' => 'Accrued Expenses', 'account_type' => 'Liability', 'parent_code' => null],
            ['account_code' => '4000', 'account_name' => 'Paid-in Capital', 'account_type' => 'Equity', 'parent_code' => null],
            ['account_code' => '4100', 'account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'parent_code' => null],
            ['account_code' => '4200', 'account_name' => 'Unrealized Forex Gains/Losses', 'account_type' => 'Equity', 'parent_code' => null],
            ['account_code' => '5000', 'account_name' => 'Revenue - Forex Trading', 'account_type' => 'Revenue', 'parent_code' => null],
            ['account_code' => '5100', 'account_name' => 'Revenue - Revaluation Gain', 'account_type' => 'Revenue', 'parent_code' => null],
            ['account_code' => '6000', 'account_name' => 'Expense - Forex Loss', 'account_type' => 'Expense', 'parent_code' => null],
            ['account_code' => '6100', 'account_name' => 'Expense - Revaluation Loss', 'account_type' => 'Expense', 'parent_code' => null],
            ['account_code' => '6200', 'account_name' => 'Expense - Operating', 'account_type' => 'Expense', 'parent_code' => null],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(
                ['account_code' => $account['account_code']],
                $account
            );
        }
    }
}
