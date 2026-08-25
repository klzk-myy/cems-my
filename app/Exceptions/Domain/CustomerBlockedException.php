<?php

namespace App\Exceptions\Domain;

class CustomerBlockedException extends DomainException
{
    public function __construct(public readonly int $customerId, string $reason)
    {
        parent::__construct(
            "Customer #{$customerId} cannot transact: {$reason}"
        );
    }

    public function getStatusCode(): int
    {
        return 403;
    }
}
