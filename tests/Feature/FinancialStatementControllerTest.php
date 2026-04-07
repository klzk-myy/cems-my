<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialStatementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;
    protected User $tellerUser;
    protected User $complianceUser;

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

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => 'compliance_officer',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    public function test_trial_balance_requires_manager(): void
    {
        // Teller should get 403
        $response = $this->actingAs($this->tellerUser)->get('/accounting/trial-balance');
        $response->assertStatus(403);

        // Compliance officer should also get 403 (not manager/admin)
        $response = $this->actingAs($this->complianceUser)->get('/accounting/trial-balance');
        $response->assertStatus(403);

        // Manager should be able to access
        $response = $this->actingAs($this->managerUser)->get('/accounting/trial-balance');
        $response->assertStatus(200);
    }

    public function test_profit_loss_requires_manager(): void
    {
        // Teller should get 403
        $response = $this->actingAs($this->tellerUser)->get('/accounting/profit-loss');
        $response->assertStatus(403);

        // Compliance officer should also get 403 (not manager/admin)
        $response = $this->actingAs($this->complianceUser)->get('/accounting/profit-loss');
        $response->assertStatus(403);

        // Manager should be able to access
        $response = $this->actingAs($this->managerUser)->get('/accounting/profit-loss');
        $response->assertStatus(200);
    }

    public function test_balance_sheet_requires_manager(): void
    {
        // Teller should get 403
        $response = $this->actingAs($this->tellerUser)->get('/accounting/balance-sheet');
        $response->assertStatus(403);

        // Compliance officer should also get 403 (not manager/admin)
        $response = $this->actingAs($this->complianceUser)->get('/accounting/balance-sheet');
        $response->assertStatus(403);

        // Manager should be able to access
        $response = $this->actingAs($this->managerUser)->get('/accounting/balance-sheet');
        $response->assertStatus(200);
    }

    public function test_cash_flow_requires_manager(): void
    {
        // Teller should get 403
        $response = $this->actingAs($this->tellerUser)->get('/accounting/cash-flow');
        $response->assertStatus(403);

        // Manager should be able to access
        $response = $this->actingAs($this->managerUser)->get('/accounting/cash-flow');
        $response->assertStatus(200);
    }

    public function test_ratios_requires_manager(): void
    {
        // Teller should get 403
        $response = $this->actingAs($this->tellerUser)->get('/accounting/ratios');
        $response->assertStatus(403);

        // Manager should be able to access
        $response = $this->actingAs($this->managerUser)->get('/accounting/ratios');
        $response->assertStatus(200);
    }
}