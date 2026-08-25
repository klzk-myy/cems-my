<?php

namespace App\Exceptions\Domain;

class ThresholdNotFoundException extends DomainException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Threshold not found: {$identifier}");
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
