<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JournalEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;

    protected User $tellerUser;

    protected ChartOfAccount $cashAccount;

    protected ChartOfAccount $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash', 'account_type' => 'Asset', 'is_active' => true]
        );

        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Forex Trading Revenue', 'account_type' => 'Revenue', 'is_active' => true]
        );
    }

    protected function createPendingJournalEntry(): JournalEntry
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-202604-0001',
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Manual',
            'description' => 'Test revenue entry',
            'status' => 'Pending',
            'created_by' => $this->tellerUser->id,
            'posted_by' => $this->tellerUser->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '5000',
            'debit' => '0',
            'credit' => '5000.00',
            'description' => 'Revenue for forex trading',
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '1000',
            'debit' => '5000.00',
            'credit' => '0',
            'description' => 'Cash received',
        ]);

        return $entry->fresh()->load('lines');
    }

    public function test_journal_entry_rejection_requires_notes(): void
    {
        $entry = $this->createPendingJournalEntry();

        // Try to reject without notes
        $response = $this->actingAs($this->managerUser)->post("/accounting/journal/{$entry->id}/approve", [
            'action' => 'reject',
            'notes' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Rejection notes are required.');

        // Verify entry is still pending
        $entry->refresh();
        $this->assertEquals('Pending', $entry->status);
    }

    public function test_journal_entry_approve_changes_status_to_posted(): void
    {
        $entry = $this->createPendingJournalEntry();

        $response = $this->actingAs($this->managerUser)->post("/accounting/journal/{$entry->id}/approve", [
            'action' => 'approve',
            'notes' => 'Approved for posting',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $entry->refresh();
        $this->assertEquals('Posted', $entry->status);
        $this->assertEquals($this->managerUser->id, $entry->approved_by);
        $this->assertNotNull($entry->approved_at);
        $this->assertEquals('Approved for posting', $entry->approval_notes);
    }

    public function test_journal_entry_reject_changes_status_to_draft(): void
    {
        $entry = $this->createPendingJournalEntry();

        $response = $this->actingAs($this->managerUser)->post("/accounting/journal/{$entry->id}/approve", [
            'action' => 'reject',
            'notes' => 'Entry needs correction -不平衡',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $entry->refresh();
        $this->assertEquals('Draft', $entry->status);
        $this->assertEquals('Entry needs correction -不平衡', $entry->approval_notes);
    }

    public function test_teller_cannot_approve_journal_entries(): void
    {
        $entry = $this->createPendingJournalEntry();

        $response = $this->actingAs($this->tellerUser)->post("/accounting/journal/{$entry->id}/approve", [
            'action' => 'approve',
            'notes' => 'Approved',
        ]);

        $response->assertStatus(403);

        // Verify entry is still pending
        $entry->refresh();
        $this->assertEquals('Pending', $entry->status);
    }

    public function test_cannot_approve_own_journal_entry(): void
    {
        // Create a pending entry created by manager (not teller)
        $entry = JournalEntry::create([
            'entry_number' => 'JE-202604-0002',
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Manual',
            'description' => 'Manager created entry',
            'status' => 'Pending',
            'created_by' => $this->managerUser->id,
            'posted_by' => $this->managerUser->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '5000',
            'debit' => '0',
            'credit' => '1000.00',
            'description' => 'Revenue',
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code' => '1000',
            'debit' => '1000.00',
            'credit' => '0',
            'description' => 'Cash',
        ]);

        $response = $this->actingAs($this->managerUser)->post("/accounting/journal/{$entry->id}/approve", [
            'action' => 'approve',
            'notes' => 'Self-approval attempt',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify entry is still pending
        $entry->refresh();
        $this->assertEquals('Pending', $entry->status);
    }
}
