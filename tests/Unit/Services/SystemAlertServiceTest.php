<?php

namespace Tests\Unit\Services;

use App\Services\System\SystemAlertService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemAlertServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(SystemAlertService::class);

        $this->assertInstanceOf(SystemAlertService::class, $service);
    }
}
