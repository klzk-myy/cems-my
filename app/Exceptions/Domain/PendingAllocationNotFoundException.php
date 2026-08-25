<?php

namespace App\Exceptions\Domain;

class PendingAllocationNotFoundException extends DomainException
{
    public function __construct(string $currency)
    {
        parent::__construct("No pending allocation found for {$currency}");
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
