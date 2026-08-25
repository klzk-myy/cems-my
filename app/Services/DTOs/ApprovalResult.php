<?php

namespace App\Services\DTOs;

use App\Models\Transaction;

class ApprovalResult
{
    /**
     * @param  bool  $success  Whether approval succeeded
     * @param  string  $message  User-facing message
     * @param  Transaction|null  $transaction  The approved transaction (if success)
     * @param  bool  $requiresProcessing  Whether transaction needs further processing (e.g., refunds)
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?Transaction $transaction = null,
        public readonly bool $requiresProcessing = false
    ) {}
}
