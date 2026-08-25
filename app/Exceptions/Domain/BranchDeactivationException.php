<?php

namespace App\Exceptions\Domain;

class BranchDeactivationException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function getStatusCode(): int
    {
        return 403;
    }
}
