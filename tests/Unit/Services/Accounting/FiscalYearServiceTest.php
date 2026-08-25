<?php

namespace Tests\Unit\Services\Accounting;

use App\Models\FiscalYear;
use App\Services\Accounting\FiscalYearService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FiscalYearServiceTest extends TestCase
{
    use RefreshDatabase;

    private FiscalYearService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FiscalYearService::class);
    }

    #[Test]
    public function create_fiscal_year_persists_record(): void
    {
        $before = FiscalYear::count();

        $year = $this->service->createFiscalYear('2025', '2025-01-01', '2025-12-31');

        $this->assertInstanceOf(FiscalYear::class, $year);
        $this->assertSame('2025', $year->year_code);
        $this->assertSame('2025-01-01', $year->start_date->toDateString());
        $this->assertSame('2025-12-31', $year->end_date->toDateString());
        $this->assertSame($before + 1, FiscalYear::count());
    }

    #[Test]
    public function create_fiscal_year_rejects_duplicate_year_code(): void
    {
        $this->service->createFiscalYear('2025', '2025-01-01', '2025-12-31');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->service->createFiscalYear('2025', '2025-01-01', '2025-12-31');
    }
}
