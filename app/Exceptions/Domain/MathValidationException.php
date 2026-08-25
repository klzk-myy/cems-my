<?php

namespace App\Exceptions\Domain;

class MathValidationException extends DomainException
{
    public function __construct(string $message = 'Mathematical operation validation failed')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
