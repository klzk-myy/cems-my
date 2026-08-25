<?php

namespace App\Exceptions\Domain;

class CaseManagementException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
