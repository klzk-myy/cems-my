<?php

namespace App\Exceptions\Domain;

class UserManagementException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 403;
    }
}
