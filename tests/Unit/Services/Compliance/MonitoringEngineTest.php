<?php

namespace Tests\Unit\Services\Compliance;

use App\Services\Compliance\MonitoringEngine;
use App\Services\Compliance\Monitors\BaseMonitor;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private MonitoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new MonitoringEngine(new MathService);
    }

    public function test_register_monitor_adds_class_to_list(): void
    {
        $monitorClass = TestMonitor::class;

        $this->engine->registerMonitor($monitorClass);

        $this->assertContains($monitorClass, $this->engine->getRegisteredMonitors());
    }

    public function test_register_monitor_does_not_add_duplicate(): void
    {
        $monitorClass = TestMonitor::class;

        $this->engine->registerMonitor($monitorClass);
        $this->engine->registerMonitor($monitorClass);

        $registered = $this->engine->getRegisteredMonitors();
        $count = array_count_values($registered);
        $this->assertEquals(1, $count[$monitorClass]);
    }

    public function test_get_registered_monitors_returns_array(): void
    {
        $monitors = $this->engine->getRegisteredMonitors();

        $this->assertIsArray($monitors);
        // Default monitors are auto-registered (7 monitors)
        $this->assertGreaterThanOrEqual(7, count($monitors));
    }

    public function test_get_monitor_returns_instance(): void
    {
        $this->engine->registerMonitor(TestMonitor::class);

        $monitor = $this->engine->getMonitor(TestMonitor::class);

        $this->assertInstanceOf(BaseMonitor::class, $monitor);
    }

    public function test_run_all_calls_all_monitors(): void
    {
        $this->engine->registerMonitor(TestMonitor::class);
        $this->engine->registerMonitor(TestMonitor2::class);

        $results = $this->engine->runAll();

        $this->assertNotEmpty($results);
    }

    public function test_run_monitor_runs_specific_monitor(): void
    {
        $this->engine->registerMonitor(TestMonitor::class);

        $results = $this->engine->runMonitor(TestMonitor::class);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }
}

/**
 * Test monitor implementation.
 */
class TestMonitor extends BaseMonitor
{
    public function run(): array
    {
        return [
            $this->createFinding(
                \App\Enums\FindingType::VelocityExceeded,
                \App\Enums\FindingSeverity::High,
                'Transaction',
                1,
                ['test' => 'value']
            ),
        ];
    }

    protected function getFindingType(): \App\Enums\FindingType
    {
        return \App\Enums\FindingType::VelocityExceeded;
    }
}

/**
 * Second test monitor implementation.
 */
class TestMonitor2 extends BaseMonitor
{
    public function run(): array
    {
        return [
            $this->createFinding(
                \App\Enums\FindingType::StructuringPattern,
                \App\Enums\FindingSeverity::High,
                'Transaction',
                2,
                ['test2' => 'value2']
            ),
        ];
    }

    protected function getFindingType(): \App\Enums\FindingType
    {
        return \App\Enums\FindingType::StructuringPattern;
    }
}
