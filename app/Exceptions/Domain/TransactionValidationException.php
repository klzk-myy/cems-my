<?php

namespace App\Exceptions\Domain;

class TransactionValidationException extends TransactionException
{
    public function __construct(
        public ?string $field = null,
        string $message = 'Transaction validation failed',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
