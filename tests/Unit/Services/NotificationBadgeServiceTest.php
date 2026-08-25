<?php

namespace Tests\Unit\Services;

use App\Services\System\NotificationBadgeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationBadgeServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(NotificationBadgeService::class);

        $this->assertInstanceOf(NotificationBadgeService::class, $service);
    }
}
