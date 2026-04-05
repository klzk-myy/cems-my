<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations
        $this->artisan('migrate');
    }

    public function test_compliance_officer_can_access_compliance_portal()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);

        $response = $this->actingAs($user)
            ->get(route('compliance'));

        $response->assertStatus(200);
        $response->assertViewIs('compliance');
        $response->assertSee('Compliance Portal');
    }

    public function test_admin_can_access_compliance_portal()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('compliance'));

        $response->assertStatus(200);
    }

    public function test_teller_cannot_access_compliance_portal()
    {
        $user = User::factory()->create(['role' => 'teller']);

        $response = $this->actingAs($user)
            ->get(route('compliance'));

        $response->assertStatus(403);
    }

    public function test_compliance_page_displays_flagged_transactions()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Completed',
        ]);
        $flag = FlaggedTransaction::factory()->create([
            'transaction_id' => $transaction->id,
            'flag_type' => 'Velocity',
            'status' => 'Open',
        ]);

        $response = $this->actingAs($user)
            ->get(route('compliance'));

        $response->assertStatus(200);
        $response->assertSee('Open Flags');
        $response->assertSee('Velocity');
    }

    public function test_compliance_filters_work()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);

        $response = $this->actingAs($user)
            ->get(route('compliance', ['status' => 'Open']));

        $response->assertStatus(200);
    }

    public function test_assign_flag_action()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);
        $flag = FlaggedTransaction::factory()->create(['status' => 'Open']);

        $response = $this->actingAs($user)
            ->patch(route('compliance.flags.assign', $flag));

        $response->assertRedirect();
        $this->assertDatabaseHas('flagged_transactions', [
            'id' => $flag->id,
            'status' => 'Under_Review',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_resolve_flag_action()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);
        $flag = FlaggedTransaction::factory()->create(['status' => 'Under_Review']);

        $response = $this->actingAs($user)
            ->patch(route('compliance.flags.resolve', $flag));

        $response->assertRedirect();
        $this->assertDatabaseHas('flagged_transactions', [
            'id' => $flag->id,
            'status' => 'Resolved',
            'reviewed_by' => $user->id,
        ]);
    }
}
