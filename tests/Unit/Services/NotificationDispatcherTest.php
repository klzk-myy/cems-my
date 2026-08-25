<?php

namespace Tests\Unit\Services;

use App\Services\System\NotificationDispatcher;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(NotificationDispatcher::class);

        $this->assertInstanceOf(NotificationDispatcher::class, $service);
    }
}
