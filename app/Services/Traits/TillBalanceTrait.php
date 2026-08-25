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
     * @param  string  $type  Transaction type ('Buy' or 'Sell')
     * @param  string  $amountLocal  Local currency amount
     * @param  string  $amountForeign  Foreign currency amount
     */
    protected function updateTillBalance(
        TillBalance $tillBalance,
        string $type,
        string $amountLocal,
        string $amountForeign
    ): void {
        $this->tillBalanceManager->applyTransaction(
            $tillBalance,
            TransactionType::from($type),
            $amountLocal,
            $amountForeign
        );
    }
}
