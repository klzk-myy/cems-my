<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createService(User $user): StockTransferService
    {
        return new StockTransferService($user);
    }

    public function test_create_request_generates_transfer_number(): void
    {
        $user = User::factory()->create(['role' => UserRole::Manager]);
        $service = $this->createService($user);

        $transfer = $service->createRequest([
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'type' => StockTransfer::TYPE_STANDARD,
            'items' => [
                ['currency_code' => 'USD', 'quantity' => '1000.0000', 'rate' => '4.500000', 'value_myr' => '4500.00'],
            ],
        ]);

        $this->assertNotNull($transfer->transfer_number);
        $this->assertStringStartsWith('TRF-', $transfer->transfer_number);
        $this->assertEquals(StockTransfer::STATUS_REQUESTED, $transfer->status);
    }

    public function test_approve_by_branch_manager_changes_status(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0001',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);
        $service->approveByBranchManager($transfer);

        $transfer->refresh();
        $this->assertEquals(StockTransfer::STATUS_BM_APPROVED, $transfer->status);
        $this->assertNotNull($transfer->branch_manager_approved_at);
    }

    public function test_approve_by_hq_requires_bm_approval_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0002',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $service = $this->createService($admin);

        $this->expectException(\RuntimeException::class);
        $service->approveByHQ($transfer);
    }

    public function test_dispatch_requires_hq_approval(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0003',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_BM_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $service = $this->createService($admin);

        $this->expectException(\RuntimeException::class);
        $service->dispatch($transfer);
    }

    public function test_cancel_sets_status_and_reason(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0004',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);
        $service->cancel($transfer, 'Stock no longer needed');

        $transfer->refresh();
        $this->assertEquals(StockTransfer::STATUS_CANCELLED, $transfer->status);
        $this->assertEquals('Stock no longer needed', $transfer->cancellation_reason);
    }

    public function test_teller_cannot_approve(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0005',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $teller->id,
        ]);

        $service = $this->createService($teller);

        $this->expectException(\RuntimeException::class);
        $service->approveByBranchManager($transfer);
    }

    public function test_receive_items_logs_high_variance_to_audit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0006',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $item = $transfer->items()->create([
            'currency_code' => 'USD',
            'quantity' => '1000.0000',
            'rate' => '4.500000',
            'value_myr' => '4500.00',
        ]);

        $service = $this->createService($admin);
        // Received only 890 out of 1000 = 11% variance (> 5% threshold)
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '890.0000']]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'stock_transfer_variance_exceeded',
            'entity_type' => 'StockTransfer',
            'entity_id' => $transfer->id,
        ]);
    }
}