<?php

namespace App\Exceptions\Domain;

class PositionLimitExceededException extends DomainException
{
    public function __construct(
        public readonly string $currency,
        public readonly string $projected,
        public readonly string $limit
    ) {
        parent::__construct(
            "Position limit exceeded for {$currency}: projected {$projected} exceeds limit {$limit}."
        );
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
