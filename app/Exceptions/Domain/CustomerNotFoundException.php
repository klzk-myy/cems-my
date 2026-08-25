<?php

namespace App\Exceptions\Domain;

use Exception;

class CustomerNotFoundException extends Exception
{
    public function __construct(int $customerId, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Customer ID {$customerId} not found", $code, $previous);
    }
}
