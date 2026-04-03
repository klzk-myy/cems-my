<?php

namespace Tests\Unit;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MathService;
use App\Services\PeriodCloseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodCloseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PeriodCloseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $mathService = new MathService;
        $accountingService = new AccountingService($mathService);
        $this->service = new PeriodCloseService($accountingService, $mathService);

        // Seed chart of accounts
        $this->seedChartOfAccounts();
    }

    protected function seedChartOfAccounts(): void
    {
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset']
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '4000'],
            ['account_name' => 'Revenue', 'account_type' => 'Revenue']
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Expenses', 'account_type' => 'Expense']
        );

        ChartOfAccount::firstOrCreate(
            ['account_code' => '3100'],
            ['account_name' => 'Retained Earnings', 'account_type' => 'Equity']
        );
    }

    public function test_can_close_open_period()
    {
        $user = User::factory()->create();

        $period = AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'open',
        ]);

        $result = $this->service->closePeriod($period, $user->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('closed', $period->fresh()->status);
    }

    public function test_cannot_close_already_closed_period()
    {
        $user = User::factory()->create();

        $period = AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already closed');

        $this->service->closePeriod($period, $user->id);
    }
}
