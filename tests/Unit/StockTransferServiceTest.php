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
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '890.0000']]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'stock_transfer_variance_exceeded',
            'entity_type' => 'StockTransfer',
            'entity_id' => $transfer->id,
        ]);
    }

    public function test_transfer_in_transit_cannot_be_approved_by_branch_manager(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0007',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transfer is not in requested status');
        $service->approveByBranchManager($transfer);
    }

    public function test_cancelled_transfer_cannot_be_approved(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0008',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_CANCELLED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
            'cancellation_reason' => 'Test cancellation',
        ]);

        $service = $this->createService($manager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transfer is not in requested status');
        $service->approveByBranchManager($transfer);
    }

    public function test_receive_items_with_zero_quantity(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0009',
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
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '0.0000']]);

        $item->refresh();
        $this->assertEquals('0.0000', $item->quantity_received);
        $this->assertTrue($item->hasVariance());
    }

    public function test_receive_items_with_quantity_exceeding_original(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0010',
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
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '1500.0000']]);

        $item->refresh();
        $this->assertEquals('1500.0000', $item->quantity_received);
        $this->assertTrue($item->hasVariance());
    }

    public function test_receive_items_for_already_fully_received_item(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0011',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $item = $transfer->items()->create([
            'currency_code' => 'USD',
            'quantity' => '1000.0000',
            'quantity_received' => '1000.0000',
            'quantity_in_transit' => '0.0000',
            'rate' => '4.500000',
            'value_myr' => '4500.00',
        ]);

        $service = $this->createService($admin);
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '1000.0000']]);

        $item->refresh();
        $this->assertEquals('1000.0000', $item->quantity_received);
    }

    public function test_create_request_with_empty_items_array(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $service = $this->createService($manager);

        $transfer = $service->createRequest([
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'type' => StockTransfer::TYPE_STANDARD,
            'items' => [],
        ]);

        $this->assertNotNull($transfer->transfer_number);
        $this->assertEquals(StockTransfer::STATUS_REQUESTED, $transfer->status);
        $this->assertCount(0, $transfer->items);
    }

    public function test_cancel_already_cancelled_transfer(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0012',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_CANCELLED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
            'cancellation_reason' => 'First cancellation',
        ]);

        $service = $this->createService($manager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transfer is already cancelled');
        $service->cancel($transfer, 'Second cancellation attempt');
    }

    public function test_cancel_completed_transfer_fails(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0013',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_COMPLETED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $service = $this->createService($manager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel a completed transfer');
        $service->cancel($transfer, 'Attempt to cancel completed');
    }

    public function test_compliance_officer_cannot_approve_by_branch_manager(): void
    {
        $complianceOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0014',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $complianceOfficer->id,
        ]);

        $service = $this->createService($complianceOfficer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only managers can approve transfers');
        $service->approveByBranchManager($transfer);
    }

    public function test_compliance_officer_cannot_dispatch(): void
    {
        $complianceOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0015',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_HQ_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $service = $this->createService($complianceOfficer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only admin can dispatch transfers');
        $service->dispatch($transfer);
    }

    public function test_compliance_officer_cannot_receive_items(): void
    {
        $complianceOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0016',
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

        $service = $this->createService($complianceOfficer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only admin can receive items');
        $service->receiveItems($transfer, [['id' => $item->id, 'quantity_received' => '1000.0000']]);
    }

    public function test_compliance_officer_cannot_cancel(): void
    {
        $complianceOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0017',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $complianceOfficer->id,
        ]);

        $service = $this->createService($complianceOfficer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only managers can cancel transfers');
        $service->cancel($transfer, 'Attempt by compliance officer');
    }
}
