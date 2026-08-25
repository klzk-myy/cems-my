<?php

namespace Tests\Unit\Services;

use App\Services\Reporting\ReportSchedulingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportSchedulingServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(ReportSchedulingService::class);

        $this->assertInstanceOf(ReportSchedulingService::class, $service);
    }
}
