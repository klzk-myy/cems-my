<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected User $requester,
    ) {}

    public function createRequest(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'type' => $data['type'] ?? StockTransfer::TYPE_STANDARD,
                'status' => StockTransfer::STATUS_REQUESTED,
                'source_branch_name' => $data['source_branch_name'],
                'destination_branch_name' => $data['destination_branch_name'],
                'requested_by' => $this->requester->id,
                'requested_at' => now(),
                'notes' => $data['notes'] ?? null,
                'total_value_myr' => $data['total_value_myr'] ?? '0.00',
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $transfer->items()->create([
                    'currency_code' => $item['currency_code'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'value_myr' => $item['value_myr'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function approveByBranchManager(StockTransfer $transfer): void
    {
        if ($this->requester->role !== UserRole::Manager && $this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only managers can approve transfers');
        }

        if (!$transfer->isPending()) {
            throw new \RuntimeException('Transfer is not in requested status');
        }

        $transfer->approveByBranchManager($this->requester);
    }

    public function approveByHQ(StockTransfer $transfer): void
    {
        if ($this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only HQ (Admin) can approve transfers');
        }

        if ($transfer->status !== StockTransfer::STATUS_BM_APPROVED) {
            throw new \RuntimeException('Transfer must be BM-approved before HQ approval');
        }

        $transfer->approveByHQ($this->requester);
    }

    public function dispatch(StockTransfer $transfer): void
    {
        if ($this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only admin can dispatch transfers');
        }

        if ($transfer->status !== StockTransfer::STATUS_HQ_APPROVED) {
            throw new \RuntimeException('Transfer must be HQ-approved before dispatch');
        }

        $transfer->dispatch();
    }

    public function receiveItems(StockTransfer $transfer, array $items): void
    {
        if ($this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only admin can receive items');
        }

        if ($transfer->status !== StockTransfer::STATUS_IN_TRANSIT) {
            throw new \RuntimeException('Transfer must be in transit to receive items');
        }

        foreach ($items as $itemData) {
            $item = $transfer->items()->where('id', $itemData['id'])->first();
            if ($item) {
                $item->update([
                    'quantity_received' => $itemData['quantity_received'],
                    'quantity_in_transit' => bcsub($item->quantity, $itemData['quantity_received'], 4),
                ]);

                if ($item->hasVariance()) {
                    $item->update(['variance_notes' => "Variance: {$item->variance}"]);
                }
            }
        }

        $allFullyReceived = $transfer->items->every(fn($item) => $item->isFullyReceived());
        if (!$allFullyReceived) {
            $transfer->update(['status' => StockTransfer::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    public function complete(StockTransfer $transfer): void
    {
        if ($this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only admin can complete transfers');
        }

        if (!in_array($transfer->status, [StockTransfer::STATUS_IN_TRANSIT, StockTransfer::STATUS_PARTIALLY_RECEIVED])) {
            throw new \RuntimeException('Transfer must be in transit or partially received to complete');
        }

        $transfer->complete();
    }

    public function cancel(StockTransfer $transfer, string $reason): void
    {
        if ($this->requester->role !== UserRole::Manager && $this->requester->role !== UserRole::Admin) {
            throw new \RuntimeException('Only managers can cancel transfers');
        }

        if ($transfer->isCompleted()) {
            throw new \RuntimeException('Cannot cancel a completed transfer');
        }

        $transfer->cancel($reason);
    }

    public function getPendingTransfers(): Collection
    {
        return StockTransfer::pending()->with('items')->get();
    }

    public function getInTransitTransfers(): Collection
    {
        return StockTransfer::inTransit()->with('items')->get();
    }

    public function getTransfersByBranch(string $branchName): Collection
    {
        return StockTransfer::where('source_branch_name', $branchName)
            ->orWhere('destination_branch_name', $branchName)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}