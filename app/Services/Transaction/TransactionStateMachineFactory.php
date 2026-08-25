<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Services\AuditService;

class TransactionStateMachineFactory
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function make(Transaction $transaction): TransactionStateMachine
    {
        return new TransactionStateMachine($transaction, $this->auditService);
    }
}
