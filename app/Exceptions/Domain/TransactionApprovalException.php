<?php

namespace App\Exceptions\Domain;

class TransactionApprovalException extends TransactionException
{
    public function __construct(
        public int $transactionId,
        string $message = 'Transaction approval failed',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
