<?php

namespace Tests\Unit\Enums;

use App\Enums\CounterStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CounterStatusTest extends TestCase
{
    #[Test]
    public function maintenance_value_is_allowed_by_database(): void
    {
        $this->assertSame('maintenance', CounterStatus::Maintenance->value);
    }
}
