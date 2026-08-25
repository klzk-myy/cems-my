<?php

namespace Tests\Unit\Services;

use App\Services\System\QueryOptimizerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueryOptimizerServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(QueryOptimizerService::class);

        $this->assertInstanceOf(QueryOptimizerService::class, $service);
    }
}
