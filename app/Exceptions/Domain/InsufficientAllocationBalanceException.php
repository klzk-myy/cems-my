<?php

namespace App\Exceptions\Domain;

class InsufficientAllocationBalanceException extends DomainException
{
    public function __construct(
        public readonly string $currency,
        public readonly string $available,
        public readonly string $requested
    ) {
        parent::__construct(
            "Insufficient available balance in teller allocation. Currency: {$currency}, Available: {$available}, Requested: {$requested}"
        );
    }
}
