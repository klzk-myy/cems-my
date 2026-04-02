<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ReportGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_manager_can_access_reports_dashboard()
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(200);
        $response->assertViewIs('reports');
        $response->assertSee('Reports & Analytics');
    }

    public function test_compliance_officer_can_access_reports_dashboard()
    {
        $user = User::factory()->create(['role' => 'compliance_officer']);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_reports_dashboard()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(200);
    }

    public function test_teller_cannot_access_reports_dashboard()
    {
        $user = User::factory()->create(['role' => 'teller']);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(403);
    }

    public function test_reports_page_displays_recent_reports()
    {
        $user = User::factory()->create(['role' => 'manager']);
        ReportGenerated::factory()->count(3)->create([
            'report_type' => 'LCTR',
            'generated_by' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(200);
        $response->assertSee('Recently Generated Reports');
        $response->assertSee('LCTR');
    }

    public function test_reports_page_displays_all_report_cards()
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)
            ->get(route('reports'));

        $response->assertStatus(200);
        // Check that all 8 report cards are displayed
        $response->assertSee('LCTR Report');
        $response->assertSee('MSB(2) Report');
        $response->assertSee('Trial Balance');
        $response->assertSee('Profit & Loss');
        $response->assertSee('Balance Sheet');
        $response->assertSee('Currency Position');
        $response->assertSee('Customer Risk Report');
        $response->assertSee('Audit Trail');
    }

    public function test_lctr_report_page_loads()
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)
            ->get(route('reports.lctr', ['month' => now()->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertViewIs('reports.lctr');
        $response->assertSee('LCTR Report');
        $response->assertSee('Transaction Details');
    }

    public function test_msb2_report_page_loads()
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)
            ->get(route('reports.msb2', ['date' => now()->subDay()->toDateString()]));

        $response->assertStatus(200);
        $response->assertViewIs('reports.msb2');
        $response->assertSee('MSB(2) Report');
        $response->assertSee('Currency Summary');
    }

    public function test_lctr_requires_manager_or_admin()
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $response = $this->actingAs($teller)
            ->get(route('reports.lctr'));

        $response->assertStatus(403);
    }

    public function test_msb2_requires_manager_or_admin()
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $response = $this->actingAs($teller)
            ->get(route('reports.msb2'));

        $response->assertStatus(403);
    }
}
