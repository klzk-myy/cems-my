<?php

namespace Tests\Unit\Services;

use App\Services\AuditService;
use App\Services\Transaction\TransactionStateMachineFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionStateMachineFactoryTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = new TransactionStateMachineFactory(new AuditService);

        $this->assertInstanceOf(TransactionStateMachineFactory::class, $service);
    }
}
