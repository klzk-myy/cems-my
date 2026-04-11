<?php

namespace Tests\Unit\Services\Compliance;

use App\Models\Customer;
use App\Models\SanctionEntry;
use App\Models\SanctionList;
use App\Services\Compliance\Monitors\SanctionsRescreeningMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctionsRescreeningMonitorTest extends TestCase
{
    use RefreshDatabase;

    private SanctionsRescreeningMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new SanctionsRescreeningMonitor(new MathService);
    }

    public function test_no_findings_when_no_sanction_entries(): void
    {
        $customer = Customer::factory()->create();

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_no_findings_when_customer_matches_no_sanctions(): void
    {
        $customer = Customer::factory()->create([
            'full_name' => 'Ahmad bin Abdullah',
        ]);

        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Different Person Name',
        ]);

        $findings = $this->monitor->run();

        $this->assertEmpty($findings);
    }

    public function test_generates_finding_when_customer_matches_sanction(): void
    {
        $customer = Customer::factory()->create([
            'full_name' => 'John Smith',
            'nationality' => 'British',
        ]);

        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'entity_type' => 'Individual',
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals('Critical', $findings[0]['severity']);
        $this->assertEquals($customer->id, $findings[0]['subject_id']);
        $this->assertEquals('Sanction_Match', $findings[0]['finding_type']);
    }

    public function test_handles_multiple_sanction_matches(): void
    {
        $customer = Customer::factory()->create([
            'full_name' => 'Known Criminal',
        ]);

        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Known Criminal',
        ]);
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Criminal Mastermind',
            'aliases' => json_encode(['Known Criminal', 'KC']),
        ]);

        $findings = $this->monitor->run();

        $this->assertCount(1, $findings);
        $this->assertEquals(2, $findings[0]['details']['match_count']);
    }
}
