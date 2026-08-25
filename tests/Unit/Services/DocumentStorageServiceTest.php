<?php

namespace Tests\Unit\Services;

use App\Services\System\DocumentStorageService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentStorageServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = app(DocumentStorageService::class);

        $this->assertInstanceOf(DocumentStorageService::class, $service);
    }
}
