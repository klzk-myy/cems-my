<?php

namespace Tests\Unit\Services\Accounting;

use App\Enums\AccountingPeriodStatus;
use App\Models\AccountingPeriod;
use App\Services\Accounting\MonthEndCloseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthEndCloseServiceTest extends TestCase
{
    use RefreshDatabase;

    private MonthEndCloseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MonthEndCloseService::class);
    }

    #[Test]
    public function pre_flight_checks_pass_when_period_exists_and_is_open(): void
    {
        $date = Carbon::now()->startOfMonth();

        AccountingPeriod::create([
            'period_code' => '2025-01',
            'year_code' => '2025',
            'start_date' => $date->copy()->toDateString(),
            'end_date' => $date->copy()->endOfMonth()->toDateString(),
            'status' => AccountingPeriodStatus::Open,
        ]);

        $result = $this->service->preFlightChecks($date);

        $this->assertTrue($result['passed']);
        $this->assertEmpty($result['failures']);
    }

    #[Test]
    public function pre_flight_checks_fail_when_no_period_exists_for_date(): void
    {
        $date = Carbon::now()->startOfMonth();

        $result = $this->service->preFlightChecks($date);

        $this->assertFalse($result['passed']);
        $this->assertNotEmpty($result['failures']);
    }
}
