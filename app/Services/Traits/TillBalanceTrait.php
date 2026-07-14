<?php

namespace App\Services\Traits;

use App\Enums\TransactionType;
use App\Models\TillBalance;
use App\Services\Branch\TillBalanceManager;

/**
 * Shared till balance operations for transaction services.
 *
 * Removes duplicate updateTillBalance() methods across:
 * - TransactionCreationService
 * - TransactionApprovalService
 * - TransactionImportService
 */
trait TillBalanceTrait
{
    protected TillBalanceManager $tillBalanceManager;

    /**
     * Update till balance by applying a transaction.
     *
     * @param  TillBalance  $tillBalance  The till balance to update
     * @param  TransactionType  $type  Transaction type
     * @param  string  $amountLocal  Local currency amount
     * @param  string  $amountForeign  Foreign currency amount
     */
    protected function updateTillBalance(
        TillBalance $tillBalance,
        TransactionType $type,
        string $amountLocal,
        string $amountForeign
    ): void {
        $this->tillBalanceManager->applyTransaction(
            $tillBalance,
            $type,
            $amountLocal,
            $amountForeign
        );
    }
}
