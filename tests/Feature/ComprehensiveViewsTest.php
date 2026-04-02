<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\ReportGenerated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * TEST 1: Compliance Portal - Authorization and Access Control
     * Logical error: Wrong role permissions could expose sensitive AML data
     */
    public function test_compliance_portal_requires_proper_authorization()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);
        $teller = User::factory()->create(['role' => 'teller']);

        // Compliance officers should have access
        $this->actingAs($complianceOfficer)
            ->get(route('compliance'))
            ->assertStatus(200);

        // Admins should have access
        $this->actingAs($admin)
            ->get(route('compliance'))
            ->assertStatus(200);

        // Managers should NOT have access (code error if they do)
        $this->actingAs($manager)
            ->get(route('compliance'))
            ->assertStatus(403);

        // Tellers should NOT have access
        $this->actingAs($teller)
            ->get(route('compliance'))
            ->assertStatus(403);

        // Guests should NOT have access
        $this->get(route('compliance'))
            ->assertRedirect('/login');
    }

    /**
     * TEST 2: Compliance Stats Calculation Accuracy
     * Logical error: Stats not counting correctly could mislead compliance team
     */
    public function test_compliance_stats_calculate_correctly()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        // Create test data with specific counts
        $openFlags = FlaggedTransaction::factory()->count(5)->create(['status' => 'Open']);
        $underReviewFlags = FlaggedTransaction::factory()->count(3)->create(['status' => 'Under_Review']);
        $resolvedTodayFlags = FlaggedTransaction::factory()->count(2)->create([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);
        $highPriorityFlags = FlaggedTransaction::factory()->count(4)->create([
            'status' => 'Open',
            'flag_type' => 'Sanction_Match',
        ]);

        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance'));

        $response->assertStatus(200);

        // Verify stats are passed to view
        $response->assertViewHas('stats', function ($stats) {
            return $stats['open'] === 9 // 5 Open + 4 High Priority (also Open)
                && $stats['under_review'] === 3
                && $stats['resolved_today'] === 2
                && $stats['high_priority'] === 4;
        });
    }

    /**
     * TEST 3: Compliance Filtering Logic
     * Logical error: Filters might not work or show wrong data
     */
    public function test_compliance_filters_work_correctly()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        // Create flags with different types
        FlaggedTransaction::factory()->create(['flag_type' => 'Velocity', 'status' => 'Open']);
        FlaggedTransaction::factory()->create(['flag_type' => 'Velocity', 'status' => 'Resolved']);
        FlaggedTransaction::factory()->create(['flag_type' => 'Structuring', 'status' => 'Open']);

        // Test status filter
        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance', ['status' => 'Open']));

        $response->assertStatus(200);
        $flags = $response->viewData('flags');
        $this->assertEquals(2, $flags->total(), 'Should show only Open flags');

        // Test flag type filter
        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance', ['flag_type' => 'Velocity']));

        $flags = $response->viewData('flags');
        $this->assertEquals(2, $flags->total(), 'Should show only Velocity flags');

        // Test combined filters
        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance', ['status' => 'Open', 'flag_type' => 'Velocity']));

        $flags = $response->viewData('flags');
        $this->assertEquals(1, $flags->total(), 'Should show only Open Velocity flags');
    }

    /**
     * TEST 4: Reports Dashboard - Authorization
     * Logical error: Wrong access could expose financial reports
     */
    public function test_reports_dashboard_requires_manager_or_admin()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $admin = User::factory()->create(['role' => 'admin']);
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);
        $teller = User::factory()->create(['role' => 'teller']);

        // Managers should have access
        $this->actingAs($manager)
            ->get(route('reports'))
            ->assertStatus(200);

        // Admins should have access
        $this->actingAs($admin)
            ->get(route('reports'))
            ->assertStatus(200);

        // Compliance officers should NOT have access
        $this->actingAs($complianceOfficer)
            ->get(route('reports'))
            ->assertStatus(403);

        // Tellers should NOT have access
        $this->actingAs($teller)
            ->get(route('reports'))
            ->assertStatus(403);
    }

    /**
     * TEST 5: Reports Dashboard - All Report Cards Present
     * Code error: Missing cards in the view
     */
    public function test_reports_dashboard_shows_all_eight_report_cards()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)
            ->get(route('reports'));

        $response->assertStatus(200);

        // All 8 report cards should be visible
        $response->assertSee('LCTR Report');
        $response->assertSee('MSB(2) Report');
        $response->assertSee('Trial Balance');
        $response->assertSee('Profit'); // Use partial match to avoid HTML encoding issue
        $response->assertSee('Loss');
        $response->assertSee('Balance Sheet');
        $response->assertSee('Currency Position');
        $response->assertSee('Customer Risk Report');
        $response->assertSee('Audit Trail');
    }

    /**
     * TEST 6: Recent Reports Data Integrity
     * Logical error: Wrong data or missing relations
     */
    public function test_recent_reports_shows_correct_data()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        // Create reports generated by different users
        ReportGenerated::factory()->create([
            'report_type' => 'LCTR',
            'generated_by' => $manager->id,
            'generated_at' => now()->subHours(2),
        ]);

        $anotherUser = User::factory()->create(['role' => 'manager']);
        ReportGenerated::factory()->create([
            'report_type' => 'MSB2',
            'generated_by' => $anotherUser->id,
            'generated_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports'));

        $response->assertStatus(200);

        $recentReports = $response->viewData('recentReports');
        $this->assertCount(2, $recentReports);

        // Verify ordering (most recent first)
        $this->assertEquals('MSB2', $recentReports->first()->report_type);

        // Verify eager loaded relation
        $this->assertNotNull($recentReports->first()->generatedBy);
    }

    /**
     * TEST 7: LCTR Report - Transaction Qualification Logic
     * Logical error: Wrong transactions included/excluded
     */
    public function test_lctr_only_includes_qualifying_completed_transactions()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $customer = Customer::factory()->create();

        $thisMonth = now()->format('Y-m');

        // Should be included (>= RM 25,000, Completed)
        Transaction::factory()->create([
            'amount_local' => 50000,
            'amount_foreign' => 10000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        // Should NOT be included (amount < 25,000)
        Transaction::factory()->create([
            'amount_local' => 10000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        // Should NOT be included (Pending status)
        Transaction::factory()->create([
            'amount_local' => 50000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Pending',
            'created_at' => now(),
        ]);

        // Should NOT be included (previous month)
        Transaction::factory()->create([
            'amount_local' => 50000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Completed',
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.lctr', ['month' => $thisMonth]));

        $response->assertStatus(200);

        $transactions = $response->viewData('transactions');
        $this->assertCount(1, $transactions, 'Should only include 1 qualifying transaction');
        $this->assertEquals(50000, $transactions->first()->amount_local);
    }

    /**
     * TEST 8: LCTR Report - Stats Calculation Accuracy
     * Logical error: Stats showing wrong totals
     */
    public function test_lctr_stats_calculate_correctly()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $currency = Currency::factory()->create(['code' => 'USD']);

        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        // Create transactions
        Transaction::factory()->create([
            'amount_local' => 30000,
            'currency_code' => 'USD',
            'customer_id' => $customer1->id,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'amount_local' => 40000,
            'currency_code' => 'USD',
            'customer_id' => $customer2->id,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'amount_local' => 35000,
            'currency_code' => 'USD',
            'customer_id' => $customer1->id, // Same customer
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.lctr', ['month' => now()->format('Y-m')]));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');

        $this->assertEquals(3, $stats['count'], 'Should count 3 transactions');
        $this->assertEquals(105000, $stats['total_amount'], 'Should sum to 105000');
        $this->assertEquals(2, $stats['unique_customers'], 'Should have 2 unique customers');
    }

    /**
     * TEST 9: LCTR Report - Pending Transactions Warning
     * Logical error: Not warning about excluded pending transactions
     */
    public function test_lctr_shows_warning_for_pending_qualifying_transactions()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $currency = Currency::factory()->create(['code' => 'USD']);
        $customer = Customer::factory()->create();

        // Completed transaction
        Transaction::factory()->create([
            'amount_local' => 50000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Completed',
        ]);

        // Pending qualifying transaction
        Transaction::factory()->create([
            'amount_local' => 60000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.lctr'));

        $response->assertStatus(200);

        $pendingCount = $response->viewData('pendingTransactions');
        $this->assertEquals(1, $pendingCount);

        $response->assertSee('Pending');
        $response->assertSee('60000');
    }

    /**
     * TEST 10: MSB2 Report - Currency Aggregation Accuracy
     * Logical error: Wrong aggregation could affect regulatory compliance
     */
    public function test_msb2_aggregates_currency_data_correctly()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $usd = Currency::factory()->create(['code' => 'USD']);
        $eur = Currency::factory()->create(['code' => 'EUR']);
        $customer = Customer::factory()->create();

        $today = now()->toDateString();

        // USD Buy transactions
        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_foreign' => 1000,
            'amount_local' => 4700,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_foreign' => 500,
            'amount_local' => 2350,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        // USD Sell transactions
        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Sell',
            'amount_foreign' => 300,
            'amount_local' => 1410,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        // EUR Buy transaction
        Transaction::factory()->create([
            'currency_code' => 'EUR',
            'type' => 'Buy',
            'amount_foreign' => 800,
            'amount_local' => 4000,
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.msb2', ['date' => $today]));

        $response->assertStatus(200);

        $summary = $response->viewData('summary');

        // Should have 2 currencies
        $this->assertCount(2, $summary);

        // Find USD data
        $usdData = $summary->firstWhere('currency_code', 'USD');
        $this->assertNotNull($usdData);
        $this->assertEquals(1500, $usdData->buy_volume_foreign);
        $this->assertEquals(2, $usdData->buy_count);
        $this->assertEquals(7050, $usdData->buy_amount_myr);
        $this->assertEquals(300, $usdData->sell_volume_foreign);
        $this->assertEquals(1, $usdData->sell_count);
        $this->assertEquals(1410, $usdData->sell_amount_myr);

        // Find EUR data
        $eurData = $summary->firstWhere('currency_code', 'EUR');
        $this->assertNotNull($eurData);
        $this->assertEquals(800, $eurData->buy_volume_foreign);
        $this->assertEquals(0, $eurData->sell_count);
    }

    /**
     * TEST 11: MSB2 Report - Stats and Totals
     * Logical error: Incorrect totals in summary
     */
    public function test_msb2_stats_calculate_correctly()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        Currency::factory()->create(['code' => 'USD']);

        // Create transactions
        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_foreign' => 1000,
            'amount_local' => 4700,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_foreign' => 500,
            'amount_local' => 2350,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Sell',
            'amount_foreign' => 300,
            'amount_local' => 1410,
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.msb2'));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');

        $this->assertEquals(3, $stats['total_transactions']);
        $this->assertEquals(7050, $stats['total_buy_myr']);
        $this->assertEquals(1410, $stats['total_sell_myr']);
        $this->assertEquals(5640, $stats['net_position']); // 7050 - 1410
    }

    /**
     * TEST 12: MSB2 Report - Negative Net Position Alert
     * Logical error: Not warning when sells exceed buys
     */
    public function test_msb2_shows_warning_for_negative_net_position()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        Currency::factory()->create(['code' => 'USD']);

        // More sells than buys
        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_local' => 1000,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'currency_code' => 'USD',
            'type' => 'Sell',
            'amount_local' => 3000,
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.msb2'));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertLessThan(0, $stats['net_position']);

        $response->assertSee('Negative net position');
    }

    /**
     * TEST 13: Flag Assign Action - Authorization and Logic
     * Code error: Wrong status update or no authorization check
     */
    public function test_flag_assign_requires_compliance_officer()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);
        $teller = User::factory()->create(['role' => 'teller']);

        $flag = FlaggedTransaction::factory()->create(['status' => 'Open']);

        // Teller should not be able to assign
        $this->actingAs($teller)
            ->patch(route('compliance.flags.assign', $flag))
            ->assertStatus(403);

        // Compliance officer should be able to assign
        $this->actingAs($complianceOfficer)
            ->patch(route('compliance.flags.assign', $flag))
            ->assertRedirect();

        $flag->refresh();
        $this->assertEquals('Under_Review', $flag->status);
        $this->assertEquals($complianceOfficer->id, $flag->assigned_to);
    }

    /**
     * TEST 14: Flag Resolve Action - Logic and Authorization
     * Code error: Wrong status or missing timestamp
     */
    public function test_flag_resolve_updates_correctly()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        $flag = FlaggedTransaction::factory()->create([
            'status' => 'Under_Review',
            'assigned_to' => $complianceOfficer->id,
        ]);

        $this->actingAs($complianceOfficer)
            ->patch(route('compliance.flags.resolve', $flag))
            ->assertRedirect();

        $flag->refresh();
        $this->assertEquals('Resolved', $flag->status);
        $this->assertEquals($complianceOfficer->id, $flag->reviewed_by);
        $this->assertNotNull($flag->resolved_at);
    }

    /**
     * TEST 15: Edge Case - Empty Data Handling
     * Code error: Division by zero or null errors
     */
    public function test_views_handle_empty_data_gracefully()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        // Compliance with no flags
        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance'));
        $response->assertStatus(200);
        $response->assertSee('No flagged transactions found');

        // LCTR with no transactions
        $response = $this->actingAs($manager)
            ->get(route('reports.lctr'));
        $response->assertStatus(200);
        $response->assertSee('No qualifying transactions');

        // MSB2 with no transactions
        $response = $this->actingAs($manager)
            ->get(route('reports.msb2'));
        $response->assertStatus(200);
        $response->assertSee('No transactions found');
    }

    /**
     * TEST 16: Edge Case - Invalid Parameters
     * Code error: 500 errors on bad input
     */
    public function test_views_handle_invalid_parameters()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        // Invalid month format
        $response = $this->actingAs($manager)
            ->get(route('reports.lctr', ['month' => 'invalid']));
        $response->assertStatus(200); // Should handle gracefully, not crash

        // Invalid date format
        $response = $this->actingAs($manager)
            ->get(route('reports.msb2', ['date' => 'not-a-date']));
        $response->assertStatus(200);

        // Invalid filter values
        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance', ['status' => 'InvalidStatus']));
        $response->assertStatus(200);
    }

    /**
     * TEST 17: Data Security - Customer Name Masking
     * Logical error: PII exposure in reports
     */
    public function test_lctr_masks_customer_names()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $currency = Currency::factory()->create(['code' => 'USD']);
        $customer = Customer::factory()->create(['full_name' => 'John Smith']);

        Transaction::factory()->create([
            'amount_local' => 50000,
            'currency_code' => 'USD',
            'customer_id' => $customer->id,
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('reports.lctr'));

        $response->assertStatus(200);

        // Should NOT see full name
        $response->assertDontSee('John Smith');

        // Should see masked version
        $response->assertSee('Jo****th');
    }

    /**
     * TEST 18: Pagination Works Correctly
     * Code error: Pagination not working or filters lost
     */
    public function test_compliance_pagination_preserves_filters()
    {
        $complianceOfficer = User::factory()->create(['role' => 'compliance_officer']);

        // Create more than 20 flags (default pagination)
        FlaggedTransaction::factory()->count(25)->create(['status' => 'Open', 'flag_type' => 'Velocity']);

        $response = $this->actingAs($complianceOfficer)
            ->get(route('compliance', ['status' => 'Open']));

        $response->assertStatus(200);

        $flags = $response->viewData('flags');
        $this->assertTrue($flags->hasPages(), 'Should have multiple pages');

        // Check that pagination links preserve filters
        $response->assertSee('status=Open');
    }
}
