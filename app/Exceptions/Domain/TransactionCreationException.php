<?php

namespace App\Exceptions\Domain;

class TransactionCreationException extends TransactionException
{
    public function __construct(
        string $message = 'Transaction creation failed',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
