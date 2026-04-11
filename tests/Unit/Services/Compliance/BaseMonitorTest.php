<?php

namespace Tests\Unit\Services\Compliance;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Services\Compliance\Monitors\BaseMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseMonitorTest extends TestCase
{
    use RefreshDatabase;

    private TestableBaseMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new TestableBaseMonitor(new MathService);
    }

    public function test_create_finding_returns_correct_array_structure(): void
    {
        $type = FindingType::VelocityExceeded;
        $severity = FindingSeverity::High;
        $subjectType = 'Transaction';
        $subjectId = 123;
        $details = ['velocity' => 15, 'threshold' => 10];

        // Access protected method using reflection
        $reflection = new \ReflectionMethod(BaseMonitor::class, 'createFinding');
        $reflection->setAccessible(true);

        $finding = $reflection->invoke($this->monitor, $type, $severity, $subjectType, $subjectId, $details);

        $this->assertIsArray($finding);
        $this->assertEquals($type->value, $finding['finding_type']);
        $this->assertEquals($severity->value, $finding['severity']);
        $this->assertEquals($subjectType, $finding['subject_type']);
        $this->assertEquals($subjectId, $finding['subject_id']);
        $this->assertEquals($details, $finding['details']);
        $this->assertEquals(FindingStatus::New->value, $finding['status']);
        $this->assertArrayHasKey('generated_at', $finding);
    }

    public function test_get_default_severity_returns_finding_type_default(): void
    {
        $reflection = new \ReflectionMethod(BaseMonitor::class, 'getDefaultSeverity');
        $reflection->setAccessible(true);

        $severity = $reflection->invoke($this->monitor);

        // RiskScoreChange has Low as default severity per FindingType enum
        $this->assertEquals(FindingSeverity::Low, $severity);
    }

    public function test_execute_calls_run_and_stores_findings(): void
    {
        $findings = $this->monitor->execute();

        // The test monitor returns one finding when run
        $this->assertIsArray($findings);
        $this->assertNotEmpty($findings);

        // Verify the finding was stored in the database
        $this->assertDatabaseHas('compliance_findings', [
            'finding_type' => FindingType::RiskScoreChange->value,
            'subject_type' => 'Customer',
            'subject_id' => 999,
        ]);
    }
}

/**
 * Testable implementation of BaseMonitor for testing.
 */
class TestableBaseMonitor extends BaseMonitor
{
    public function run(): array
    {
        return [
            $this->createFinding(
                FindingType::RiskScoreChange,
                FindingSeverity::Medium,
                'Customer',
                999,
                ['old_score' => 25, 'new_score' => 65]
            ),
        ];
    }

    protected function getFindingType(): FindingType
    {
        return FindingType::RiskScoreChange;
    }
}
