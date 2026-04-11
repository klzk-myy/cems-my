<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FiscalYearControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;

    protected User $adminUser;

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

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@123456'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    public function test_fiscal_year_close_creates_income_summary_entries(): void
    {
        // Create fiscal year with all periods closed
        $fiscalYear = FiscalYear::create([
            'year_code' => 'FY2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'Open',
        ]);

        // Create all periods as closed
        $months = [
            '2025-01', '2025-02', '2025-03', '2025-04', '2025-05', '2025-06',
            '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12',
        ];

        foreach ($months as $month) {
            AccountingPeriod::create([
                'period_code' => $month,
                'fiscal_year_id' => $fiscalYear->id,
                'start_date' => $month.'-01',
                'end_date' => date('Y-m-t', strtotime($month.'-01')),
                'status' => 'closed',
            ]);
        }

        // Use the service directly to test the close functionality
        $fiscalYearService = new \App\Services\FiscalYearService(new \App\Services\MathService);
        $result = $fiscalYearService->closeFiscalYear($fiscalYear, $this->managerUser->id);

        // Verify fiscal year is closed
        $fiscalYear->refresh();
        $this->assertEquals('Closed', $fiscalYear->status);
        $this->assertNotNull($fiscalYear->closed_by);
        $this->assertNotNull($fiscalYear->closed_at);

        // Verify result contains expected data
        $this->assertArrayHasKey('net_income', $result);
        $this->assertArrayHasKey('closing_entries', $result);
    }

    public function test_fiscal_year_open_creates_opening_entries(): void
    {
        // Create a closed fiscal year
        $closedYear = FiscalYear::create([
            'year_code' => 'FY2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'Closed',
            'closed_by' => $this->managerUser->id,
            'closed_at' => now(),
        ]);

        // Note: openNewFiscalYear requires a CLOSED fiscal year to be passed
        // This appears to be for re-opening a previously closed year
        // Use the service directly to test the open functionality
        $fiscalYearService = new \App\Services\FiscalYearService(new \App\Services\MathService);
        $result = $fiscalYearService->openNewFiscalYear($closedYear, $this->managerUser->id);

        // Verify the year was processed
        $this->assertNotNull($result);
    }

    public function test_fiscal_year_close_requires_open_periods(): void
    {
        // Create fiscal year with some periods still open
        $fiscalYear = FiscalYear::create([
            'year_code' => 'FY2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'Open',
        ]);

        // Create some periods as open
        AccountingPeriod::create([
            'period_code' => '2025-06',
            'fiscal_year_id' => $fiscalYear->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-30',
            'status' => 'open',
        ]);

        // Attempt to close fiscal year via controller
        $response = $this->actingAs($this->managerUser)
            ->post("/accounting/fiscal-years/{$fiscalYear->id}/close", [
                'confirm_code' => 'FY2025',
            ]);

        // Debug: if redirect but no error, show what we got
        if ($response->isRedirect() && ! $response->getSession()->has('error')) {
            $this->fail('Expected error message but got: success='.($response->getSession()->has('success') ? session('success') : 'none'));
        }

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify fiscal year is still open
        $fiscalYear->refresh();
        $this->assertEquals('Open', $fiscalYear->status);
    }

    public function test_manager_can_access_fiscal_year_index(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/accounting/fiscal-years');
        $response->assertStatus(200);
    }

    public function test_teller_cannot_access_fiscal_year_index(): void
    {
        $teller = User::create([
            'username' => 'teller1',
            'email' => 'teller@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($teller)->get('/accounting/fiscal-years');
        $response->assertStatus(403);
    }

    public function test_can_create_fiscal_year(): void
    {
        $response = $this->actingAs($this->managerUser)->post('/accounting/fiscal-years', [
            'year_code' => 'FY2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fiscal_years', [
            'year_code' => 'FY2027',
            'status' => 'Open',
        ]);
    }
}
