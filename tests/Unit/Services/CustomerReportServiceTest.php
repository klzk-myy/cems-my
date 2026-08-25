<?php

namespace Tests\Unit\Services;

use App\Services\Reporting\CustomerReportService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerReportServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(CustomerReportService::class);

        $this->assertInstanceOf(CustomerReportService::class, $service);
    }
}
