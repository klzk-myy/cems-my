<?php

namespace App\Listeners;

use App\Events\TransactionCancelled;
use Illuminate\Support\Facades\Log;

class TransactionCancelledListener
{
    public function __invoke(TransactionCancelled $event): void
    {
        Log::info('Transaction cancelled', [
            'transaction_id' => $event->transaction->id,
            'reason' => $event->reason,
            'cancelled_by' => $event->cancelledBy,
        ]);
    }
}
