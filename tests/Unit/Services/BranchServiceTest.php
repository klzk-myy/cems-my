<?php

namespace Tests\Unit\Services;

use App\Services\Branch\BranchService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BranchServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(BranchService::class);

        $this->assertInstanceOf(BranchService::class, $service);
    }
}
