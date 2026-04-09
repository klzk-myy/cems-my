<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_transfer(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);

        $response = $this->actingAs($manager)->post(route('stock-transfers.store'), [
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'type' => 'Standard',
            'notes' => 'Test transfer',
            'items' => [
                [
                    'currency_code' => 'USD',
                    'quantity' => '1000.0000',
                    'rate' => '4.500000',
                    'value_myr' => '4500.00',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'status' => 'Requested',
        ]);
    }

    public function test_teller_cannot_create_transfer(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);

        $response = $this->actingAs($teller)->get(route('stock-transfers.create'));
        $response->assertForbidden();
    }

    public function test_manager_can_approve_transfer(): void
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

        $response = $this->actingAs($manager)->post(route('stock-transfers.approve-bm', $transfer));

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransfer::STATUS_BM_APPROVED,
        ]);
    }

    public function test_admin_can_approve_hq(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0002',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_BM_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('stock-transfers.approve-hq', $transfer));

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransfer::STATUS_HQ_APPROVED,
        ]);
    }

    public function test_manager_cannot_approve_hq(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0003',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_BM_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->post(route('stock-transfers.approve-hq', $transfer));

        $response->assertForbidden();
    }

    public function test_admin_can_dispatch_transfer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0004',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_HQ_APPROVED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('stock-transfers.dispatch', $transfer));

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransfer::STATUS_IN_TRANSIT,
        ]);
    }

    public function test_index_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => UserRole::Manager]);

        $response = $this->actingAs($user)->get(route('stock-transfers.index'));

        $response->assertOk();
    }

    public function test_show_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => UserRole::Manager]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-0005',
            'type' => StockTransfer::TYPE_STANDARD,
            'status' => StockTransfer::STATUS_REQUESTED,
            'source_branch_name' => 'HQ',
            'destination_branch_name' => 'Branch A',
            'requested_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('stock-transfers.show', $transfer));

        $response->assertOk();
    }
}
