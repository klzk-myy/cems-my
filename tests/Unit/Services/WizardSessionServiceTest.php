<?php

namespace Tests\Unit\Services;

use App\Services\System\WizardSessionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WizardSessionServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(WizardSessionService::class);

        $this->assertInstanceOf(WizardSessionService::class, $service);
    }
}
