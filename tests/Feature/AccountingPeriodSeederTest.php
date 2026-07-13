<?php

namespace Tests\Feature;

use App\Enums\AccountingPeriodStatus;
use App\Models\AccountingPeriod;
use Database\Seeders\AccountingPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPeriodSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_open_periods_around_current_month(): void
    {
        $this->seed(AccountingPeriodSeeder::class);

        $expectedCodes = [
            now()->subMonth()->format('Y-m'),
            now()->format('Y-m'),
            now()->addMonth()->format('Y-m'),
        ];

        $this->assertCount(3, AccountingPeriod::all());

        foreach ($expectedCodes as $code) {
            $period = AccountingPeriod::where('period_code', $code)->first();

            $this->assertNotNull($period, "Period {$code} was not seeded");
            $this->assertSame(AccountingPeriodStatus::Open, $period->status);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AccountingPeriodSeeder::class);
        $this->seed(AccountingPeriodSeeder::class);

        $this->assertCount(3, AccountingPeriod::all());
    }
}
